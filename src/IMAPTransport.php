<?php

namespace rdx\imap;

use rdx\imap\IMAPException;
use rdx\imap\IMAPTransportInterface;

class IMAPTransport implements IMAPTransportInterface {

	protected $resource; // imap_open() resource

	public function open( $server, $username, $password, $mailbox, array $flags ) {
		if ( !empty($flags) ) {
			$server .= '/' . implode('/', $flags);
		}

		$mailbox = '{' . $server . '}' . $mailbox;
		$this->resource = @imap_open($mailbox, $username, $password);
		if ( !$this->resource ) {
			$error = imap_last_error();
			imap_errors();
			imap_alerts();
			throw new IMAPException($error);
		}

		return $this;
	}

	public function headers() {
		return imap_headers($this->resource);
	}

	public function utf8( $string ) {
		return imap_utf8($string);
	}

	public function headerinfo( $msgNumber ) {
		return imap_headerinfo($this->resource, $msgNumber);
	}

	public function clearflag( $msgNumber, $flag ) {
		return imap_clearflag_full($this->resource, $msgNumber, $flag);
	}

	public function setflag( $msgNumber, $flag ) {
		return imap_setflag_full($this->resource, $msgNumber, $flag);
	}

	public function fetchstructure( $msgNumber ) {
		return imap_fetchstructure($this->resource, $msgNumber);
	}

	public function fetchbody( $msgNumber, $section ) {
		return imap_fetchbody($this->resource, $msgNumber, $section, FT_PEEK);
	}

	public function expunge() {
		return imap_expunge($this->resource);
	}

	public function delete( $msgNumber ) {
		return imap_delete($this->resource, $msgNumber);
	}

	public function mailboxmsginfo() {
		return imap_mailboxmsginfo($this->resource);
	}

}
