<?php

namespace Ezoic_Namespace;

// Only create custom widget if WP_Widget is supported
if (class_exists('WP_Widget')) {
	class Ezoic_AdTester_Widget extends \WP_Widget
	{
		public function __construct()
		{
			parent::__construct('ezoic_adtester_widget', 'Ezoic AdTester Widget');
		}

		public function widget($args, $instance)
		{
			$embed_code = $instance['embed_code'];
			echo $embed_code;

			// Track insertion if position ID found
			if (preg_match('/id="ezoic-pub-ad-placeholder-(\d+)"/', $embed_code, $matches)) {
				$position_id = $matches[1];
				Ezoic_Integration_Logger::track_insertion($position_id);
				Ezoic_Integration_Logger::console_debug(
					"Inserted in sidebar widget",
					'Sidebar Ads',
					'info',
					$position_id
				);
			}
		}

		public function form($instance)
		{
			// Do nothing
		}
	}
}
