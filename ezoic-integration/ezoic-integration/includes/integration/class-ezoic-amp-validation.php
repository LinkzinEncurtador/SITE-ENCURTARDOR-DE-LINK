<?php

namespace Ezoic_Namespace;

class Ezoic_Amp_Validation {
	private $orig_content;

	public function __construct( $orig_content ) {
		$this->orig_content = $orig_content;
	}

	public function fix_amp_validation() {

		if ( empty( trim( $this->orig_content ) ) ) {
			return $this->orig_content;
		}

		// add in missing doctype for amp
		if ( Ezoic_Integration_Request_Utils::is_amp_endpoint() ) {
			if ( strtolower( substr( $this->orig_content, 0, 10 ) ) !== "<!doctype " ) {
				$this->orig_content = "<!DOCTYPE html>" . $this->orig_content;
			}
		}

		return $this->orig_content;
	}
}
