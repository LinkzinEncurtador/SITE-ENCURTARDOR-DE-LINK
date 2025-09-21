<?php

namespace Ezoic_Namespace;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_Integration_CDN_Settings
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		if (Ezoic_Cdn::ezoic_cdn_show_post_ids()) {
			// Add Post IDs to admin table
			add_action('manage_posts_columns', array($this, 'ezoic_add_id_column'), 5);
			add_filter('manage_posts_custom_column', array($this, 'ezoic_add_id_column_content'), 5, 2);

			add_action('manage_pages_columns', array($this, 'ezoic_add_id_column'), 5);
			add_filter('manage_pages_custom_column', array($this, 'ezoic_add_id_column_content'), 5, 2);
		}
	}


	public function initialize_cdn_settings()
	{

		add_settings_section(
			'ezoic_cdn_settings_section',
			__('Ezoic CDN Settings', 'ezoic'),
			array($this, 'ezoic_cdn_settings_section_callback'),
			'ezoic_cdn'
		);

		add_settings_field(
			'ezoic_cdn_api_key',
			'Ezoic API Key',
			array($this, 'ezoic_cdn_api_key_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_enabled',
			'Automatic Recaching',
			array($this, 'ezoic_cdn_enabled_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_show_post_ids',
			'Show IDs on Page/Post Lists',
			array($this, 'ezoic_cdn_show_post_ids_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_always_clear_posts',
			'Always Clear Post/Page IDs',
			array($this, 'ezoic_cdn_always_clear_posts_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_always_clear_urls',
			'Always Clear URLs',
			array($this, 'ezoic_cdn_always_clear_urls_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_always_home',
			'Purge Home',
			array($this, 'ezoic_cdn_always_home_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_verbose_mode',
			'Verbose Mode',
			array($this, 'ezoic_cdn_verbose_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section',
			array('class' => 'ez_hidden')
		);
		//====================== FB Stuff ==========================
		add_settings_field(
			'fb_cache_clear_enabled',
			'Clear Facebook Share Cache',
			array($this, 'fb_clear_cache_enabled_field'),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		if (Ezoic_Cdn::fb_clear_cache_enabled()) {
			add_settings_field(
				'fb_app_id',
				'Facebook App ID',
				array($this, 'fb_app_id_field'),
				'ezoic_cdn',
				'ezoic_cdn_settings_section'
			);
			add_settings_field(
				'fb_app_secret',
				'Facebook App Secret',
				array($this, 'fb_app_secret_field'),
				'ezoic_cdn',
				'ezoic_cdn_settings_section'
			);

			add_settings_field(
				'fb_app_auth_token',
				'Facebook App Authentication Token',
				array($this, 'fb_app_auth_token_field'),
				'ezoic_cdn',
				'ezoic_cdn_settings_section'
				//				array('class' => 'ez_hidden')
			);
		}

		register_setting('ezoic_cdn', 'ezoic_cdn_api_key');
		register_setting('ezoic_cdn', 'ezoic_cdn_enabled', array('default' => true));
		register_setting('ezoic_cdn', 'ezoic_cdn_show_post_ids', array('default' => false));
		register_setting('ezoic_cdn', 'ezoic_cdn_always_clear_posts', array(
			'default'           => '',
			'sanitize_callback' => array(
				$this,
				'ezoic_cdn_sanitize_always_clear_posts'
			)
		));
		register_setting('ezoic_cdn', 'ezoic_cdn_always_clear_urls', array(
			'default' => '',
			'sanitize_callback' => array(
				$this,
				'ezoic_cdn_sanitize_always_clear_urls'
			)
		));

		register_setting('ezoic_cdn', 'ezoic_cdn_always_home', array('default' => true));
		register_setting('ezoic_cdn', 'ezoic_cdn_domain');
		register_setting('ezoic_cdn', 'ezoic_cdn_verbose_mode', array('default' => false));
		register_setting('ezoic_cdn', 'fb_clear_cache_enabled', array('default' => 'off'));
		register_setting('ezoic_cdn', 'fb_app_id', array(
			'default'           => null,
			'type'              => 'string',
			'sanitize_callback' => array($this, 'fb_validate_app_id')
		));
		register_setting('ezoic_cdn', 'fb_app_secret', array(
			'default'           => null,
			'type'              => 'string',
			'sanitize_callback' => array(
				$this,
				'fb_validate_app_secret'
			)
		));
		register_setting('ezoic_cdn', 'fb_app_auth_token', array(
			'default'           => null,
			'type'              => 'string',
			'sanitize_callback' => array(
				$this,
				'fb_validate_app_auth_token'
			)
		));
	}

	/**
	 * Empty Callback for WordPress Settings
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_cdn_settings_section_callback()
	{

		$ping_test = "";
		$api_key   = Ezoic_Cdn::ezoic_cdn_api_key();
		if (! empty($api_key)) {
			$ping_test = Ezoic_Cdn::ezoic_cdn_ping();
		}
		include_once(EZOIC__PLUGIN_DIR . 'admin/partials/ezoic-integration-admin-display-cdn.php');
	}

	/**
	 * WordPress Settings Field for defining the Ezoic API Key
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_cdn_api_key_field()
	{
?>
		<input type="text" name="ezoic_cdn_api_key" class="regular-text code"
			value="<?php echo (esc_attr(Ezoic_Cdn::ezoic_cdn_api_key())); ?>" />
		<p class="description">
			You can find your <a href="https://pubdash.ezoic.com/settings/apigateway" target="_blank">API key
				here</a>.<br /><em>*Required</em>
		</p>
	<?php
	}

	/**
	 * WordPress Settings Field for enabling/disabling auto-purge
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_cdn_enabled_field()
	{
		$value = Ezoic_Cdn::ezoic_cdn_is_enabled(true);

	?>
		<input type="radio" id="ezoic_cdn_enabled_on" name="ezoic_cdn_enabled" value="on"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_cdn_enabled_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_cdn_enabled_off" name="ezoic_cdn_enabled" value="off"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_cdn_enabled_off">Disabled</label>
		<p class="description">
			Turn on automatic clearing of Ezoic caches when a post or page is updated.<br /><em>*Recommend enabling</em>
		</p>
	<?php
	}

	function ezoic_cdn_show_post_ids_field()
	{
		$checked = Ezoic_Cdn::ezoic_cdn_show_post_ids(true);
	?>
		<input type="radio"
			name="ezoic_cdn_show_post_ids"
			id="ezoic_cdn_show_post_ids_on"
			value="on"
			<?php
			if ($checked) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_cdn_show_post_ids_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio"
			name="ezoic_cdn_show_post_ids"
			id="ezoic_cdn_show_post_ids_off"
			value="off"
			<?php
			if (! $checked) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_cdn_show_post_ids_off">Disabled</label>

		<p class="description">
			Display the ID for each Post/Page on the "Posts" and "Pages" admin views.
		</p>
	<?php
	}

	/**
	 * Wordpress Settings field for post/page IDs to always purge
	 *
	 * @return void
	 * @since 2.5.10
	 */
	function ezoic_cdn_always_clear_posts_field()
	{
	?>
		<input type="text"
			id="ezoic_cdn_always_clear_posts"
			placeholder="1,2,3,4..."
			name="ezoic_cdn_always_clear_posts"
			value="<?php echo (esc_attr(Ezoic_Cdn::ezoic_cdn_always_clear_post_ids())); ?>">

		<p class="description">
			Enter Post IDs for Posts/Pages to be automatically purged on any update (separated by commas, max 50)
		</p>
	<?php
	}

	/**
	 * Wordpress Settings field for URLs to always purge
	 *
	 * @return void
	 * @since 2.7.5
	 */
	function ezoic_cdn_always_clear_urls_field()
	{
	?>
		<textarea
			id="ezoic_cdn_always_clear_urls"
			placeholder="http://<?php echo (Ezoic_Cdn::ezoic_cdn_get_domain()); ?>/example-url-to-clear"
			name="ezoic_cdn_always_clear_urls"
			cols="80"><?php echo (esc_attr(Ezoic_Cdn::ezoic_cdn_always_clear_urls())); ?></textarea>
		<p class="description">
			Enter URLs to be automatically purged on any update. URLs must contain http/https protocol (separated by newline, max 50)
		</p>
	<?php
	}

	/**
	 * WordPress Settings Field for enabling/disabling verbose mode
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function ezoic_cdn_always_home_field()
	{
		$checked = Ezoic_Cdn::ezoic_cdn_always_purge_home(true);
	?>
		<input type="radio" id="ezoic_cdn_always_home_on" name="ezoic_cdn_always_home" value="on"
			<?php
			if ($checked) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_cdn_always_home_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_cdn_always_home_off" name="ezoic_cdn_always_home" value="off"
			<?php
			if (! $checked) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_cdn_always_home_off">Disabled</label>
		<p class="description">
			Will purge the home page whenever purging for any post (Automatic Recaching must be enabled).<br /><em>*Recommend
				enabling</em>
		</p>
		<p class="description" id="ez-advanced-collapse"><br />
			[ <a href="#">show advanced options</a> ]
		</p>
	<?php
	}

	/**
	 * WordPress Settings Field for enabling/disabling verbose mode
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function ezoic_cdn_verbose_field()
	{
		$checked = Ezoic_Cdn::ezoic_cdn_verbose_mode(true);
	?>
		<input type="radio" id="ezoic_cdn_verbose_on" name="ezoic_cdn_verbose_mode" value="on" <?php
																								if ($checked) {
																									echo ('checked="checked"');
																								}
																								?> />
		<label for="ezoic_cdn_verbose_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_cdn_verbose_off" name="ezoic_cdn_verbose_mode" value="off" <?php
																									if (! $checked) {
																										echo ('checked="checked"');
																									}
																									?> />
		<label for="ezoic_cdn_verbose_off">Disabled</label>
		<p class="description" id="tagline-description">
			Outputs debug messages whenever submitting purge,
			<span style="color: red;font-weight: bold;">will slow down editing, leave disabled unless you need it</span>.
		</p>
	<?php
	}

	public static function ezoic_cdn_sanitize_always_clear_posts($input)
	{
		$current_value = get_option('ezoic_cdn_always_clear_posts');

		if ($input == '0') {
			add_settings_error('ezoic_cdn_always_clear_posts', 'ezoic_cdn_always_clear_posts_error', '"0" is not a valid Post ID');

			return $current_value;
		}

		$ids_to_save = Ezoic_Cdn::ezoic_cdn_split_post_ids_str($input);

		foreach ($ids_to_save as $id) {
			if (empty($id) || (int) $id <= 0) {
				add_settings_error('ezoic_cdn_always_clear_posts', 'ezoic_cdn_always_clear_posts_error', '"Always Clear Post/Page IDs" must be a comma-separated list of post IDs greater than 0.');

				return $current_value;
			}
		}

		if (count($ids_to_save) > 50) {
			add_settings_error('ezoic_cdn_always_clear_posts', 'ezoic_cdn_always_clear_posts_error', 'Cannot set more than 50 posts/pages to always clear');

			return $current_value;
		}

		return implode(',', array_map('intval', $ids_to_save));
	}

	public static function ezoic_cdn_sanitize_always_clear_urls($input)
	{
		$current_value = get_option('ezoic_cdn_always_clear_urls', '');
		$urls = Ezoic_Cdn::ezoic_cdn_split_urls_str($input);
		$domain = Ezoic_Cdn::ezoic_cdn_get_domain();

		if (count($urls) > 50) {
			add_settings_error('ezoic_cdn_always_clear_urls', 'ezoic_cdn_always_clear_urls_error', 'Cannot set more than 50 URLs to always clear');

			return $current_value;
		}

		foreach ($urls as $url) {
			$url = trim($url);
			if (empty($url)) {
				add_settings_error('ezoic_cdn_always_clear_urls', 'ezoic_cdn_always_clear_urls_error', '"Always Clear URLs" must not have empty lines bewtween URLs.');

				return $current_value;
			}

			if (!filter_var($url, FILTER_VALIDATE_URL)) {
				add_settings_error('ezoic_cdn_always_clear_urls', 'ezoic_cdn_always_clear_urls_error', '"Always Clear URLs" must use valid URLs (including http or https).');

				return $current_value;
			}

			if (strpos($url, $domain) === false) {
				add_settings_error('ezoic_cdn_always_clear_urls', 'ezoic_cdn_always_clear_urls_error', '"Always Clear URLs" must use URLs within the site\'s domain.');

				return $current_value;
			}

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_NOBODY, true);
			$result = curl_exec($curl);

			if ($result !== false) {
				$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ($code == 404) {
					add_settings_error('ezoic_cdn_always_clear_urls', 'ezoic_cdn_always_clear_urls_error', "Not a valid URL: $url");

					return $current_value;
				}
			} else {
				add_settings_error('ezoic_cdn_always_clear_urls', 'ezoic_cdn_always_clear_urls_error', "Not a valid URL: $url");

				return $current_value;
			}
		}

		return implode("\n", $urls);
	}

	public function ezoic_add_id_column($columns)
	{
		$columns['ezoic_post_id'] = 'ID';

		return $columns;
	}

	public function ezoic_add_id_column_content($column, $id)
	{
		if ('ezoic_post_id' == $column) {
			echo $id;
		}
	}

	/**
	 * WordPress settings for saving and viewing the stored facebook cache clear status setting
	 *
	 * @since 2.6.26
	 */

	public function fb_clear_cache_enabled_field()
	{
		$checked = Ezoic_Cdn::fb_clear_cache_enabled();
	?>
		<input type="radio" id="fb_clear_cache_enabled_on" name="fb_clear_cache_enabled" value="on" <?php
																									if ($checked) {
																										echo ('checked="checked"');
																									}
																									?> />
		<label for="fb_clear_cache_enabled_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="fb_clear_cache_enabled_off" name="fb_clear_cache_enabled" value="off" <?php
																										if (! $checked) {
																											echo ('checked="checked"');
																										}
																										?> />
		<label for="fb_clear_cache_enabled_off">Disabled</label>
		<p class="description">
			Will enable automatic facebook share cache purging on content update<br /><em>*Recommend enabling</em>
		</p>
	<?php
	}


	/**
	 * WordPress settings for saving and viewing the stored facebook app id
	 *
	 * @since 2.6.26
	 */

	public function fb_app_id_field()
	{
	?>
		<input type="text" name="fb_app_id" value="<?php echo (esc_attr(Ezoic_Cdn::fb_get_app_id())); ?>" />
		<p class="description">
			Your Facebook App ID
		</p>
	<?php

	}

	/**
	 * WordPress settings for saving and viewing the stored facebook app secret
	 *
	 * @since 2.6.26
	 */

	public function fb_app_secret_field()
	{
	?>
		<input type="text" name="fb_app_secret" value="<?php echo (esc_attr(Ezoic_Cdn::fb_get_app_secret())); ?>" />
		<p class="description">
			Your Facebook App Secret
		</p>
	<?php
	}

	/**
	 * WordPress settings for saving and viewing the stored facebook app auth token
	 *
	 * @since 2.6.26
	 */

	public function fb_app_auth_token_field()
	{
	?>
		<input type="text" name="fb_app_auth_token"
			value="<?php echo (esc_attr(Ezoic_Cdn::fb_get_app_auth_token())); ?>" />
		<p class="description">
			Your Facebook App Auth Token
		</p>
<?php
	}


	public static function fb_validate_app_id($input)
	{
		$fb            = new FacebookShareCache();
		$current_value = get_option('fb_app_id');

		if (! $fb->validate_app_id($input) && Ezoic_Cdn::fb_clear_cache_enabled()) {
			add_settings_error('fb_app_id', 'fb_app_id_error', '"Facebook App ID" must be set and a valid Facebook app ID');

			return $current_value;
		}

		return $input;
	}

	public static function fb_validate_app_secret($input)
	{

		$fb            = new FacebookShareCache();
		$current_value = get_option('fb_app_secret');

		if (! $fb->validate_app_secret($input) && Ezoic_Cdn::fb_clear_cache_enabled()) {
			add_settings_error('fb_app_secret', 'fb_app_secret_error', '"Facebook App Secret" must be set and a valid Facebook app secret:' . $input);

			return $current_value;
		}

		return $input;
	}

	public static function fb_validate_app_auth_token($input)
	{
		$current_value = get_option('fb_app_auth_token');

		$appId     = get_option('fb_app_id');
		$appSecret = get_option('fb_app_secret');

		if ((isset($appId) &&  strlen($appId) > 0) && (isset($appSecret) && strlen($appSecret) > 0)) {

			$fb = new FacebookShareCache();

			$token = $fb->update_fb_auth_token();

			if (strlen($token)) {
				return $token;
			} else {
				return $current_value;
			}
		}
	}
}

?>