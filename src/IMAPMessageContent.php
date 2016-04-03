<?php

namespace rdx\imap;

abstract class IMAPMessageContent {

	protected $structure; // stdClass
	protected $parts = []; // Array<rdx\imap\IMAPMessagePart>
	protected $parameters = [];

	public function allParts( $withContainers = false ) {
		$parts = [];
		$iterate = function($message) use (&$iterate, &$parts, $withContainers) {
			foreach ( $message->parts() as $part ) {
				if ( $part->parts() ) {
					if ( $withContainers ) {
						$parts[] = $part;
					}

					$iterate($part);
				}
				else {
					$parts[] = $part;
				}
			}
		};

		$iterate($this);
		return $parts;
	}

	public function part( $index ) {
		$parts = $this->parts();
		return @$parts[$index];
	}

	public function parameters() {
		if ( empty($this->parameters) ) {
			$structure = $this->structure();

			$this->parameters['bytes'] = @$structure->bytes;

			foreach ((array) @$structure->parameters as $param) {
				$this->parameters[ strtolower($param->attribute) ] = $param->value;
			}
			foreach ((array) @$structure->dparameters as $param) {
				$this->parameters[ strtolower($param->attribute) ] = $param->value;
			}
		}

		return $this->parameters;
	}

	public function parameter( $name ) {
		$parameters = $this->parameters();
		return @$parameters[ strtolower($name) ];
	}

	protected function subtypeContent( $subtypes, $recursive ) {
		$subtypes = (array) $subtypes;
		foreach ( $this->parts($recursive) as $part ) {
			if ( in_array($part->subtype, $subtypes) ) {
				return $part->content();
			}
		}
	}

	public function text( $recursive = false ) {
		return $this->subtypeContent($this->mailbox()->getTextSubtypes(), $recursive);
	}

	public function html( $recursive = false ) {
		return $this->subtypeContent($this->mailbox()->getHtmlSubtypes(), $recursive);
	}

	public function deliveryStatus( $recursive = true ) {
		return $this->subtypeContent('DELIVERY-STATUS', $recursive);
	}

}
