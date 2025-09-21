<?php

namespace Ezoic_Namespace;

abstract class Ezoic_AdTester_Inserter
{
	protected $config;
	protected $page_type;

	protected function __construct($config)
	{
		$this->config = $config;

		// Figure out page type using centralized helper
		$this->page_type = Ezoic_AdPos::get_current_page_type();
	}


	/**
	 * Check if a placeholder should be included based on active placement logic
	 */
	public static function should_include_placeholder($config, $placeholder)
	{
		if (!$placeholder) {
			return false;
		}

		$position_type = $placeholder->position_type;
		$position_id = $placeholder->position_id;

		// Check if this placeholder should be included based on active placement logic
		if (isset($config->enable_placement_id_selection) && $config->enable_placement_id_selection === true) {
			// Position ID selection enabled: only include if this is the active placement
			$active_position_id = $config->get_active_placement($position_type);
			$should_include = ($active_position_id && $active_position_id == $position_id);

			return $should_include;
		} else {
			// Position ID selection disabled, but check if we have active placement data
			$active_position_id = $config->get_active_placement($position_type);

			if ($active_position_id) {
				// We have active placement data, so use it even when selection is disabled
				$should_include = ($active_position_id == $position_id);

				/*Ezoic_Integration_Logger::console_debug(
					"Using active placement data: Placement {$position_id} for position type {$position_type}. Active: {$active_position_id}. Include: " . ($should_include ? 'YES' : 'NO'),
					'Ad System'
				);*/

				return $should_include;
			} else {
				// No active placement data, include all placeholders
				Ezoic_Integration_Logger::console_debug(
					"No active placement data for {$position_type}. Including placement {$position_id}.",
					'Ad System',
					'info',
					$position_id
				);

				return true;
			}
		}
	}
}
