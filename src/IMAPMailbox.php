<?php

namespace rdx\imap;

class IMAPMailbox {

	protected $server = '';
	protected $username = '';
	protected $password = '';
	protected $mailbox = '';
	protected $flags = [];

	protected $imap; // imap_open() resource

	public function __construct( $server, $username, $password, $mailbox = null, $flags = [] ) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->mailbox = $mailbox ?: 'INBOX';
		$this->flags = $flags;
	}

	public function connect() {
		if ( !$this->imap ) {
			$server = $this->server;
			if ( !empty($this->flags) ) {
				$server .= '/' . implode('/', $this->flags);
			}

			$mailbox = '{' . $server . '}' . $this->mailbox;
			$this->imap = imap_open($mailbox, $this->username, $this->password);
		}

		return $this->imap;
	}

	public function imap() {
		return $this->imap;
	}

	public function headers( $newestFirst = true ) {
		$this->connect();

		$headers = imap_headers($this->imap());

		if ( $newestFirst ) {
			$headers = array_reverse($headers);
		}

		return $headers;
	}

	public function message( $msgNum ) {
		$this->connect();

		return new IMAPMessage($this, $msgNum);
	}

	public function messages( array $options = [] ) {
		$options += [
			'offset' => 0,
			'limit' => 0,
			'seen' => null,
			'newestFirst' => true,
		];

		$headers = $this->headers($options['newestFirst']);

		$messages = [];
		$eligibles = 0;
		foreach ( $headers AS $n => $header ) {
			if ( preg_match('/([UN]?)\s+(\d+)\)/', $header, $match) ) {
				$unseen = (bool) trim($match[1]);
				$msgNum = (int) $match[2];

				$eligible = $options['seen'] === null || $unseen != $options['seen'];
				if ( $eligible ) {
					$eligibles++;

					if ( $eligibles > $options['offset'] ) {
						$messages[] = new IMAPMessage($this, $msgNum, $unseen);
					}
				}

				if ( $options['limit'] && isset($messages[$options['limit']-1]) ) {
					break;
				}
			}
		}

		return $messages;
	}

}
