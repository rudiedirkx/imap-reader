<?php

namespace rdx\imap;

use rdx\imap\IMAPMailbox;
use rdx\imap\IMAPMessagePart;

class IMAPMessage implements IMAPMessagePartInterface {

	protected $mailbox; // typeof IMAPMailbox

	protected $msgNumber = 1; // starts at 1, not 0
	protected $unseen = true;

	protected $headers = [];
	protected $structure; // typeof stdClass

	protected $subject = '';
	protected $parts = [];
	protected $parameters = [];
	protected $plainBody;
	protected $HTMLBody;
	protected $attachments = []; // typeof Array<IMAPMessageAttachment>

	public function __construct( IMAPMailbox $mailbox, $msgNumber, $unseen = null ) {
		$this->mailbox = $mailbox;
		$this->msgNumber = $msgNumber;
		$this->unseen = $unseen;

		if ( $unseen === null ) {
			$this->unseen = (bool) trim($this->header('unseen'));
		}
	}

	protected function flags( $flags, $clear ) {
		$cb = [$this->imap(), $clear ? 'clearflag' : 'setflag'];

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
			$headers = $this->mailbox()->imap()->headerinfo($this->msgNumber);
			foreach ( $headers as $name => $value ) {
				$this->headers[ strtolower($name) ] = $value;
			}
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

	public function allParts( $withContainers = false ) {
		$parts = [];
		$iterate = function($message) use (&$iterate, &$parts, $withContainers) {
			foreach ( $message->parts() as $part ) {
				if ( $part->parts() ) {
					if ( $withContainers ) {
						$parts[] = $part;
					}

					$iterate($part);
				}
				else {
					$parts[] = $part;
				}
			}
		};

		$iterate($this);
		return $parts;
	}

	public function part( $index ) {
		$parts = $this->parts();
		return @$parts[$index];
	}

	public function structure() {
		if ( empty($this->structure) ) {
			$this->structure = $this->mailbox()->imap()->fetchstructure($this->msgNumber);
		}

		return $this->structure;
	}

	public function content() {
		if ( count($this->parts()) == 1 ) {
			return $this->part(0)->content();
		}

		return '';
	}

	public function parameters() {
		if ( empty($this->parameters) ) {
			$structure = $this->structure();

			$this->parameters['bytes'] = @$structure->bytes;

			foreach ((array) @$structure->parameters as $param) {
				$this->parameters[ strtolower($param->attribute) ] = $param->value;
			}
			foreach ((array) @$structure->dparameters as $param) {
				$this->parameters[ strtolower($param->attribute) ] = $param->value;
			}
		}

		return $this->parameters;
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
