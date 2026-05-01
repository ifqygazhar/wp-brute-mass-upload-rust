<?php

/**
 * Helpers library functions
 *
 * @since      0.0.1
 *
 * @package    Wtsr
 * @subpackage Wtsr/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @version 0.0.1
 * @since   0.0.1
 * @return  bool
 */
function cf7cstmzr_is_plugin_activated( $plugin_folder, $plugin_file ) {
    if ( cf7cstmzr_is_plugin_active_simple( $plugin_folder . '/' . $plugin_file ) ) {
        return true;
    } else {
        return cf7cstmzr_is_plugin_active_by_file( $plugin_file );
    }
}

/**
 * @version 0.0.1
 * @since   0.0.1
 * @return  bool
 */
function cf7cstmzr_is_plugin_active_simple( $plugin ) {
    return (
        in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
        ( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
    );
}

/**
 * @version 0.0.1
 * @since   0.0.1
 * @return  bool
 */
function cf7cstmzr_is_plugin_active_by_file( $plugin_file ) {
    foreach ( cf7cstmzr_get_active_plugins() as $active_plugin ) {
        $active_plugin = explode( '/', $active_plugin );

        if ( isset( $active_plugin[1] ) && $plugin_file === $active_plugin[1] ) {
            return true;
        }
    }

    return false;
}

/**
 * @version 0.0.1
 * @since   0.0.1
 * @return  array
 */
function cf7cstmzr_get_active_plugins() {
    $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
    if ( is_multisite() ) {
        $active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
    }
    return $active_plugins;
}

/**
 * @param $notice
 * @param $level
 */
function cf7cstmzr_show_notice($notice, $level) {
    include_once CF7CSTMZR_PLUGIN_PATH . 'admin/partials/notices' . $notice . '.php';
}
