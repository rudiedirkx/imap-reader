<?php

namespace rdx\imap;

use DateTimeImmutable;
use DateTimeZone;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\IHeader;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\Message;

class ImapMessage {

	protected ImapMessage $originalMessage;

	public function __construct(
		protected ?int $uid,
		protected IMessage $message,
	) {}

	public function getUid() : ?int {
		return $this->uid;
	}

	public function getSubject() : ?string {
		return $this->message->getSubject();
	}

	public function getAddress(string $name) : ?string {
		$header = $this->message->getHeader($name);
		if (!$header instanceof AddressHeader) {
			return null;
		}

		return $header->getEmail();
	}

	/**
	 * @return list<string>
	 */
	public function getAddresses(string $name) : array {
		$addresses = [];
		foreach ($this->message->getAllHeadersByName($name) as $header) {
			if (!$header instanceof AddressHeader) {
				continue;
			}

			foreach ($header->getAddresses() as $address) {
				$addresses[] = $address->getEmail();
			}
		}

		return $addresses;
	}

	public function getDatetime() : ?DateTimeImmutable {
		$header = $this->message->getHeader(HeaderConsts::DATE);
		if (!$header instanceof DateHeader) {
			return null;
		}

		$datetime = $header->getDateTimeImmutable();
		if (!$datetime) {
			return null;
		}

		$timezone = new DateTimeZone(date_default_timezone_get());
		return $datetime->setTimezone($timezone);
	}

	public function getHeader(string $name) : ?string {
		$header = $this->message->getHeader($name);
		if (!$header) {
			return null;
		}

		return $this->decodeHeader($header);
	}

	/**
	 * @return list<string>
	 */
	public function getHeaders(string $name) : array {
		$values = [];
		foreach ($this->message->getAllHeadersByName($name) as $header) {
			$values[] = $this->decodeHeader($header);
		}

		return $values;
	}

	public function getDeliveryStatus() : ?ImapDeliveryStatus {
		$text = $this->getDeliveryStatusText();
		if ($text === null) {
			return null;
		}

		return new ImapDeliveryStatus($text);
	}

	public function getDeliveryStatusText() : ?string {
		$part = $this->message->getPartByMimeType('message/delivery-status');
		if (!$part) {
			return null;
		}

		return $part->getContent();
	}

	public function isAutoReply() : bool {
		$autoSubmitted = $this->getHeader('Auto-Submitted') ?? '';
		$autoSubmitted = strtolower(trim($autoSubmitted));
		if (str_starts_with($autoSubmitted, 'auto-replied')) {
			return true;
		}

		if ($this->getHeader('X-Autoreply') !== null || $this->getHeader('X-Autorespond') !== null) {
			return true;
		}

		$precedence = $this->getHeader('Precedence') ?? '';
		$precedence = strtolower(trim($precedence));
		return $precedence === 'auto_reply';
	}

	public function getOriginalMessage() : ?ImapMessage {
		if (isset($this->originalMessage)) {
			return $this->originalMessage;
		}

		$part = $this->message->getPartByMimeType('message/rfc822');
		if (!$part) {
			$part = $this->message->getPartByMimeType('text/rfc822-headers');
		}
		if (!$part) {
			return null;
		}

		$stream = $part->getBinaryContentStream();
		if (!$stream) {
			return null;
		}

		$raw = (string) $stream;
		$parsed = Message::from($raw, true);

		return $this->originalMessage = new self(null, $parsed);
	}

	public function getRawFull() : string {
		return (string) $this->message;
	}

	protected function decodeHeader(IHeader $header) : string {
		$raw = $header->getRawValue();
		$decoded = iconv_mime_decode($raw, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

		return $decoded === false ? $raw : $decoded;
	}
}
