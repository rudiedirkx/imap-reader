<?php

use rdx\imap\IMAPMailbox;

require 'env.php';
require 'autoload.php';

header('Content-type: text/plain');

$mbox = new IMAPMailbox(ICA_BOUNCE_HOST, ICA_BOUNCE_USER, ICA_BOUNCE_PASS, 'INBOX', ['ssl', 'novalidate-cert']);

$message = $mbox->message(1);
// print_r($message->structure());
// print_r($message->simpleStructure());

$part = $message->subtypePart('DELIVERY-STATUS', false);
// echo $part->content();
print_r($part->headers());

// exit;

// echo $message->headerString() . "\n\n";
// print_r($message->headers());

$part = $message->subtypePart('RFC822', false);
// print_r($part);
// echo $part->headerString() . "\n\n";
// print_r($part->headers());
var_dump($part->header('subject'));

exit;

$header = explode("\r\n\r\n", $part->content())[0];
echo "$header\n\n";
$headers = preg_split('#[\r\n]+(?=\w)#', $header);
$headers = array_map('mb_decode_mimeheader', $headers);
print_r($headers);
// mb_decode_mimeheader();
