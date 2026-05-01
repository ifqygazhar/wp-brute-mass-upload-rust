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
class Cf7_Customizer_Admin_Ajax {

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

	public function save_form_customizer_settings() {
        $plugin_version = Cf7_License::get_license_version();
	    $style_schemes = get_option('cf7cstmzr_style_schemes', array());

	    if (empty($style_schemes)) {
	        $create_default = true;
        }

	    $style_scheme_slug = !empty($_POST['styleSchemeSlug']) ? sanitize_text_field($_POST['styleSchemeSlug']) : 'default';
	    $style_scheme_title = !empty($_POST['styleSchemeTitle']) ? sanitize_text_field($_POST['styleSchemeTitle']) : __('Default Scheme', 'cf7-styler');

	    $form_data_array = array();

        if (!empty($_POST['formData'])) {
            foreach ($_POST['formData'] as $form_data) {
                $name = str_replace('cf7cstmzr_', '', sanitize_text_field($form_data['name']));
                $forbidden_names = array();

                if ('free' === $plugin_version) {
                    $forbidden_names = array(
                        'form_bg_img',
                        'form_bg_img-position',
                        'form_bg_img-opacity',
                        'form_bg_img-size',
                        'checkbox_full-width',
                        'radiobutton_full-width',
                    );
                }

                if (in_array($name, $forbidden_names)) {
                    $value = '';
                } elseif ('custom_css' === $name) {
                    $value = sanitize_textarea_field($form_data['value']);
                } else {
                    $value = sanitize_text_field($form_data['value']);
                }

                if (!empty($value)) {
                    $name_array = explode('_', $name);

                    if (!empty($name_array[4])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]][$name_array[3]][$name_array[4]] = $value;
                    } elseif (!empty($name_array[3])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]][$name_array[3]] = $value;
                    } elseif (!empty($name_array[2])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]] = $value;
                    } elseif (!empty($name_array[1])) {
                        $form_data_array[$name_array[0]][$name_array[1]] = $value;
                    }
                }
            }
        }

        $style_scheme = array(
            'title' => $style_scheme_title,
            'scheme' => $form_data_array,
        );

        $style_schemes[$style_scheme_slug] = $style_scheme;

        update_option('cf7cstmzr_style_schemes', $style_schemes);

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . __('Saved', 'cf7-styler'));

        if (!empty($create_default)) {
            $response['url'] = get_site_url() . '/wp-admin/admin.php?page=cf7cstmzr_page&tab=form-customize';
        }

        echo json_encode($response);
        wp_die();
    }

    public function new_form_customizer_settings() {
	    $title = !empty(sanitize_text_field($_POST['title'])) ? sanitize_text_field($_POST['title']) : false;

	    if (!$title) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('Please input style scheme title', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

	    if (!empty($_POST['copySettings']) && 'true' === $_POST['copySettings']) {
	        $copySettings = true;
        } else {
            $copySettings = false;
        }

        $plugin_version = Cf7_License::get_license_version();
        $style_schemes = get_option('cf7cstmzr_style_schemes', array());
        $style_scheme_slug = 'cf7cstmzr_' . time();
        $style_scheme_title = $title;

        $form_data_array = array();

        if (!empty($_POST['formData'])) {
            foreach ($_POST['formData'] as $form_data) {
                $name = str_replace('cf7cstmzr_', '', sanitize_text_field($form_data['name']));

                if ($copySettings) {
                    $forbidden_names = array();

                    if ('free' === $plugin_version) {
                        $forbidden_names = array(
                            'form_bg_img',
                            'form_bg_img-position',
                            'form_bg_img-opacity',
                            'form_bg_img-size',
                            'checkbox_full-width',
                            'radiobutton_full-width',
                        );
                    }

                    if (in_array($name, $forbidden_names)) {
                        $value = '';
                    } elseif ('custom_css' === $name) {
                        $value = sanitize_textarea_field($form_data['value']);
                    } else {
                        $value = sanitize_text_field($form_data['value']);
                    }
                } else {
                    $value = '';
                }

                if (!empty($value)) {
                    $name_array = explode('_', $name);

                    if (!empty($name_array[4])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]][$name_array[3]][$name_array[4]] = $value;
                    } elseif (!empty($name_array[3])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]][$name_array[3]] = $value;
                    } elseif (!empty($name_array[2])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]] = $value;
                    } elseif (!empty($name_array[1])) {
                        $form_data_array[$name_array[0]][$name_array[1]] = $value;
                    }
                }
            }
        }

        $style_scheme = array(
            'title' => $style_scheme_title,
            'scheme' => $form_data_array,
        );

        $isFw = !empty($_POST['isFw']) && 'true' === $_POST['isFw'] ? '&fw=1' : '';

        $style_schemes[$style_scheme_slug] = $style_scheme;

        update_option('cf7cstmzr_style_schemes', $style_schemes);

        $response = array('success' => 1,
                          'error' => 0,
                          'message' => __('Success', 'cf7-styler') . ': ' . __('Settings saved as ', 'cf7-styler') . $title,
                          'url' => get_site_url() . '/wp-admin/admin.php?page=cf7cstmzr_page&tab=form-customize&style_scheme=' . $style_scheme_slug . $isFw);

        echo json_encode($response);
        wp_die();
    }

    public function close_welcome() {
        update_option('cf7cstmzr_welcome_done', '1');

        $response = array('success' => 1, 'error' => 0, 'url' => get_site_url() . '/wp-admin/admin.php?page=cf7cstmzr_page');

        echo json_encode($response);
        wp_die();
    }

    public function delete_form_customizer_settings() {
        $scheme = !empty(sanitize_text_field($_POST['scheme'])) ? sanitize_text_field($_POST['scheme']) : false;

        if (!$scheme) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('No style scheme selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        if ('default' === $scheme) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('You can not delete', 'cf7-styler') . ' ' . __('Default Scheme', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        $style_schemes = get_option('cf7cstmzr_style_schemes', array());

        if (!empty($style_schemes[$scheme])) {
            $title = $style_schemes[$scheme]['title'];
            unset ($style_schemes[$scheme]);

            update_option('cf7cstmzr_style_schemes', $style_schemes);

            $enabled_globally = get_option('cf7cstmzr_enabled_globally', '');

            if ($enabled_globally === $scheme) {
                delete_option('cf7cstmzr_enabled_globally');
            }

            $cf7_scheme_args = array (
                'numberposts' => -1,
                'orderby'     => 'title',
                'order'       => 'ASC',
                'post_type'   => 'wpcf7_contact_form',
                'post_status'   => 'publish',
                'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
                'meta_query' => array(
                    'relation' => 'LIKE',
                    array(
                        'key' => 'cf7cstmzr_style_scheme',
                        'value' => $scheme,
                    )
                )
            );

            $cf7_scheme_forms = get_posts($cf7_scheme_args);

            if (!empty($cf7_scheme_forms)) {
                foreach ($cf7_scheme_forms as $form) {
                    delete_post_meta( $form->ID, 'cf7cstmzr_style_scheme' );
                }
            }

            $isFw = !empty($_POST['isFw']) && 'true' === $_POST['isFw'] ? '&fw=1' : '';

            $response = array(
                'success' => 1,
                'error' => 0,
                'message' => __('Success', 'cf7-styler') . ': ' . $title . ' ' . __('deleted', 'cf7-styler'),
                'url' => get_site_url() . '/wp-admin/admin.php?page=cf7cstmzr_page&tab=form-customize' . $isFw
            );

            echo json_encode($response);
            wp_die();
        }
    }

    public function load_body_tag() {
        if (empty($_POST['loadBody']) || 'true' === sanitize_text_field($_POST['loadBody']) ) {
            update_option('cf7cstmzr-load-body-tag', 1);
            $confirm = __('Style will be loaded in <body> tag', 'cf7-styler');
        } else {
            update_option('cf7cstmzr-load-body-tag', 0);
            $confirm = __('Style will be loaded in <head> tag', 'cf7-styler');
        }

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . $confirm );

        echo json_encode($response);
        wp_die();
    }

    public function preview_form_customizer_settings() {
        delete_option('cf7cstmzr_style_schemes_preview');
        $style_schemes = array();
        $style_scheme_slug = 'preview';
        $style_scheme_title = 'preview';

        $form_data_array = array();

        if (empty($_POST['unstyle']) || 'true' === sanitize_text_field($_POST['unstyle']) ) {
            update_option('cf7cstmzr-preview-styled', 0);
        } else {
            update_option('cf7cstmzr-preview-styled', 1);
        }

        if (!empty($_POST['previewMode']) ) {
            update_option('cf7cstmzr-preview-mode', sanitize_text_field($_POST['previewMode']));
        }

        if (!empty($_POST['splitModeValue']) ) {
            update_option('cf7cstmzr-split-mode', sanitize_text_field($_POST['splitModeValue']));
        }

        if (!empty($_POST['formData'])) {
            foreach ($_POST['formData'] as $form_data) {
                $name = str_replace('cf7cstmzr_', '', sanitize_text_field($form_data['name']));

                if ('custom_css' === $name) {
                    $value = sanitize_textarea_field($form_data['value']);
                } else {
                    $value = sanitize_text_field($form_data['value']);
                }

                if (!empty($value)) {
                    $name_array = explode('_', $name);

                    if (!empty($name_array[4])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]][$name_array[3]][$name_array[4]] = $value;
                    } elseif (!empty($name_array[3])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]][$name_array[3]] = $value;
                    } elseif (!empty($name_array[2])) {
                        $form_data_array[$name_array[0]][$name_array[1]][$name_array[2]] = $value;
                    } elseif (!empty($name_array[1])) {
                        $form_data_array[$name_array[0]][$name_array[1]] = $value;
                    }
                }
            }
        }

        $style_scheme = array(
            'title' => $style_scheme_title,
            'scheme' => $form_data_array,
        );

        $style_schemes[$style_scheme_slug] = $style_scheme;

        update_option('cf7cstmzr_style_schemes_preview', $style_schemes);

        $response = array('success' => 1, 'error' => 0);

        echo json_encode($response);
        wp_die();
    }

    public function enable_globally() {
        $scheme = !empty($_POST['scheme']) ? sanitize_text_field($_POST['scheme']) : false;

        if (!$scheme) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('Style scheme is not selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        $style_schemes = get_option('cf7cstmzr_style_schemes', array());

        if (empty($style_schemes[$scheme])) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Success', 'cf7-styler') . ': ' . __('This scheme is not existed', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        update_option('cf7cstmzr_enabled_globally', $scheme);

        $title = $style_schemes[$scheme]['title'];

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . $title . ' ' . __('enabled for all forms', 'cf7-styler'));

        echo json_encode($response);
        wp_die();
    }

    public function disable_globally() {
        delete_option('cf7cstmzr_enabled_globally');

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . __('Style scheme disabled for all forms', 'cf7-styler'));

        echo json_encode($response);
        wp_die();
    }

    public function cache_form() {
        $form = !empty($_POST['form']) ? sanitize_text_field($_POST['form']) : false;

        if (!$form) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('Form is not selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        update_option( 'cf7cstmzr_cache_form',  $form);

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . __('Style scheme enabled for this form', 'cf7-styler'));

        echo json_encode($response);
        wp_die();
    }

    public function enable_for_form() {
        $plugin_version = Cf7_License::get_license_version();
        $scheme = !empty($_POST['scheme']) ? sanitize_text_field($_POST['scheme']) : false;

        if (!$scheme) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('Style scheme is not selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        $form = !empty($_POST['form']) ? sanitize_text_field($_POST['form']) : false;

        if (!$form) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('Form is not selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        if ('free' === $plugin_version) {
            global $wpdb;

            $sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key='cf7cstmzr_style_scheme';";

            $wpdb->query($sql);
        }

        update_post_meta( $form, 'cf7cstmzr_style_scheme', $scheme );

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . __('Style scheme enabled for this form', 'cf7-styler'));

        echo json_encode($response);
        wp_die();
    }

    public function disable_for_form() {
        $form = !empty($_POST['form']) ? sanitize_text_field($_POST['form']) : false;

        if (!$form) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('Form is not selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

        delete_post_meta( $form, 'cf7cstmzr_style_scheme' );

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'cf7-styler') . ': ' . __('Style scheme disabled for this form', 'cf7-styler'));

        echo json_encode($response);
        wp_die();
    }

    public function change_form_preview() {
	    $form_id = !empty($_POST['formId']) ? sanitize_text_field($_POST['formId']) : false;

	    if (!$form_id) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('CF7 Form is not selected', 'cf7-styler'));

            echo json_encode($response);
            wp_die();
        }

	    $form_preview = Cf7_Style_Scheme::get_form_preview($form_id);

	    $content = wp_remote_get('http://cf7-customizer.loc/cf7cstmzr-form-customizer/' . $form_id);

        $fragments = array (
            '#form-preview-container' => $form_preview,
        );

        // $response = array('success' => 1, 'error' => 0, 'fragments' => $fragments);
        $response = array(
            'success' => 1,
            'error' => 0,
            'src' => 'http://cf7-customizer.loc/cf7cstmzr-form-customizer/' . $form_id,
            'content' => $content['body'],
        );

        echo json_encode($response);
        wp_die();
    }

    public function install_plugin() {
        $plugin = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';

        if (empty($plugin)) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'cf7-styler') . ': ' . __('No plugin selected to install', 'cf7-styler'));
            echo json_encode($response);

            wp_die();
        }

        $required_plugins = Cf7_Required_Plugin::get_required_plugins();

        if (empty($required_plugins[$plugin])) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'wp2leads-wtsr') . ': ' . __('Plugin you are trying to install is not in the required list', 'wp2leads-wtsr'));
            echo json_encode($response);

            wp_die();
        }

        $result = Cf7_Required_Plugin::install_and_activate_plugin($plugin);

        if (!$result) {
            $response = array('success' => 0, 'error' => 1, 'message' => __('Error', 'wp2leads-wtsr') . ': ' . __('Plugin could not be installed manually', 'wp2leads-wtsr'));

            echo 'error';
            echo '&&&&&';
            echo json_encode($response);
            wp_die();
        }

        $plugin_name = $required_plugins[$plugin]['label'];

        $response = array('success' => 1, 'error' => 0, 'message' => __('Success', 'wp2leads-wtsr') . ': ' . $plugin_name . __(' installed and activated', 'wp2leads-wtsr'));

        echo 'success';
        echo '&&&&&';
        echo json_encode($response);
        wp_die();
    }
}
