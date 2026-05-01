<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       saleswonder.biz
 * @since      1.0.0
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/public
 * @author     Tobias Conrad <tc@saleswonder.biz>
 */
class Cf7_Customizer_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Cf7_Customizer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Cf7_Customizer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cf7-customizer-public.css', array(), $this->version . time(), 'all' );

        $permalink_structure = get_option('permalink_structure');

        if (!empty($permalink_structure)) {
            $form_id = get_query_var( 'cf7cstmzr-form' );
        } else {
            if (!empty($_GET['cf7cstmzr_page']) && !empty($_GET['form_id'])) {
                $form_id = $_GET['form_id'];
            }
        }
        $plugin_version = Cf7_License::get_license_version();

        if ($form_id) { // Backend testing
            $style_scheme_slug = !empty($_GET['style_scheme']) ? sanitize_text_field($_GET['style_scheme']) : 'default';
            $style_schemes = get_option('cf7cstmzr_style_schemes', array());
            $is_styled = get_option('cf7cstmzr-preview-styled', true);
            $preview_mode = get_option('cf7cstmzr-preview-mode', false);

            if (!empty($preview_mode)) {
                if ('unstyled' === $preview_mode) {
                    $is_styled = false;
                } elseif ('current-style' === $preview_mode) {
                    $is_styled = true;
                } else {
                    $is_styled = true;
                }
            }

            if ($is_styled) {
                if ('split-mode' === $preview_mode) {
                    $temp_styles = get_option('cf7cstmzr_style_schemes_preview', false);

                    $live_style = get_post_meta( $form_id, 'cf7cstmzr_style_scheme', true );

                    if (empty($live_style)  && 'free' !== $plugin_version) {
                        $live_style = get_option('cf7cstmzr_enabled_globally', false);
                    }

                    if (!empty($live_style)) {
                        $live_styles = array(
                            'preview' => array(
                                'title' => 'preview',
                                'scheme' => $style_schemes[$live_style]['scheme'],
                            ),
                        );
                    } else {
                        $live_styles = array(
                            'preview' => array(
                                'title' => 'preview',
                                'scheme' => array(),
                            ),
                        );
                    }
                } elseif ('current-style' === $preview_mode) {
                    $temp_styles = get_option('cf7cstmzr_style_schemes_preview', false);
                } else {
                    $temp_style = get_post_meta( $form_id, 'cf7cstmzr_style_scheme', true );

                    if (empty($temp_style)  && 'free' !== $plugin_version) {
                        $temp_style = get_option('cf7cstmzr_enabled_globally', false);
                    }

                    if (!empty($temp_style)) {
                        $temp_styles = array(
                            'preview' => array(
                                'title' => 'preview',
                                'scheme' => $style_schemes[$temp_style]['scheme'],
                            ),
                        );
                    } else {
                        $temp_styles = array(
                            'preview' => array(
                                'title' => 'preview',
                                'scheme' => array(),
                            ),
                        );
                    }
                }

                if (!empty($live_styles)) {
                    if (!empty($live_styles['preview']['scheme'])) {
                        if ('free' === $plugin_version && 'current-style' !== $preview_mode) {
                            if (!empty($live_styles['preview']["scheme"]["form"]["bg"]["img"])) $live_styles['preview']["scheme"]["form"]["bg"]["img"] = '';
                            if (!empty($live_styles['preview']["scheme"]["form"]["bg"]["img-position"])) $live_styles['preview']["scheme"]["form"]["bg"]["img-position"] = '';
                            if (!empty($live_styles['preview']["scheme"]["form"]["bg"]["img-opacity"])) $live_styles['preview']["scheme"]["form"]["bg"]["img-opacity"] = '';
                            if (!empty($live_styles['preview']["scheme"]["form"]["bg"]["img-size"])) $live_styles['preview']["scheme"]["form"]["bg"]["img-size"] = '';
                            if (!empty($live_styles['preview']["scheme"]["checkbox"]["full-width"])) $live_styles['preview']["scheme"]["checkbox"]["full-width"] = '';
                            if (!empty($live_styles['preview']["scheme"]["radiobutton"]["full-width"])) $live_styles['preview']["scheme"]["radiobutton"]["full-width"] = '';
                        }

                        $live_custom_css = Cf7_Style_Scheme::get_inline_style_scheme($live_styles, 'preview', array(), array(), '.live-style ');
                    } else {
                        $live_custom_css = '';
                    }
                }

                if (!empty($temp_styles)) {
                    if (!empty($temp_styles['preview']['scheme'])) {
                        if ('free' === $plugin_version && ('current-style' !== $preview_mode && 'split-mode' !== $preview_mode)) {
                            if (!empty($temp_styles['preview']["scheme"]["form"]["bg"]["img"])) $temp_styles['preview']["scheme"]["form"]["bg"]["img"] = '';
                            if (!empty($temp_styles['preview']["scheme"]["form"]["bg"]["img-position"])) $temp_styles['preview']["scheme"]["form"]["bg"]["img-position"] = '';
                            if (!empty($temp_styles['preview']["scheme"]["form"]["bg"]["img-opacity"])) $temp_styles['preview']["scheme"]["form"]["bg"]["img-opacity"] = '';
                            if (!empty($temp_styles['preview']["scheme"]["form"]["bg"]["img-size"])) $temp_styles['preview']["scheme"]["form"]["bg"]["img-size"] = '';
                            if (!empty($temp_styles['preview']["scheme"]["checkbox"]["full-width"])) $temp_styles['preview']["scheme"]["checkbox"]["full-width"] = '';
                            if (!empty($temp_styles['preview']["scheme"]["radiobutton"]["full-width"])) $temp_styles['preview']["scheme"]["radiobutton"]["full-width"] = '';
                        }
                        if ('split-mode' === $preview_mode) {
                            $custom_css = Cf7_Style_Scheme::get_inline_style_scheme($temp_styles, 'preview', array(), array(), '.current-style ');
                        } else {
                            $custom_css = Cf7_Style_Scheme::get_inline_style_scheme($temp_styles, 'preview', array(), array());
                        }
                    } else {
                        $custom_css = '';
                    }
                } else {
                    if ('free' === $plugin_version) {
                        if (!empty($style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img"])) $style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img"] = '';
                        if (!empty($style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img-position"])) $style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img-position"] = '';
                        if (!empty($style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img-opacity"])) $style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img-opacity"] = '';
                        if (!empty($style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img-size"])) $style_schemes[$style_scheme_slug]["scheme"]["form"]["bg"]["img-size"] = '';
                        if (!empty($style_schemes[$style_scheme_slug]["scheme"]["checkbox"]["full-width"])) $style_schemes[$style_scheme_slug]["scheme"]["checkbox"]["full-width"] = '';
                        if (!empty($style_schemes[$style_scheme_slug]["scheme"]["radiobutton"]["full-width"])) $style_schemes[$style_scheme_slug]["scheme"]["radiobutton"]["full-width"] = '';
                    }

                    $custom_css = Cf7_Style_Scheme::get_inline_style_scheme($style_schemes, $style_scheme_slug);
                }

                wp_add_inline_style( $this->plugin_name, $custom_css );

                if (!empty($live_custom_css)) {
                    wp_add_inline_style( $this->plugin_name, $live_custom_css );
                }
            }

            // delete_option('cf7cstmzr_style_schemes_preview');
        } else { // Frontend use

            $is_body_tag = false;

            if ('free' !== $plugin_version) {
                $is_body_tag = get_option('cf7cstmzr-load-body-tag', false);
            }

            if (empty($is_body_tag)) {
                $enabled_globally = false;
                $enabled_globally_option = get_option('cf7cstmzr_enabled_globally', false);

                if (!empty($enabled_globally_option)) {

                    $enabled_globally = 'default';

                    if ('free' !== $plugin_version) {
                        $enabled_globally = $enabled_globally_option;
                    }
                }

                $globally_styled_forms = Cf7_Style_Scheme::get_globally_styled_forms();
                $individually_styled_forms = Cf7_Style_Scheme::get_individually_styled_forms();
                $style_schemes = get_option('cf7cstmzr_style_schemes', array());

                if ($enabled_globally && 'free' !== $plugin_version) {
                    if ('free' === $plugin_version) {
                        if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img"] = '';
                        if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-position"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-position"] = '';
                        if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-opacity"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-opacity"] = '';
                        if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-size"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-size"] = '';
                        if (!empty($style_schemes[$enabled_globally]["scheme"]["checkbox"]["full-width"])) $style_schemes[$enabled_globally]["scheme"]["checkbox"]["full-width"] = '';
                        if (!empty($style_schemes[$enabled_globally]["scheme"]["radiobutton"]["full-width"])) $style_schemes[$enabled_globally]["scheme"]["radiobutton"]["full-width"] = '';
                    }

                    $custom_css = Cf7_Style_Scheme::get_inline_style_scheme($style_schemes, $enabled_globally, array_keys($globally_styled_forms));

                    wp_add_inline_style( $this->plugin_name, $custom_css );
                }

                // if ('free' !== $plugin_version && !empty($individually_styled_forms)) {
                if (!empty($individually_styled_forms)) {
                    foreach ($individually_styled_forms as $form_id => $individually_style) {
                        if ('free' === $plugin_version) {
                            if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img"] = '';
                            if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-position"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-position"] = '';
                            if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-opacity"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-opacity"] = '';
                            if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-size"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-size"] = '';
                            if (!empty($style_schemes[$individually_style]["scheme"]["checkbox"]["full-width"])) $style_schemes[$individually_style]["scheme"]["checkbox"]["full-width"] = '';
                            if (!empty($style_schemes[$individually_style]["scheme"]["radiobutton"]["full-width"])) $style_schemes[$individually_style]["scheme"]["radiobutton"]["full-width"] = '';
                        }

                        $custom_css = Cf7_Style_Scheme::get_inline_style_scheme($style_schemes, $individually_style, array($form_id));
                        wp_add_inline_style( $this->plugin_name, $custom_css );
                    }
                }
            }
        }
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        $permalink_structure = get_option('permalink_structure');

        if (!empty($permalink_structure)) {
            $form_id = get_query_var( 'cf7cstmzr-form' );
        } else {
            if (!empty($_GET['cf7cstmzr_page']) && !empty($_GET['form_id'])) {
                $form_id = $_GET['form_id'];
            }
        }
        if ($form_id) {
            wp_enqueue_script('cf7cstmzr-split', plugin_dir_url( __FILE__ ) . 'vendors/split.min.js', array('jquery'), '1.5.11');
        }
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cf7-customizer-public.js', array( 'jquery' ), $this->version . time(), true );

		wp_localize_script( $this->plugin_name, 'cf7cstmzrJsObj', array(
            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        ));
	}

	public function add_wrapper($output, $tag, $attr, $m) {
	    if ('contact-form-7' === $tag) {
            $permalink_structure = get_option('permalink_structure');

            if (!empty($permalink_structure)) {
                $form_id = get_query_var( 'cf7cstmzr-form' );
            } else {
                if (!empty($_GET['cf7cstmzr_page']) && !empty($_GET['form_id'])) {
                    $form_id = $_GET['form_id'];
                }
            }
            $plugin_version = Cf7_License::get_license_version();
            $output_before = '';
            $is_body_tag = false;
            $is_form_exists = !empty($attr["id"]) ? get_post($attr["id"]) : null;

            if ('free' !== $plugin_version) {
                $is_body_tag = get_option('cf7cstmzr-load-body-tag', false);
            }

            if ($form_id) {

            } else {
                global $post;

                if ('free' !== $plugin_version) {
                    $is_page_body_tag = get_post_meta( $post->ID, 'cf7cstmzr-load-body-tag', true );

                    if (!empty($is_page_body_tag)) {
                        $is_body_tag = $is_page_body_tag;
                    }
                }

                if ('free' !== $plugin_version && current_user_can('administrator') && (is_single() || is_page())) {
                    wp_enqueue_style( 'dashicons' );
                    $frontend_control_action = did_action('cf7cstmzr_frontend_control');

                    ob_start();

                    if (1 > $frontend_control_action && $is_form_exists) {
                        do_action('cf7cstmzr_frontend_control');

                        ?>
                        <div id="cf7cstmzr_frontend">
                            <div id="cf7cstmzr_frontend_togler">
                                <span class="dashicons dashicons-admin-generic"></span> <?php _e('CF7 Styler', 'cf7-styler') ?>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <label for="cf7cstmzr_frontend_load-body-tag" style="font-size:13px;line-height:1;">
                                    <input type="checkbox" id="cf7cstmzr_frontend_load-body-tag" value="1"<?php echo !empty($is_page_body_tag) ? ' checked' : '' ?>>
                                    <?php _e('Load styles inside <code>&lt;body&gt;</code> tag on this page', 'cf7-styler') ?>
                                </label>
                            </div>

                            <div>
                                <button id="cf7cstmzr_frontend_save" class="cf7cstmzr-button" type="button" data-post="<?php echo $post->ID ?>">
                                    <?php _e('Save', 'cf7-styler') ?>
                                </button>
                            </div>

                            <div>
                                <button id="cf7cstmzr_frontend_close" class="cf7cstmzr-link" type="button">
                                    <?php _e('Close', 'cf7-styler') ?>
                                </button>
                            </div>
                        </div>
                        <?php
                    }

                    if ($is_form_exists) {
                        ?>
                        <div style="text-align: right;">
                            <div id="cf7cstmzr_form_frontend_togler">
                                <span class="dashicons dashicons-admin-generic"></span> <?php _e('CF7 Styler', 'cf7-styler') ?>
                            </div>

                            <a class="cf7cstmzr_form_frontend_link" target="_blank" href="<?php echo get_admin_url(); ?>/admin.php?page=cf7cstmzr_page&tab=form-customize" data-form="<?php echo $attr["id"]; ?>">
                                <span class="dashicons dashicons-edit"></span> <?php _e('Open styler', 'cf7-styler') ?>
                            </a>
                        </div>
                        <?php
                    }

                    $output_before .= ob_get_clean();
                } elseif ('free' === $plugin_version && current_user_can('administrator') && (is_single() || is_page())) {
                    if ($is_form_exists) {
                        ob_start();
                        wp_enqueue_style( 'dashicons' );
                        ?>
                        <div style="text-align: right;">
                            <a class="cf7cstmzr_form_frontend_link" target="_blank" href="<?php echo get_admin_url(); ?>/admin.php?page=cf7cstmzr_page&tab=form-customize" data-form="<?php echo $attr["id"]; ?>">
                                <span class="dashicons dashicons-edit"></span> <?php _e('Open styler', 'cf7-styler') ?>
                            </a>
                        </div>
                        <?php
                        $output_before .= ob_get_clean();
                    }
                }
                if (!empty($is_body_tag)) {
                        $num_action = did_action('cf7cstmzr_load_body_tag');

                    if (1 > $num_action) {
                        do_action('cf7cstmzr_load_body_tag');

                        $enabled_globally = false;
                        $enabled_globally_option = get_option('cf7cstmzr_enabled_globally', false);

                        if (!empty($enabled_globally_option)) {

                            $enabled_globally = 'default';

                            if ('free' !== $plugin_version) {
                                $enabled_globally = $enabled_globally_option;
                            }
                        }
                        $globally_styled_forms = Cf7_Style_Scheme::get_globally_styled_forms();
                        $individually_styled_forms = Cf7_Style_Scheme::get_individually_styled_forms();

                        $style_schemes = get_option('cf7cstmzr_style_schemes', array());

                        if ($enabled_globally && 'free' !== $plugin_version) {
                            if ('free' === $plugin_version) {
                                if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img"] = '';
                                if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-position"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-position"] = '';
                                if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-opacity"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-opacity"] = '';
                                if (!empty($style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-size"])) $style_schemes[$enabled_globally]["scheme"]["form"]["bg"]["img-size"] = '';
                                if (!empty($style_schemes[$enabled_globally]["scheme"]["checkbox"]["full-width"])) $style_schemes[$enabled_globally]["scheme"]["checkbox"]["full-width"] = '';
                                if (!empty($style_schemes[$enabled_globally]["scheme"]["radiobutton"]["full-width"])) $style_schemes[$enabled_globally]["scheme"]["radiobutton"]["full-width"] = '';
                            }

                            $custom_css = '<style>'.Cf7_Style_Scheme::get_inline_style_scheme($style_schemes, $enabled_globally, array_keys($globally_styled_forms)).'</style>';
                            $output_before .= $custom_css;
                        }


                        // if ('free' !== $plugin_version && !empty($individually_styled_forms)) {
                        if (!empty($individually_styled_forms)) {
                            foreach ($individually_styled_forms as $form_id => $individually_style) {
                                if ('free' === $plugin_version) {
                                    if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img"] = '';
                                    if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-position"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-position"] = '';
                                    if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-opacity"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-opacity"] = '';
                                    if (!empty($style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-size"])) $style_schemes[$individually_style]["scheme"]["form"]["bg"]["img-size"] = '';
                                    if (!empty($style_schemes[$individually_style]["scheme"]["checkbox"]["full-width"])) $style_schemes[$individually_style]["scheme"]["checkbox"]["full-width"] = '';
                                    if (!empty($style_schemes[$individually_style]["scheme"]["radiobutton"]["full-width"])) $style_schemes[$individually_style]["scheme"]["radiobutton"]["full-width"] = '';
                                }

                                $custom_css = '<style>'.Cf7_Style_Scheme::get_inline_style_scheme($style_schemes, $individually_style, array($form_id)).'</style>';
                                $output_before .= $custom_css;
                            }
                        }
                    }
                }
            }

            $output_after = '';

            if ($is_form_exists) {
                $output_before .= '<div id="cf7cstmzr-form" class="cf7cstmzr-form-'.$attr["id"].' cf7cstmzr-form">';
                $output_after .= '</div>';
            }

	        $output = $output_before . $output . $output_after;
        }

	    return $output;
    }

    public function save_frontend_settings() {
        $loadBodyTag = !empty($_POST['loadBodyTag']) ? sanitize_text_field($_POST['loadBodyTag']) : false;
        $postId = !empty($_POST['postId']) ? sanitize_text_field($_POST['postId']) : false;

        if (empty($postId)) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('You can not use this settings on this page type', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        if ($loadBodyTag && 'true' === $loadBodyTag) {
            update_post_meta( $postId, 'cf7cstmzr-load-body-tag', 1 );
        } else {
            delete_post_meta( $postId, 'cf7cstmzr-load-body-tag' );
        }

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . __('Saved', 'cf7-styler'));

        echo json_encode($response);
        wp_die();
    }
}
