<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Sidebar_Inserter extends Ezoic_AdTester_Inserter
{
	private $has_run = false;

	public function __construct($config)
	{
		parent::__construct($config);
	}

	/**
	 * Insert sidebar placeholders
	 */
	public function insert()
	{
		if ($this->has_run) {
			Ezoic_Integration_Logger::console_debug(
				"Sidebar inserter skipped - already run",
				'Sidebar Ads'
			);
			return;
		}

		$sidebars = get_option('sidebars_widgets');

		if (empty($sidebars)) {
			Ezoic_Integration_Logger::console_debug("Sidebar inserter exit: sidebars empty", 'Sidebar Ads');
			return;
		}

		// Attempt to find the correct sidebar, based on configurable id
		if (!array_key_exists($this->config->sidebar_id, $sidebars) || !is_array($sidebars[$this->config->sidebar_id]) || count($sidebars[$this->config->sidebar_id]) === 0) {
			Ezoic_Integration_Logger::console_debug("Sidebar inserter exit: sidebar '{$this->config->sidebar_id}' not found or empty", 'Sidebar Ads');
			return;
		}

		// No widgets in this sidebar
		if (count($sidebars[$this->config->sidebar_id]) === 0) {
			Ezoic_Integration_Logger::console_debug("Sidebar inserter exit: no widgets in sidebar", 'Sidebar Ads');
			return;
		}

		// If the custom sidebar widget was not defined, do not attempt to add
		if (!class_exists('Ezoic_Namespace\Ezoic_AdTester_Widget')) {
			return;
		}

		// Get insertion rules
		$insertion_rules = $this->get_rules();
		if (count($insertion_rules) === 0) {
			// No rules found, return
			return;
		}

		// Register widget, if needed
		if (is_active_widget(false, false, 'ezoic_adtester_widget', true) === false) {
			Ezoic_Integration_Logger::console_debug(
				"Registering AdTester widget class",
				'Sidebar Ads',
				'info'
			);
			register_widget('Ezoic_Namespace\Ezoic_AdTester_Widget');
		} else {
			Ezoic_Integration_Logger::console_debug(
				"AdTester widget already registered",
				'Sidebar Ads',
				'info'
			);
		}

		$widget_counter = 0;
		$insert_counter = 1;
		$adpos_options = array();
		$new_widgets = array();
		$existing_position_ids = array();

		// Remove any existing AdPos widgets from sidebar to prevent conflicts
		$sidebars[$this->config->sidebar_id] = array_filter($sidebars[$this->config->sidebar_id], function ($widget) {
			return \ez_stripos($widget, 'ezoic_adpos') !== 0;
		});
		$sidebars[$this->config->sidebar_id] = array_values($sidebars[$this->config->sidebar_id]); // Re-index array

		// First pass: collect existing position IDs from adpos widgets
		$existing_adpos_options = get_option('widget_ezoic_adpos_widget', array());
		foreach ($sidebars[$this->config->sidebar_id] as $widget) {
			if (\ez_stripos($widget, 'ezoic_adpos') === 0) {
				$widget_number = str_replace('ezoic_adpos_widget-', '', $widget);
				if (isset($existing_adpos_options[$widget_number]['position'])) {
					$widget_position = $existing_adpos_options[$widget_number]['position'];
					// Look up what position ID this adpos widget would insert
					if (isset($insertion_rules[$widget_position])) {
						$existing_position_ids[] = $insertion_rules[$widget_position]->position_id;
						Ezoic_Integration_Logger::console_debug(
							"Found existing AdPos widget: position {$widget_position} -> position_id {$insertion_rules[$widget_position]->position_id}",
							'Sidebar Ads',
							'info'
						);
					}
				}
			}
		}

		foreach ($sidebars[$this->config->sidebar_id] as $widget) {
			if (\ez_stripos($widget, 'ezoic_adtester') !== 0 && \ez_stripos($widget, 'ezoic_adpos') !== 0) {

				if (isset($insertion_rules[$widget_counter])) {
					// Check if this position ID already exists in an adpos widget
					$position_id = $insertion_rules[$widget_counter]->position_id;
					if (in_array($position_id, $existing_position_ids)) {
						Ezoic_Integration_Logger::console_debug(
							"Sidebar placement skipped - already exists in widget",
							'Sidebar Ads',
							'info',
							$position_id
						);
						unset($insertion_rules[$widget_counter]);
					} else {
						// Create AdPos widget instead of AdTester widget for dynamic functionality
						$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
						$adpos_options[$insert_counter] = array(
							'position' => $widget_counter
						);
						$insert_counter++;
						// Remove this rule so it doesn't get processed again in fallback loop
						unset($insertion_rules[$widget_counter]);
					}
				}

				$new_widgets[] = $widget;
				$widget_counter++;
			}
			// Skip existing ezoic widgets - they will be replaced with new ones
		}

		foreach ($insertion_rules as $rule_idx => $remaining_rule) {
			if ($rule_idx < $widget_counter) {
				// Check if this position ID already exists in an adpos widget
				if (in_array($remaining_rule->position_id, $existing_position_ids)) {
					Ezoic_Integration_Logger::console_debug(
						"Sidebar placement skipped in fallback - already exists in widget",
						'Sidebar Ads',
						'info',
						$remaining_rule->position_id
					);
					continue;
				}

				// Insert at the requested position - this should have been handled in the main loop
				// This is a fallback for rules that weren't processed there
				$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
				$adpos_options[$insert_counter] = array(
					'position' => $rule_idx
				);
				$insert_counter++;
			} else {
				// Position is beyond available widgets - only insert at end for specific position types
				if ($remaining_rule->position_type === 'sidebar_bottom' || strpos($remaining_rule->position_type, 'sidebar_floating') !== false) {
					// Check if this position ID already exists in an adpos widget
					if (in_array($remaining_rule->position_id, $existing_position_ids)) {
						Ezoic_Integration_Logger::console_debug(
							"Sidebar placement skipped at end - already exists in widget",
							'Sidebar Ads',
							'info',
							$remaining_rule->position_id
						);
						continue;
					}

					$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
					$adpos_options[$insert_counter] = array(
						'position' => $rule_idx
					);
					$insert_counter++;
				} else {
					Ezoic_Integration_Logger::console_debug(
						"Sidebar placement skipped - wanted position {$rule_idx} but only {$widget_counter} widgets exist. Position type {$remaining_rule->position_type} not allowed at end.",
						'Sidebar Ads',
						'warning',
						$remaining_rule->position_id
					);
				}
			}
		}


		// Replace existing widgets with new widgets
		$sidebars[$this->config->sidebar_id] = $new_widgets;

		update_option('widget_ezoic_adpos_widget', $adpos_options);
		update_option('sidebars_widgets', $sidebars);

		$this->has_run = true;
	}

	/**
	 * Returns a map of relavent rules
	 */
	private function get_rules()
	{
		$rules = array();
		$added_placements = array(); // Track which placements we've already added

		foreach ($this->config->placeholder_config as $ph_config) {
			if (
				$ph_config->page_type == $this->page_type &&	// Current page type
				$ph_config->display != 'disabled' &&			// Rule is enabled
				$ph_config->display == 'after_widget'			// Rule is a sidebar rule
			) {
				$placeholder = $this->config->placeholders[$ph_config->placeholder_id];

				// Skip if we've already added this placement ID
				if (in_array($placeholder->position_id, $added_placements)) {
					continue;
				}

				if (Ezoic_AdTester_Inserter::should_include_placeholder($this->config, $placeholder)) {
					$rules[(int) $ph_config->display_option] = $placeholder;
					$added_placements[] = $placeholder->position_id;
				} else {
					Ezoic_Integration_Logger::console_debug(
						"Sidebar rule rejected: Placement {$placeholder->position_id} for position type {$placeholder->position_type}",
						'Sidebar Ads',
						'info',
						$placeholder->position_id
					);
				}
			}
		}

		\ksort($rules, SORT_NUMERIC);

		return $rules;
	}
}
