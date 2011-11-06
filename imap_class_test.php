<pre><?php

require 'settings.php'; // $host, $user, $pass

require 'IMAP.php';

$mbox = new IMAPMailbox($host, $user, $pass);
print_r($mbox);

$messages = $mbox->messages(array('seen' => false, 'offset' => 0, 'limit' => 20, 'newestFirst' => false));
print_r($messages);

