<pre><?php

require 'settings.php'; // $host, $user, $pass

require 'IMAP.php';

$mbox = new IMAPMailbox($host, $user, $pass);
print_r($mbox);

$messages = $mbox->messages(array('seen' => false, 'offset' => 0, 'limit' => 3, 'newestFirst' => true));
#print_r($messages);

foreach ( $messages AS $message ) {

	echo "------------------------------------------------------------------------\n";

	echo "Subject: ".$message->subject()."\n";

	$parts = $message->parts();
//	print_r($parts);

	print_r($message);

	foreach ( $message->attachments AS $attachment ) {
		var_dump($attachment->save(__DIR__.'/attachments/'));
	}

}

