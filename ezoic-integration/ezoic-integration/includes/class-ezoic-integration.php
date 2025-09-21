<?php

namespace {

	include_once 'include-functions.php';
}


namespace Ezoic_Namespace {

	/**
	 * The file that defines the core plugin class
	 *
	 * A class definition that includes attributes and functions used across both the
	 * public-facing side of the site and the admin area.
	 *
	 * @link       https://ezoic.com
	 * @since      1.0.0
	 *
	 * @package    Ezoic_Integration
	 * @subpackage Ezoic_Integration/includes
	 */

	$GLOBALS['ezoic_integration_buffer'] = '';

	if (! defined('EZOIC_URL')) {
		define('EZOIC_URL', getenv('EZOIC_URL') ? getenv('EZOIC_URL') : 'https://publisherbe.ezoic.com');
	}
	if (! defined('EZOIC_API_URL')) {
		define('EZOIC_API_URL', getenv('EZOIC_API_URL') ? getenv('EZOIC_API_URL') : 'https://api-gateway.ezoic.com');
	}
	if (! defined('EZOIC_GATEWAY_URL')) {
		define('EZOIC_GATEWAY_URL', getenv('EZOIC_GATEWAY_URL') ? getenv('EZOIC_GATEWAY_URL') : 'https://g.ezoic.net');
	}

	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Ezoic_Integration
	 * @subpackage Ezoic_Integration/includes
	 * @author     Ezoic Inc. <support@ezoic.com>
	 */
	class Ezoic_Integration
	{

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Ezoic_Integration_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The features list that's responsible for maintaining and registering all features that power
		 * the plugin.
		 *
		 * @since    2.0
		 * @access   protected
		 * @var      Ezoic_Integration_Features $features Maintains and registers all features for the plugin.
		 */
		protected $features;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $plugin_name The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $version The current version of the plugin.
		 */
		protected $version;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct()
		{
			if (defined('EZOIC_INTEGRATION_VERSION')) {
				$this->version = EZOIC_INTEGRATION_VERSION;
			} else {
				$this->version = '1.0.0';
			}
			$this->plugin_name = EZOIC__PLUGIN_SLUG;

			$this->load_dependencies();
			$this->load_features();
			$this->set_locale();
			$this->define_rest_endpoints();
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}

		/**
		 * Determines if the plugin is running the first time after activation to allow post-activation
		 * events to run.
		 */
		public function after_activate()
		{
			// Determine if this is the first "run" of the plugin
			if (is_admin() && \get_option('activated_plugin') == 'ezoic_integration') {
				\delete_option('activated_plugin');

				// Fire after activation event
				\do_action('ez_after_activate');
			}
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Ezoic_Integration_Loader. Orchestrates the hooks of the plugin.
		 * - Ezoic_Integration_i18n. Defines internationalization functionality.
		 * - Ezoic_Integration_Admin. Defines all hooks for the admin area.
		 * - Ezoic_Integration_Public. Defines all hooks for the public side of the site.
		 * - Ezoic_Integration_Features. Orchestrates the features of the plugin.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_dependencies()
		{

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ezoic-integration-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ezoic-integration-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-integration-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-ezoic-integration-public.php';

			/**
			 * The class responsible for orchestrating the features of the
			 * core plugin.
			 */
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ezoic-integration-features.php';

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-speed-settings.php';

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-js-integration-settings.php';

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-emote-settings.php';


			$this->loader   = new Ezoic_Integration_Loader();
			$this->features = new Ezoic_Integration_Features($this->loader);
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Ezoic_Integration_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale()
		{

			$plugin_i18n = new Ezoic_Integration_i18n();

			$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks()
		{

			$plugin_admin    = new Ezoic_Integration_Admin($this->get_plugin_name(), $this->get_version());
			$plugin_settings = new Ezoic_Integration_Admin_Settings($this->get_plugin_name(), $this->get_version());
			$cdn_settings    = new Ezoic_Integration_CDN_Settings($this->get_plugin_name(), $this->get_version());
			$ad_settings     = new Ezoic_Integration_Ad_Settings();
			$speed_settings  = new Ezoic_Speed_Settings();
			$emote_settings  = new Ezoic_Emote_Settings();
			$atm_settings    = new Ezoic_AdsTxtManager_Settings($this->get_plugin_name(), $this->get_version());

			$this->loader->add_action('admin_init', $this, 'after_activate');

			// only verify domains without ez header
			if (Ezoic_Integration_Admin::is_cloud_integrated() == false) {
				$this->loader->add_action('ez_after_activate', $plugin_admin, 'verify_domain');
			}

			$this->loader->add_action('rest_api_init', $ad_settings, 'register_rest');

			// We need to make sure that caching is not enabled while a pub is using a cloud integration. If the request is
			// coming from a cloud integrated site, we turn caching off and clean up any cache files and modifications.
			$plugin_settings->handle_cloud_integrated_with_caching($plugin_admin);

			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

			$this->loader->add_action('admin_enqueue_scripts', $this, 'dequeue_bad_scripts', 999999);

			$this->loader->add_action('admin_menu', $plugin_settings, 'setup_plugin_options_menu');
			$this->loader->add_action('admin_init', $plugin_settings, 'initialize_display_options');
			$this->loader->add_action('admin_init', $plugin_settings, 'initialize_advanced_options');
			$this->loader->add_action('admin_init', $plugin_settings, 'initialize_js_integration_settings');
			$this->loader->add_action('admin_init', $plugin_settings, 'handle_enable_js_integration');
			$this->loader->add_action('admin_init', $plugin_settings, 'handle_disable_js_integration');
			$this->loader->add_action('admin_init', $cdn_settings, 'initialize_cdn_settings');
			$this->loader->add_action('admin_init', $speed_settings, 'initialize_ezoic_speed_settings');
			$this->loader->add_action('admin_init', $atm_settings, 'initialize_adstxtmanager_settings');
			$this->loader->add_action('admin_init', $emote_settings, 'initialize_emote_settings');

			$this->loader->add_action('admin_init', $plugin_admin, 'clear_plugin_caches');
			$this->loader->add_action('activated_plugin', $plugin_admin, 'set_cache_regenerate');
			$this->loader->add_action('deactivated_plugin', $plugin_admin, 'set_cache_regenerate');
			$this->loader->add_action('upgrader_process_complete', $plugin_admin, 'upgrade_set_cache_clear', 10, 2);

			$this->loader->add_action('admin_footer', $plugin_admin, 'theme_switch_notification');

			// Fires when advanced settings are updated
			$this->loader->add_action('update_option_ezoic_integration_options', $plugin_settings, 'handle_update_ezoic_integration_options', 0, 3);

			// Hooks related to ezoic caching.
			$this->loader->add_action('post_updated', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('comment_post', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('update_option_permalink_structure', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('save_post', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('after_delete_post', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('create_category', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('delete_category', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('create_term', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('delete_term', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('wp_create_nav_menu', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('wp_update_nav_menu', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action('wp_delete_nav_menu', $plugin_settings, 'handle_clear_cache');

			// Add Settings link to the plugin.
			$plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->plugin_name . '.php');
			$this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');
		}

		/**
		 * Removes conflicting scripts from other plugins
		 */
		public function dequeue_bad_scripts()
		{
			global $pagenow;
			if (in_array($pagenow, array('options-general.php')) && (isset($_GET['page']) && $_GET['page'] == EZOIC__PLUGIN_SLUG)) {
				wp_deregister_script('js_files_for_wp_admin');
				wp_deregister_script('vue');
				wp_deregister_script('axios');
				wp_deregister_script('vuejs');
			}
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @return    string    The name of the plugin.
		 * @since     1.0.0
		 */
		public function get_plugin_name()
		{
			return $this->plugin_name;
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_public_hooks()
		{
			// Check if preview mode is requested
			$is_preview_mode = self::is_js_preview_mode();

			$is_admin = is_admin();
			$is_disabled = defined('EZOIC__DISABLE') && EZOIC__DISABLE;
			$should_load = (!$is_disabled && !$is_admin) || $is_preview_mode;

			// Load public hooks on frontend pages when not disabled, or in preview mode
			if ($should_load) {
				$plugin_public = new Ezoic_Integration_Public($this->get_plugin_name(), $this->get_version());
				$plugin_public->register_hooks($this->loader);
			}
		}

		/**
		 * Check if JavaScript preview mode is active via URL parameter or cookie
		 * Static method that can be used by both classes
		 */
		public static function is_js_preview_mode()
		{
			// Check URL parameter first (takes priority)
			if (isset($_GET['ez_js_preview'])) {
				return $_GET['ez_js_preview'] == '1';
			}

			// Check cookie if URL parameter not present
			return isset($_COOKIE['ez_js_preview']) && $_COOKIE['ez_js_preview'] == '1';
		}

		/**
		 * Check if JavaScript integration is enabled with WordPress placeholders
		 */
		public static function should_use_js_placeholders()
		{
			// Check if JavaScript integration is enabled
			$js_enabled = get_option('ezoic_js_integration_enabled', false);

			// Check if WordPress placeholders should be used in JS mode
			$js_options = get_option('ezoic_js_integration_options', array());
			$use_wp_placeholders = isset($js_options['js_use_wp_placeholders']) && $js_options['js_use_wp_placeholders'];

			return ($js_enabled && $use_wp_placeholders);
		}

		/**
		 * Load all rest endpoints that need to be available globally
		 */
		private function define_rest_endpoints()
		{
			$authenticator = new Ezoic_Integration_Authentication();
			$this->loader->add_action('rest_api_init', $authenticator, 'register');
		}

		/**
		 * Load all the features for ezoic and the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_features()
		{
			// Do not activate features if the static site is requested
			if (defined('EZOIC__DISABLE') && EZOIC__DISABLE) {
				return;
			}

			$features = array(
				'Ezoic_Wp_Integration',
				'Ezoic_AdTester',
				'Ezoic_AdPos',
				'Ezoic_Cdn',
				'Ezoic_CMS',
				'Ezoic_Emote',
				'Ezoic_Leap',
				'Ezoic_Microdata',
				'Ezoic_AdsTxtManager',
				'FacebookShareCache'
			);

			foreach ($features as $featureName) {
				// Enable features unless disabled by get params
				if (defined('EZOIC__DISABLE__FEATURE') && count(EZOIC__DISABLE__FEATURE) > 0 && in_array($featureName, EZOIC__DISABLE__FEATURE)) {
					continue;
				} else {
					$fullFeatureName = 'Ezoic_Namespace\\' . $featureName;
					$this->features->add_feature(new $fullFeatureName());
				}
			}
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run()
		{
			$this->features->run();
			$this->loader->run();

			//$this->after_activate();
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @return    Ezoic_Integration_Loader    Orchestrates the hooks of the plugin.
		 * @since     1.0.0
		 */
		public function get_loader()
		{
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @return    string    The version number of the plugin.
		 * @since     1.0.0
		 */
		public function get_version()
		{
			return $this->version;
		}
	}
}
