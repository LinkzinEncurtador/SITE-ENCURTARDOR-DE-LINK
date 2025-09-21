<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-content-collector.php');

class Ezoic_Integration_Buffer_Content_Collector implements iEzoic_Integration_Content_Collector {

	public function __construct() {

	}

	public function get_orig_content() {
		return $this->get_buffered_final_content();
	}

	private function get_buffered_final_content() {
		$final = "";
		$outputs = array();

		while( ob_get_level() >= 1 ) {

			// Get the current level.
			$level = ob_get_level();
			$outputs[] =  ob_get_clean();

			// If the current level has not changed, abort.
			if (ob_get_level() == $level) {
				break;
			}
		}

		$outputs = array_reverse($outputs);

		foreach($outputs as $output) {
			$final .= $output;
		}

		return apply_filters( 'ez_buffered_final_content', $final );
	}
}
