<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin/partials
 */
?>

<?php if (isset($type)) : ?>
	<?php if ($type == 'integration_error') : ?>
		<div class="error notice update-message notice-error">
			<p><?php
				_e('<strong>INTEGRATION ERROR:</strong>&nbsp; ' . $results['error'], 'ezoic');
				?></p>
		</div>
	<?php elseif ($type == 'not_integrated') : ?>
		<?php
		// Check if JavaScript integration is enabled
		$js_integration_enabled = get_option('ezoic_js_integration_enabled', false);
		if (!$js_integration_enabled) : ?>
			<div class="notice notice-info">
				<p><strong>No Integration Detected</strong></p>
				<p>We couldn't detect an active Ezoic integration. You can enable JavaScript integration in the Integration tab or explore other integration options.</p>
			</div>
		<?php else : ?>
			<div class="updated notice">
				<p><strong>JavaScript Integration Enabled</strong>&nbsp; Your JavaScript integration is active. Configure it in the Integration tab.</p>
			</div>
		<?php endif; ?>
	<?php elseif ($is_integrated) : ?>
		<div class="updated notice">
			<p><strong>SUCCESS!</strong>&nbsp; You are now fully integrated with <?php echo EZOIC__SITE_NAME; ?>!</p>
		</div>
	<?php endif; ?>
<?php endif ?>