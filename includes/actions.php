<?php
/**
 * Front-End Actions
 *
 * @package     SLP
 * @subpackage  Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register settings in the database.
 * 
 * @since  1.0.0
 * @return void
 */
add_action( 'admin_init', function() {
    register_setting( 'slp_settings_group', 'slp_settings', [
        'sanitize_callback' => 'slp_sanitize_settings',
    ] );
} );

add_action( 'init', function () {
    $options = get_option( 'slp_settings', [] );

    if ( isset( $options['enable'] ) && $options['enable'] ) {
        $auth_key = sanitize_text_field( $_GET['auth_key'] ?? '' );

        if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ) {
            if ( empty( $auth_key ) || $auth_key !== $options['auth_key'] ) {
                wp_safe_redirect( $options['redirect_url'] ?? home_url() );
                exit;
            }
        }

        if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) !== false && ! is_user_logged_in() ) {
            if ( empty( $auth_key ) || $auth_key !== $options['auth_key'] ) {
                wp_safe_redirect( $options['redirect_url'] ?? home_url() );
                exit;
            }
        }
    }
} );
