<?php
/**
 * Theme Switch Notification
 */

add_thickbox();

?>
<style type="text/css">
    #TB_window {
        width: 596px !important;
        height: 440px !important;
        margin-left: -298px !important;
    }
</style>
<script>
    jQuery(document).ready(function ($) {
        $('body').on('click', '.theme-browser .theme .theme-actions .button.activate, .theme-overlay .theme-actions .button.activate', function (evt) {
                evt.preventDefault();
                tb_show("", "#TB_inline?width=560&height=390&inlineId=ez-theme-switch");
                $('#ez-theme-activate').attr("href", $(this).attr('href'));
                return false;
            }
        );

        $('body').on('click', '#ez-theme-close', function (evt) {
            evt.preventDefault();
            tb_remove();
            return false;
        });
    });
</script>

<div id="ez-theme-switch" class="" style="display:none;">
    <div id="ez-theme-modal">
        <p><img src="<?php echo plugins_url( '/admin/img', EZOIC__PLUGIN_FILE ); ?>/ezoic-logo.png" width="190"
                height="40" alt="Ezoic"/></p>
        <h3><span class="dashicons dashicons-warning"></span> Switching your WordPress Theme can negatively impact your
            ad revenue and disrupt your placeholder
            setup.</h3>
        <p>If you want to switch your WordPress theme, we advise reaching out to your Ezoic Representative or visit <a
                    href="https://support.ezoic.com/" target="_blank">support.ezoic.com</a> for more information before
            making this change.</p>
        <p>
            <a id="ez-theme-close" class="button button-large button-primary"
               href="#"><?php _e( 'Keep Current Theme' ) ?></a>
        </p>
        <p><br/>Or proceed with theme change:<br/><br/>
            <a id="ez-theme-activate" class="button button-large"
               href="#"><?php _e( 'Activate New Theme' ) ?></a></p>
    </div>
</div>
