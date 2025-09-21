<?php

namespace Ezoic_Namespace;

class Ezoic_Leap extends Ezoic_Feature
{
	private $wp_data;

	public function __construct()
	{
		$this->is_public_enabled = false;
		$this->is_admin_enabled  = true;

		$this->wp_data = new Ezoic_Leap_Wp_Data();
	}

	public function register_public_hooks($loader)
	{
		// No public hooks
	}

	public function register_admin_hooks($loader)
	{
		// Send debug data on core/theme/plugin updates
		$loader->add_action('admin_init', $this->wp_data, 'send_debug_to_ezoic');
		// $loader->add_action( 'switch_theme', $this->wp_data, 'set_debug_to_ezoic' );
		$loader->add_action('activated_plugin', $this->wp_data, 'set_debug_to_ezoic');
		$loader->add_action('deactivated_plugin', $this->wp_data, 'set_debug_to_ezoic');
		$loader->add_action('upgrader_process_complete', $this->wp_data, 'set_debug_to_ezoic');
	}
}
