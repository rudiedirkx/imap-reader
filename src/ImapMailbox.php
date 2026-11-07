<?php

namespace rdx\imap;

use DirectoryTree\ImapEngine\Exceptions\Exception as EngineException;
use DirectoryTree\ImapEngine\Exceptions\RuntimeException as EngineRuntimeException;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MessageInterface;
use DirectoryTree\ImapEngine\MessageQueryInterface;
use Generator;
use ZBateson\MailMimeParser\Message;

class ImapMailbox {

	protected const CHUNK_SIZE = 250;

	protected FolderInterface $folder;
	/** @var array<string, string> */
	protected array $status;

	public function __construct(
		string $host,
		string $username,
		string $password,
		int $port = 993,
		string $encryption = 'ssl',
		string $mailbox = 'INBOX',
		bool $validateCert = true,
	) {
		$engine = new Mailbox([
			'host' => $host,
			'port' => $port,
			'encryption' => $encryption,
			'validate_cert' => $validateCert,
			'username' => $username,
			'password' => $password,
		]);

		try {
			$folders = $engine->folders();
			$folder = $folders->find($mailbox);
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		if (!$folder) {
			throw new ImapException("Mailbox '$mailbox' not found");
		}

		$this->folder = $folder;
	}

	public static function fromDsn(string $dsn) : self {
		$parts = parse_url($dsn);
		if (!is_array($parts) || !isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'])) {
			throw new ImapException('Invalid IMAP DSN');
		}

		[$encryption, $defaultPort] = match ($parts['scheme']) {
			'imaps' => ['ssl', 993],
			'imap' => ['starttls', 143],
			default => throw new ImapException("Invalid IMAP DSN scheme '{$parts['scheme']}'"),
		};

		$path = trim($parts['path'] ?? '', '/');
		$mailbox = rawurldecode($path);

		$options = [];
		parse_str($parts['query'] ?? '', $options);
		$validateCert = filter_var($options['validate_cert'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

		return new self(
			host: $parts['host'],
			username: rawurldecode($parts['user']),
			password: rawurldecode($parts['pass']),
			port: $parts['port'] ?? $defaultPort,
			encryption: $encryption,
			mailbox: $mailbox === '' ? 'INBOX' : $mailbox,
			validateCert: $validateCert ?? true,
		);
	}

	public function getTotalMessages() : int {
		$status = $this->getStatus();
		return (int) $status['MESSAGES'];
	}

	public function getTotalUnseenMessages() : int {
		$status = $this->getStatus();
		return (int) $status['UNSEEN'];
	}

	public function getAllIterator(int $limit = 50) : ImapMessageIterator {
		try {
			$query = $this->folder->messages();
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		return $this->iterate($query, $limit);
	}

	public function getAllSinceIterator(string $sinceDate, int $limit = 50) : ImapMessageIterator {
		try {
			$query = $this->folder->messages()->since($sinceDate);
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		return $this->iterate($query, $limit);
	}

	public function getUnseenIterator(int $limit = 50) : ImapMessageIterator {
		try {
			$query = $this->folder->messages()->unseen();
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		return $this->iterate($query, $limit);
	}

	public function markSeen(ImapMessage $message) : void {
		$uid = $message->getUid();
		if ($uid === null) {
			throw new ImapException('Message has no UID');
		}

		try {
			$this->folder
				->messages()
				->uid($uid)
				->markRead();
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		unset($this->status);
	}

	public function deleteOneMessage(ImapMessage $message) : void {
		$uid = $message->getUid();
		if ($uid === null) {
			throw new ImapException('Message has no UID');
		}

		$this->deleteManyUids([$uid]);
	}

	/**
	 * @param list<int> $uids
	 */
	public function deleteManyUids(array $uids) : void {
		if (!$uids) {
			return;
		}

		try {
			$this->folder
				->messages()
				->destroy($uids);
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}
	}

	public function expunge() : int {
		try {
			$expunged = $this->folder->expunge();
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		unset($this->status);

		return count($expunged);
	}

	protected function iterate(MessageQueryInterface $query, int $limit) : ImapMessageIterator {
		try {
			$found = $query->get();
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		$uids = [];
		foreach ($found as $message) {
			$uids[] = $message->uid();
		}
		rsort($uids);

		$total = count($uids);
		$uids = array_slice($uids, 0, $limit);
		$messages = $this->fetchInChunks($uids);

		return new ImapMessageIterator($total, $messages);
	}

	/**
	 * @param list<int> $uids
	 * @return Generator<int, ImapMessage>
	 */
	protected function fetchInChunks(array $uids) : Generator {
		$chunks = array_chunk($uids, self::CHUNK_SIZE);
		foreach ($chunks as $chunk) {
			try {
				$fetched = $this->folder
					->messages()
					->uid($chunk)
					->withHeaders()
					->withBody()
					->get();
			}
			catch (EngineException | EngineRuntimeException $ex) {
				throw new ImapException($ex->getMessage(), previous: $ex);
			}

			$messages = [];
			foreach ($fetched as $message) {
				$messages[$message->uid()] = $this->makeMessage($message);
			}
			krsort($messages);

			foreach ($messages as $message) {
				yield $message;
			}
		}
	}

	protected function makeMessage(MessageInterface $message) : ImapMessage {
		$raw = (string) $message;
		$parsed = Message::from($raw, true);

		return new ImapMessage($message->uid(), $parsed);
	}

	/**
	 * @return array<string, string>
	 */
	protected function getStatus() : array {
		if (isset($this->status)) {
			return $this->status;
		}

		try {
			$status = $this->folder->status();
		}
		catch (EngineException | EngineRuntimeException $ex) {
			throw new ImapException($ex->getMessage(), previous: $ex);
		}

		return $this->status = $status;
	}
}
