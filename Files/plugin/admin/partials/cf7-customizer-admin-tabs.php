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

$tabs = array(
    'form-customize' => __('Form Customizing', 'cf7-styler'),
    'settings' => __('Settings', 'cf7-styler'),
    'license' => __('License', 'cf7-styler'),
);

$active_tab = !empty($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'form-customize';

return;
?>
<h2 id="wtsr-nav-tab" class="nav-tab-wrapper">
    <?php
    foreach ($tabs as $tab_slug => $tab) {
        ?>
        <a href="?page=cf7cstmzr_page&tab=<?php echo $tab_slug ?>"
           class="nav-tab <?php echo $active_tab === $tab_slug ? 'nav-tab-active' : ''; ?>"
        ><?php echo $tab; ?></a>
        <?php
    }
    ?>
</h2>