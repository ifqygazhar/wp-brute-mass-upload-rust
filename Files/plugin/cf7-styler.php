<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://saleswonder.biz
 * @since             1.3.6
 * @package           Cf7_Customizer
 * @wordpress-plugin
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Tested up to: 6.2
 * Plugin Name:       WOW Style Contact Form 7
 * Plugin URI:        https://saleswonder.biz/blog/4free-contact-form-7-cf7-formular-und-klick-tipp-einfach-verbinden/
 * Description:       How to turn your contact form7 form into a converting and easy to use and pro styled contact form, "survey" lead generator or an eye catching form
 * Version:           1.5.3
 * Author:            Tobias Conrad
 * Author URI:        https://saleswonder.biz
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       cf7-styler
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if( !defined('CF7CSTMZR_VERSION') ) {
	define( 'CF7CSTMZR_VERSION', '1.5.3' );
}

if( !defined('CF7CSTMZR_BRANCH') ) {
	define( 'CF7CSTMZR_BRANCH', 'master' );
}

if( !defined('CF7CSTMZR_SLUG') ) {
	define( 'CF7CSTMZR_SLUG', 'cf7-styler' );
}

if ( ! defined( 'CF7CSTMZR_PLUGIN_FILE' ) ) {
    define( 'CF7CSTMZR_PLUGIN_FILE', __FILE__ );
}

if( !defined('CF7CSTMZR_PLUGIN_URL') ) {
	define( 'CF7CSTMZR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if( !defined('CF7CSTMZR_PLUGIN_PATH') ) {
	define( 'CF7CSTMZR_PLUGIN_PATH', plugin_dir_path( CF7CSTMZR_PLUGIN_FILE ) );
}

if( !function_exists('cf7cstmzr_is_plugin_activated') ) {
	require_once 'includes/functions.php';
}
if( !class_exists('Cf7_License') ) {
	require_once 'includes/lib/Cf7_License.php';
}

if ('free' === Cf7_License::get_license_version()) {

    if ( ! function_exists( 'cf7_styler' ) ) {
        // Create a helper function for easy SDK access.
        function cf7_styler() {
            global $cf7_styler;
            $secret_key = defined( 'WP_FS__SECRET_KEY' ) && WP_FS__SECRET_KEY ?  WP_FS__SECRET_KEY : '';

            if ( ! isset( $cf7_styler ) ) {
                // Include Freemius SDK.
                require_once dirname(__FILE__) . '/freemius/start.php';

                $params = array(
                    'id'                  => '4879',
                    'slug'                => 'cf7-styler',
                    'premium_slug'        => 'cf7-styler-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_430f963531baceba1e271f3a35041',
                    'is_premium'          => false,
                    'premium_suffix'      => 'Pro',
                    // If your plugin is a serviceware, set this option to false.
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'is_org_compliant'      => true,
                    'trial'               => array(
                        'days'               => 14,
                        'is_require_payment' => true,
                    ),
                    'has_affiliation'     => 'all',
                    'menu'                => array(
                        'slug'           => 'cf7cstmzr_page',
			'support'    => true,
			'contact'    => false,
                        'parent'         => array(
                            'slug' => 'wpcf7',
                        ),
                    ),
                    // Set the SDK to work in a sandbox mode (for development & testing).
                    // IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
                    'secret_key'          => $secret_key,
                );

                if (!cf7cstmzr_is_plugin_activated( 'contact-form-7', 'wp-contact-form-7.php' )) {
                    $params['menu'] = array(
                        'slug'           => 'cf7cstmzr_page',
                        'support'        => true,
			'contact'    	=> false,
                    );
                }

                $cf7_styler = fs_dynamic_init( $params );
            }

            return $cf7_styler;
        }

        // Init Freemius.
        cf7_styler();
        // Signal that SDK was initiated.
        do_action( 'cf7_styler_loaded' );
    }
}

if( !function_exists('cf7cstmzr_show_cf7_missing_notice') ) {
	function cf7cstmzr_show_cf7_missing_notice() {
		echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'CF7 Customizer requires Contact Form 7 plugin to be installed and active. You can download %s.', 'cf7-styler' ), '<a href="/wp-admin/plugin-install.php?s=contact+form+7&tab=search&type=term" target="_blank">Contact Form 7 here</a>' ) . '</strong></p></div>';
	}
}

if( !function_exists('cf7cstmzr_show_dev_env_notice') ) {
    function cf7cstmzr_show_dev_env_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('WOW Style Contact Form 7', 'cf7-styler') ?> DEV env:</strong>  version <strong><?php echo CF7CSTMZR_VERSION ?></strong>

                <?php
                if (defined( 'CF7CSTMZR_BRANCH' ) && CF7CSTMZR_BRANCH) {
                    ?>
                    current branch <strong><?php echo CF7CSTMZR_BRANCH ?></strong>
                    <?php
                }
                ?>
            </p>
        </div>
        <?php
    }
}

if ( !cf7cstmzr_is_plugin_activated( 'contact-form-7', 'wp-contact-form-7.php' ) ) {
    if(!isset($_GET['page']) || sanitize_text_field($_GET['page']) !== 'cf7cstmzr_page') {
        add_action( 'admin_notices', 'cf7cstmzr_show_cf7_missing_notice' );
    }
}

if ( defined( 'CF7CSTMZR_DEV_ENV' ) && CF7CSTMZR_DEV_ENV) {
    add_action( 'admin_notices', 'cf7cstmzr_show_dev_env_notice' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cf7-customizer-activator.php
 */
if( !function_exists('activate_cf7_customizer') ) {
	function activate_cf7_customizer() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-cf7-customizer-activator.php';

		cf7_customizer_deactivate_previous(); //Deactivate any previously active instance of the plugin, before activating new one

		Cf7_Customizer_Activator::activate();
	}
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cf7-customizer-deactivator.php
 */
if( !function_exists('deactivate_cf7_customizer') ) {
	function deactivate_cf7_customizer() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-cf7-customizer-deactivator.php';
		Cf7_Customizer_Deactivator::deactivate();
	}
}



if( !function_exists('cf7_customizer_deactivate_previous') ) {
	/**
	 * Deactivate any previously active instance of the plugin
	 */
	function cf7_customizer_deactivate_previous() {
		if ( current_user_can( 'activate_plugins' ) && class_exists( 'Cf7_Customizer' ) && defined('CF7CSTMZR_PLUGIN_FILE') ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			deactivate_plugins ( plugin_basename ( CF7CSTMZR_PLUGIN_FILE ), true );
		}
	}
}

register_activation_hook( __FILE__, 'activate_cf7_customizer' );
register_deactivation_hook( __FILE__, 'deactivate_cf7_customizer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
if( !class_exists('Cf7_Customizer') ) {
	require plugin_dir_path( __FILE__ ) . 'includes/class-cf7-customizer.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if( !function_exists('run_cf7_customizer') ) {
	function run_cf7_customizer() {

		$plugin = new Cf7_Customizer();
		$plugin->run();

	}
}
run_cf7_customizer();
