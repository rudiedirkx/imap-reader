<?php

use rdx\imap\IMAPException;
use rdx\imap\IMAPMailbox;

require 'env.php';
require 'autoload.php';

header('Content-type: text/plain');

try {
	$mbox = new IMAPMailbox(IMAP_BOUNCES_HOST, IMAP_BOUNCES_USER, IMAP_BOUNCES_PASS, 'INBOX', ['ssl']);
}
catch (\Exception $ex) {
	exit($ex->getMessage());
}

print_r($mbox->msgInfo());
echo "\n";

// print_r($mbox->headers(false));
// echo "\n";

if ( true ) {
	// LOAD MANY
	$messages = $mbox->messages(['newestFirst' => false, 'limit' => 20]);
	var_dump(count($messages));
	echo "\n";
}
else {
	// LOAD ONE
	$messages = [$mbox->message(4)];
}

foreach ($messages as $message) {
	echo "\n\n";
	echo '[' . $message->msgNumber() . '] [' . date('Y-m-d H:i:s', $message->utc()) . '] ' . $message->subject() . "\n";

	// continue;

	echo "\n";
	// print_r($message->headers());
	// print_r($message->parameters());



	// $printSection = function($section) use ($message) {
	// 	$body = imap_fetchbody($message->mailbox->imap(), $message->msgNumber(), $section, FT_PEEK);
	// 	if ( $body ) {
	// 		// echo "\n\n\n\n\n\n\n\n\n\n\n\n\n";
	// 		// echo "===============================================================\n";
	// 		// echo "===============================================================\n";
	// 		// echo "===============================================================\n";
	// 		// echo "===============================================================\n";
	// 		// echo "\n\n\n\n\n\n\n\n\n\n\n\n\n";

	// 		echo $section . "\n";
	// 		// echo $body;
	// 	}
	// };

	// for ($i=1; $i<5; $i++) {
	// 	$section = (string) $i;
	// 	$printSection($section);

	// 	for ($j=1; $j<5; $j++) {
	// 		$section = implode('.', [$i, $j]);
	// 		$printSection($section);

	// 		for ($k=1; $k<5; $k++) {
	// 			$section = implode('.', [$i, $j, $k]);
	// 			$printSection($section);

	// 			for ($l=1; $l<5; $l++) {
	// 				$section = implode('.', [$i, $j, $k, $l]);
	// 				$printSection($section);
	// 			}
	// 		}
	// 	}
	// }

	// print_r($message->structure());
	// continue;


	echo implode("\n", $message->simpleStructure()) . "\n\n";

	$body = $message->subtypeContent('DELIVERY-STATUS', true);
	$failed = $body && is_int(strpos($body, 'Action: failed'));

	echo "\n\nDELIVERY-STATUS:\n";
	echo trim($body) . "\n\n";

	if ( $failed ) {
		$addresses = [];
		if (preg_match_all('#(Final|Original)\-recipient:\s*rfc822;([^\s]+)#i', $body, $matches)) {
			$addresses = array_values(array_unique($matches[2]));
		}
		echo "failed:\n";
		print_r($addresses);
	}
	else {
		echo "not failed\n";
	}

	// echo "\n\nPLAIN:\n";
	// echo $message->text(true);
	// echo "\n\nHTML:\n";
	// echo $message->html(true);

	// foreach ($message->allParts(true) as $part) {
	// 	echo "\n\n\n\n\n\n\n\n\n\n\n\n\n";
	// 	echo "===============================================================\n";
	// 	echo "===============================================================\n";
	// 	echo "===============================================================\n";
	// 	echo "===============================================================\n";
	// 	echo "\n\n\n\n\n\n\n\n\n\n\n\n\n";

	// 	echo implode('.', $part->section()) . ' :: ' . $part->subtype() . ":\n\n";

	// 	print_r($part->parameters());
	// 	echo "\n";

	// 	$body = $part->content();
	// 	var_dump(strlen($body));
	// 	echo $body;
	// }

	echo "\n\n\n\n";
	echo "\n\n\n\n";
	echo "\n\n\n\n";
	echo "\n\n\n\n";
	echo "\n\n\n\n";
}

exit;
