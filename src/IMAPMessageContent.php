<?php

namespace rdx\imap;

abstract class IMAPMessageContent {

	protected $structure; // stdClass
	protected $parts = []; // Array<rdx\imap\IMAPMessagePart>
	protected $parameters = [];

	public function allParts( $withContainers = false ) {
		$parts = [];
		$iterate = function( $message ) use (&$iterate, &$parts, $withContainers) {
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

			foreach ( (array) @$structure->parameters as $param ) {
				$this->parameters[ strtolower($param->attribute) ] = $param->value;
			}
			foreach ( (array) @$structure->dparameters as $param ) {
				$this->parameters[ strtolower($param->attribute) ] = $param->value;
			}
		}

		return $this->parameters;
	}

	public function parameter( $name ) {
		$parameters = $this->parameters();
		return @$parameters[ strtolower($name) ];
	}

	public function subtypePart( $subtypes, $recursive ) {
		$subtypes = (array) $subtypes;
		$method = [$this, $recursive ? 'allParts' : 'parts'];
		$parts = call_user_func($method);
		array_unshift($parts, $this);

		foreach ( $parts as $part ) {
			if ( in_array($part->subtype(), $subtypes) ) {
				return $part;
			}
		}
	}

	public function subtypeContent( $subtypes, $recursive ) {
		if ( $part = $this->subtypePart($subtypes, $recursive) ) {
			return $part->content();
		}
	}

	public function text( $recursive = false ) {
		return $this->subtypeContent($this->mailbox()->getTextSubtypes(), $recursive);
	}

	public function html( $recursive = false ) {
		return $this->subtypeContent($this->mailbox()->getHtmlSubtypes(), $recursive);
	}

}
