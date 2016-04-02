<?php

use rdx\imap\IMAPMailbox;

require 'env.php';
require 'autoload.php';

header('Content-type: text/plain');

$mbox = new IMAPMailbox(IMAP_11NK5_HOST, IMAP_11NK5_USER, IMAP_11NK5_PASS, 'INBOX', ['ssl', 'novalidate-cert']);

$messages = $mbox->messages(false);
echo count($messages) . " new messages...\n\n";


exit;


foreach ( $messages AS $message ) {

	$title = $message->subject();
var_dump($title);

	$message->parts();
	$body = $message->plainBody;
	if ( $body ) {
		$text = $body->content();

		if ( preg_match('#^https?://[a-z0-9]#i', $text) ) {
			$tags = preg_split('/\s+/', $text);

			$url = array_shift($tags);

			$tags = implode(' ', $tags);

			if ( $tags ) {
var_dump($url);
var_dump($tags);

				if ( $message->unseen ) {
					$q = compact('title', 'url', 'tags');
					$qs = http_build_query($q);

					$rsp = @file_get_contents('http://hotblocks.nl/tags/index.php?'.$qs);
					echo "SUBMIT URL: ";
					if ( $rsp ) {
						var_dump(strlen($rsp));
					}
					else {
						echo "FAIL\n";
						$message->unflag('seen');
					}
				}
				/*else {
					// 25% chance
					if ( !rand(0, 3) ) {
						echo "UNFLAG SEEN: ";
						var_dump($message->unflag('seen'));
					}
				}*/
			}
		}
	}

	echo "\n\n\n\n\n\n";

}
