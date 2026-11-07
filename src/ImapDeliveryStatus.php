<?php

namespace rdx\imap;

class ImapDeliveryStatus {

	public function __construct(
		protected string $text,
	) {}

	/**
	 * @return list<string>
	 */
	public function getFailedRecipients() : array {
		$blocks = preg_split('#\R\s*\R#', $this->text);

		$addresses = [];
		foreach ($blocks as $block) {
			if (!preg_match('#^Action:\s*failed#im', $block)) {
				continue;
			}

			$address = $this->getAddress($block, 'Original-Recipient') ?? $this->getAddress($block, 'Final-Recipient');
			if ($address !== null) {
				$addresses[] = $address;
			}
		}

		return $addresses;
	}

	protected function getAddress(string $block, string $field) : ?string {
		if (preg_match('#^' . $field . ':\s*rfc822;\s*(\S+)#im', $block, $match)) {
			return $match[1];
		}

		return null;
	}
}
