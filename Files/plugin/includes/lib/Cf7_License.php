<?php


class Cf7_License {
    public static function get_license_version() {
        if (function_exists('cf7_styler')) {
            $is_paying = cf7_styler()->is_paying_or_trial();

            if ($is_paying) {
                return  'pro';
            }

            if (cf7_styler()->is_paying__fs__()) {
                return  'pro';
            }

            if (cf7_styler()->is_trial()) {
                return  'pro';
            }
        }

        if ( !cf7cstmzr_is_plugin_activated( 'wp2leads', 'wp2leads.php' ) ) {
            return 'free';
        }

        $wp2l_license = get_option('wp2l_license');
        $wp2l_license_version = !empty($wp2l_license['version']) ? $wp2l_license['version'] : 'free';

        return $wp2l_license_version;
    }

    public static function is_wp2leads_installed() {
        return cf7cstmzr_is_plugin_activated( 'wp2leads', 'wp2leads.php' );
    }
}