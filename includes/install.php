<?php
/**
 * Install Functions
 *
 * @package     SLP
 * @subpackage  Functions/Install
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, 'slp_on_activate' );
register_deactivation_hook( __FILE__, 'slp_on_deactivate' );

/**
 * Handle plugin activation.
 *
 * @param bool $networkwide Whether the plugin is activated network-wide.
 * 
 * @since  5.0.0
 * @return void
 */
function slp_on_activate( $networkwide = false ): void {
    if ( is_multisite() && $networkwide ) {
        slp_handle_multisite_activation( 'slp_activate_blog' );
    } else {
        slp_activate_blog();
    }
}

/**
 * Handle plugin deactivation.
 *
 * @param bool $networkwide Whether the plugin is deactivated network-wide.
 * 
 * @since  5.0.0
 * @return void
 */
function slp_on_deactivate( $networkwide = false ): void {
    if ( is_multisite() && $networkwide ) {
        slp_handle_multisite_activation( 'slp_deactivate_blog' );
    } else {
        slp_deactivate_blog();
    }
}

/**
 * Activate the plugin for a single site.
 * 
 * @since  5.0.0
 * @return void
 */
function slp_activate_blog(): void {
    // Add default settings if not already present.
    if ( ! get_option( 'slp_settings' ) ) {
        add_option( 'slp_settings', [
            'enable'       => 0,
            'auth_key'     => '',
            'redirect_url' => home_url(),
        ] );
    }
}

/**
 * Deactivate the plugin for a single site.
 * 
 * @since  5.0.0
 * @return void
 */
function slp_deactivate_blog(): void {
    // Cleanup tasks if needed.
}

/**
 * Handle activation or deactivation across a multisite network.
 *
 * @param callable $callback The function to call for each blog.
 * 
 * @since  5.0.0
 * @return void
 */
function slp_handle_multisite_activation( callable $callback ): void {
    global $wpdb;

    $original_blog_id = get_current_blog_id();
    $blog_ids         = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        call_user_func( $callback );
    }

    // Restore the original blog context.
    switch_to_blog( $original_blog_id );
}

/**
 * Handle new blog creation on a multisite network.
 *
 * @param int    $blog_id Blog ID.
 * @param int    $user_id User ID.
 * @param string $domain  Domain name.
 * @param string $path    Blog path.
 * @param int    $site_id Site ID.
 * @param array  $meta    Blog metadata.
 * 
 * @since  5.0.0
 * @return void
 */
add_action( 'wpmu_new_blog', function( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
        switch_to_blog( $blog_id );
        slp_activate_blog();
        restore_current_blog();
    }
}, 10, 6 );

// Create variable for settings link filter.
$plugin_name = plugin_basename( __FILE__ );

/**
 * Add settings link on plugin page
 *
 * @param array $links an array of links related to the plugin.
 * 
 * @since  5.0.0
 * @return array updatead array of links related to the plugin.
 */
function stealth_login_page_settings_link( $links ) {
    // Settings link.
    $settings_link = '<a href="options-general.php?page=stealth-login-page">' . esc_html__( 'Settings', 'stealth-login-page' ) . '</a>';

    array_unshift( $links, $settings_link );

    return $links;
}
add_filter( "plugin_action_links_$plugin_name", 'stealth_login_page_settings_link' );
