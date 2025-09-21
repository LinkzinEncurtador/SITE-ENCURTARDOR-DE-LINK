<?php

namespace Ezoic_Namespace;

class Ezoic_Speed_Settings
{
	public function __construct()
	{
		if (! is_admin()) {
			if (self::is_setting_enabled('ezoic_disable_emojis')) {
				add_action('init', array($this, 'disable_emojis'));
			}

			if (self::is_setting_enabled('ezoic_schemeless_urls')) {
				add_filter('script_loader_src', array($this, 'schemeless_urls'));
				add_filter('style_loader_src', array($this, 'schemeless_urls'));
			}

			if (self::is_setting_enabled('ezoic_remove_jquery_migrate')) {
				add_action('wp_default_scripts', array($this, 'remove_jquery_migrate'));
			}

			add_action('template_redirect', array($this, 'ezoic_header_cleanup'));
		}
	}

	// Configure display of settings fields
	public function initialize_ezoic_speed_settings()
	{
		if (false === \get_option('ezoic_speed_settings')) {
			$default_array = $this->default_speed_settings();
			update_option('ezoic_speed_settings', $default_array);
		}

		add_settings_section(
			'ezoic_speed_settings_section',
			__('Speed Settings', 'ezoic'),
			array($this, 'ezoic_speed_settings_overview'),
			'ezoic_speed_settings'
		);

		add_settings_field(
			'ezoic_disable_emojis',
			'Remove Emojis',
			array($this, 'ezoic_disable_emojis_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_schemeless_urls',
			__('Scheme-less URLs for JavaScript and CSS files', 'ezoic'),
			array($this, 'ezoic_disable_schemeless_urls_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_jquery_migrate',
			__('Remove jQuery Migrate', 'ezoic'),
			array($this, 'ezoic_remove_jquery_migrate_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_feed_links',
			__('Remove feed links', 'ezoic'),
			array($this, 'ezoic_remove_feed_links_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_rsd_link',
			__('Remove RSD link'),
			array($this, 'ezoic_remove_rsd_link_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_wlwmanifest_link',
			__('Remove WLW manifest link'),
			array($this, 'ezoic_remove_wlwmanifest_link_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_adjacent_posts_links',
			__('Remove adjacent posts links'),
			array($this, 'ezoic_remove_adjacent_posts_links_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_wordpress_version_number',
			__('Remove WordPress version number'),
			array($this, 'ezoic_remove_wordpress_version_number_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		add_settings_field(
			'ezoic_remove_shortlink',
			__('Remove shortlink'),
			array($this, 'ezoic_remove_shortlink_field'),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section'
		);

		// Register setting (this is an array of the various settings, see default_speed_settings())
		register_setting('ezoic_speed_settings', 'ezoic_speed_settings');
	}

	// Output page header with overview of settings
	public function ezoic_speed_settings_overview()
	{
		echo '<p>' . __('These settings can improve your site\'s speed by removing or disabling certain features', 'ezoic') . '</p>';
		echo '<hr/>';
	}

	// Ouput field
	public function ezoic_disable_emojis_field()
	{
		$value = self::is_setting_enabled('ezoic_disable_emojis');

?>
		<input type="radio" id="ezoic_disable_emojis_on" name="ezoic_speed_settings[ezoic_disable_emojis]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_disable_emojis_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_disable_emojis_off" name="ezoic_speed_settings[ezoic_disable_emojis]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_disable_emojis_off">Disabled</label>
		<p class="description">
			Removes default WordPress emojis styles and scripts
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_disable_schemeless_urls_field()
	{
		$value = self::is_setting_enabled('ezoic_schemeless_urls');

	?>
		<input type="radio" id="ezoic_schemeless_urls_on" name="ezoic_speed_settings[ezoic_schemeless_urls]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_schemeless_urls_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_schemeless_urls_off" name="ezoic_speed_settings[ezoic_schemeless_urls]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_schemeless_urls_off">Disabled</label>
		<p class="description">
			Updates URLs for JavaScript and CSS references to omit protocol (http/https)
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_jquery_migrate_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_jquery_migrate');

	?>
		<input type="radio" id="ezoic_remove_jquery_migrate_on" name="ezoic_speed_settings[ezoic_remove_jquery_migrate]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_jquery_migrate_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_jquery_migrate_off" name="ezoic_speed_settings[ezoic_remove_jquery_migrate]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_jquery_migrate_off">Disabled</label>
		<p class="description">
			Prevents jQuery Migrate script from being included on site pages
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_feed_links_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_feed_links');

	?>
		<input type="radio" id="ezoic_remove_feed_links_on" name="ezoic_speed_settings[ezoic_remove_feed_links]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_feed_links_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_feed_links_off" name="ezoic_speed_settings[ezoic_remove_feed_links]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_feed_links_off">Disabled</label>
		<p class="description">
			Removes RSS feed links from the page header
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_rsd_link_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_rsd_link');

	?>
		<input type="radio" id="ezoic_remove_rsd_link_on" name="ezoic_speed_settings[ezoic_remove_rsd_link]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_rsd_link_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_rsd_link_off" name="ezoic_speed_settings[ezoic_remove_rsd_link]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_rsd_link_off">Disabled</label>
		<p class="description">
			Removes RSD (<a href="https://en.wikipedia.org/wiki/Really_Simple_Discovery" target="_blank">Really Simple Discovery</a>) link from the page header
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_wlwmanifest_link_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_wlwmanifest_link');

	?>
		<input type="radio" id="ezoic_remove_wlwmanifest_link_on" name="ezoic_speed_settings[ezoic_remove_wlwmanifest_link]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_wlwmanifest_link_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_wlwmanifest_link_off" name="ezoic_speed_settings[ezoic_remove_wlwmanifest_link]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_wlwmanifest_link_off">Disabled</label>
		<p class="description">
			Removes <a href="https://en.wikipedia.org/wiki/Windows_Live_Writer" target="_blank">Windows Live Writer manifest</a> link from page header
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_adjacent_posts_links_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_adjacent_posts_links');

	?>
		<input type="radio" id="ezoic_remove_adjacent_posts_links_on" name="ezoic_speed_settings[ezoic_remove_adjacent_posts_links]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_adjacent_posts_links_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_adjacent_posts_links_off" name="ezoic_speed_settings[ezoic_remove_adjacent_posts_links]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_adjacent_posts_links_off">Disabled</label>
		<p class="description">
			Removes links to the previous and next posts from single post pages
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_wordpress_version_number_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_wordpress_version_number');

	?>
		<input type="radio" id="ezoic_remove_wordpress_version_number_on" name="ezoic_speed_settings[ezoic_remove_wordpress_version_number]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_wordpress_version_number_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_wordpress_version_number_off" name="ezoic_speed_settings[ezoic_remove_wordpress_version_number]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_wordpress_version_number_off">Disabled</label>
		<p class="description">
			Removes WordPress version data from the page header
		</p>
	<?php
	}

	// Ouput field
	public function ezoic_remove_shortlink_field()
	{
		$value = self::is_setting_enabled('ezoic_remove_shortlink');

	?>
		<input type="radio" id="ezoic_remove_shortlink_off" name="ezoic_speed_settings[ezoic_remove_shortlink]" value="1"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_shortlink_off">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_remove_shortlink_on" name="ezoic_speed_settings[ezoic_remove_shortlink]" value="0"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_remove_shortlink_on">Disabled</label>
		<p class="description">
			Removes WordPress shortlink data from page header
		</p>
<?php
	}

	// Establish default settings
	public function default_speed_settings()
	{
		$defaults = array(
			'ezoic_remove_feed_links'                 => '1',
			'ezoic_remove_rsd_link'                   => '1',
			'ezoic_remove_wlwmanifest_link'           => '1',
			'ezoic_remove_adjacent_posts_links'       => '1',
			'ezoic_remove_wordpress_version_number'   => '1',
			'ezoic_remove_shortlink'                  => '1',
		);

		return $defaults;
	}

	// Determine if a setting is enabled
	public static function is_setting_enabled($setting)
	{
		$settings = get_option('ezoic_speed_settings', null);

		// If no settings configured, return false
		if (! is_array($settings)) {
			return false;
		}

		// If the setting is in the array, return status
		if (array_key_exists($setting, $settings)) {
			return $settings[$setting] == '1';
		}

		return false;
	}

	public function disable_emojis()
	{
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('admin_print_styles', 'print_emoji_styles');
		remove_filter('the_content_feed', 'wp_staticize_emoji');
		remove_filter('comment_text_rss', 'wp_staticize_emoji');
		remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

		add_filter('tiny_mce_plugins', array($this, 'disable_emojis_tinymce'));
		add_filter('wp_resource_hints', array($this, 'remove_emojis_dns_prefetch'), 10, 2);
	}

	public function schemeless_urls($url)
	{
		return str_replace(['https:', 'http:'], '', $url);
	}

	public function remove_jquery_migrate($scripts)
	{
		if (empty($scripts->registered['jquery'])) {
			return;
		}
		$script = $scripts->registered['jquery'];
		if ($script->deps) {
			$script->deps = array_diff($script->deps, array('jquery-migrate'));
		}
	}

	public function remove_emojis_dns_prefetch($urls, $relation_type)
	{
		if ('dns-prefetch' !== $relation_type) {
			return $urls;
		}
		return array_filter($urls, function ($url) {
			return false === strpos($url, 'https://s.w.org/images/core/emoji/');
		});
	}

	public function disable_emojis_tinymce($plugins)
	{
		return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
	}

	public function ezoic_header_cleanup()
	{
		if (self::is_setting_enabled('ezoic_remove_feed_links')) {
			remove_action('wp_head', 'feed_links', 2);
			remove_action('wp_head', 'feed_links_extra', 3);
		}

		if (self::is_setting_enabled('ezoic_remove_rsd_link')) {
			remove_action('wp_head', 'rsd_link');
		}

		if (self::is_setting_enabled('ezoic_remove_wlwmanifest_link')) {
			remove_action('wp_head', 'wlwmanifest_link');
		}

		if (self::is_setting_enabled('ezoic_remove_adjacent_posts_links')) {
			remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
		}

		if (self::is_setting_enabled('ezoic_remove_wordpress_version_number')) {
			remove_action('wp_head', 'wp_generator');
		}

		if (self::is_setting_enabled('ezoic_remove_shortlink')) {
			remove_action('wp_head', 'wp_shortlink_wp_head');
		}
	}
}
