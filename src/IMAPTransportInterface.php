<?php

namespace rdx\imap;

interface IMAPTransportInterface {

	public function open( $server, $username, $password, $mailbox, array $flags );
	public function headers();
	public function utf8( $string );
	public function headerinfo( $msgNumber );
	public function clearflag( $msgNumber, $flag );
	public function setflag( $msgNumber, $flag );
	public function fetchstructure( $msgNumber );
	public function fetchbody( $msgNumber, $section );

}
