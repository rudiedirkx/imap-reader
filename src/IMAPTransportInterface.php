<?php

namespace rdx\imap;

interface IMAPTransportInterface {

	/** @return IMAPTransport */
	public function open( $server, $username, $password, $mailbox, array $flags );

	/** @return string[] */
	public function headers();

	/** @return string */
	public function utf8( $string );

	/** @return string[] */
	public function headerinfo( $msgNumber );

	/** @return bool */
	public function unflag( $msgNumber, $flag );

	/** @return bool */
	public function flag( $msgNumber, $flag );

	/** @return object */
	public function fetchstructure( $msgNumber );

	/** @return string */
	public function fetchbody( $msgNumber, $section );

	/** @return bool */
	public function expunge();

	/** @return bool */
	public function delete( $msgNumber );

	/** @return object */
	public function mailboxmsginfo();

}
