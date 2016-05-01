<?php

namespace rdx\imap;

use rdx\imap\IMAPMailbox;
use rdx\imap\IMAPMessageContent;
use rdx\imap\IMAPMessagePart;

class IMAPMessage extends IMAPMessageContent implements IMAPMessagePartInterface {

	protected $mailbox; // rdx\imap\IMAPMailbox

	protected $msgNumber = 1; // starts at 1, not 0
	protected $unseen = true;

	protected $headers = [];
	protected $subject = '';
	protected $subtype = '';

	public function __construct( IMAPMailbox $mailbox, $msgNumber, $unseen = null ) {
		$this->mailbox = $mailbox;
		$this->msgNumber = $msgNumber;
		$this->unseen = $unseen;

		if ( $unseen === null ) {
			$this->unseen = (bool) trim($this->header('unseen'));
		}
	}

	protected function flags( $flags, $clear ) {
		$cb = [$this->mailbox()->imap(), $clear ? 'unflag' : 'flag'];

		$feedback = [];
		foreach ( (array)$flags AS $flag ) {
			$flag = '\\' . ucfirst($flag);
			$feedback[] = call_user_func($cb, $this->msgNumber, $flag);
		}

		return is_array($flags) ? $feedback : $feedback[0];
	}

	public function flag( $flags ) {
		return $this->flags($flags, false);
	}

	public function unflag( $flags ) {
		return $this->flags($flags, true);
	}

	public function utc() {
		return strtotime($this->header('date'));
	}

	public function subject() {
		if ( empty($this->subject) ) {
			$subject = $this->mailbox()->imap()->utf8($this->header('subject'));
			$this->subject = trim($subject);
		}

		return $this->subject;
	}

	public function headers() {
		if ( empty($this->headers) ) {
			$this->headers = $this->mailbox()->imap()->headerinfo($this->msgNumber);
		}

		return $this->headers;
	}

	public function header( $name ) {
		$headers = $this->headers();
		return @$headers[ strtolower($name) ];
	}

	public function createMessagePart( $structure, $section ) {
		return new IMAPMessagePart($this, $structure, $section);
	}

	public function parts() {
		if ( empty($this->parts) ) {
			$structure = $this->structure();

			// Possibilities:
			// - PLAIN
			// - ALTERNATIVE
			// - MIXED
			// - DELIVERY-STATUS
			// - RFC822
			// - REPORT
			// - HTML
			// - CALENDAR
			// - JPEG

			if ( empty($structure->parts) ) {
				$this->parts[] = $this->createMessagePart(
					$structure,
					[1]
				);
			}
			else {
				foreach ($structure->parts as $n => $part) {
					$this->parts[] = $this->createMessagePart(
						$part,
						[$n+1]
					);
				}
			}
		}

		return $this->parts;
	}

	public function structure() {
		if ( empty($this->structure) ) {
			$this->structure = $this->mailbox()->imap()->fetchstructure($this->msgNumber);
		}

		return $this->structure;
	}

	public function subtype() {
		if ( empty($this->subtype) ) {
			$structure = $this->structure();
			$this->subtype = @$structure->subtype ?: '';
		}

		return $this->subtype;
	}

	public function section() {
		return [];
	}

	public function content() {
		if ( count($this->parts()) == 1 ) {
			return $this->part(0)->content();
		}

		return '';
	}

	public function delete() {
		return $this->mailbox()->imap()->delete($this->msgNumber);
	}

	public function simpleStructure() {
		$parts = [];
		foreach ( $this->allParts(true) as $part ) {
			$name = '';

			$name .= implode('.', $part->section()) . '. ';
			if ( $part->parts() ) {
				$name .= '*';
			}
			$name .= $part->subtype();
			if ( $bytes = $part->parameter('bytes') ) {
				$name .= ' (' . $bytes . ')';
			}

			$parts[] = $name;
		}

		return $parts;
	}

	public function msgNumber() {
		return $this->msgNumber;
	}

	public function mailbox() {
		return $this->mailbox;
	}

}
