<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       saleswonder.biz
 * @since      1.0.0
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/includes
 * @author     Tobias Conrad <tc@saleswonder.biz>
 */
class Cf7_Customizer {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Cf7_Customizer_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'CF7_CUSTOMIZER_VERSION' ) ) {
			$this->version = CF7_CUSTOMIZER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'cf7-styler';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Cf7_Customizer_Loader. Orchestrates the hooks of the plugin.
	 * - Cf7_Customizer_i18n. Defines internationalization functionality.
	 * - Cf7_Customizer_Admin. Defines all hooks for the admin area.
	 * - Cf7_Customizer_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cf7-customizer-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cf7-customizer-i18n.php';

        /**
         * Libraries
         */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/lib/Cf7_Required_Plugin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/lib/Cf7_Style_Scheme.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/lib/Cf7_License.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/lib/Cf7_Template.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cf7-customizer-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cf7-customizer-admin-ajax.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-cf7-customizer-public.php';

		$this->loader = new Cf7_Customizer_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Cf7_Customizer_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Cf7_Customizer_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Cf7_Customizer_Admin( $this->plugin_name, $this->version );
		$plugin_admin_ajax = new Cf7_Customizer_Admin_Ajax( $this->plugin_name, $this->version );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'settings_page' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'plugin_menu_optin', 50 );
		$this->loader->add_action( 'init', $plugin_admin, 'rewrite_rule' );
		$this->loader->add_action( 'init', $plugin_admin, 'check_installation' );
		$this->loader->add_action( 'init', $plugin_admin, 'check_version' );
		$this->loader->add_action( 'template_redirect', $plugin_admin, 'template_redirect' );

        $this->loader->add_filter('show_admin_bar', $plugin_admin, 'show_admin_bar');
        $this->loader->add_filter('admin_body_class', $plugin_admin, 'admin_body_class');

        $this->loader->add_action('wp_ajax_cf7cstmzr_save_form_customizer_settings', $plugin_admin_ajax, 'save_form_customizer_settings');
        $this->loader->add_action('wp_ajax_cf7cstmzr_new_form_customizer_settings', $plugin_admin_ajax, 'new_form_customizer_settings');
        $this->loader->add_action('wp_ajax_cf7cstmzr_delete_form_customizer_settings', $plugin_admin_ajax, 'delete_form_customizer_settings');
        $this->loader->add_action('wp_ajax_cf7cstmzr_enable_globally', $plugin_admin_ajax, 'enable_globally');
        $this->loader->add_action('wp_ajax_cf7cstmzr_disable_globally', $plugin_admin_ajax, 'disable_globally');
        $this->loader->add_action('wp_ajax_cf7cstmzr_enable_for_form', $plugin_admin_ajax, 'enable_for_form');
        $this->loader->add_action('wp_ajax_cf7cstmzr_disable_for_form', $plugin_admin_ajax, 'disable_for_form');
        $this->loader->add_action('wp_ajax_cf7cstmzr_change_form_preview', $plugin_admin_ajax, 'change_form_preview');
        $this->loader->add_action('wp_ajax_cf7cstmzr_preview_form_customizer_settings', $plugin_admin_ajax, 'preview_form_customizer_settings');
        $this->loader->add_action('wp_ajax_cf7cstmzr_load_body_tag', $plugin_admin_ajax, 'load_body_tag');
        $this->loader->add_action('wp_ajax_cf7cstmzr_cache_form', $plugin_admin_ajax, 'cache_form');
        $this->loader->add_action('wp_ajax_nopriv_cf7cstmzr_cache_form', $plugin_admin_ajax, 'cache_form');
        $this->loader->add_action('wp_ajax_cf7cstmzr_install_plugin', $plugin_admin_ajax, 'install_plugin');
        $this->loader->add_action('wp_ajax_cf7cstmzr_close_welcome', $plugin_admin_ajax, 'close_welcome');

        $this->loader->add_filter('wpcf7_editor_panels', $plugin_admin, 'add_cf7_metabox');
        $this->loader->add_action('save_post_wpcf7_contact_form', $plugin_admin, 'save_cf7_metabox', 15, 3);

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Cf7_Customizer_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_filter( 'do_shortcode_tag', $plugin_public, 'add_wrapper', 9999, 4 );

        $this->loader->add_action('wp_ajax_cf7cstmzr_frontend_save', $plugin_public, 'save_frontend_settings');
        $this->loader->add_action('wp_ajax_nopriv_cf7cstmzr_frontend_save', $plugin_public, 'save_frontend_settings');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Cf7_Customizer_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
