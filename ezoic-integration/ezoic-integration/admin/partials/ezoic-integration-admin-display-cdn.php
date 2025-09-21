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
 *
 * @var $ping_test
 * @var $api_key
 *
 */
?>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('#ez_integration #ez-advanced-collapse a').click(function () {
            if ($(".ez_hidden").is(":visible")) {
                $(this).text('show advanced options');
            } else {
                $(this).text('hide advanced options');
            }
            $(".ez_hidden").toggle("slide");
            return false;
        });
    });
</script>

<p>
    This feature uses the Ezoic CDN API to automatically purge content/pages from the Ezoic CDN whenever a post or page
    is updated.
</p>
<hr/>

<?php if ( ! empty( $ping_test ) && is_array( $ping_test ) && $ping_test[0] == false ) : ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            //$('#ez_integration table.form-table tr:not(:first-child)').hide();
            $("#ez_integration input[name='ezoic_cdn_api_key']").addClass("ez_error");
        });
    </script>
    <div class="error notice update-message notice-error">
        <p><?php
			_e( '<strong>CDN INTEGRATION NOT SUCCESSFUL</strong>', 'ezoic' );
			if ( ! empty( $ping_test[1] ) ) {
				_e( '<br/>' . $ping_test[1], 'ezoic' );
			}
			_e( '<br/>Please verify your \'<strong>Ezoic API Key</strong>\' is correct and active.', 'ezoic' );
			?></p>
    </div>
<?php elseif ( ! empty( $api_key ) && get_option( 'ezoic_cdn_enabled' ) !== 'on' ) : ?>
    <div class="warning notice notice-warning">
        <p><?php
			_e( '<strong>CDN PURGING DISABLED</strong>', 'ezoic' );
			_e( '<br/>To enable automatic Ezoic CDN purging, please enable \'<strong>Automatic Recaching</strong>\' option below.',
				'ezoic' );
			?></p>
    </div>
<?php endif; ?>
