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
 * @var $is_welcome_done
 */

if (!empty($is_welcome_done)) {
    $file = 'tutorials';
} else {
    $file = 'welcome';
}

$locale = get_user_locale();
$locale_short = explode('_', $locale)[0];
$filename = plugin_dir_path( CF7CSTMZR_PLUGIN_FILE ) . 'admin/partials/tutorials/'.$file.'-'.$locale_short.'.php';

if (!file_exists($filename)) {
    $filename = plugin_dir_path( CF7CSTMZR_PLUGIN_FILE ) . 'admin/partials/tutorials/'.$file.'-en.php';
}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1><?php
        if (empty($is_welcome_done)) {
            _e('Welcome', 'cf7-styler');
        } else {
            _e('Tutorial', 'cf7-styler');
        }
    ?></h1>
    <div class="cf7cstmzr-tutorial-section">
        <?php
        ob_start();
        include $filename;

        echo apply_filters('the_content', ob_get_clean());

        if (empty($is_welcome_done)) {
            ?>
            <div>
                <button class="cf7cstmzr-close-welcome button button-primary"><?php _e('Start styling', 'cf7-styler'); ?></button>
            </div>
            <?php
        }
        ?>
    </div>
</div>