<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       saleswonder.biz
 * @since      1.0.0
 *
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Cf7_Customizer
 * @subpackage Cf7_Customizer/includes
 * @author     Tobias Conrad <tc@saleswonder.biz>
 */
class Cf7_Customizer_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

        add_filter( 'plugin_locale', 'Cf7_Customizer_i18n::check_de_locale');

		load_plugin_textdomain(
            'cf7-styler',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

        remove_filter( 'plugin_locale', 'Cf7_Customizer_i18n::check_de_locale');
	}

    public static function check_de_locale($domain) {
        $site_lang = get_user_locale();
        $de_lang_list = array(
            'de_CH_informal',
            'de_DE_formal',
            'de_AT',
            'de_CH',
            'de_DE'
        );

        if (in_array($site_lang, $de_lang_list)) return 'de_DE';
        return $domain;
    }
}
