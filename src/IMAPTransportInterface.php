<?php

namespace rdx\imap;

interface IMAPTransportInterface {

	/**
	 * @param $server
	 * @param $username
	 * @param $password
	 * @param $mailbox
	 * @param array $flags
	 * @return IMAPTransportInterface
	 */
	public function open( $server, $username, $password, $mailbox, array $flags );

	/**
	 * @return array<string>
	 */
	public function headers();

	/**
	 * @param $string
	 * @return string
	 */
	public function utf8( $string );

	/**
	 * @param $msgNumber
	 * @return array <mixed>
	 */
	public function headerinfo( $msgNumber );

	/**
	 * @param $msgNumber
	 * @param $flag
	 * @return bool
	 */
	public function unflag( $msgNumber, $flag );

	/**
	 * @param $msgNumber
	 * @param $flag
	 * @return bool
	 */
	public function flag( $msgNumber, $flag );

	/**
	 * @param $msgNumber
	 * @return object
	 */
	public function fetchstructure( $msgNumber );

	/**
	 * @param $msgNumber
	 * @param $section
	 * @return string
	 */
	public function fetchbody( $msgNumber, $section );

	/**
	 * @return bool
	 */
	public function expunge();

	/**
	 * @param $msgNumber
	 * @return bool
	 */
	public function delete( $msgNumber );

	/**
	 * @return object
	 */
	public function mailboxmsginfo();

}
