<?php
/**
    cf7cstmzr_form_text_color
    cf7cstmzr_form_bg_color
    cf7cstmzr_form_bg_img
    cf7cstmzr_form_text_size
    cf7cstmzr_form_padding
    cf7cstmzr_form_margin
    cf7cstmzr_form_border_width
    cf7cstmzr_form_border_radius
    cf7cstmzr_form_border_color

    cf7cstmzr_input_text_color
    cf7cstmzr_input_bg_color
    cf7cstmzr_input_padding
    cf7cstmzr_input_margin
    cf7cstmzr_input_text_size
    cf7cstmzr_input_border_width
    cf7cstmzr_input_border_radius
    cf7cstmzr_input_border_color

    cf7cstmzr_button_text_color
    cf7cstmzr_button_bg_color
    cf7cstmzr_button_text_color-hover
    cf7cstmzr_button_bg_color-hover
    cf7cstmzr_button_text_size
    cf7cstmzr_button_border_width
    cf7cstmzr_button_border_radius
    cf7cstmzr_button_border_color
    cf7cstmzr_button_border_color-hover

$input_text_color
$input_bg_color
$input_padding
$input_margin
$input_text_size
$input_border_width
$input_border_radius
$input_border_color

$button_text_color
$button_bg_color
$button_text_color_hover
$button_bg_color_hover
$button_text_size
$button_border_width
$button_border_radius
$button_border_color
$button_border_color_hover
 */
delete_option('cf7cstmzr-preview-mode');
$plugin_version = Cf7_License::get_license_version();
$is_styled = get_option('cf7cstmzr-preview-styled', true);
$preview_mode = get_option('cf7cstmzr-preview-mode', 'split-mode');
$split_mode = get_option('cf7cstmzr-split-mode', false);
$is_body_tag = get_option('cf7cstmzr-load-body-tag', false);
$default_style_scheme_settings = Cf7_Style_Scheme::get_default_style_scheme();
$cache_form = get_option('cf7cstmzr_cache_form', '');
$is_fw = !empty($_GET['fw']) ? sanitize_text_field($_GET['fw']) : '';

$style_scheme_slug = 'default';

if ('free' !== $plugin_version) {
    $style_scheme_slug = !empty($_GET['style_scheme']) ? sanitize_text_field($_GET['style_scheme']) : 'default';
}

$style_schemes = get_option('cf7cstmzr_style_schemes', array());

if (empty($style_schemes)) {
    delete_option('cf7cstmzr_enabled_globally');
    $style_scheme_slug = 'default';
} else {
    if (empty($style_schemes[$style_scheme_slug])) {
        $style_scheme_slug = 'default';
    }
}


$enabled_globally = '';
$enabled_globally_title = '';
$enabled_globally_option = get_option('cf7cstmzr_enabled_globally', false);

if (!empty($enabled_globally_option) && 'free' !== $plugin_version) {
    $enabled_globally = 'default';

    if ('free' !== $plugin_version) {
        $enabled_globally = $enabled_globally_option;
    }
}

if (!empty($enabled_globally)) {
    $enabled_globally_title = $style_schemes[$enabled_globally]['title'];
}

$style_scheme = Cf7_Style_Scheme::normalize_style_sheme($style_schemes, $style_scheme_slug);

$args = array (
    'numberposts' => -1,
    'orderby'     => 'title',
    'order'       => 'ASC',
    'post_type'   => 'wpcf7_contact_form',
    'post_status'   => 'publish',
    'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
);

$cf7_forms = get_posts($args);

$cf7_scheme_args = array (
    'numberposts' => -1,
    'orderby'     => 'title',
    'order'       => 'ASC',
    'post_type'   => 'wpcf7_contact_form',
    'post_status'   => 'publish',
    'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
    'meta_query' => array(
        'relation' => 'EXISTS',
        array(
            'key' => 'cf7cstmzr_style_scheme',
        )
    )
);

$forms_group_by_style_scheme = Cf7_Style_Scheme::get_forms_group_by_style_scheme();
$current_url_state = get_site_url() . '/wp-admin/admin.php?page=cf7cstmzr_page';

if (!empty($_GET['tab'])) {
    $current_url_state .= '&tab=' . sanitize_text_field($_GET['tab']);
}

if (!empty($_GET['style_scheme'])) {
    $current_url_state .= '&style_scheme=' . sanitize_text_field($_GET['style_scheme']);
}

$permalink_structure = get_option('permalink_structure');
?>
<div id="cf7cstmzr-main-container"<?php echo !empty($is_fw) ? ' class="fw"' : ''; ?>>
    <input type="hidden" id="cf7cstmzr-current-url" value="<?php echo $current_url_state; ?>">
    <div class="cf7cstmzr-row">
        <div class="cf7cstmzr-col">
            <div class="cf7cstmzr-col-inner">
                <!-- Style Scheme Settings - Start -->
                <div class="cf7cstmzr-settings-menu">
                    <form id="cf7cstmzr-settings-form">
                        <div id="cf7cstmzr-settings-container-header">
                            <div id="cf7cstmzr-settings-container-header-title" style="padding: 10px;margin:0 0 5px 0;background-color:#fff;border-top:5px solid #ccc; border-bottom: 1px solid #ccc;">
                                <h3 style="margin: 0;"><?php _e('Style schemes settings', 'cf7-styler'); ?></h3>

                                <span id="cf7cstmzr_to_fw" class="dashicons dashicons-editor-expand" title="<?php _e('Full Screen', 'cf7-styler') ?>"<?php echo !empty($is_fw) ? ' style="display:none;"' : ''; ?>></span>
                                <span id="cf7cstmzr_exit_fw" class="dashicons dashicons-editor-contract" title="<?php _e('Exit Full Screen', 'cf7-styler') ?>" <?php echo empty($is_fw) ? ' style="display:none;"' : ''; ?>></span>
                            </div>
                            <!-- Style Scheme Settings Control - Start -->
                            <div class="cf7cstmzr-settings-control">
                                <div class="cf7cstmzr-settings-control-inner">
                                    <?php
                                    if (!empty($style_schemes)) {
                                        ?>
                                        <div id="cf7cstmzr-settings-select" class="cf7cstmzr-settings-control-item">
                                            <?php
                                            ob_start();
                                            ?>
                                            <h4 id="cf7cstmzr_selected_style_scheme" class="cf7cstmzr-description-togler-holder" style="margin-top:0;" data-slug="<?php echo $style_scheme_slug; ?>">
                                                <?php echo $style_schemes[$style_scheme_slug]['title']; ?>
                                                <?php
                                                if ('free' !== $plugin_version) {
                                                    ?>
                                                    <span class="dashicons dashicons-editor-help cf7cstmzr-description-togler" data-target="cf7cstmzr-select-scheme-description"></span>
                                                    <?php
                                                }
                                                ?>
                                            </h4>
                                            <?php
                                            $settings_select_content = ob_get_clean();

                                            if ( 'free' !== $plugin_version ) {
                                                ob_start();

                                                if (1 < count($style_schemes)) {
                                                    ?>
                                                    <h4 class="cf7cstmzr-description-togler-holder" style="margin-top:0;">
                                                        <?php _e('Style schemes list', 'cf7-styler'); ?>
                                                        <span class="dashicons dashicons-editor-help cf7cstmzr-description-togler" data-target="cf7cstmzr-select-scheme-description"></span>
                                                    </h4>

                                                    <select id="cf7cstmzr_select_style_scheme" data-confirm="<?php _e('Do you want to save changes before leaving page?', 'cf7-styler'); ?>" class="cf7cstmzr-form-control" style="margin-bottom:10px;">
                                                        <?php
                                                        foreach ($style_schemes as $style_scheme_item_slug => $style_scheme_item) {
                                                            $style_forms_array = array();

                                                            if (!empty($forms_group_by_style_scheme[$style_scheme_item_slug])) {
                                                                $style_forms_array = $forms_group_by_style_scheme[$style_scheme_item_slug];
                                                            }

                                                            $style_forms_array_json = json_encode( $style_forms_array )
                                                            ?>
                                                            <option value="<?php echo $style_scheme_item_slug; ?>"<?php echo $style_scheme_item_slug === $style_scheme_slug ? ' selected' : ''; ?> data-forms='<?php echo $style_forms_array_json; ?>'>
                                                                <?php echo $style_scheme_item['title']; ?>
                                                            </option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php
                                                    if ('default' !== $style_scheme_slug) {
                                                        ?>
                                                        <input id="cf7cstmzr-current-slug" type="hidden" value="<?php echo $style_scheme_slug ?>">
                                                        <input id="cf7cstmzr-current-title" type="hidden" value="<?php echo $style_schemes[$style_scheme_slug]['title']; ?>">
                                                        <?php
                                                    }

                                                    $settings_select_content = ob_get_clean();
                                                }
                                            }

                                            echo $settings_select_content;
                                            ?>
                                        </div>
                                        <?php

                                        if (!empty($cf7_forms)) {
                                            ?>
                                            <div>
                                                <?php
                                                if ('free' !== $plugin_version) {
                                                    if ($enabled_globally !== $style_scheme_slug) {
                                                        ?>
                                                        <div id="cf7cstmzr-select-scheme-description" style="display: none;">
                                                            <p style="margin-top:0;margin-bottom:10px;">
                                                                <small>
                                                                    <?php
                                                                    if (!empty($enabled_globally)) { // Check if any Scheme enabled
                                                                        echo sprintf( __( '<strong>%s</strong> used for all forms globally, click "Use for all forms" if you want to use current Scheme.', 'cf7-styler' ), $enabled_globally_title );
                                                                    } else { // No scheme enabled
                                                                        _e('You do not use any of Style Schemes for Contact Form 7. Click "Use for all forms" to use current Scheme globally.', 'cf7-styler');
                                                                    }
                                                                    ?>
                                                                </small>
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <button id="cf7cstmzr-enable-globally" class="button button-success button-small" type="button" data-scheme="<?php echo $style_scheme_slug ?>">
                                                                <?php _e('Activate style for all forms*', 'cf7-styler') ?>
                                                            </button>
                                                        </div>
                                                        <?php
                                                    } else {
                                                        ?>
                                                        <div id="cf7cstmzr-select-scheme-description" style="display: none;">
                                                            <p style="margin-top:0;margin-bottom:10px;">
                                                                <small>
                                                                    <?php _e('This Style Scheme enabled globally for all forms. If you want to use your theme\'s style for Contact Form 7 click "Disable" button.', 'cf7-styler') ?>
                                                                </small>
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <button id="cf7cstmzr-disable-globally" class="button button-primary button-small" type="button">
                                                                <?php _e('Disable style for all forms', 'cf7-styler') ?>
                                                            </button>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                    <div class="cf7cstmzr-description-togler-holder" style="padding-top: 10px;">
                                                        <input type="checkbox" value="1" id="cf7cstmzr-load-body-tag"<?php echo !empty($is_body_tag) ? ' checked' : '';?>> <?php _e('Load styles in <code>&lt;body&gt;</code> tag', 'cf7-styler') ?>
                                                        <span class="dashicons dashicons-editor-help cf7cstmzr-description-togler" data-target="cf7cstmzr-body-tag-description"></span>
                                                    </div>
                                                    <div id="cf7cstmzr-body-tag-description" style="display: none;">
                                                        <div style="margin-top:10px;margin-bottom:10px;">
                                                                <small style="display: block;">
                                                                    <?php _e('Some themes or page builders (fe. Thrive Architect, OptimizePress etc.) could remove inline styles inside <code>&lt;head&gt;</code> tag. Loading style scheme within <code>&lt;body&gt;</code> tag will fix this issue and show your forms styled.', 'cf7-styler') ?>
                                                                </small>

                                                                <small style="display: block;margin-top:10px;">
                                                                    <?php _e('If you need to load style scheme inside <code>&lt;body&gt;</code> tag only on some pages, you can do it on frontend using "CF7 Styler" button. This function is only available for single post types (pages, posts, products etc.) and in premium version. You can not do it on archives pages (blog, products list etc) in this case you need to use global settings.', 'cf7-styler') ?>
                                                                </small>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <?php

                                            if ('free' === $plugin_version) {
                                                ?>
                                                <hr style="margin-top:10px;margin-bottom:10px;">
                                                <div id="upgrade-to-pro-list">
                                                    <div>
                                                        &#8226; <?php _e('Style all forms in one click globally', 'cf7-styler'); ?>
                                                    </div>
                                                    <div style="margin-top: 3px;">
                                                        &#8226; <?php _e('Create multiple style schemes', 'cf7-styler') ?>
                                                    </div>
                                                    <div style="margin-top: 3px;">
                                                        &#8226; <?php _e('Style each form with any style scheme individually', 'cf7-styler'); ?>
                                                    </div>
                                                    <div style="margin-top: 3px;">
                                                        &#8226; <?php _e('Load styles in <code>&lt;body&gt;</code> tag', 'cf7-styler') ?> <span class="dashicons dashicons-editor-help cf7cstmzr-description-togler" data-target="cf7cstmzr-body-tag-description"></span>
                                                    </div>
                                                    <div class="button-holder">
                                                        <a class="button button-small button-success go-to-upgrade" data-confirm="<?php _e('Do you want to save changes before leaving page?', 'cf7-styler'); ?>" href="admin.php?page=cf7cstmzr_page-pricing" target="_blank"><?php _e('Upgrade to Pro', 'cf7-styler') ?></a>
                                                        <a class="button button-small button-success go-to-upgrade" data-confirm="<?php _e('Do you want to save changes before leaving page?', 'cf7-styler'); ?>" href="admin.php?trial=true&page=cf7cstmzr_page-pricing" target="_blank"><?php _e('14-days Trial', 'cf7-styler') ?></a>
                                                    </div>
                                                </div>

                                                <div id="cf7cstmzr-body-tag-description" style="display: none;">
                                                    <div style="margin-top:10px;margin-bottom:10px;">
                                                        <small style="display: block;">
                                                            <?php _e('Some themes or page builders (fe. Thrive Architect, OptimizePress etc.) could remove inline styles inside <code>&lt;head&gt;</code> tag. Loading style scheme within <code>&lt;body&gt;</code> tag will fix this issue and show your forms styled.', 'cf7-styler') ?>
                                                        </small>

                                                        <small style="display: block;margin-top:10px;">
                                                            <?php _e('If you need to load style scheme inside <code>&lt;body&gt;</code> tag only on some pages, you can do it on frontend using "CF7 Styler" button. This function is only available for single post types (pages, posts, products etc.). You can not do it on archives pages (blog, products list etc) in this case you need to use global settings.', 'cf7-styler') ?>
                                                        </small>

                                                        <small style="display: block;margin-top:10px;">
                                                            <?php _e('As an alternative you can disable clean out the styles like on Thrive architect:', 'cf7-styler') ?>
                                                            <br>
                                                            <?php _e('Step 1. Click "Edit with Thrive architect" link in admin page list', 'cf7-styler') ?>
                                                            <br>
                                                            <?php _e('Step 2. Click "Settings" icon on right vertical menu', 'cf7-styler') ?>
                                                            <br>
                                                            <?php _e('Step 3. "Advanced settings" => "CSS in the &lt;head&gt; section"', 'cf7-styler') ?>
                                                            <br>
                                                            <?php _e('Step 4. Make sure that "Do not strip CSS from &lt;head&gt;" is checked', 'cf7-styler') ?>
                                                            <br>
                                                            <?php _e('Step 5. Click "SAVE WORK" button', 'cf7-styler') ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                    } else {
                                        ?>
                                        <h4 style="margin-top: 0;margin-bottom: 10px;"><?php _e('Start with creating Default Style Scheme', 'cf7-styler') ?></h4>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <!-- Style Scheme Settings Control - End -->

                            <!-- Style Scheme Settings Control - Start -->
                            <div class="cf7cstmzr-settings-control" style="margin-bottom:5px;">
                                <div class="cf7cstmzr-settings-control-inner">
                                    <div class="cf7cstmzr-settings-control-item">
                                        <?php
                                        if (false) {
                                            ?>
                                            <button id="cf7cstmzr-settings-save" class="cf7cstmzr-settings-save button button-icon" type="button" disabled="disabled" title="<?php _e("I'm happy with this! Save", 'cf7-styler') ?>">
                                                <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                            </button>
                                            <?php
                                        }

                                        if (true) {
                                            ?>
                                            <button id="cf7cstmzr-settings-save" class="cf7cstmzr-settings-save button" type="button" disabled="disabled" title="<?php _e("I'm happy with this! Save", 'cf7-styler') ?>">
                                                <?php _e('Save', 'cf7-styler') ?>
                                            </button>
                                            <?php
                                        }
                                        ?>

                                        <?php
                                        if ('free' !== $plugin_version) {
                                            ?>
                                            <button id="cf7cstmzr-settings-save-as" class="cf7cstmzr-settings-save-as button button-primary button-icon" type="button"<?php echo empty($style_schemes) ? ' style="display:none;"' : ''; ?> title="<?php _e("Create new style scheme!", 'cf7-styler') ?>">
                                                <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                                            </button>
                                            <?php
                                        }

                                        if (true) {
                                            ?>
                                            <button id="cf7cstmzr-settings-reset" class="cf7cstmzr-settings-reset button button-primary button-icon" type="button" title="<?php _e('Clean Styles', 'cf7-styler') ?>">
                                                <span class="dashicons dashicons-editor-removeformatting" style="vertical-align: middle;"></span>
                                            </button>

                                            <button id="cf7cstmzr-settings-default" class="cf7cstmzr-settings-default button button-primary" type="button" data-default-settings='<?php echo json_encode($default_style_scheme_settings['scheme']) ?>'>
                                                <?php _e('Reset to default', 'cf7-styler') ?>
                                            </button>
                                            <?php
                                        }

                                        if ('free' !== $plugin_version && !empty($style_schemes) && 'default' !== $style_scheme_slug) {
                                            ?>
                                            <button id="cf7cstmzr-settings-delete" class="cf7cstmzr-settings-delete button button-danger button-icon" type="button">
                                                <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                            </button>
                                            <?php
                                        }
                                        ?>
                                    </div>

                                    <?php
                                    if ('free' !== $plugin_version) {
                                        ?>
                                        <div id="cf7cstmzr-settings-create-new" class="cf7cstmzr-settings-control-item" style="display: none">
                                            <input id="cf7cstmzr_settings_title_new" class="cf7cstmzr-form-control" type="text" value="" style="margin-bottom:10px" placeholder="<?php _e('Input style scheme title', 'cf7-styler') ?>">
                                            <p style="margin: 5px 0">
                                                <small>
                                                    <?php _e('You can create new style scheme with current scheme settings. If you want to create blank style schem unchek checkbox below.', 'cf7-styler') ?>
                                                </small>
                                            </p>

                                            <div style="margin-bottom: 10px;">
                                                <input id="cf7cstmzr_settings_copy_new" type="checkbox" value="1" checked><?php _e('Copy current style scheme settings', 'cf7-styler') ?>
                                            </div>

                                            <button id="cf7cstmzr-settings-create" class="cf7cstmzr-settings-create button button-primary" type="button"><?php _e('Create', 'cf7-styler') ?></button>
                                            <button id="cf7cstmzr-settings-save-as-cancel" class="cf7cstmzr-settings-save-as-cancel button" type="button"><?php _e('Cancel', 'cf7-styler') ?></button>
                                        </div>
                                        <?php
                                    }
                                    ?>

                                </div>

                                <?php
                                if (!empty($permalink_structure)) {
                                    ?>
                                    <input id="cf7cstmzr-url" type="hidden" value="<?php echo get_site_url() . '/cf7cstmzr-form-customizer/' ?>">
                                    <?php

                                } else {
                                    ?>
                                    <input id="cf7cstmzr-url" type="hidden" value="<?php echo get_site_url() . '/?cf7cstmzr_page=1&form_id=' ?>">
                                    <?php
                                }
                                ?>
                                <input id="cf7cstmzr-admin-url" type="hidden" value="<?php echo get_site_url() . '/wp-admin/admin.php?page=cf7cstmzr_page&tab=form-customize' ?>">
                            </div>
                            <!-- Style Scheme Settings Control - End -->
                        </div>

                        <div id="cf7cstmzr-settings-container-body">

                            <?php
                            $web_safe_fonts = Cf7_Style_Scheme::get_web_safe_fonts();
                            ?>

                            <!-- Form Text - Start -->
                            <?php Cf7_Template::settings_item_start(__('Form Text', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_form_text_color"><?php _e('Text Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_text_color" name="cf7cstmzr_form_text_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['form_text_color']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_form_text_size"><?php _e('Font Size', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_text_size" name="cf7cstmzr_form_text_size" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="1" type="number" value="<?php echo $style_scheme['form_text_size']; ?>">
                                        </div>
                                    </div>


                                    <label for="cf7cstmzr_form_text_font-family"><?php _e('Font Family', 'cf7-styler') ?></label>
                                    <select name="cf7cstmzr_form_text_font-family" id="cf7cstmzr_form_text_font-family" class="cf7cstmzr-form-control cf7cstmzr-dropdown-field" style="max-width:200px;">
                                        <option value=""><?php _e('- select -', 'cf7-styler') ?></option>
                                        <?php
                                        foreach ($web_safe_fonts as $font_slug => $font_family) {
                                            ?>
                                            <option value="<?php echo $font_slug; ?>"<?php echo $style_scheme['form_text_font_family'] === $font_slug ? ' selected' : ''; ?>><?php echo $font_family[0]; ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <label>
                                        <?php _e('Links Settings', 'cf7-styler') ?>
                                    </label>

                                    <div style="display: inline-block">
                                        <label for="cf7cstmzr_form_text_link-color"><?php _e('Links Color', 'cf7-styler') ?></label>
                                        <input id="cf7cstmzr_form_text_link-color" name="cf7cstmzr_form_text_link-color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['form_text_link_color']; ?>">
                                    </div>

                                    <div style="display: inline-block">
                                        <label for="cf7cstmzr_form_text_link-hover-color"><?php _e('Links Hover Color', 'cf7-styler') ?></label>
                                        <input id="cf7cstmzr_form_text_link-hover-color" name="cf7cstmzr_form_text_link-hover-color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['form_text_link_hover_color']; ?>">
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <label>
                                        <?php _e('Labels Settings', 'cf7-styler') ?>
                                    </label>

                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_form_text_label-color"><?php _e('Labels Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_text_label-color" name="cf7cstmzr_form_text_label-color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['form_text_label_color']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_form_text_label-size"><?php _e('Labels Font Size', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_text_label-size" name="cf7cstmzr_form_text_label-size" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="1" type="number" value="<?php echo $style_scheme['form_text_label_size']; ?>">
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top;margin-right:10px;">
                                            <label for="cf7cstmzr_form_text_label-weight"><?php _e('Font Weight', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_text_label-weight" name="cf7cstmzr_form_text_label-weight" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="100" max="900" step="100" type="number" value="<?php echo $style_scheme['form_text_label_weight']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_form_text_label-style"><?php _e('Font Style', 'cf7-styler') ?></label>

                                            <select name="cf7cstmzr_form_text_label-style" id="cf7cstmzr_form_text_label-style" class="cf7cstmzr-form-control cf7cstmzr-dropdown-field" style="max-width:200px;">
                                                <option value=""><?php _e('- select -', 'cf7-styler') ?></option>
                                                <option value="normal"<?php echo $style_scheme['form_text_label_style'] === 'normal' ? ' selected' : ''; ?>><?php _e('Normal', 'cf7-styler') ?></option>
                                                <option value="italic"<?php echo $style_scheme['form_text_label_style'] === 'italic' ? ' selected' : ''; ?>><?php _e('Italic', 'cf7-styler') ?></option>
                                                <option value="oblique"<?php echo $style_scheme['form_text_label_style'] === 'normal' ? ' selected' : ''; ?>><?php _e('Oblique', 'cf7-styler') ?></option>
                                                <option value="inherit"<?php echo $style_scheme['form_text_label_style'] === 'normal' ? ' selected' : ''; ?>><?php _e('Inherit', 'cf7-styler') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Form Text - End -->

                            <!-- Form BG Image & Colors - Start -->
                            <?php Cf7_Template::settings_item_start(__('Form BG Image & Colors', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <label for="cf7cstmzr_form_bg_color"><?php _e('Background Color', 'cf7-styler') ?></label>
                                    <input id="cf7cstmzr_form_bg_color" name="cf7cstmzr_form_bg_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['form_bg_color']; ?>">
                                </div>

                                <!-- Form BG Image - Start -->
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $no_save_class = '';
                                    if ('free' === $plugin_version) {
                                        $style_scheme['form_bg_img'] = '';
                                        $style_scheme['form_bg_img_position'] = '';
                                        $style_scheme['form_bg_img_opacity'] = '';
                                        $style_scheme['form_bg_img_size'] = '';
                                        ?>
                                        <p style="margin-top:0;margin-bottom:5px;">
                                            <?php _e('Image background settings available in live mode only for Professional version. You can test it in preview mode "current style", but it will not be saved.', 'cf7-styler') ?>
                                        </p>

                                        <p style="margin-top:0;">
                                            <a class="button button-small button-success go-to-upgrade" href="admin.php?page=cf7cstmzr_page-pricing" data-confirm="<?php _e('Do you want to save changes before leaving page?', 'cf7-styler'); ?>" target="_blank"><?php _e('Upgrade to Pro', 'cf7-styler') ?></a>
                                        </p>
                                        <?php

                                        $no_save_class = ' not-save';
                                    }
                                    ?>
                                    <div>
                                        <div style="display: inline-block; vertical-align: top; margin-right:10px;">
                                            <label for="cf7cstmzr_form_bg_img"><?php _e('Background Image', 'cf7-styler') ?></label>

                                            <div id="cf7cstmzr_uploaded_image_holder" data-save="<?php echo $no_save_class; ?>">
                                                <?php
                                                if (!empty($style_scheme['form_bg_img'])) {
                                                    $image_attributes = wp_get_attachment_image_src( $style_scheme['form_bg_img'], 'full' );
                                                    ?>
                                                    <img src="<?php echo $image_attributes[0] ?>" alt="">
                                                    <?php
                                                }
                                                ?>
                                            </div>

                                            <input id="cf7cstmzr_form_bg_img" name="cf7cstmzr_form_bg_img" type="hidden" value="<?php echo $style_scheme['form_bg_img'] ?>"><br>

                                            <button id="cf7cstmzr_upload_image_btn" class="button button-primary button-small" type="button"<?php echo !empty($style_scheme['form_bg_img']) ? ' style="display:none;"' : '' ?>>
                                                <?php _e('Upload Image', 'cf7-styler'); ?>
                                            </button>

                                            <button id="cf7cstmzr_delete_image_btn" class="button button-primary button-small" type="button"<?php echo empty($style_scheme['form_bg_img']) ? ' style="display:none;"' : '' ?>>
                                                <?php _e('Delete Image', 'cf7-styler'); ?>
                                            </button>
                                        </div>

                                        <div style="display: inline-block; vertical-align: top;">
                                            <div id="cf7cstmzr_form_bg_img-position-holder">
                                                <label><?php _e('Image Position', 'cf7-styler') ?></label>

                                                <div id="cf7cstmzr_top_right_bottom_left_container">
                                                    <div class="cf7cstmzr_top_right"></div>
                                                    <div class="cf7cstmzr_right_bottom"></div>
                                                    <div class="cf7cstmzr_bottom_left"></div>
                                                    <div class="cf7cstmzr_left_top"></div>

                                                    <input id="cf7cstmzr_form_bg_img-position_top"
                                                           name="cf7cstmzr_form_bg_img-position"
                                                           class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_top<?php echo $no_save_class; ?>"
                                                           type="radio"
                                                           value="top"<?php echo 'top' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_top-right"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_top-right<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="top-right"<?php echo 'top-right' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_right"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_right<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="right"<?php echo 'right' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_right-bottom"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_right-bottom<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="right-bottom"<?php echo 'right-bottom' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_bottom"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_bottom<?php echo $no_save_class; ?>"
                                                            type="radio" value="bottom"<?php echo 'bottom' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_bottom-left"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_bottom-left<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="bottom-left"<?php echo 'bottom-left' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_left"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_left<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="left"<?php echo 'left' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_left-top"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_left-top<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="left-top"<?php echo 'left-top' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ><input
                                                            id="cf7cstmzr_form_bg_img-position_center"
                                                            name="cf7cstmzr_form_bg_img-position"
                                                            class="cf7cstmzr-radio-field cf7cstmzr_position cf7cstmzr_position_center<?php echo $no_save_class; ?>"
                                                            type="radio"
                                                            value="center"<?php echo 'center' === $style_scheme['form_bg_img_position'] ? ' checked' : ''; ?>
                                                        <?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>
                                                    ></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top; margin-right:10px;">
                                            <div id="cf7cstmzr_form_bg_img-opacity-holder" style="margin-top: 10px;">
                                                <label for="cf7cstmzr_form_bg_img-opacity"><?php _e('Image Opacity', 'cf7-styler') ?></label>
                                                <input id="cf7cstmzr_form_bg_img-opacity" name="cf7cstmzr_form_bg_img-opacity" class="cf7cstmzr-number cf7cstmzr-form-control<?php echo $no_save_class; ?>" min="0" max="1" step="0.1" type="number" value="<?php echo $style_scheme['form_bg_img_opacity']; ?>"<?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>>
                                            </div>
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <div id="cf7cstmzr_form_bg_img-size-holder" style="margin-top: 10px;">
                                                <label for="cf7cstmzr_form_bg_img-size"><?php _e('Image Size', 'cf7-styler') ?></label>
                                                <select id="cf7cstmzr_form_bg_img-size" name="cf7cstmzr_form_bg_img-size" class="cf7cstmzr-form-control<?php echo $no_save_class; ?>"<?php echo empty($style_scheme['form_bg_img']) ? ' disabled' : ''; ?>>
                                                    <option value=""><?php _e('- select -', 'cf7-styler') ?></option>
                                                    <option value="no-repeat"<?php echo 'no-repeat' === $style_scheme['form_bg_img_size'] ? ' selected' : ''; ?>><?php _e('Original size', 'cf7-styler') ?></option>
                                                    <option value="cover"<?php echo 'cover' === $style_scheme['form_bg_img_size'] ? ' selected' : ''; ?>><?php _e('Cover', 'cf7-styler') ?></option>
                                                    <option value="contain"<?php echo 'contain' === $style_scheme['form_bg_img_size'] ? ' selected' : ''; ?>><?php _e('Contain', 'cf7-styler') ?></option>
                                                    <option value="repeat-y"<?php echo 'repeat-y' === $style_scheme['form_bg_img_size'] ? ' selected' : ''; ?>><?php _e('Repeat vertical', 'cf7-styler') ?></option>
                                                    <option value="repeat-x"<?php echo 'repeat-x' === $style_scheme['form_bg_img_size'] ? ' selected' : ''; ?>><?php _e('Repeat horizontal', 'cf7-styler') ?></option>
                                                    <option value="repeat"<?php echo 'repeat' === $style_scheme['form_bg_img_size'] ? ' selected' : ''; ?>><?php _e('Repeat both', 'cf7-styler') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Form BG Image - End -->
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Form BG Image & Colors - End -->

                            <!-- Form Padding, Margin & Border - Start -->
                            <?php Cf7_Template::settings_item_start(__('Form Padding, Margin & Border', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $form_padding = false;

                                    if (
                                        ($style_scheme['form_padding_top'] === $style_scheme['form_padding_right']) &&
                                        ($style_scheme['form_padding_right'] === $style_scheme['form_padding_bottom']) &&
                                        ($style_scheme['form_padding_bottom'] === $style_scheme['form_padding_left'])
                                    ) {
                                        $form_padding = $style_scheme['form_padding_top'];
                                    }
                                    ?>
                                    <label for="cf7cstmzr_form_padding"><?php _e('Padding', 'cf7-styler') ?></label>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations-main"<?php echo false !== $form_padding ? '' : ' style="display:none;"' ?>>
                                        <input id="cf7cstmzr_form_padding" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-parent-input cf7cstmzr-parent-text-field" type="number" min="0" value="<?php echo $form_padding; ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </div>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations"<?php echo false === $form_padding ? '' : ' style="display:none;"' ?>>
                                        <label for="cf7cstmzr_form_padding-top" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-up-alt"></span></label>
                                        <input id="cf7cstmzr_form_padding-top" name="cf7cstmzr_form_padding_top" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_padding_top']; ?>">
                                        <label for="cf7cstmzr_form_padding-right" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-right-alt"></span></label>
                                        <input id="cf7cstmzr_form_padding-right" name="cf7cstmzr_form_padding_right" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_padding_right']; ?>"><br>
                                        <label for="cf7cstmzr_form_padding-bottom" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-down-alt"></span></label>
                                        <input id="cf7cstmzr_form_padding-bottom" name="cf7cstmzr_form_padding_bottom" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_padding_bottom']; ?>">
                                        <label for="cf7cstmzr_form_padding-left" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-left-alt"></span></label>
                                        <input id="cf7cstmzr_form_padding-left" name="cf7cstmzr_form_padding_left" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_padding_left']; ?>">
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $form_margin = false;

                                    if (
                                        ($style_scheme['form_margin_top'] === $style_scheme['form_margin_right']) &&
                                        ($style_scheme['form_margin_right'] === $style_scheme['form_margin_bottom']) &&
                                        ($style_scheme['form_margin_bottom'] === $style_scheme['form_margin_left'])
                                    ) {
                                        $form_margin = $style_scheme['form_margin_top'];
                                    }
                                    ?>
                                    <label for="cf7cstmzr_form_margin"><?php _e('Margin', 'cf7-styler') ?></label>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations-main"<?php echo false !== $form_margin ? '' : ' style="display:none;"' ?>>
                                        <input id="cf7cstmzr_form_margin" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-parent-input cf7cstmzr-parent-text-field" type="number" min="0" value="<?php echo $form_margin; ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </div>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations"<?php echo false === $form_margin ? '' : ' style="display:none;"' ?>>
                                        <label for="cf7cstmzr_form_margin-top" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-up-alt"></span></label>
                                        <input id="cf7cstmzr_form_margin-top" name="cf7cstmzr_form_margin_top" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_margin_top']; ?>">
                                        <label for="cf7cstmzr_form_margin-right" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-right-alt"></span></label>
                                        <input id="cf7cstmzr_form_margin-right" name="cf7cstmzr_form_margin_right" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_margin_right']; ?>"><br>
                                        <label for="cf7cstmzr_form_margin-bottom" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-down-alt"></span></label>
                                        <input id="cf7cstmzr_form_margin-bottom" name="cf7cstmzr_form_margin_bottom" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_margin_bottom']; ?>">
                                        <label for="cf7cstmzr_form_margin-left" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-left-alt"></span></label>
                                        <input id="cf7cstmzr_form_margin-left" name="cf7cstmzr_form_margin_left" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_margin_left']; ?>">
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $form_border_width = false;

                                    if (
                                        ($style_scheme['form_border_width_top'] === $style_scheme['form_border_width_right']) &&
                                        ($style_scheme['form_border_width_right'] === $style_scheme['form_border_width_bottom']) &&
                                        ($style_scheme['form_border_width_bottom'] === $style_scheme['form_border_width_left'])
                                    ) {
                                        $form_border_width = $style_scheme['form_border_width_top'];
                                    }
                                    ?>
                                    <label for="cf7cstmzr_form_border_width"><?php _e('Border Width', 'cf7-styler') ?></label>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations-main"<?php echo false !== $form_border_width ? '' : ' style="display:none;"' ?>>
                                        <input id="cf7cstmzr_form_border_width" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-parent-input cf7cstmzr-parent-text-field" type="number" min="0" value="<?php echo $form_border_width; ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </div>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations"<?php echo false === $form_border_width ? ' style="margin-bottom:10px;"' : ' style="display:none;"' ?>>
                                        <label for="cf7cstmzr_form_border_width-top" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-up-alt"></span></label>
                                        <input id="cf7cstmzr_form_border_width-top" name="cf7cstmzr_form_border_width_top" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_border_width_top']; ?>">

                                        <label for="cf7cstmzr_form_border_width-right" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-right-alt"></span></label>
                                        <input id="cf7cstmzr_form_border_width-right" name="cf7cstmzr_form_border_width_right" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_border_width_right']; ?>"><br>

                                        <label for="cf7cstmzr_form_border_width-bottom" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-down-alt"></span></label>
                                        <input id="cf7cstmzr_form_border_width-bottom" name="cf7cstmzr_form_border_width_bottom" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_border_width_bottom']; ?>">

                                        <label for="cf7cstmzr_form_border_width-left" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-left-alt"></span></label>
                                        <input id="cf7cstmzr_form_border_width-left" name="cf7cstmzr_form_border_width_left" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['form_border_width_left']; ?>">
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <label for="cf7cstmzr_form_border_type"><?php _e('Border Type', 'cf7-styler') ?></label>

                                        <select name="cf7cstmzr_form_border_type" id="cf7cstmzr_form_border_type" class="cf7cstmzr-form-control cf7cstmzr-dropdown-field">
                                            <option value=""><?php _e('- select -', 'cf7-styler') ?></option>
                                            <?php
                                            foreach (Cf7_Template::get_border_type() as $border_type_val => $border_type_label) {
                                                ?>
                                                <option value="<?php echo $border_type_val ?>"<?php echo $style_scheme['form_border_type'] === $border_type_val ? ' selected' : ''; ?>><?php echo $border_type_label ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div>
                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_form_border_radius"><?php _e('Border Radius', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_border_radius" name="cf7cstmzr_form_border_radius" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" type="number" value="<?php echo $style_scheme['form_border_radius']; ?>">
                                        </div>

                                        <div style="display: inline-block;vertical-align: top;">
                                            <label for="cf7cstmzr_form_border_color"><?php _e('Border Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_form_border_color" name="cf7cstmzr_form_border_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['form_border_color']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php Cf7_Template::setting_shadow($style_scheme, 'form'); ?>
                                </div>
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Form Padding, Margin & Border - End -->

                            <!-- Input Fields Text and Colors - Start -->
                            <?php Cf7_Template::settings_item_start(__('Input Fields', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <label for="cf7cstmzr_input_width"><?php _e('Make input fields full width?', 'cf7-styler') ?></label>

                                    <input id="cf7cstmzr_input_width_yes" name="cf7cstmzr_input_full-width" class="cf7cstmzr-radio-field" type="radio" value="yes"<?php echo 'yes' === $style_scheme['input_full_width'] ? ' checked' : ''; ?>>
                                    <label for="cf7cstmzr_input_width_yes" class="cf7cstmzr_inline_label"><?php _e('YES', 'cf7-styler') ?></label>
                                    <br>
                                    <input id="cf7cstmzr_input_width_no" name="cf7cstmzr_input_full-width" class="cf7cstmzr-radio-field" type="radio" value="no"<?php echo 'no' === $style_scheme['input_full_width'] ? ' checked' : ''; ?>>
                                    <label for="cf7cstmzr_input_width_no" class="cf7cstmzr_inline_label"><?php _e('NO', 'cf7-styler') ?></label>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_input_text_size"><?php _e('Font Size', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_text_size" name="cf7cstmzr_input_text_size" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" type="number" value="<?php echo $style_scheme['input_text_size']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_input_text_line-height"><?php _e('Line Height', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_text_line-height" name="cf7cstmzr_input_text_line-height" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="0" step="0.1" type="number" value="<?php echo $style_scheme['input_text_line_height']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_input_text_color"><?php _e('Text Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_text_color" name="cf7cstmzr_input_text_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['input_text_color']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_input_bg_color"><?php _e('BG Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_bg_color" name="cf7cstmzr_input_bg_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['input_bg_color']; ?>">

                                        </div>
                                    </div>
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top"><label for="cf7cstmzr_input_bg_color-opacity"><?php _e('BG Opacity', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_bg_color-opacity" name="cf7cstmzr_input_bg_color-opacity" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="0" max="1" step="0.1" type="number" value="<?php echo $style_scheme['input_bg_color_opacity']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $input_padding = false;

                                    if (
                                        ($style_scheme['input_padding_top'] === $style_scheme['input_padding_right']) &&
                                        ($style_scheme['input_padding_right'] === $style_scheme['input_padding_bottom']) &&
                                        ($style_scheme['input_padding_bottom'] === $style_scheme['input_padding_left'])
                                    ) {
                                        $input_padding = $style_scheme['input_padding_top'];
                                    }
                                    ?>

                                    <label for="cf7cstmzr_input_padding"><?php _e('Padding', 'cf7-styler') ?></label>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations-main"<?php echo false !== $input_padding ? '' : ' style="display:none;"' ?>>
                                        <input id="cf7cstmzr_input_padding" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-parent-input cf7cstmzr-parent-text-field" type="number" min="0" value="<?php echo $input_padding; ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </div>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations"<?php echo false === $input_padding ? '' : ' style="display:none;"' ?>>
                                        <label for="cf7cstmzr_input_padding-top" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-up-alt"></span></label>
                                        <input id="cf7cstmzr_input_padding-top" name="cf7cstmzr_input_padding_top" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_padding_top']; ?>">
                                        <label for="cf7cstmzr_input_padding-right" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-right-alt"></span></label>
                                        <input id="cf7cstmzr_input_padding-right" name="cf7cstmzr_input_padding_right" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_padding_right']; ?>"><br>
                                        <label for="cf7cstmzr_input_padding-bottom" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-down-alt"></span></label>
                                        <input id="cf7cstmzr_input_padding-bottom" name="cf7cstmzr_input_padding_bottom" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_padding_bottom']; ?>">
                                        <label for="cf7cstmzr_input_padding-left" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-left-alt"></span></label>
                                        <input id="cf7cstmzr_input_padding-left" name="cf7cstmzr_input_padding_left" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_padding_left']; ?>">
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $input_margin = false;

                                    if (
                                        ($style_scheme['input_margin_top'] === $style_scheme['input_margin_right']) &&
                                        ($style_scheme['input_margin_right'] === $style_scheme['input_margin_bottom']) &&
                                        ($style_scheme['input_margin_bottom'] === $style_scheme['input_margin_left'])
                                    ) {
                                        $input_margin = $style_scheme['input_margin_top'];
                                    }
                                    ?>
                                    <label for="cf7cstmzr_input_margin"><?php _e('Margin', 'cf7-styler') ?></label>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations-main"<?php echo false !== $input_margin ? '' : ' style="display:none;"' ?>>
                                        <input id="cf7cstmzr_input_margin" name="cf7cstmzr_input_margin" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-parent-input cf7cstmzr-parent-text-field" type="number" min="0" value="<?php echo $input_margin; ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </div>

                                    <div class="cf7cstmzr-settings-item-body-setting-variations"<?php echo false === $input_margin ? '' : ' style="display:none;"' ?>>
                                        <label for="cf7cstmzr_input_margin-top" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-up-alt"></span></label>
                                        <input id="cf7cstmzr_input_margin-top" name="cf7cstmzr_input_margin_top" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_margin_top']; ?>">
                                        <label for="cf7cstmzr_input_margin-right" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-right-alt"></span></label>
                                        <input id="cf7cstmzr_input_margin-right" name="cf7cstmzr_input_margin_right" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_margin_right']; ?>"><br>
                                        <label for="cf7cstmzr_input_margin-bottom" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-down-alt"></span></label>
                                        <input id="cf7cstmzr_input_margin-bottom" name="cf7cstmzr_input_margin_bottom" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_margin_bottom']; ?>">
                                        <label for="cf7cstmzr_input_margin-left" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-left-alt"></span></label>
                                        <input id="cf7cstmzr_input_margin-left" name="cf7cstmzr_input_margin_left" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_margin_left']; ?>">
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $input_border_width = false;

                                    if (
                                        ($style_scheme['input_border_width_top'] === $style_scheme['input_border_width_right']) &&
                                        ($style_scheme['input_border_width_right'] === $style_scheme['input_border_width_bottom']) &&
                                        ($style_scheme['input_border_width_bottom'] === $style_scheme['input_border_width_left'])
                                    ) {
                                        $input_border_width = $style_scheme['input_border_width_top'];
                                    }
                                    ?>
                                    <label for="cf7cstmzr_input_border_width"><?php _e('Border', 'cf7-styler') ?></label>

                                    <div style="margin-bottom: 10px;">
                                        <div class="cf7cstmzr-settings-item-body-setting-variations-main"<?php echo false !== $input_border_width ? '' : ' style="display:none;"' ?>>
                                            <input id="cf7cstmzr_input_border_width" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-parent-input cf7cstmzr-parent-text-field" type="number" min="0" value="<?php echo $input_border_width; ?>">
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </div>

                                        <div class="cf7cstmzr-settings-item-body-setting-variations"<?php echo false === $input_border_width ? '' : ' style="display:none;"' ?>>
                                            <label for="cf7cstmzr_input_border_width-top" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-up-alt"></span></label>
                                            <input id="cf7cstmzr_input_border_width-top" name="cf7cstmzr_input_border_width_top" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_border_width_top']; ?>">

                                            <label for="cf7cstmzr_input_border_width-right" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-right-alt"></span></label>
                                            <input id="cf7cstmzr_input_border_width-right" name="cf7cstmzr_input_border_width_right" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_border_width_right']; ?>"><br>

                                            <label for="cf7cstmzr_input_border_width-bottom" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-down-alt"></span></label>
                                            <input id="cf7cstmzr_input_border_width-bottom" name="cf7cstmzr_input_border_width_bottom" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_border_width_bottom']; ?>">

                                            <label for="cf7cstmzr_input_border_width-left" class="cf7cstmzr_inline_label"><span class="dashicons dashicons-arrow-left-alt"></span></label>
                                            <input id="cf7cstmzr_input_border_width-left" name="cf7cstmzr_input_border_width_left" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-inline-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['input_border_width_left']; ?>">
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_input_border_radius"><?php _e('Radius', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_border_radius" name="cf7cstmzr_input_border_radius" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" type="number" value="<?php echo $style_scheme['input_border_radius']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_input_border_color"><?php _e('Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_input_border_color" name="cf7cstmzr_input_border_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['input_border_color']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php Cf7_Template::setting_shadow($style_scheme, 'input'); ?>
                                </div>
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Input Fields Text and Colors - End -->

                            <!-- Checkboxes & Radiobuttons - Start -->
                            <?php Cf7_Template::settings_item_start(__('Checkboxes & Radiobuttons', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php
                                    $no_save_class = '';
                                    if ('free' === $plugin_version) {
                                        $style_scheme['checkbox_full_width'] = '';
                                        $style_scheme['radiobutton_full_width'] = '';
                                        ?>
                                        <p style="margin-top:0;margin-bottom:5px;">
                                            <?php _e('One per line styles for checkboxes and radiobuttons in live mode only for Professional version. You can test it in preview mode "current style", but it will not be saved.', 'cf7-styler') ?>
                                        </p>

                                        <p style="margin-top:0;">
                                            <a class="button button-small button-success go-to-upgrade" href="admin.php?page=cf7cstmzr_page-pricing" data-confirm="<?php _e('Do you want to save changes before leaving page?', 'cf7-styler'); ?>" target="_blank"><?php _e('Upgrade to Pro', 'cf7-styler') ?></a>
                                        </p>
                                        <?php

                                        $no_save_class = ' not-save';
                                    }
                                    ?>

                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block;vertical-align: top;margin-right:10px;max-width:45%;">
                                            <label for="cf7cstmzr_input_width"><?php _e('Make checkbox item one per line', 'cf7-styler') ?></label>

                                            <input id="cf7cstmzr_checkbox_width_yes" name="cf7cstmzr_checkbox_full-width" class="cf7cstmzr-radio-field<?php echo $no_save_class; ?>" type="radio" value="yes"<?php echo 'yes' === $style_scheme['checkbox_full_width'] ? ' checked' : ''; ?>>
                                            <label for="cf7cstmzr_checkbox_width_yes" class="cf7cstmzr_inline_label"><?php _e('YES', 'cf7-styler') ?></label>
                                            <br>
                                            <input id="cf7cstmzr_checkbox_width_no" name="cf7cstmzr_checkbox_full-width" class="cf7cstmzr-radio-field<?php echo $no_save_class; ?>" type="radio" value="no"<?php echo 'no' === $style_scheme['checkbox_full_width'] ? ' checked' : ''; ?>>
                                            <label for="cf7cstmzr_checkbox_width_no" class="cf7cstmzr_inline_label"><?php _e('NO', 'cf7-styler') ?></label>
                                        </div>

                                        <div style="display: inline-block; vertical-align: top;max-width:45%;">
                                            <label for="cf7cstmzr_radiobutton_width"><?php _e('Make radiobutton item one per line', 'cf7-styler') ?></label>

                                            <input id="cf7cstmzr_radiobutton_width_yes" name="cf7cstmzr_radiobutton_full-width" class="cf7cstmzr-radio-field<?php echo $no_save_class; ?>" type="radio" value="yes"<?php echo 'yes' === $style_scheme['radiobutton_full_width'] ? ' checked' : ''; ?>>
                                            <label for="cf7cstmzr_radiobutton_width_yes" class="cf7cstmzr_inline_label"><?php _e('YES', 'cf7-styler') ?></label>
                                            <br>
                                            <input id="cf7cstmzr_radiobutton_width_no" name="cf7cstmzr_radiobutton_full-width" class="cf7cstmzr-radio-field<?php echo $no_save_class; ?>" type="radio" value="no"<?php echo 'no' === $style_scheme['radiobutton_full_width'] ? ' checked' : ''; ?>>
                                            <label for="cf7cstmzr_radiobutton_width_no" class="cf7cstmzr_inline_label"><?php _e('NO', 'cf7-styler') ?></label>
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="display: inline-block; vertical-align: top">
                                        <label for="cf7cstmzr_checkbox_text_label-size"><?php _e('Labels Font Size', 'cf7-styler') ?></label>
                                        <input id="cf7cstmzr_checkbox_text_label-size" name="cf7cstmzr_checkbox_text_label-size" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="1" type="number" value="<?php echo $style_scheme['checkbox_text_label_size']; ?>">
                                    </div>
                                </div>
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Checkboxes & Radiobuttons - End -->

                            <!-- Button Text & Colors - Start -->
                            <?php // Cf7_Template::settings_item_start(__('Button Size, Text & Colors', 'cf7-styler')); ?>
                            <?php Cf7_Template::settings_item_start(__('Buttons', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_input_width"><?php _e('Make full width?', 'cf7-styler') ?></label>

                                            <input id="cf7cstmzr_button_width_yes" name="cf7cstmzr_button_full-width" class="cf7cstmzr-radio-field" type="radio" value="yes"<?php echo 'yes' === $style_scheme['button_full_width'] ? ' checked' : ''; ?>>
                                            <label for="cf7cstmzr_button_width_yes" class="cf7cstmzr_inline_label"><?php _e('YES', 'cf7-styler') ?></label>
                                            <br>
                                            <input id="cf7cstmzr_button_width_no" name="cf7cstmzr_button_full-width" class="cf7cstmzr-radio-field" type="radio" value="no"<?php echo 'no' === $style_scheme['button_full_width'] ? ' checked' : ''; ?>>
                                            <label for="cf7cstmzr_button_width_no" class="cf7cstmzr_inline_label"><?php _e('NO', 'cf7-styler') ?></label>
                                        </div>

                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_padding"><?php _e('Padding', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_padding" name="cf7cstmzr_button_padding" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="0" type="number" value="<?php echo $style_scheme['button_padding']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_text_color"><?php _e('Text Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_text_color" name="cf7cstmzr_button_text_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['button_text_color']; ?>">
                                        </div>

                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_text_color-hover"><?php _e('Hover Text Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_text_color-hover" name="cf7cstmzr_button_text_color-hover" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['button_text_color_hover']; ?>">
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_bg_color"><?php _e('BG Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_bg_color" name="cf7cstmzr_button_bg_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['button_bg_color']; ?>">
                                        </div>

                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_bg_color-hover"><?php _e('Hover BG Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_bg_color-hover" name="cf7cstmzr_button_bg_color-hover" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['button_bg_color_hover']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_text_size"><?php _e('Font Size', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_text_size" name="cf7cstmzr_button_text_size" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" type="number" value="<?php echo $style_scheme['button_text_size']; ?>">
                                        </div>

                                        <div style="display: inline-block;vertical-align: top;margin-right: 10px;">
                                            <label for="cf7cstmzr_button_text_line-height"><?php _e('Line Height', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_text_line-height" name="cf7cstmzr_button_text_line-height" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" min="0" step="0.1" type="number" value="<?php echo $style_scheme['button_text_line_height']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <label><?php _e('Button Border', 'cf7-styler') ?></label>
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_button_border_width"><?php _e('Width', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_border_width" name="cf7cstmzr_button_border_width" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" type="number" value="<?php echo $style_scheme['button_border_width']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_button_border_radius"><?php _e('Radius', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_border_radius" name="cf7cstmzr_button_border_radius" class="cf7cstmzr-number cf7cstmzr-form-control cf7cstmzr-text-field" type="number" value="<?php echo $style_scheme['button_border_radius']; ?>">
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 10px;">
                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_button_border_color"><?php _e('Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_border_color" name="cf7cstmzr_button_border_color" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['button_border_color']; ?>">
                                        </div>

                                        <div style="display: inline-block; vertical-align: top">
                                            <label for="cf7cstmzr_button_border_color-hover"><?php _e('Hover Color', 'cf7-styler') ?></label>
                                            <input id="cf7cstmzr_button_border_color-hover" name="cf7cstmzr_button_border_color-hover" class="cf7cstmzr-color-picker" type="text" value="<?php echo $style_scheme['button_border_color_hover']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <?php Cf7_Template::setting_shadow($style_scheme, 'button'); ?>
                                </div>
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Button Text & Colors - End -->

                            <!-- Custom Styles - Start -->
                            <?php Cf7_Template::settings_item_start(__('Custom CSS', 'cf7-styler')); ?>
                                <div class="cf7cstmzr-settings-item-body-setting">
                                    <label for="cf7cstmzr_custom_css"><?php _e('Custom CSS Code', 'cf7-styler') ?></label>

                                    <div style="margin:10px 0;">
                                        <button id="cf7cstmzr_custom_css_save" class="button button-small" type="button" disabled>
                                            <?php _e('Preview', 'cf7-styler') ?>
                                        </button>

                                        <button id="cf7cstmzr_custom_css_clear" class="button button-small<?php echo !empty($style_scheme['custom_css']) ? ' button-primary' : '' ?>" type="button"<?php echo empty($style_scheme['custom_css']) ? ' disabled' : '' ?>>
                                            <?php _e('Clear', 'cf7-styler') ?>
                                        </button>
                                    </div>

                                    <textarea name="cf7cstmzr_custom_css" id="cf7cstmzr_custom-css" class="widefat textarea" cols="30" rows="10"><?php echo $style_scheme['custom_css']; ?></textarea>

                            </div>
                            <?php Cf7_Template::settings_item_end(); ?>
                            <!-- Custom Styles - End -->
                        </div>
                    </form>
                </div>
                <!-- Style Scheme Settings - End -->
            </div>
        </div>

        <div class="cf7cstmzr-col-wide">
            <!-- Select Form - Start -->
            <div id="cf7cstmzr-preview-container-header">
                <div id="cf7cstmzr-preview-container-header-title" style="padding: 10px;margin:0 0 5px 0;background-color:#fff;border-top:5px solid #ccc; border-bottom: 1px solid #ccc;">
                    <h3 style="margin:0;display: inline-block;margin-right: 25px;"><?php _e('Style schemes preview', 'cf7-styler'); ?></h3>
                    <?php include CF7CSTMZR_PLUGIN_PATH . 'admin/partials/cf7-customizer-admin-preview-mode.php'; ?>

                    <div class="cf7cstmzr-preview-control-icons only-desktop" style="display: inline-block;">
                        <span class="dashicons dashicons-desktop" title="<?php _e('Desktop View', 'cf7-styler') ?>"></span>
                        <span class="dashicons dashicons-tablet" title="<?php _e('Tablet View', 'cf7-styler') ?>"></span>
                        <span class="dashicons dashicons-smartphone" title="<?php _e('Mobile View', 'cf7-styler') ?>"></span>
                    </div>
                </div>

                <div id="select-form-container" class="cf7cstmzr-settings-control">
                    <div class="cf7cstmzr-settings-control-inner">
                        <?php
                        if (!empty($cf7_forms)) {
                            $show_description = 'free' !== $plugin_version && !empty($style_schemes);
                            ?>
                            <div class="cf7cstmzr-container-with-description">
                                <div>
                                    <h4<?php echo $show_description ? ' class="cf7cstmzr-description-togler-holder"' : ''; ?> style="display: inline-block;">
                                        <?php
                                        if (1 === count($cf7_forms)) {
                                            echo get_the_title($cf7_forms[0]->ID);
                                        } else {
                                            echo __('Contact form 7 list', 'cf7-styler');
                                        }

                                        // if ($show_description) {
                                        if (true) {
                                            ?><span class="dashicons dashicons-editor-help cf7cstmzr-description-togler" data-target="cf7cstmzr-select-form-description"></span><?php
                                        }
                                        ?>

                                    </h4>

                                    <select id="cf7cstmzr_select_form"
                                            class="cf7cstmzr-form-control"<?php echo 1 === count($cf7_forms) ? ' style="display:none;"' : ' style="margin-bottom:5px; display:inline-block;max-width:250px;"'; ?>
                                        <?php echo 'free' === $plugin_version ? ' data-mode="free"' : ' data-mode="free"' ?>
                                    >
                                        <?php
                                        $i = 1;
                                        $first_form_scheme = '';
                                        $first_form_scheme_title = '';
                                        foreach ($cf7_forms as $cf7_form) {
                                            $form_style_scheme_data = '';
                                            $form_style_scheme_data_title = '';

                                            $cf7cstmzr_style_scheme = get_post_meta( $cf7_form->ID, 'cf7cstmzr_style_scheme', true );

                                            if (!empty($cf7cstmzr_style_scheme)) {
                                                if (!empty($style_schemes[$cf7cstmzr_style_scheme])) {
                                                    $form_style_scheme_data = $cf7cstmzr_style_scheme;
                                                    $form_style_scheme_data_title = $style_schemes[$cf7cstmzr_style_scheme]['title'];

                                                    if (1 === $i) {
                                                        $first_form_scheme = $cf7cstmzr_style_scheme;
                                                        $first_form_scheme_title = $style_schemes[$cf7cstmzr_style_scheme]['title'];
                                                    }
                                                } else {

                                                }
                                            }
                                            ?>
                                            <option
                                                    value="<?php echo $cf7_form->ID ?>"
                                                    data-scheme="<?php echo $form_style_scheme_data; ?>"
                                                    data-scheme-title="<?php echo $form_style_scheme_data_title; ?>"
                                                    data-scheme-global="<?php echo $enabled_globally; ?>"
                                                    data-scheme-global-title="<?php echo $enabled_globally_title; ?>"
                                                <?php echo (int)$cache_form === (int)$cf7_form->ID ? ' selected' : ''; ?>
                                            >
                                                <?php echo get_the_title($cf7_form->ID); ?>
                                            </option>
                                            <?php
                                            $i++;
                                        }
                                        ?>
                                    </select>
                                    <?php
                                    if ($show_description) {
                                        if ( $style_scheme_slug === $first_form_scheme ) { // This form styled with current style scheme
                                            $display_enabled_message = ' style="display:block;"';
                                            $display_enabled_another_message = ' style="display:none;"';
                                            $display_disabled_message = ' style="display:none;"';
                                            $display_disabled_globally_message = ' style="display:none;"';
                                            $display_enabled_button = ' style="display:none;"';
                                            $display_disabled_button = ' style="display:inline-block;"';
                                        } elseif( !empty( $first_form_scheme ) ) {
                                            $display_enabled_message = ' style="display:none;"';
                                            $display_enabled_another_message = ' style="display:block;"';
                                            $display_disabled_message = ' style="display:none;"';
                                            $display_disabled_globally_message = ' style="display:none;"';
                                            $display_enabled_button = ' style="display:inline-block;"';
                                            $display_disabled_button = ' style="display:none;"';
                                        } elseif( !empty( $enabled_globally ) ) {
                                            $display_enabled_message = ' style="display:none;"';
                                            $display_enabled_another_message = ' style="display:none;"';
                                            $display_disabled_message = ' style="display:block;"';
                                            $display_disabled_globally_message = ' style="display:none;"';
                                            $display_enabled_button = ' style="display:inline-block;"';
                                            $display_disabled_button = ' style="display:none;"';
                                        } else {
                                            $display_enabled_message = ' style="display:none;"';
                                            $display_enabled_another_message = ' style="display:none;"';
                                            $display_disabled_message = ' style="display:none;"';
                                            $display_disabled_globally_message = ' style="display:block;"';
                                            $display_enabled_button = ' style="display:inline-block;"';
                                            $display_disabled_button = ' style="display:none;"';
                                        }
                                    } else {
                                        $display_disabled_single_form = ' style="display:none;"';
                                        $display_enabled_single_form = ' style="display:none;"';

                                        if ( $style_scheme_slug === $first_form_scheme ) { // This form styled with current style scheme
                                            $display_enabled_button = ' style="display:none;"';
                                            $display_disabled_button = ' style="display:inline-block;"';
                                        } elseif( !empty( $first_form_scheme ) ) {
                                            $display_enabled_button = ' style="display:inline-block;"';
                                            $display_disabled_button = ' style="display:none;"';
                                        } elseif( !empty( $enabled_globally ) ) {
                                            $display_enabled_button = ' style="display:inline-block;"';
                                            $display_disabled_button = ' style="display:none;"';
                                        } else {
                                            $display_enabled_button = ' style="display:inline-block;"';
                                            $display_disabled_button = ' style="display:none;"';
                                        }
                                    }
                                    ?>
                                    <button id="cf7cstmzr-enable-for-form" class="button button-success"  type="button" data-scheme="<?php echo $style_scheme_slug ?>"<?php echo $display_enabled_button; ?>>
                                        <?php _e('Activate style for current form', 'cf7-styler') ?>
                                    </button>

                                    <button id="cf7cstmzr-disable-for-form" class="button button-primary" type="button" data-scheme="<?php echo $first_form_scheme ?>"<?php echo $display_disabled_button; ?>>
                                        <?php _e('Disable style for current form', 'cf7-styler') ?>
                                    </button>
                                </div>
                                <?php
                                if ($show_description) {
                                    ?>
                                    <div id="cf7cstmzr-select-form-description" class="cf7cstmzr-description">
                                        <?php
                                        ?>
                                        <p style="margin-top:0;margin-bottom:5px;">
                                            <span id="cf7cstmzr-enabled-message"<?php echo $display_enabled_message; ?>>
                                                <?php _e('This form is styled with current Style Scheme, you can disable it and use global settings.', 'cf7-styler') ?>
                                            </span>

                                            <span id="cf7cstmzr-enabled-another-message"<?php echo $display_enabled_another_message; ?>>
                                                <?php echo sprintf( __( 'This form is styled with <strong>%s</strong>, you can style it with current Style scheme.', 'cf7-styler' ), $first_form_scheme_title ); ?>
                                            </span>

                                            <span id="cf7cstmzr-disabled-message"<?php echo $display_disabled_message; ?>>
                                                <?php echo sprintf( __( 'This form is styled with <strong>%s</strong> globally, you can style it with current Style scheme.', 'cf7-styler' ), $enabled_globally_title ); ?>
                                            </span>

                                            <span id="cf7cstmzr-disabled-globally-message"<?php echo $display_disabled_globally_message; ?>>
                                                <?php _e('This form not styled with any Style scheme, you can enable current Scheme for this form or set up global Style Scheme by clicking "Use for all forms" below scheme title.', 'cf7-styler') ?>
                                            </span>
                                        </p>
                                    </div>

                                    <div>
                                        <?php
                                        if (false) {
                                            ?>
                                            <button id="cf7cstmzr-enable-for-form" class="button button-success" type="button" data-scheme="<?php echo $style_scheme_slug ?>"<?php echo $display_enabled_button; ?>>
                                                <?php _e('Activate style for current form', 'cf7-styler') ?>
                                            </button>

                                            <button id="cf7cstmzr-disable-for-form" class="button button-primary" type="button" data-scheme="<?php echo $first_form_scheme ?>"<?php echo $display_disabled_button; ?>>
                                                <?php _e('Disable style for current form', 'cf7-styler') ?>
                                            </button>
                                            <?php
                                        }
                                        ?>

                                        <?php
                                        if (false) {
                                            ?>
                                            <div style="padding-top: 3px;display: inline-block;margin-left: 10px;">
                                                <input type="checkbox" value="1" id="cf7cstmzr-preview-unstyle"<?php echo empty($is_styled) ? ' checked' : '';?>> <?php _e('Preview Unstyled', 'cf7-styler') ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <?php
                                } else {
                                    $styled_form_id = '';
                                    $styled_form_title = '';
                                    $styled_form_style_title = '';
                                    $styled_form_style_slug = '';

                                    $individually_styled_forms = Cf7_Style_Scheme::get_individually_styled_forms();

                                    if (!empty($individually_styled_forms)) {
                                        foreach ($individually_styled_forms  as $styled_id => $styled_form_style) {
                                            $styled_form = get_post($styled_id);

                                            if (!empty($styled_form)) {
                                                $styled_form_id = $styled_id;
                                                $styled_form_title = $styled_form->post_title;
                                                $styled_form_style_title = $style_schemes[$styled_form_style]['title'];
                                                $styled_form_style_slug = $styled_form_style;
                                            }
                                        }
                                    }
                                    ?>
                                    <div
                                            id="cf7cstmzr-select-single-form-description"
                                            class="cf7cstmzr-description"
                                            data-form="<?php echo $styled_form_id ?>"
                                            data-form-title="<?php echo $styled_form_title ?>"
                                            data-scheme="<?php echo $styled_form_style_slug ?>"
                                            data-scheme-title="<?php echo $styled_form_style_title ?>"
                                    >
                                        <span id="cf7cstmzr-enabled-single-form"<?php echo $display_enabled_single_form; ?>>
                                            <?php
                                            echo sprintf( __( 'Currently <strong id="cf7cstmzr-styled_form_title">%s</strong> form is styled with <strong id="cf7cstmzr-styled_form_style_title">%s</strong>. As in free version you can style only one form at a time and if you activate style for current form, style will be removed from other form.', 'cf7-styler' ), $styled_form_title, $styled_form_style_title );
                                            ?>
                                        </span>

                                        <span id="cf7cstmzr-disabled-single-form"<?php echo $display_disabled_single_form; ?>>
                                            <?php
                                            echo sprintf( __( 'No form is styled with style scheme, click "Activate style for current form" button to apply current scheme for this form.', 'cf7-styler' ), $styled_form_title, $styled_form_style_title );
                                            ?>
                                        </span>
                                    </div>
                                    <?php
                                    if (false) {
                                        ?>
                                        <div style="margin-bottom: 10px;">
                                            <button id="cf7cstmzr-enable-for-form" class="button button-success" type="button" data-scheme="<?php echo $style_scheme_slug ?>"<?php echo $display_enabled_button; ?>>
                                                <?php _e('Activate style for current form', 'cf7-styler') ?>
                                            </button>

                                            <button id="cf7cstmzr-disable-for-form" class="button button-primary" type="button" data-scheme="<?php echo $first_form_scheme ?>"<?php echo $display_disabled_button; ?>>
                                                <?php _e('Disable style for current form', 'cf7-styler') ?>
                                            </button>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <?php
                        } else { // No Contact Form 7 Items
                            ?>
                            <h4 style="margin-top:0;margin-bottom:10px;font-size:16px;"><?php _e('You do not have any Contact Form 7 items', 'cf7-styler') ?></h4>
                            <p style="margin-bottom: 0;">
                                <?php _e('Start with creating your <a href="admin.php?page=wpcf7-new">first Contact Form 7</a>', 'cf7-styler') ?>
                            </p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <!-- Select Form - End -->
            </div>

            <div id="cf7cstmzr-preview-container-body">
                <div id="form-preview-container" style="width:100%;height:100%;margin: auto;">
                    <div
                            id="form-preview-container_inner"
                            style="width:100%;height:100%;margin: auto;"
                    >
                        <?php
                        if (!empty($cf7_forms)) {
                            $cf7_form_id = $cf7_forms[0]->ID;

                            if (!empty($cache_form)) {
                                $cf7_form_id = $cache_form;
                            }
                            if (false) {
                                ?>
                                <iframe id="formPreviewFrame"
                                        title="Inline Frame Example"
                                        class="previous-iframe"
                                        style="width:100%;height:100%;"
                                        src="<?php echo get_site_url(); ?>/cf7cstmzr-form-customizer/<?php echo $cf7_form_id ?>?style_scheme=<?php echo $style_scheme_slug ?>&on_load=1">
                                </iframe>
                                <?php
                            }
                        } else {
                            ?>
                            <div style="padding: 35px 25px;">
                                <p style="text-align:center;">
                                    <?php _e('No Contact Form 7 items for preview', 'cf7-styler') ?>
                                </p>

                                <p style="text-align:center;">
                                    <?php _e('Start with creating your <a href="admin.php?page=wpcf7-new">first Contact Form 7</a>', 'cf7-styler') ?>
                                </p>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div id="form-preview-container-temp" style="display: block;"></div>
            </div>
        </div>
    </div>
</div>

