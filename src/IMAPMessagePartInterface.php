<?php

namespace rdx\imap;

interface IMAPMessagePartInterface {

	/** @return IMAPMailbox */
	public function mailbox();

	/** @return object */
	public function structure();

	/** @return array */
	public function section();

	/** @return IMAPMessagePartInterface[] */
	public function parts();

	/** @return IMAPMessagePartInterface[] */
	public function allParts();

	/**@return IMAPMessagePartInterface */
	public function part( $index );

	/** @return string */
	public function subtype();

	/** @return string */
	public function content();

	/** @return array */
	public function parameters();

	/** @return mixed */
	public function parameter( $name );

	// public function attachments();
	// public function allAttachments();

	/** @return string */
	public function text();

	/** @return string */
	public function html();

}
