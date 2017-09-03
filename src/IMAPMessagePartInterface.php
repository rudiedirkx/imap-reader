<?php

namespace rdx\imap;

interface IMAPMessagePartInterface {

	/**
	 * @return IMAPMailbox
	 */
	public function mailbox();

	public function structure();

	public function section();

	/**
	 * @return IMAPMessagePartInterface[]
	 */
	public function parts();

	/**
	 * @return IMAPMessagePartInterface[]
	 */
	public function allParts();

	/**
	 * @param int $index
	 * @return IMAPMessagePartInterface
	 */
	public function part( $index );

	/**
	 * @return string
	 */
	public function subtype();

		/**
	 * @return string
	 */
	public function content();

	/**
	 * @return array
	 */
	public function parameters();

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function parameter( $name );

	// public function attachments();
	// public function allAttachments();

	public function text();

	public function html();

}
