<?php

namespace rdx\imap;

use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, ImapMessage>
 */
class ImapMessageIterator implements IteratorAggregate {

	/**
	 * @param Generator<int, ImapMessage> $messages
	 */
	public function __construct(
		protected int $total,
		protected Generator $messages,
	) {}

	public function getTotal() : int {
		return $this->total;
	}

	/**
	 * @return Generator<int, ImapMessage>
	 */
	public function getIterator() : Generator {
		return $this->messages;
	}
}
