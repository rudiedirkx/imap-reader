<pre><?php

require 'env.php';
require 'IMAP.php';

$mbox = imap_open('{' . IMAP_HELPDESK_HOST . '/novalidate-cert}INBOX', IMAP_HELPDESK_USER, IMAP_HELPDESK_PASS);

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


