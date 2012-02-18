<?php

class IMAPMailbox {

	public $IMAPMessageClass = 'IMAPMessage';

	public $server = '';
	public $username = '';
	public $password = '';
	public $mailbox = '';

	public $mbox; // IMAP resource

	public function __construct( $server, $username, $password, $mailbox = 'INBOX', $flags = array() ) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->mailbox = $mailbox;
		$this->flags = $flags;

		$this->connect();
	}

	public function connect() {
		$server = $this->server;
		if ( $this->flags ) {
			$server .= '/' . implode('/', $this->flags);
		}

		$mailbox = '{'.$server.'}'.$this->mailbox;
		$this->mbox = imap_open($mailbox, $this->username, $this->password);
	}

	public function messages( $options = true ) {
		if ( is_bool($options) ) {
			$options = array('seen' => $options);
		}

		$options = self::options($options, array(
			'offset' => 0,
			'limit' => 0,
			'seen' => true,
			'newestFirst' => true,
		));

		$IMAPMessageClass = $this->IMAPMessageClass;

		$headers = imap_headers($this->mbox);
		if ( $options['newestFirst'] ) {
			$headers = array_reverse($headers);
		}

		$messages = array();
		$eligables = 0;
		foreach ( $headers AS $n => $header ) {
			if ( preg_match('/(U?)\s+(\d+)\)/', $header, $match) ) {
				$unseen = (bool)trim($match[1]);
				$msgNum = (int)$match[2];

				$eligable = $options['seen'] || $unseen;
				if ( $eligable ) {
					$eligables++;
				}

				if ( $eligable ) {
					if ( $eligables > $options['offset'] ) {
						if ( !$options['limit'] || !isset($messages[$options['limit']-1]) ) {
							$messages[] = new $IMAPMessageClass($this, $msgNum, $header, $unseen);
						}
					}
				}

				if ( $options['limit'] && isset($messages[$options['limit']-1]) ) {
					break;
				}
			}
		}

		return $messages;
	}

	static public function options( $options, $base ) {
		foreach ( $options AS $name => $value ) {
			$base[$name] = $value; // overwrite base
		}

		return $base;
	}

}

class IMAPMessage {

	static public $IMAPMessagePartClass = 'IMAPMessagePart';
	static public $IMAPMessageAttachmentClass = 'IMAPMessageAttachment';

	public $mailbox; // typeof IMAPMailbox

	public $msgNumber = 1; // starts at 1, not 0
	public $header = '';
	public $unseen = true;

	public $headers; // typeof stdClass
	public $structure; // typeof stdClass

	public $subject = '';
	public $parts = array();
	public $plainBody;
	public $HTMLBody;
	public $attachments = array(); // typeof Array<IMAPMessageAttachment>

	public function __construct( IMAPMailbox $mailbox, $msgNumber, $header, $unseen ) {
		$this->mailbox = $mailbox;
		$this->msgNumber = $msgNumber;
		$this->header = $header;
		$this->unseen = $unseen;
	}

	protected function flags( $flags, $clear ) {
		$cb = $clear ? 'imap_clearflag_full' : 'imap_setflag_full';

		$feedback = array();
		foreach ( (array)$flags AS $flag ) {
			$flag = '\\' . ucfirst($flag);
			$feedback[] = $cb($this->mailbox->mbox, (string)$this->msgNumber, $flag);
		}

		return is_array($flags) ? $feedback : $feedback[0];
	}

	public function flag( $flags ) {
		return $this->flags($flags, false);
	}

	public function unflag( $flags ) {
		return $this->flags($flags, true);
	}

	public function subject() {
		if ( !$this->subject ) {
			$headers = $this->headers();

			$subjectParts = imap_mime_header_decode($headers->Subject);
			$subject = '';
			foreach ( $subjectParts AS $p ) {
				$subject .= $p->text;
			}

			$this->subject = trim($subject);
		}

		return $this->subject;
	}

	public function headers() {
		if ( !$this->headers ) {
			$this->headers = imap_headerinfo($this->mailbox->mbox, $this->msgNumber);
		}

		return $this->headers;
	}

	public function parts() {
		if ( !$this->parts ) {
			$structure = $this->structure();

			// Possibilities:
			// - PLAIN => only plain, no attachments
			// - ALTERNATIVE => plain & html, no attachments
			// - MIXED => message (ALTERNATIVE or PLAIN) & attachments

			$IMAPMessagePartClass = self::$IMAPMessagePartClass;

			$parts = $attachments = array();

			// - PLAIN
			if ( 'PLAIN' == $structure->subtype ) {
				$parts[] = new $IMAPMessagePartClass($this, $structure, '1');
			}

			// - ALTERNATIVE
			else if ( 'ALTERNATIVE' == $structure->subtype ) {
				// get message parts
				$parts = $this->messageParts($structure->parts, null, $IMAPMessagePartClass);
			}

			// - MIXED -- uh oh -- attachments!
			else {
				$IMAPMessageAttachmentClass = self::$IMAPMessageAttachmentClass;

				foreach ( $structure->parts AS $i => $part ) {
					if ( 'ALTERNATIVE' == $part->subtype ) {
						$parts = array_merge($parts, $this->messageParts($part->parts, $i+1, $IMAPMessagePartClass));
					}
					else {
						$parts[] = new $IMAPMessagePartClass($this, $part, $i+1);

						if ( $part->ifdisposition && 'ATTACHMENT' == $part->disposition ) {
							$attachments[] = new $IMAPMessageAttachmentClass($this, $part, $i+1);
						}
					}
				}
			}

			$this->parts = $parts;
			$this->attachments = $attachments;

			foreach ( $parts AS $part ) {
				if ( 'PLAIN' == $part->subtype && !$this->plainBody ) {
					$this->plainBody = $part;
				}
				else if ( 'HTML' == $part->subtype && !$this->HTMLBody ) {
					$this->HTMLBody = $part;
				}
			}
		}

		return $this->parts;
	}

	protected function messageParts( $parts, $sectionPrefix = array(), $IMAPMessagePartClass ) {
		$sectionPrefix = (array)$sectionPrefix;

		$partObjects = array();
		foreach ( $parts AS $i => $part ) {
			$s = $sectionPrefix;
			$s[] = (string)($i+1);
			$section = implode('.', $s);

			$partObjects[] = new $IMAPMessagePartClass($this, $part, $section);
		}

		return $partObjects;
	}

	public function structure() {
		if ( !$this->structure ) {
			$this->structure = imap_fetchstructure($this->mailbox->mbox, $this->msgNumber);
		}

		return $this->structure;
	}

}

class IMAPMessagePart {

	public $section = '';
	public $subtype = '';
	public $contentType = '';
	public $charset = '';
	public $size = 0;
	public $data = '';

	public $message; // typeof IMAPMessage
	public $structure; // typeof stdClass

	public function __construct( $message, $structure, $section ) {
		$this->message = $message;
		$this->structure = $structure;
		$this->section = (string)$section;
		$this->subtype = $structure->subtype;
	}

	public function content() {
		return imap_fetchbody($this->message->mailbox->mbox, $this->message->msgNumber, $this->section);
	}

	public function decode( $content ) {
		return $content;
	}

}

class IMAPMessageAttachment extends IMAPMessagePart {

	public $filename = '';

	public function __construct( $message, $structure, $section ) {
		parent::__construct($message, $structure, $section);

		$this->filename();
	}

	public function filename() {
		if ( !$this->filename ) {
			// from dparameters
			if ( $this->structure->ifdparameters ) {
				foreach ( $this->structure->dparameters AS $param ) {
					if ( 'FILENAME' == $param->attribute ) {
						$this->filename = $param->value;
						break;
					}
				}
			}

			// from parameters
			if ( !$this->filename && $this->structure->ifparameters ) {
				foreach ( $this->structure->parameters AS $param ) {
					if ( 'NAME' == $param->attribute ) {
						$this->filename = $param->value;
						break;
					}
				}
			}
		}

		return $this->filename;
	}

	public function save( $filepath ) {
		if ( '/' == substr($filepath, -1) ) {
			$filepath .= $this->filename;
		}

		return file_put_contents($filepath, $this->decode($this->content()));
	}

	public function decode( $content ) {
		return base64_decode($content);
	}

}


