<?php

namespace Ezoic_Namespace;

class Ezoic_AdPos extends Ezoic_Feature
{
	private $has_run = false;
	private $sidebar_id = 'sidebar-1';

	public function __construct()
	{
		$this->is_public_enabled = true;
		$this->is_admin_enabled = false;
	}

	public function register_admin_hooks($loader) {}

	public function register_public_hooks($loader)
	{
		$config = Ezoic_AdTester_Config::load();

		// If integration is not enabled, exit early
		// if ( $config->enable_adpos_integration === false ) {
		//     return;
		// }

		// Add any non-sidebar public hooks here
		// Example: $loader->add_action('wp_head', $this, 'some_general_hook');

		// Register sidebar-specific hooks only if sidebar ID is configured
		$this->sidebar_id = $config->sidebar_id;
		if (!empty($this->sidebar_id)) {
			$this->register_sidebar_hooks($loader);
		}
	}

	private function register_sidebar_hooks($loader)
	{
		$loader->add_action('widgets_init', $this, 'register_adpos_widget');

		if (Ezoic_Integration::should_use_js_placeholders()) {
			$loader->add_filter('widgets_init', $this, 'set_js_sidebar_placeholders', 20);
		} else {
			$loader->add_filter('widgets_init', $this, 'set_server_side_markers', 20);
		}
	}

	public function register_adpos_widget()
	{
		if (class_exists('Ezoic_Namespace\Ezoic_AdPos_Widget')) {
			register_widget('Ezoic_Namespace\Ezoic_AdPos_Widget');
		}
	}

	public static function get_current_page_type()
	{
		// Match the supported page types from the ad insertion system
		if (\is_front_page()) {
			return 'home';
		} elseif (\is_category() || \is_home()) {
			return 'category';
		} elseif (\is_single() || \is_archive()) {
			return 'post';
		} elseif (\is_page()) {
			return 'page';
		} else {
			return 'post';
		}
	}

	public function set_js_sidebar_placeholders()
	{
		if ($this->has_run) {
			return;
		}

		$sidebars = get_option('sidebars_widgets');

		if (empty($sidebars)) {
			return;
		}

		if (!array_key_exists($this->sidebar_id, $sidebars) || !is_array($sidebars[$this->sidebar_id]) || count($sidebars[$this->sidebar_id]) === 0) {
			return;
		}

		if (!class_exists('Ezoic_Namespace\Ezoic_AdPos_Widget')) {
			return;
		}

		if (in_array('ezoic_adpos_widget-1', $sidebars[$this->sidebar_id])) {
			$sidebars[$this->sidebar_id] = array_filter($sidebars[$this->sidebar_id], function ($widget) {
				return strpos($widget, 'ezoic_adpos_widget-') !== 0;
			});
		}

		$widget_counter = 0;
		$insert_counter = 1;
		$widget_options = array();
		$new_widgets = array();

		foreach ($sidebars[$this->sidebar_id] as $widget) {
			if (\ez_stripos($widget, 'ezoic_') !== 0) {
				$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;

				$widget_options[$insert_counter] = array(
					'position' => $widget_counter
				);
				$insert_counter++;

				$new_widgets[] = $widget;
				$widget_counter++;
			}
		}

		// Handle high-numbered positions (9998, 9999) from AdTester config
		$config = Ezoic_AdTester_Config::load();
		$page_type = self::get_current_page_type();

		foreach ($config->placeholder_config as $ph_config) {
			if (
				$ph_config->page_type == $page_type &&
				$ph_config->display != 'disabled' &&
				$ph_config->display == 'after_widget' &&
				(int) $ph_config->display_option > $widget_counter
			) {
				$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
				$widget_options[$insert_counter] = array(
					'position' => (int) $ph_config->display_option
				);
				$insert_counter++;
			}
		}

		$sidebars[$this->sidebar_id] = $new_widgets;

		update_option('widget_ezoic_adpos_widget', $widget_options);
		update_option('sidebars_widgets', $sidebars);

		$this->has_run = true;
	}

	public function set_server_side_markers()
	{
		if ($this->has_run) {
			return;
		}

		$sidebars = get_option('sidebars_widgets');

		if (empty($sidebars)) {
			return;
		}

		if (!array_key_exists($this->sidebar_id, $sidebars) || !is_array($sidebars[$this->sidebar_id]) || count($sidebars[$this->sidebar_id]) === 0) {
			return;
		}

		if (!class_exists('Ezoic_Namespace\Ezoic_AdPos_Widget')) {
			return;
		}

		if (in_array('ezoic_adpos_widget-1', $sidebars[$this->sidebar_id])) {
			$sidebars[$this->sidebar_id] = array_filter($sidebars[$this->sidebar_id], function ($widget) {
				return strpos($widget, 'ezoic_adpos_widget-') !== 0;
			});
		}

		$insert_counter = 1;
		$widget_options = array();
		$new_widgets = array();

		$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
		$widget_options[$insert_counter] = array(
			'location' => 'top'
		);
		$insert_counter++;

		foreach ($sidebars[$this->sidebar_id] as $widget) {
			if (\ez_stripos($widget, 'ezoic_') !== 0) {
				$new_widgets[] = $widget;
			}
		}

		$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
		$widget_options[$insert_counter] = array(
			'location' => 'middle'
		);
		$insert_counter++;

		$new_widgets[] = 'ezoic_adpos_widget-' . $insert_counter;
		$widget_options[$insert_counter] = array(
			'location' => 'bottom'
		);

		$sidebars[$this->sidebar_id] = $new_widgets;

		update_option('widget_ezoic_adpos_widget', $widget_options);
		update_option('sidebars_widgets', $sidebars);

		$this->has_run = true;
	}
}
