<?php

namespace rdx\imap;

interface IMAPMessagePartInterface {
	public function structure();
	public function parts();
	public function allParts();
	public function part( $index );
	public function content();
	public function parameters();

	// public function attachments();
	// public function allAttachments();
}
