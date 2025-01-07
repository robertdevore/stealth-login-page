<?php
/**
 * Uninstall Script for Stealth Login Page
 *
 * @package     SLP
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2013, Jesse Petersen
 * @license     GPL-2.0-or-later
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Perform uninstallation for Stealth Login Page.
 */
function slp_uninstall(): void {
    delete_option( 'slp_settings' );
}

if ( is_multisite() ) {
    global $wpdb;
    $original_blog_id = get_current_blog_id();
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        slp_uninstall();
    }

    // Restore original blog.
    switch_to_blog( $original_blog_id );
} else {
    slp_uninstall();
}
