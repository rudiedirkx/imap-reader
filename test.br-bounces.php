<pre><?php

require 'env.php';
require 'IMAP.php';

$mbox = imap_open('{' . IMAP_BOUNCES_HOST . '/novalidate-cert}INBOX', IMAP_BOUNCES_USER, IMAP_BOUNCES_PASS);

$headers = imap_headers($mbox);
print_r($headers);
