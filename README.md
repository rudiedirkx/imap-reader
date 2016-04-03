IMAP reader
====

[![Build Status](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rudiedirkx/IMAP-reader/?branch=master)

Reads e-mails on an IMAP server.

* Retrieves message parts
* Recognizes PLAIN & HTML parts
* Recognizes attachments
* Can decode & save attachments

Uses PHP's built in [IMAP module](http://www.php.net/manual/en/ref.imap.php).

This lib doesn't (yet):

* Read from POP3 server
* Decode properly
