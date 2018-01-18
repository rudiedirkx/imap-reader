<?php

namespace rdx\imap;

class IMAPMessagePart extends IMAPMessageContent implements IMAPMessagePartInterface {

	/** @var int[] */
	protected $section = [];

	/** @var string */
	protected $subtype = '';

	/** @var IMAPMessage */
	protected $message;

	/** @var object[] */
	protected $skippedParts = [];

	public function __construct( IMAPMessage $message, $structure, array $section ) {
		$this->message = $message;
		$this->structure = $structure;
		$this->section = $section;
		$this->subtype = strtoupper($structure->subtype);

		$this->parts();
	}

	/** @return IMAPMessagePart[] */
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

	/** @return object */
	public function structure() {
		return $this->structure;
	}

	/** @return string */
	public function content() {
		return $this->message()->mailbox()->imap()->fetchbody(
			$this->message()->msgNumber(),
			implode('.', $this->section())
		);
	}

	/** @return IMAPMessage */
	public function message() {
		return $this->message;
	}

	/** @return IMAPMailbox */
	public function mailbox() {
		return $this->message()->mailbox();
	}

	/** @return int[] */
	public function section() {
		return $this->section;
	}

	/** @return string */
	public function subtype() {
		return $this->subtype;
	}

}
