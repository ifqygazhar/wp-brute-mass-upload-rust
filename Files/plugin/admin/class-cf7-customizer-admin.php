<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       saleswonder.biz
 * @since      1.0.0
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/admin
 * @author     Tobias Conrad <tc@saleswonder.biz>
 */
class Cf7_Customizer_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
        if(isset($_GET['page']) && sanitize_text_field($_GET['page']) == 'cf7cstmzr_page') {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'codemirror', plugin_dir_url( __FILE__ ) . 'vendors/codemirror.css', array(), $this->version . time(), 'all' );
            wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cf7-customizer-admin.css', array(), $this->version . time(), 'all' );
        }
        wp_enqueue_style( $this->plugin_name . '-global', plugin_dir_url( __FILE__ ) . 'css/cf7-customizer-admin-global.css', array(), $this->version . time(), 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        if(isset($_GET['page']) && (sanitize_text_field($_GET['page']) == 'cf7cstmzr_page' || sanitize_text_field($_GET['page']) == 'cf7cstmzr_tutorial_page')) {
            wp_enqueue_code_editor(array('type' => 'text/css'));

            if ( ! did_action( 'wp_enqueue_media' ) ) {
                wp_enqueue_media();
            }

            wp_enqueue_script( 'codemirror', plugin_dir_url( __FILE__ ) . 'vendors/codemirror.js', array( 'jquery' ), '5.49.2', true );
            wp_enqueue_script( 'jRespond', plugin_dir_url( __FILE__ ) . 'js/jRespond.js', array( 'jquery' ), '0.10', true );
            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cf7-customizer-admin.js', array( 'jquery', 'jquery-ui-core', 'wp-color-picker' ), $this->version . time(), true );
        }
	}

	public function settings_page() {
        $is_welcome_done = get_option('cf7cstmzr_welcome_done');
        if (!cf7cstmzr_is_plugin_activated( 'contact-form-7', 'wp-contact-form-7.php' )) {
            add_menu_page(
                __( 'CF7 Styler', 'cf7-styler' ),
                __( 'CF7 Styler', 'cf7-styler' ),
                'administrator',
                'cf7cstmzr_page',
                array($this, 'render_settings_page'),
                'dashicons-email-alt',
                6
            );
            if (!empty($is_welcome_done)) {
                add_submenu_page(
                    'cf7cstmzr_page',
                    __( 'Support & KB', 'cf7-styler' ),
                    __( 'Support & KB', 'cf7-styler' ),
                    'administrator',
                    'cf7cstmzrcf7cstmzr_tutorial_page',
                    array($this, 'render_tutorials_page')
                );
            }
        } else {
            add_submenu_page(
                'wpcf7',
                __( 'CF7 Styler', 'cf7-styler' ),
                __( 'CF7 Styler', 'cf7-styler' ),
                'administrator',
                'cf7cstmzr_page',
                array($this, 'render_settings_page')
            );
            if (!empty($is_welcome_done)) {
                add_submenu_page(
                    'wpcf7',
                    __( 'Support & KB', 'cf7-styler' ),
                    '<span class="cf7cstmzr-submenu-item">'.__( 'Support & KB', 'cf7-styler' ).'</span>',
                    'administrator',
                    'cf7cstmzr_tutorial_page',
                    array($this, 'render_settings_page')
                );
            }
        }
    }

    public function render_settings_page() {
        $is_welcome_done = get_option('cf7cstmzr_welcome_done');

	    if (!empty($_GET['page']) && ('cf7cstmzr_tutorial_page' === sanitize_text_field($_GET['page']) || empty($is_welcome_done))) {
            include_once CF7CSTMZR_PLUGIN_PATH . 'admin/partials/cf7-customizer-admin-tutorial.php';
        } else {
            include_once CF7CSTMZR_PLUGIN_PATH . 'admin/partials/cf7-customizer-admin-display.php';
        }
    }

    public function check_installation() {
        $style_schemes = get_option('cf7cstmzr_style_schemes', array());

        if (empty($style_schemes)) {
            $default_scheme = array(
                'default' => Cf7_Style_Scheme::get_default_style_scheme()
            );

            update_option('cf7cstmzr_style_schemes', $default_scheme);
        }
    }

    public function rewrite_rule() {
        flush_rewrite_rules();

        add_rewrite_rule('^cf7cstmzr-form-customizer/(.*)/?', 'index.php?cf7cstmzr-form=$matches[1]', 'top');
        add_rewrite_tag( '%cf7cstmzr-form%', '([^&]+)' );
    }

    public function check_version() {
        $plugin_version = Cf7_License::get_license_version();

        if ('free' === $plugin_version) {
            delete_option('cf7cstmzr_enabled_globally');
            $individually_styled_forms = Cf7_Style_Scheme::get_individually_styled_forms();
        }
    }

    public function template_redirect() {
        $permalink_structure = get_option('permalink_structure');

        if (!empty($permalink_structure)) {
            $form_id = get_query_var( 'cf7cstmzr-form' );
        } else {
            if (!empty($_GET['cf7cstmzr_page']) && !empty($_GET['form_id'])) {
                $form_id = $_GET['form_id'];
            }
        }

	    if ($form_id) {
	        $form_post = get_post($form_id);

	        if (!$form_post || 'publish' !== $form_post->post_status) {
                die;
            }

            global $content_width;

            if (empty($content_width)) {
                $content_width = 640;
            }

            $content_width = $content_width . 'px';

            $content_width = '90%';

            $form = get_post($form_id);

            get_header();

            if (!empty($form)) {
                $preview_mode = get_option('cf7cstmzr-preview-mode', false);
                $is_split_mode = $preview_mode === 'split-mode';
                ?>
                <div id="style-preview-container" style="max-width: <?php echo $content_width ?>; margin: auto; padding: 20px;">
                    <?php
                    if ($is_split_mode) {
                        $split_mode = get_option('cf7cstmzr-split-mode', 'live-style');
                        ?>
                        <style>
                            #style-preview-container {
                                width: 90%!important;
                            }
                            @media screen and (min-width: 599px) {
                                #style-preview-container {
                                    width: 80%!important;
                                }
                            }
                            @media screen and (min-width: 961px) {
                                #style-preview-container {
                                    width: 70%!important;
                                }
                            }
                            @media screen and (min-width: 1200px) {
                                #style-preview-container {
                                    width: 60%!important;
                                }
                            }
                            #split-container:after {
                                display: table;
                                content: ' ';
                                clear: both;
                            }
                            .split, .gutter.gutter-horizontal {
                                float: left;
                            }
                            .gutter.gutter-horizontal {
                                margin-right: 2px;
                                margin-left: 2px;
                                cursor: ew-resize;
                                background-color: #ddd;
                                border-top: 1px solid #eee;
                                border-right: 1px solid #ccc;
                                border-bottom: 1px solid #ccc;
                                border-left: 1px solid #eee;
                                background-image: url("<?php echo CF7CSTMZR_PLUGIN_URL . '/public/vendors/vertical.png'; ?>");
                                background-size: contain;
                                background-repeat: no-repeat!important;
                                background-position: center center!important;
                            }
                            .float-split .split {
                                overflow-y: auto;
                                overflow-x: hidden;
                            }

                            .float-split .split .split-inner {
                                float: left;
                            }

                            .float-split .split:last-child .split-inner {
                                float: right;
                            }
                            .scroll-split .split {
                                overflow-y: auto;
                                overflow-x: scroll;
                            }
                        </style>
                        <div id="split-container" class="float-split" style="display:flex">
                            <div class="split current-style" id="split-left">
                                <div class="split-inner">
                                    <?php echo do_shortcode('[contact-form-7 id="'.$form_id.'" title="' . $form->post_title . '"]'); ?>
                                </div>
                            </div>
                            <div class="split <?php echo $split_mode; ?>" id="split-right">
                                <div class="split-inner">
                                    <?php echo do_shortcode('[contact-form-7 id="'.$form_id.'" title="' . $form->post_title . '"]'); ?>
                                </div>
                            </div>
                        </div>

                        <script>
                            (function($){
                                var sizes = localStorage.getItem('split-sizes')

                                if (sizes) {
                                    sizes = JSON.parse(sizes)
                                } else {
                                    sizes = [50, 50] // default sizes
                                }

                                var splitInstance;
                                var height = 0;

                                $(document).ready(function () {
                                    recalculateSplit();
                                    splitInstance = Split(['#split-left', '#split-right'], {
                                        sizes: sizes,
                                        minSize: 0,
                                        gutter: function (index, direction) {
                                            var gutter = document.createElement('div');
                                            gutter.className = 'gutter gutter-' + direction + ' logo-gutter';
                                            gutter.style.height = height + 'px';
                                            return gutter
                                        },
                                        onDragEnd: function(sizes) {
                                            localStorage.setItem('split-sizes', JSON.stringify(sizes))
                                        },
                                        gutterSize: 10
                                    });

                                    setTimeout(function () {
                                        recalculateGutterHeight();
                                    }, 100);
                                });

                                $(window).on( 'resize', function() {
                                    recalculateSplit();

                                    setTimeout(function () {
                                        recalculateGutterHeight();
                                    }, 100);
                                } );

                                function recalculateGutterHeight() {
                                    var splitInner = $('.split-inner');

                                    if (splitInner.length) {
                                        height = 0;
                                        splitInner.each(function() {
                                            var splitHeight = $(this).outerHeight();

                                            if (height < splitHeight) {
                                                height = splitHeight;
                                            }
                                        });
                                    }

                                    $('#style-preview-container .gutter-horizontal').outerHeight(height);
                                }

                                function recalculateSplit() {
                                    var form = $('.cf7cstmzr-form');
                                    var split = $('.split');
                                    var splitInner = $('.split-inner');
                                    var splitContainer = $('#split-container');
                                    var stylePreviewContainer = $('#style-preview-container');
                                    var width = stylePreviewContainer.width();

                                    if (splitInner.length) {
                                        splitInner.each(function() {
                                            $(this).outerWidth(width - 16);
                                        });
                                    }
                                }
                            })(jQuery);
                        </script>
                        <?php
                    } else {
                        ?>
                        <style>
                            #style-preview-container {
                                width: 90%!important;
                            }
                            @media screen and (min-width: 599px) {
                                #style-preview-container {
                                    width: 80%!important;
                                }
                            }
                            @media screen and (min-width: 961px) {
                                #style-preview-container {
                                    width: 70%!important;
                                }
                            }
                            @media screen and (min-width: 1200px) {
                                #style-preview-container {
                                    width: 60%!important;
                                }
                            }
                        </style>
                        <?php
                        echo do_shortcode('[contact-form-7 id="'.$form_id.'" title="' . $form->post_title . '"]');
                    }
                    ?>
                </div>
                <?php
            }

            get_footer();
	        die;
        }
    }

    public function show_admin_bar($show_admin_bar) {
        $permalink_structure = get_option('permalink_structure');

        if (!empty($permalink_structure)) {
            $form_id = get_query_var( 'cf7cstmzr-form' );
        } else {
            if (!empty($_GET['cf7cstmzr_page']) && !empty($_GET['form_id'])) {
                $form_id = $_GET['form_id'];
            }
        }

        if ($form_id) {
            return false;
        }

        return $show_admin_bar;
    }

    public function admin_body_class($classes) {
        if(isset($_GET['page']) && sanitize_text_field($_GET['page']) == 'cf7cstmzr_page') {
            $classes = $classes . ' cf7cstmzr-body';

            if (!empty($_GET["fw"])) {
                $classes = $classes . ' my-body-noscroll-class';
            }
        }

	    return $classes;
    }

    public function add_cf7_metabox($panels) {
        $plugin_version = Cf7_License::get_license_version();

        $panels['cf7cstmzr-style-scheme'] = array(
            'title' => __('CF7 Styler', 'cf7-styler'),
            'callback' => array($this, 'show_metabox')
        );

        return $panels;
    }

    public function show_metabox($contact_form) {
        $plugin_version = Cf7_License::get_license_version();
        $style_schemes = get_option('cf7cstmzr_style_schemes', array());
        $form_id = $contact_form->id();
        $selected_style = get_post_meta($form_id, 'cf7cstmzr_style_scheme', true);
        $individually_styled_forms = Cf7_Style_Scheme::get_individually_styled_forms();

        if ('free' === $plugin_version && !empty($individually_styled_forms)) {
            foreach ($individually_styled_forms as $styled_form_id => $styled_form_style) {
                $styled_form = get_post($styled_form_id);

                if (!empty($styled_form)) {
                    $styled_form_title = $styled_form->post_title;
                    $styled_form_style_title = $style_schemes[$styled_form_style]['title'];
                }

            }
        }

        if (!empty($style_schemes)) {
            ?>
            <h2>
                <?php echo __('Select style scheme for this form', 'cf7-styler') ?>
            </h2>

            <select name="cf7cstmzr_style_scheme" id="cf7cstmzr_style_scheme" class="large-text">
                <option value=""><?php echo __('- disable style scheme -', 'cf7-styler') ?></option>
                <?php
                foreach ($style_schemes as $slug => $scheme) {
                    if ('free' === $plugin_version && 'default' !== $slug) {
                        continue;
                    }
                    ?>
                    <option value="<?php echo $slug ?>"<?php echo $slug === $selected_style ? ' selected' : ''; ?>><?php echo $scheme['title'] ?></option>
                    <?php
                }
                ?>
            </select>

            <?php
            if ('free' === $plugin_version && empty($selected_style) && !empty($styled_form_title)) {
                ?>
                <p>
                    <?php
                    echo sprintf( __( 'Currently <strong>%s</strong> form is styled with <strong>%s</strong>. As in free version you can style only one form at a time and if you activate style for current form, style will be removed from other form.', 'cf7-styler' ), $styled_form_title, $styled_form_style_title );
                    ?>
                </p>
                <?php
            }
            ?>
            <a class="button button-primary" target="_blank" href="<?php echo get_admin_url(); ?>/admin.php?page=cf7cstmzr_page&tab=form-customize">
                <?php _e('Open styler', 'cf7-styler') ?>
            </a>
            <?php
        }
    }

    public function save_cf7_metabox($post_ID, $post, $update) {
        $plugin_version = Cf7_License::get_license_version();

        $cf7cstmzr_style_scheme = !empty($_POST['cf7cstmzr_style_scheme']) ? sanitize_text_field($_POST['cf7cstmzr_style_scheme']) : false;

        if (!empty($cf7cstmzr_style_scheme)) {
            if ('free' === $plugin_version) {
                global $wpdb;

                $sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key='cf7cstmzr_style_scheme';";

                $wpdb->query($sql);
            }

            update_post_meta( $post_ID, 'cf7cstmzr_style_scheme', $cf7cstmzr_style_scheme );
        } else {
            delete_post_meta($post_ID, 'cf7cstmzr_style_scheme');
        }
    }

    public function plugin_menu_optin() {
        global $submenu;

        if (function_exists('cf7_styler')) {
            $reconnect_url = cf7_styler()->get_activation_url( array(
                'nonce'     => wp_create_nonce( cf7_styler()->get_unique_affix() . '_reconnect' ),
                'fs_action' => ( cf7_styler()->get_unique_affix() . '_reconnect' ),
            ) );

            $is_registered = cf7_styler()->is_registered();

            if (!$is_registered && isset($submenu["wpcf7"])) {
                $submenu["wpcf7"][] = array(
                    '<span class="cf7cstmzr-submenu-item">' . __('Opt-in to see account', 'cf7-styler') . '</span>',
                    'manage_options',
                    $reconnect_url
                );
            }
        }
    }
}
