<?php

namespace rdx\imap;

class IMAPMessage extends IMAPMessageContent implements IMAPMessagePartInterface {

	/** @var IMAPMailbox */
	protected $mailbox;

	/** @var int */
	protected $msgNumber = 1; // starts at 1, not 0

	/** @var bool */
	protected $unseen = true;

	/** @var string[]  */
	protected $headers = [];

	/** @var string */
	protected $subject = '';

	/** @var string */
	protected $subtype = '';

	public function __construct( IMAPMailbox $mailbox, $msgNumber, $unseen = null ) {
		$this->mailbox = $mailbox;
		$this->msgNumber = $msgNumber;
		$this->unseen = $unseen;

		if ( $unseen === null ) {
			$this->unseen = (bool) trim($this->header('unseen'));
		}
	}

	/** @return bool|bool[] */
	protected function flags( $flags, $clear ) {
		$cb = [$this->mailbox()->imap(), $clear ? 'unflag' : 'flag'];

		$feedback = [];
		foreach ( (array) $flags AS $flag ) {
			$flag = '\\' . ucfirst($flag);
			$feedback[] = call_user_func($cb, $this->msgNumber, $flag);
		}

		return is_array($flags) ? $feedback : $feedback[0];
	}

	/** @return bool|bool[] */
	public function flag( $flags ) {
		return $this->flags($flags, false);
	}

	/** @return bool|bool[] */
	public function unflag( $flags ) {
		return $this->flags($flags, true);
	}

	/** @return int */
	public function utc() {
		return strtotime($this->header('date'));
	}

	/** @return string */
	public function subject() {
		if ( empty($this->subject) ) {
			$subject = $this->mailbox()->imap()->utf8($this->header('subject'));
			$this->subject = trim($subject);
		}

		return $this->subject;
	}

	/** @return string[] */
	public function headers() {
		if ( empty($this->headers) ) {
			$this->headers = $this->mailbox()->imap()->headerinfo($this->msgNumber);
		}

		return $this->headers;
	}

	/** @return string */
	public function header( $name ) {
		$headers = $this->headers();
		return @$headers[ strtolower($name) ];
	}

	/** @return IMAPMessagePart */
	public function createMessagePart( $structure, $section ) {
		return new IMAPMessagePart($this, $structure, $section);
	}

	/** @return IMAPMessagePart[] */
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
				foreach ( $structure->parts as $n => $part ) {
					$this->parts[] = $this->createMessagePart(
						$part,
						[$n + 1]
					);
				}
			}
		}

		return $this->parts;
	}

	/** @return object */
	public function structure() {
		if ( empty($this->structure) ) {
			$this->structure = $this->mailbox()->imap()->fetchstructure($this->msgNumber);
		}

		return $this->structure;
	}

	/** @return string */
	public function subtype() {
		if ( empty($this->subtype) ) {
			$structure = $this->structure();
			$this->subtype = @$structure->subtype ?: '';
		}

		return $this->subtype;
	}

	/** @return int[] */
	public function section() {
		return [];
	}

	/** @return string */
	public function content() {
		if ( count($this->parts()) == 1 ) {
			return $this->part(0)->content();
		}

		return '';
	}

	/** @return string */
	public function decodedContent() {
		if ( count($this->parts()) == 1 ) {
			return $this->part(0)->decodedContent();
		}

		return '';
	}

	/** @return bool */
	public function delete() {
		return $this->mailbox()->imap()->delete($this->msgNumber);
	}

	/** @return string[] */
	public function simpleStructure() {
		$parts = [];
		foreach ( $this->allParts() as $part ) {
			$name = '';

			$name .= implode('.', $part->section()) . '. ';
			if ( $part->parts() ) {
				$name .= '*';
			}
			$name .= $part->subtype();
			if ( $part->parameter('disposition') ) {
				if ( $filename = $part->filename() ) {
					$name .= ' (' . $filename . ')';
				}
			}
			if ( $bytes = $part->parameter('bytes') ) {
				$name .= ' (' . number_format($bytes/1024, 1) . 'kb)';
			}

			$parts[] = $name;
		}

		return $parts;
	}

	/** @return int */
	public function msgNumber() {
		return $this->msgNumber;
	}

	/** @return IMAPMailbox */
	public function mailbox() {
		return $this->mailbox;
	}

}
