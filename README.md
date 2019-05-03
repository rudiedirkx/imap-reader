IMAP reader
====

[![Build Status](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/?branch=master)

Reads e-mails on an IMAP server.

Features
----

* Retrieves message parts
* Recognizes PLAIN & HTML parts
* Recognizes attachments

Uses PHP's built in [IMAP module](http://www.php.net/manual/en/ref.imap.php).

Examples
----

Init connection & find messages:

	$mbox = new rdx\imap\IMAPMailbox('example.com', 'user', 'password', 'INBOX', ['ssl', 'tls']);
	$messages = $mbox->messages([
		'newestFirst' => true, // bool
		'seen' => false, // null|bool
		'limit' => 10, // int
		'offset' => 0, // int
	]);

See a message's structure:

	foreach ($messages as $message) {
		echo $message->simpleStructure() . "\n\n";
		
		// Could be something complex like:
		// 1. PLAIN (517)
		// 2. DELIVERY-STATUS (315)
		// 3. *RFC822 (2446)
		// 3.1. PLAIN (610)
		// 3.2. HTML (744)
		
		// Or something simple like:
		// 1. PLAIN (123)
		// 2. JPEG (76543)
	}

Find all HTML parts, including attachments, forwards etc:

	foreach ($messages as $message) {
		$htmls = $message->html(true); // true for recursive, false for only top level parts
	}

Read bounce mail to find rejected addresses:

	foreach ($messages as $message) {
		$body = $message->subtypeContent('DELIVERY-STATUS');
		if ($body && strpos($body, 'failed') !== false) {
			// Extract address and do something
		}
	}

Find ALL image attachments:

	foreach ($messages as $message) {
		$attachments = $message->subtypeParts(['JPEG', 'PNG', 'GIF'], true); // true = recursive
		
		foreach ($attachments as $att) {
			$att->saveAttachment('/some/folder');
		}
	}
