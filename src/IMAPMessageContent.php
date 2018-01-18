<?php

namespace rdx\imap;

abstract class IMAPMessageContent implements IMAPMessagePartInterface {

	/** @var object */
	protected $structure;

	/** @var IMAPMessagePart[] */
	protected $parts = [];

	/** @var array */
	protected $parameters = [];

	/** @return IMAPMessagePartInterface[] */
	abstract public function parts();

	/** @return IMAPMessagePartInterface[] */
	public function allParts( $withContainers = false ) {
		$parts = [];
		$iterate = function( IMAPMessagePartInterface $message ) use (&$iterate, &$parts, $withContainers) {
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

	/** @return IMAPMessagePartInterface */
	public function part( $index ) {
		$parts = $this->parts();
		return @$parts[$index];
	}

	/** @return array */
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

	/** @return mixed */
	public function parameter( $name ) {
		$parameters = $this->parameters();
		return @$parameters[ strtolower($name) ];
	}

	/** @return IMAPMessagePartInterface */
	public function subtypePart( $subtypes, $recursive ) {
		$subtypes = (array) $subtypes;

		/** @var IMAPMessagePartInterface[] $parts */
		$method = [$this, $recursive ? 'allParts' : 'parts'];
		$parts = call_user_func($method);
		array_unshift($parts, $this);

		foreach ( $parts as $part ) {
			if ( in_array($part->subtype(), $subtypes) ) {
				return $part;
			}
		}

		return null;
	}

	/** @return string */
	public function subtypeContent( $subtypes, $recursive ) {
		if ( $part = $this->subtypePart($subtypes, $recursive) ) {
			return $part->content();
		}

		return '';
	}

	/** @return string */
	public function text( $recursive = false ) {
		return $this->subtypeContent($this->mailbox()->getTextSubtypes(), $recursive);
	}

	/** @return string */
	public function html( $recursive = false ) {
		return $this->subtypeContent($this->mailbox()->getHtmlSubtypes(), $recursive);
	}

}
