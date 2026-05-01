<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       saleswonder.biz
 * @since      1.0.0
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/admin/partials
 */

$plugin_version = Cf7_License::get_license_version();
$is_wp2leads_installed = Cf7_License::is_wp2leads_installed();
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1>
        <?php _e('WOW Style Contact Form 7', 'cf7-styler'); ?>
    </h1>

    <div id="cf7cstmzr-sticky-message"></div>

    <?php
    if (false) {
        ?>
        <p style="margin-bottom: 5px;">
            <?php _e('If you are using page builders, like Thrive Architect, OptimizePress etc., please check our <a href="https://saleswonder.biz/knowledge-base/" target="_blank">Knowledge Base page</a> for fixing possible issues', 'cf7-styler'); ?>
        </p>
        <?php
    }
    ?>

    <?php
    if ('free' === $plugin_version && $is_wp2leads_installed) {
        ?>
        <p style="margin-bottom: 5px;">
            <?php _e('Do you like to get styler premium version for free? Then <a href="?page=wp2l-admin&tab=settings" target="_blank">Enter your WP2LEADS pro license</a> or get a license <a href="https://wp2leads-for-klick-tipp.com/web/go-pro-plus-get-all-done4u/" target="_blank">here</a>!', 'cf7-styler'); ?>
        </p>
        <?php
    }

    if ('free' === $plugin_version && function_exists('cf7_styler')) {
        if (!cf7_styler()->is_trial_utilized()) {
            ?>
            <p style="margin-bottom: 5px;">
                <?php _e('Start your 14-day free trial with all Professional functions <a href="admin.php?billing_cycle=annual&trial=true&page=cf7cstmzr_page-pricing" class="go-to-upgrade" data-confirm="Do you want to save changes before leaving page?" target="_blank">here</a>!', 'cf7-styler'); ?>
            </p>
            <?php
        }
    }
    ?>

    <?php include_once CF7CSTMZR_PLUGIN_PATH . 'admin/partials/cf7-customizer-admin-tabs.php'; ?>

    <?php
    if (!cf7cstmzr_is_plugin_activated( 'contact-form-7', 'wp-contact-form-7.php' )) {
        include_once CF7CSTMZR_PLUGIN_PATH . 'admin/partials/cf7-customizer-admin-tab-required-plugin.php';
    } else {
        include_once CF7CSTMZR_PLUGIN_PATH . 'admin/partials/cf7-customizer-admin-tab-'.$active_tab.'.php';
    }
    ?>
</div>