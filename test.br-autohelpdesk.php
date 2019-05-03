<?php

use rdx\imap\IMAPMailbox;

require 'env.php';
require 'autoload.php';

header('Content-type: text/plain');

$mbox = new IMAPMailbox(IMAP_HELPDESK_HOST, IMAP_HELPDESK_USER, IMAP_HELPDESK_PASS, 'INBOX', ['novalidate-cert']);

print_r($mbox->headers());

$messages = $mbox->messages();
var_dump(count($messages));
// print_r($messages);

$woop = null;
foreach ($messages as $message) {
	echo "\n\n\n\n";
	echo $message->subject() . "\n";
	echo implode("\n", $message->simpleStructure()) . "\n";

	if (strpos($message->subject(), '"woop"')) {
		$woop = $message;
	}
}

echo "\n\n\n\n\n\n\n";
print_r($woop);

$imgs = $woop->subtypeParts(['JPEG', 'PNG', 'GIF'], true);
var_dump(count($imgs));

foreach ($imgs as $img) {
	$filename = $img->safeFilename();
	// echo substr($img->content(), 0, 10) . "\n\n";
	// echo substr($img->decodedContent(), 0, 10) . "\n\n";
	echo "$filename\n";
	var_dump($img->saveAttachment(__DIR__ . '/attachments'));
}



exit;

$headers = imap_headers($mbox);
print_r($headers);

foreach ( $headers AS $hd ) {
	if ( preg_match('/(\d+)\)/', $hd, $match) ) {
		$msgNum = $match[1];

		$hd = imap_headerinfo($mbox, $msgNum);
		$new = !!trim($hd->Unseen);

		if ( $new ) {

			// subject -- should contain #code#
			$title = get_plain_text_subject($mbox, $msgNum, $hd);
			$code = preg_match('/#(\d+)#$/', $title, $match) ? (int)$match[1] : 0;
echo $title . "\n";

			// body -- get only last part (no conversation history)
			$attachments = array();
			$full_body = get_plain_text_body($mbox, $msgNum, $attachments);
			$body = get_last_body_part($full_body);
			if ( $attachments ) {
				$body .= "\n\n== Attachments:\n* " . implode("\n* ", $attachments);
			}
#echo $full_body . "\n===================================================\n";
echo $body . "\n\n===================================================\n===================================================\n===================================================\n";

			flush();
		}

	}
}

echo "\n";

imap_close($mbox, CL_EXPUNGE);


function get_last_body_part( $body ) {
	$lines = preg_split('/(\r\n|\r|\n)/', $body);

	$body = '';
	foreach ( $lines AS $line ) {
		if ( '>' == substr($line, 0, 1) || '-----' == substr($line, 0, 5) || '_____' == substr($line, 0, 5) ) {
			break;
		}

		$body .= $line . "\r\n";
	}

	return trim($body);
}

function get_plain_text_subject( $mbox, $msgNum, $headers = null ) {
	$headers or $headers = imap_headerinfo($mbox, $msgNum);

	$subjectParts = imap_mime_header_decode($headers->Subject);
	$subject = '';
	foreach ( $subjectParts AS $p ) {
		$subject .= $p->text;
	}

	return trim($subject);
}

function get_plain_text_body( $mbox, $msgNum, &$attachments = array() ) {
	$structure = imap_fetchstructure($mbox, $msgNum);

	// only plain text
	if ( 'PLAIN' == $structure->subtype ) {
		return trim(imap_qprint(imap_body($mbox, $msgNum)));
	}

	if ( isset($structure->parts) ) {
		// get attachments
		foreach ( $structure->parts AS $partNum => $part ) {
			if ( in_array($part->subtype, array('JPEG', 'PNG', 'GIF')) ) {
				// oeh an image
				$name = 'image-from-email-'.$msgNum.'.' . strtolower($part->subtype);
				foreach ( $part->parameters AS $param ) {
					if ( 'NAME' == $param->attribute ) {
						$name = $param->value;
					}
				}
				$data = imap_fetchbody($mbox, $msgNum, (string)($partNum+1));
				file_put_contents($attachments[] = 'attachments/'.time().'--'.$name, base64_decode($data));
			}
		}

		// multipart (probably) -- look for plain text part
		foreach ( $structure->parts AS $partNum => $part ) {
			if ( 'PLAIN' == $part->subtype ) {
				$body = imap_fetchbody($mbox, $msgNum, (string)($partNum+1));

				return trim($body);
			}
			else if ( 'ALTERNATIVE' == $part->subtype && isset($part->parts) ) {
				foreach ( $part->parts AS $subPartNum => $subPart ) {
					if ( 'PLAIN' == $subPart->subtype ) {
						$body = imap_fetchbody($mbox, $msgNum, ($partNum+1).'.'.($subPartNum+1));

						return trim($body);
					}
				}
			}
		}
	}
}


