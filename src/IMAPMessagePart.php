<?php

namespace rdx\imap;

use rdx\imap\IMAPMessage;
use rdx\imap\IMAPMessageContent;

class IMAPMessagePart extends IMAPMessageContent implements IMAPMessagePartInterface {

	protected $section = [];
	protected $subtype = '';

	protected $message; // rdx\imap\IMAPMessage
	protected $skippedParts = []; // Array<stdClass>

	public function __construct( IMAPMessage $message, $structure, array $section ) {
		$this->message = $message;
		$this->structure = $structure;
		$this->section = $section;
		$this->subtype = strtoupper($structure->subtype);

		$this->parts();
	}

	public function parts() {
		if ( empty($this->parts) && !empty($this->structure()->parts) ) {
			$parts = $this->structure()->parts;
			while ( count($parts) == 1 && empty($parts[0]->bytes) && !empty($parts[0]->parts) ) {
				$this->skippedParts[] = $parts[0];
				$parts = $parts[0]->parts;
			}

			foreach ( $parts as $n => $part ) {
				$this->parts[] = $this->message()->createMessagePart(
					$part,
					array_merge($this->section(), [$n + 1])
				);
			}
		}

		return $this->parts;
	}

	public function structure() {
		return $this->structure;
	}

	public function content() {
		return $this->message()->mailbox()->imap()->fetchbody(
			$this->message()->msgNumber(),
			implode('.', $this->section())
		);
	}

	public function message() {
		return $this->message;
	}

	public function mailbox() {
		return $this->message()->mailbox();
	}

	public function section() {
		return $this->section;
	}

	public function subtype() {
		return $this->subtype;
	}

}
