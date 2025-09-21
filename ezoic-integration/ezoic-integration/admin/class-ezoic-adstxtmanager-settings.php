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
class Ezoic_AdsTxtManager_Settings
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
	}


	/**
	 * Register and add settings
	 */
	public function initialize_adstxtmanager_settings()
	{
		add_settings_section(
			'ezoic_adstxtmanager_settings_section',
			__('Ezoic Ads.txt Manager', 'ezoic'),
			array($this, 'ezoic_adstxtmanager_settings_section_callback'),
			'ezoic_adstxtmanager'
		);

		add_settings_field(
			'ezoic_adstxtmanager_auto_detect',
			'Automatic Detection',
			array($this, 'ezoic_adstxtmanager_auto_detect_field'),
			'ezoic_adstxtmanager',
			'ezoic_adstxtmanager_settings_section'
		);

		add_settings_field(
			'ezoic_adstxtmanager_id',
			'Ads.txt Manager ID',
			array($this, 'ezoic_adstxtmanager_id_field'),
			'ezoic_adstxtmanager',
			'ezoic_adstxtmanager_settings_section'
		);


		register_setting(
			'ezoic_adstxtmanager',
			'ezoic_adstxtmanager_id',
			array('default' => 0, 'type' => 'integer', 'sanitize_callback' => array($this, 'sanitize_adstxtmanager_id'))
		);

		register_setting(
			'ezoic_adstxtmanager',
			'ezoic_adstxtmanager_auto_detect',
			array('default' => true, 'sanitize_callback' => array($this, 'sanitize_auto_detect_setting'))
		);

		if (get_option('ezoic_adstxtmanager_status') === false) {
			update_option('ezoic_adstxtmanager_status', array('status' => false, 'message' => ''));
		}

		register_setting(
			'ezoic_adstxtmanager',
			'ezoic_adstxtmanager_status',
			array('type' => 'array', 'default' => array('status' => false, 'message' => ''), 'sanitize_callback' => array($this, 'sanitize_status_array'))
		);
	}

	/**
	 * Empty Callback for WordPress Settings
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_adstxtmanager_settings_section_callback()
	{
		// Run auto-detection if enabled
		if (Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect()) {
			Ezoic_AdsTxtManager::ezoic_detect_adstxtmanager_id();
		}
?>
		<?php if (Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true) == 0 && !Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect()) : ?>
			<div class="notice notice-error adstxtmanager_activate">
				<p class="adstxtmanager_description">
					<?php _e('Ezoic\'s Ads.txt redirection is not set up. Please enable Automatic Detection or enter your Ads.txt Manager ID.', 'ezoic'); ?>
				</p>
			</div>
		<?php endif; ?>
		<p>
			<?php _e(
				'In order for Ezoic to manage your ads.txt file, you are required to set up a redirection from your websites\' ads.txt file to <a href="' . EZOIC_ADSTXT_MANAGER__SITE . '" target="_blank"><strong>Ads.txt Manager</strong></a> (an Ezoic product).',
				'ezoic'
			); ?>
		</p>
		<p><?php _e('Enable Automatic Detection, or enter your Ads.txt Manager ID number below, and the ads.txt redirection will be automatically setup for you.'); ?></p>
		<hr />
	<?php
	}

	function ezoic_adstxtmanager_id_field()
	{
		$adstxtmanager_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);
		$auto_detect = Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect();
	?>
		<?php if ($auto_detect): ?>
			<!-- Hidden field to preserve ID value when auto-detect is enabled -->
			<input type="hidden" name="ezoic_adstxtmanager_id" value="<?php echo $adstxtmanager_id; ?>" />
			<p class="description"><strong><?php echo $adstxtmanager_id; ?></strong></p>
		<?php else: ?>
			<input type="text" name="ezoic_adstxtmanager_id" class="regular-text code"
				value="<?php echo $adstxtmanager_id; ?>" />
			<p class="description">
				Manually enter your Ads.txt Manager ID.
			</p>
		<?php endif; ?>
	<?php
	}

	function ezoic_adstxtmanager_auto_detect_field()
	{
		$value = Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect();
	?>
		<input type="radio" id="ezoic_adstxtmanager_auto_detect_on" name="ezoic_adstxtmanager_auto_detect" value="on"
			<?php
			if ($value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_adstxtmanager_auto_detect_on">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_adstxtmanager_auto_detect_off" name="ezoic_adstxtmanager_auto_detect" value="off"
			<?php
			if (! $value) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_adstxtmanager_auto_detect_off">Disabled</label>
		<p class="description">
			Automatically sets your Ads.txt Manager ID that is linked to Ezoic. <br /><em>*Recommend enabling</em>
		</p>
<?php
	}

	public function sanitize_adstxtmanager_id($input)
	{
		$new_input = 0;
		if (isset($input)) {
			$new_input = absint($input);
		}

		// Refresh ads.txt status when settings are saved
		if ($new_input > 0) {
			$redirect_result = Ezoic_AdsTxtManager::ezoic_verify_adstxt_redirect();

			// If redirect is failing, attempt to run setup to fix it - but only if it's not an invalid ATM ID error
			if (!$redirect_result['status'] && (!isset($redirect_result['error']) || $redirect_result['error'] !== 'invalid_atm_id')) {
				$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
				$adsTxtSolution = $solutionFactory->GetBestSolution();
				$adsTxtSolution->SetupSolution();

				// Get the detailed setup result that may contain specific error messages
				$setup_result = get_option('ezoic_adstxtmanager_status');

				// Always recheck redirect status after setup attempt
				$recheck_result = Ezoic_AdsTxtManager::ezoic_verify_adstxt_redirect();

				// If recheck shows 'redirect_failed' and setup had detailed error messages, preserve the setup errors
				if (
					!$recheck_result['status'] && isset($recheck_result['error']) && $recheck_result['error'] === 'redirect_failed'
					&& isset($setup_result['status']) && !$setup_result['status'] && !empty($setup_result['message'])
				) {
					// Keep the detailed setup error message instead of the generic redirect failure message
					$redirect_result = $setup_result;
				} else {
					// Use the recheck result (success, invalid_atm_id, or no detailed setup errors available)
					$redirect_result = $recheck_result;
				}
			}

			update_option('ezoic_adstxtmanager_status', $redirect_result);
		}

		return $new_input;
	}

	public function sanitize_auto_detect_setting($input)
	{
		// Get current ATM ID to check if we should refresh status
		$atm_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);

		// Refresh status on any auto-detect setting change with valid ATM ID
		if ($atm_id > 0) {
			$redirect_result = Ezoic_AdsTxtManager::ezoic_verify_adstxt_redirect();
			// Update cached status with full result array
			update_option('ezoic_adstxtmanager_status', $redirect_result);
		}

		return $input;
	}

	public function sanitize_status_array($input)
	{
		// Ensure the status is always an array structure
		if (!is_array($input)) {
			return array('status' => false, 'message' => '');
		}
		return $input;
	}
}

?>