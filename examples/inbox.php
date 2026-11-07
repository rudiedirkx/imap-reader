<?php

use rdx\imap\ImapMailbox;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/env.php';

$mailbox = ImapMailbox::fromDsn(IMAP_DSN);

printf("Total %d messages (%d unseen)\n", $mailbox->getTotalMessages(), $mailbox->getTotalUnseenMessages());

$sinceDate = date('Y-m-d', strtotime('-8 days'));
$messages = $mailbox->getAllSinceIterator($sinceDate, 9999);
printf("%d messages since %s:\n", $messages->getTotal(), $sinceDate);
foreach ($messages as $i => $msg) {
	printf("%d:\n", $i + 1);
	dump([
		'uid' => $msg->getUid(),
		'datetime' => $msg->getDatetime(),
		'from' => $msg->getAddress('From'),
		'subject' => $msg->getSubject(),
		// 'received' => $msg->getHeaders('Received'),
		'orig' => [
			'datetime' => $msg->getOriginalMessage()?->getDatetime(),
			'from' => $msg->getOriginalMessage()?->getAddress('From'),
			'subject' => $msg->getOriginalMessage()?->getSubject(),
		],
	]);
	if (!$msg->getOriginalMessage() && !$msg->isAutoReply()) {
		echo $msg->getRawFull() . "\n";
	}
	echo "\n";
}
