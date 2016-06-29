<?php

namespace rdx\imap;

use rdx\imap\IMAPMessage;
use rdx\imap\IMAPTransport;

class IMAPMailbox {

	protected $server = '';
	protected $username = '';
	protected $password = '';
	protected $mailbox = '';
	protected $flags = [];

	protected $imap; // rdx\imap\IMAPTransport

	public function __construct( $server, $username, $password, $mailbox = null, array $flags = [] ) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->mailbox = $mailbox ?: 'INBOX';
		$this->flags = $flags;

		$this->connect();
	}

	public function connect() {
		if ( !$this->imap ) {
			$this->imap = $this->createTransport()->open(
				$this->server,
				$this->username,
				$this->password,
				$this->mailbox,
				$this->flags
			);
		}

		return $this->imap;
	}

	public function imap() {
		return $this->imap;
	}

	public function createTransport() {
		return new IMAPTransport;
	}

	public function createMessage( $msgNum, $unseen = null ) {
		return new IMAPMessage($this, $msgNum, $unseen);
	}

	public function headers( $newestFirst = true ) {
		$this->connect();

		$headers = $this->imap()->headers();

		if ( $newestFirst ) {
			$headers = array_reverse($headers);
		}

		return $headers;
	}

	public function message( $msgNum ) {
		$this->connect();

		return $this->createMessage($msgNum);
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
						$messages[] = $this->createMessage($msgNum, $unseen);
					}
				}

				if ( $options['limit'] && isset($messages[ $options['limit'] - 1 ]) ) {
					break;
				}
			}
		}

		return $messages;
	}

	public function getTextSubtypes() {
		return ['PLAIN'];
	}

	public function getHtmlSubtypes() {
		return ['HTML'];
	}

	public function msgInfo() {
		return $this->imap()->mailboxmsginfo();
	}

	public function vacuum() {
		return $this->imap()->expunge();
	}

}
