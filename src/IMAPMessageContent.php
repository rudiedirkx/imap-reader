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
	public function allParts() {
		$parts = [];
		$iterate = function( IMAPMessagePartInterface $message ) use (&$iterate, &$parts) {
			foreach ( $message->parts() as $part ) {
				$parts[] = $part;

				if ( count($part->parts()) ) {
					$iterate($part);
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
		$structure = $this->structure();
		return @$parameters[ strtolower($name) ] ?: @$structure->$name;
	}

	/** @return string[] */
	public function headers() {
		if ( empty($this->headers) ) {
			$headers = preg_split('#[\r\n]+(?=\w)#', $this->headerString());
			$headers = array_map('mb_decode_mimeheader', $headers);

			$this->headers = [];
			foreach ($headers as $header) {
				$x = explode(':', $header, 2);
				$this->headers[ trim(strtolower($x[0])) ][] = trim($x[1]);
			}
		}

		return $this->headers;
	}

	/** @return string|string[] */
	public function header( $name ) {
		$headers = $this->headers();
		$header = $headers[strtolower($name)] ?? [null];
		return count($header) == 1 ? $header[0] : $header;
	}

	/** @return IMAPMessagePartInterface[] */
	public function subtypeParts( $subtypes, $recursive ) {
		$subtypes = (array) $subtypes;

		$parts = $recursive ? $this->allParts() : $this->parts();
		array_unshift($parts, $this);

		return array_values(array_filter($parts, function(IMAPMessagePartInterface $part) use ($subtypes) {
			return in_array($part->subtype(), $subtypes);
		}));
	}

	/** @return IMAPMessagePartInterface */
	public function subtypePart( $subtypes, $recursive ) {
		if ( count($parts = $this->subtypeParts($subtypes, $recursive)) ) {
			return $parts[0];
		}
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
