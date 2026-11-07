# rdx/imap

IMAP reading in pure PHP, without `ext-imap`.

**3.x is a complete rewrite of 2.x.** Nothing carries over: not the classes, not the methods, not the config.

## Connecting

```php
$mailbox = ImapMailbox::fromDsn('imaps://user:pass@imap.example.com/INBOX');

$mailbox = new ImapMailbox(
	host: 'imap.example.com',
	username: 'user',
	password: 'pass',
);
```

`imaps://` is SSL (port 993), `imap://` is STARTTLS (port 143). The mailbox defaults to `INBOX`. URL-encode special characters in the DSN.

## Totals

```php
$mailbox->getTotalMessages(); // int
$mailbox->getTotalUnseenMessages(); // int
```

## Reading messages

```php
$messages = $mailbox->getAllIterator(limit: 50); // generator of ImapMessage
$messages = $mailbox->getAllSinceIterator('2026-10-30', limit: 50); // generator of ImapMessage
$messages = $mailbox->getUnseenIterator(limit: 50); // generator of ImapMessage

$messages->getTotal(); // int
foreach ($messages as $message) {
	$message->getSubject(); // ?string
	$message->getDatetime(); // ?DateTimeImmutable
	$message->getHeader('Reply-to'); // ?string
	$message->getAddress('From'); // ?string
	$message->getAddresses('To'); // list<string>
}
```

The iterator can be looped only once.

## Changing

```php
$mailbox->markSeen($message);
$mailbox->deleteOneMessage($message);
$mailbox->deleteManyUids($uids);
$mailbox->expunge(); // int
```

`deleteOneMessage()` and `deleteManyUids()` only flag messages. `expunge()` removes all flagged messages and returns how many.
