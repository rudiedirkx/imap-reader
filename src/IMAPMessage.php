<?php

namespace rdx\imap;

class IMAPMessage implements IMAPMessagePartInterface {

	public $mailbox; // typeof IMAPMailbox

	public $msgNumber = 1; // starts at 1, not 0
	public $header = '';
	public $unseen = true;

	public $headers; // typeof stdClass
	public $structure; // typeof stdClass

	public $subject = '';
	public $parts = [];
	public $parameters = [];
	public $plainBody;
	public $HTMLBody;
	public $attachments = []; // typeof Array<IMAPMessageAttachment>

	public function __construct( IMAPMailbox $mailbox, $msgNumber, $header, $unseen ) {
		$this->mailbox = $mailbox;
		$this->msgNumber = $msgNumber;
		$this->header = $header;
		$this->unseen = $unseen;
	}

	protected function flags( $flags, $clear ) {
		$cb = $clear ? 'imap_clearflag_full' : 'imap_setflag_full';

		$feedback = [];
		foreach ( (array)$flags AS $flag ) {
			$flag = '\\' . ucfirst($flag);
			$feedback[] = $cb($this->mailbox->imap(), (string)$this->msgNumber, $flag);
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
		$headers = $this->headers();
		return strtotime($headers->Date);
	}

	public function subject() {
		if ( empty($this->subject) ) {
			$headers = $this->headers();

			$subject = imap_utf8($headers->Subject);

			$this->subject = trim($subject);
		}

		return $this->subject;
	}

	public function headers() {
		if ( empty($this->headers) ) {
			$this->headers = imap_headerinfo($this->mailbox->imap(), $this->msgNumber);
		}

		return $this->headers;
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

			if ( empty($structure->parts) ) {
				$this->parts[] = new IMAPMessagePart(
					$this,
					$structure,
					[1]
				);
			}
			else {
				foreach ($structure->parts as $n => $part) {
					$this->parts[] = new IMAPMessagePart(
						$this,
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
			$this->structure = imap_fetchstructure($this->mailbox->imap(), $this->msgNumber);
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

			$name .= implode('.', $part->section) . '. ';
			if ( $part->parts() ) {
				$name .= '*';
			}
			$name .= $part->subtype;
			if ( $bytes = $part->parameter('bytes') ) {
				$name .= ' (' . $bytes . ')';
			}

			$parts[] = $name;
		}

		return $parts;
	}

}
