<?php

namespace rdx\imap;

class IMAPTransport implements IMAPTransportInterface {

	protected $resource; // imap_open() resource

	/** @return IMAPTransport */
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

	/** @return string[] */
	public function headers() {
		return imap_headers($this->resource);
	}

	/** @return string */
	public function utf8( $string ) {
		return imap_utf8($string);
	}

	/** @return string[] */
	public function headerinfo( $msgNumber ) {
		return $this->iteratorToLowercaseArray(imap_headerinfo($this->resource, $msgNumber));
	}

	/** @return bool */
	public function unflag( $msgNumber, $flag ) {
		return imap_clearflag_full($this->resource, $msgNumber, $flag);
	}

	/** @return bool */
	public function flag( $msgNumber, $flag ) {
		return imap_setflag_full($this->resource, $msgNumber, $flag);
	}

	/** @return object */
	public function fetchstructure( $msgNumber ) {
		return imap_fetchstructure($this->resource, $msgNumber);
	}

	/** @return string */
	public function fetchbody( $msgNumber, $section ) {
		return quoted_printable_decode(imap_fetchbody($this->resource, $msgNumber, $section, FT_PEEK));
	}

	/** @return bool */
	public function expunge() {
		return imap_expunge($this->resource);
	}

	/** @return bool */
	public function delete( $msgNumber ) {
		return imap_delete($this->resource, $msgNumber);
	}

	/** @return object */
	public function mailboxmsginfo() {
		return (object) $this->iteratorToLowercaseArray(imap_mailboxmsginfo($this->resource));
	}

	/** @return array */
	protected function iteratorToLowercaseArray( $iterator ) {
		$data = [];
		foreach ( $iterator as $name => $value ) {
			$data[ strtolower($name) ] = $value;
		}

		return $data;
	}

}
