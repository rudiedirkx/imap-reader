<?php

class IMAPMailbox {

	public $IMAPMessageClass = 'IMAPMessage';

	public $server = '';
	public $username = '';
	public $password = '';
	public $mailbox = '';

	public $mbox; // IMAP resource

	public function __construct( $server, $username, $password, $mailbox = 'INBOX' ) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->mailbox = $mailbox;

		$this->connect();
	}

	public function connect() {
		$this->mbox = imap_open('{'.$this->server.'}'.$this->mailbox.'', $this->username, $this->password);
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

		if ( $options['newestFirst'] ) {
			$messages = array_reverse($messages);
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

	public $mbox; // typeof IMAPMailbox

	public $msgNumber = 1; // starts at 1, not 0
	public $header = '';
	public $unseen = true;

	public $structure; // typeof stdClass

	public function __construct( IMAPMailbox $mbox, $msgNumber, $header, $unseen ) {
		$this->mbox = $mbox;
		$this->msgNumber = $msgNumber;
		$this->header = $header;
		$this->unseen = $unseen;
	}

}


