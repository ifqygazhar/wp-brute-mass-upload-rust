<?php
/**
 * Class Cf7_Required_Plugin
 */
class Cf7_Required_Plugin {
    public static function get_required_plugins() {
        $required_plugins = array(
            'cf7' => array(
                'label' => 'Contact Form 7',
                'link' => 'https://wordpress.org/plugins/contact-form-7/',
                'zip_path' => 'https://downloads.wordpress.org/plugin/contact-form-7.5.1.4.zip',
                'slug' => 'contact-form-7/wp-contact-form-7.php',
                'author' => __('By Takayuki Miyoshi', 'cf7-styler'),
                'description' => ''
            ),
        );


        if( !function_exists('is_plugin_active') ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        foreach ($required_plugins as $slug => $data) {
            if (is_plugin_active($data['slug'])) {
                unset($required_plugins[$slug]);
            }
        }

        return $required_plugins;
    }

    public static function is_plugin_installed( $plugin_slug ) {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();

        if ( !empty( $all_plugins[$plugin_slug] ) ) {
            return true;
        } else {
            return false;
        }
    }

    public static function install_plugin( $plugin_zip ) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();

        $upgrader = new Plugin_Upgrader(new Cf7_Quiet_Skin());

        $installed = $upgrader->install( $plugin_zip );

        return $installed;
    }

    public static function upgrade_plugin( $plugin_slug ) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();

        $upgrader = new Plugin_Upgrader(new Cf7_Quiet_Skin());

        $upgraded = $upgrader->upgrade( $plugin_slug );

        return $upgraded;
    }

    public static function is_plugin_newest_version($plugin_slug) {
        $current = get_site_transient( 'update_plugins' );

        if ( ! isset( $current->response[ $plugin_slug ] ) ) {
            return true;
        }

        return false;
    }

    public static function install_and_activate_plugin($plugin) {
        $required_plugins = self::get_required_plugins();

        $plugin_data = $required_plugins[$plugin];
        $plugin_zip = $plugin_data['zip_path'];
        $plugin_slug = $plugin_data['slug'];

        if (!self::is_plugin_installed($plugin_slug)) {
            $installed = self::install_plugin($plugin_zip);

            if (!is_wp_error( $installed ) && $installed) {
                self::upgrade_plugin( $plugin_slug );
                $installed = true;
            }
        } elseif(self::is_plugin_newest_version($plugin_slug)) {
            $installed = true;
        } else {
            self::upgrade_plugin( $plugin_slug );
            $installed = true;
        }

        if ( !is_wp_error( $installed ) && $installed ) {
            $activate = activate_plugin( $plugin_slug );

            if ( is_null($activate) ) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (!class_exists('WP_Upgrader_Skin')) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
}

if(version_compare(get_bloginfo('version'),'5.3', '>=') ) {
    class Cf7_Quiet_Skin extends WP_Upgrader_Skin {
        public function feedback($string, ...$args) {}
    }
} else {
    class Cf7_Quiet_Skin extends WP_Upgrader_Skin {
        public function feedback($string) {}
    }
}
