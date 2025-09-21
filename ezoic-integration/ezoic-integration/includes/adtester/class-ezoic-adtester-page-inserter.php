<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Page_Inserter extends Ezoic_AdTester_Inserter
{

	public function __construct($config)
	{
		parent::__construct($config);
	}

	public function insert($insert_position)
	{
		$rules = array();
		foreach ($this->config->placeholder_config as $ph_config) {
			if ($ph_config->page_type == $this->page_type) {
				$rules[$ph_config->placeholder_id] = $ph_config;
			}
		}

		// Insert placeholders
		foreach ($rules as $rule) {
			if ($rule->display != 'disabled') {
				$placeholder = $this->config->placeholders[$rule->placeholder_id];

				if ($rule->display === $insert_position) {
					echo $placeholder->embed_code();
					Ezoic_Integration_Logger::track_insertion($placeholder->position_id);
					Ezoic_Integration_Logger::console_debug(
						"Position {$placeholder->position_id} inserted at {$insert_position}",
						'Page Ads',
						'info',
						$placeholder->position_id
					);
				}
			}
		}
	}
}
