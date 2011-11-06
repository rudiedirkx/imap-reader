# IMAP reader

Reads e-mails on an IMAP server.

* Retrieves message parts
* Recognizes PLAIN & HTML parts
* Recognizes attachments
* Can decode & save attachments

Uses PHP's built in [IMAP module](http://www.php.net/manual/en/ref.imap.php).

This lib doesn't (yet):

* Read from POP3 server
* Send e-mail (nor will it ever)
* Decode properly
* Retrieve flags
* Set flags

Oeh that's bad. It does do 4 things and doesn't do 5 things.

