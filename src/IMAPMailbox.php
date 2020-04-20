<?php

namespace rdx\imap;

use RuntimeException;

class IMAPMailbox {

	protected $server = '';
	protected $username = '';
	protected $password = '';
	protected $mailbox = '';
	protected $flags = [];

	/**
	 * @var IMAPTransport
	 */
	protected $imap;

	public function __construct( $server, $username, $password, $mailbox = null, array $flags = [] ) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->mailbox = $mailbox ?: 'INBOX';
		$this->flags = $flags;

		$this->connect();
	}

	/** @return IMAPTransport */
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

	/** @return IMAPTransport */
	public function imap() {
		return $this->imap;
	}

	/** @return IMAPTransport */
	public function createTransport() {
		return new IMAPTransport;
	}

	/** @return IMAPMessage */
	public function createMessage( $msgNum, $unseen = null ) {
		return new IMAPMessage($this, $msgNum, $unseen);
	}

	/** @return string[] */
	public function headers( $newestFirst = true ) {
		$this->connect();

		$headers = $this->imap()->headers();

		if ( $newestFirst ) {
			$headers = array_reverse($headers);
		}

		return $headers;
	}

	/** @return IMAPMessage */
	public function message( $msgNum ) {
		$this->connect();

		return $this->createMessage($msgNum);
	}

	/** @return IMAPMessage[] */
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
			if ( preg_match('/([UN]?)\s+(\d+)([\)\d])[\d ]\d\-/', $header, $match) ) {
				$unseen = (bool) trim($match[1]);
				$msgNum = (int) rtrim($match[2] . $match[3], ')');

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
			else {
				throw new RuntimeException("Can't extract message header: '$header'");
			}
		}

		return $messages;
	}

	/** @return string[] */
	public function getTextSubtypes() {
		return ['PLAIN'];
	}

	/** @return string[] */
	public function getHtmlSubtypes() {
		return ['HTML'];
	}

	/** @return object */
	public function msgInfo() {
		return $this->imap()->mailboxmsginfo();
	}

	/** @return bool */
	public function vacuum() {
		return $this->imap()->expunge();
	}

}
