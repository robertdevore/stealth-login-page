<?php

/**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             5.0.0
  * @package           SLP
  *
  * @wordpress-plugin
  *
  * Plugin Name: Stealth Login Page
  * Description: Protect your dashboard without editing the .htaccess file -- the FIRST one that completely blocks remote bot login requests.
  * Plugin URI:  https://github.com/robertdevore/stealth-login-page/
  * Version:     5.0.0
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: stealth-login-page
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/stealth-login-page/
  */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include the install plugin.
$install_file = plugin_dir_path( __FILE__ ) . 'includes/install.php';

if ( file_exists( $install_file ) ) {
    require_once $install_file;
    error_log( 'install.php was included successfully.' );
} else {
    error_log( 'install.php not found in includes directory.' );
}

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/stealth-login-page/',
	__FILE__,
	'stealth-login-page'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Define the plugin version.
define( 'SLP_PLUGIN_VERSION', '5.0.0' );
define( 'SLP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );


// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

/*
  Copyright 2024 Robert DeVore

  Thank you Jesse Petersen for all of your contributions to the WordPress 
  community. May your legacy live on forever <3

  Thanks to Andrew Norcross (@norcross) for the original redirect code through v3.0.0
  https://gist.github.com/norcross/4342231) and Billy Fairbank
  (@billyfairbank) for the idea to turn it into a plugin.

  Thanks to David Decker for DE localization: http://deckerweb.de/kontakt/

  Limit of liability: Installation and use of this plugin acknowledges the
  understanding that this program alters the wp-config.php file and adds
  settings to the WordPress database. The author is not responsible for any
  damages or loss of data that might possibly be incurred through the
  installation or use of the plugin.

  Support: This is a free plugin, therefore support is limited to bugs that
  affect all installations. Requests of any other nature will be at the
  discretion of the plugin author to add or modify the code to account for
  various installations, servers, or plugin conflicts.
  
  Licenced under the GNU GPL:

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class Stealth_Login_Page {

    const OPTION_NAME = 'stealth_login_page_settings';
    const AUTH_COOKIE = 'stealth_auth_verified';

    /**
     * Constructor.
     *
     * Initializes the plugin by setting up the necessary hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_post_save_stealth_settings', [ $this, 'save_settings' ] );
        add_action( 'init', [ $this, 'check_auth_key' ] );
    }

    /**
     * Adds the settings page to the WordPress admin menu.
     *
     * This method registers the settings page under the "Settings" menu in the WordPress admin dashboard.
     * 
     * @since  5.0.0
     * @return void
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Stealth Login Page Settings', 'stealth-login-page' ),
            __( 'Stealth Login Page', 'stealth-login-page' ),
            'manage_options',
            'stealth-login-page',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Renders the settings page for the Stealth Login Page plugin.
     *
     * Outputs the HTML for the settings page, including form fields for enabling
     * the plugin, setting the authorization key, configuring the redirect URL, and
     * enabling the option to email the authorization key to the admin.
     *
     * @since  5.0.0
     * @return void
     */
    public function render_settings_page() {
        $options        = get_option( self::OPTION_NAME, [] );
        $enabled        = isset( $options['enabled'] ) ? $options['enabled'] : '';
        $auth_key       = isset( $options['auth_key'] ) ? esc_attr( $options['auth_key'] ) : '';
        $redirect_url   = isset( $options['redirect_url'] ) ? esc_url( $options['redirect_url'] ) : '';
        $email_checkbox = isset( $options['email_checkbox'] ) ? $options['email_checkbox'] : '';
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'Stealth Login Page Settings', 'stealth-login-page' ); ?>
                <a id="stealth-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                    <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> <?php esc_html_e( 'Support', 'stealth-login-page' ); ?>
                </a>
                <a id="stealth-docs-btn" href="https://robertdevore.com/articles/stealth-login-page/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> <?php esc_html_e( 'Documentation', 'stealth-login-page' ); ?>
                </a>
            </h1>
            <?php if ( $messages = get_transient( 'settings_errors' ) ) : ?>
                <?php foreach ( $messages as $message ) : ?>
                    <div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> is-dismissible">
                        <p><?php echo esc_html( $message['message'] ); ?></p>
                    </div>
                <?php endforeach; ?>
                <?php delete_transient( 'settings_errors' ); ?>
            <?php endif; ?>
            <form method="post" action="admin-post.php" style="margin-top:12px;">
                <input type="hidden" name="action" value="save_stealth_settings">
                <?php wp_nonce_field( 'save_stealth_settings', 'stealth_nonce' ); ?>

                <div style="background: #fff; border: 1px solid #ccc; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <label style="display: block; margin-bottom: 20px;">
                        <input type="checkbox" name="enabled" value="1" <?php checked( $enabled, '1' ); ?>>
                        <?php esc_html_e( 'Enable Stealth Login Page', 'stealth-login-page' ); ?>
                    </label>

                    <label for="auth_key" style="display: block; font-weight: bold; margin-bottom: 8px;">
                        <?php esc_html_e( 'Authorization Key:', 'stealth-login-page' ); ?>
                    </label>
                    <input type="password" id="auth_key" name="auth_key" value="<?php echo $auth_key; ?>" class="regular-text">

                    <label for="redirect_url" style="display: block; font-weight: bold; margin: 20px 0 8px;">
                        <?php esc_html_e( 'Redirect URL:', 'stealth-login-page' ); ?>
                    </label>
                    <input type="url" id="redirect_url" name="redirect_url" value="<?php echo $redirect_url; ?>" class="regular-text">

                    <label style="display: block; margin: 20px 0;">
                        <input type="checkbox" name="email_checkbox" value="1" <?php checked( $email_checkbox, '1' ); ?>>
                        <?php esc_html_e( 'Email the authorization code to the admin', 'stealth-login-page' ); ?>
                    </label>

                    <p><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'stealth-login-page' ); ?>"></p>
                </div>
            </form>
            <style type="text/css">
                #stealth-support-btn,
                #stealth-docs-btn {
                    line-height: 22px;
                    float: right;
                    margin-left: 12px;
                    display: flex;
                    align-content: center;
                    align-items: center;
                    gap: 6;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Handles saving the plugin settings.
     *
     * Validates the request, updates the plugin options in the database, and optionally
     * sends an email to the admin with the authorization key. Displays a success message
     * after saving.
     * 
     * @since  5.0.0
     * @return void
     */
    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['stealth_nonce'] ) || ! wp_verify_nonce( $_POST['stealth_nonce'], 'save_stealth_settings' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'stealth-login-page' ) );
        }

        $enabled        = isset( $_POST['enabled'] ) ? '1' : '';
        $auth_key       = sanitize_text_field( $_POST['auth_key'] );
        $redirect_url   = esc_url_raw( $_POST['redirect_url'] );
        $email_checkbox = isset( $_POST['email_checkbox'] ) ? '1' : '';

        $settings = [
            'enabled'        => $enabled,
            'auth_key'       => $auth_key,
            'redirect_url'   => $redirect_url,
            'email_checkbox' => $email_checkbox,
        ];

        update_option( self::OPTION_NAME, $settings );

        if ( $email_checkbox ) {
            $admin_email = get_option( 'admin_email' );
            wp_mail( $admin_email, __( 'Stealth Login Auth Key', 'stealth-login-page' ), sprintf( __( 'Your authorization key is: %s', 'stealth-login-page' ), $auth_key ) );
            $settings['email_checkbox'] = '';
            update_option( self::OPTION_NAME, $settings );
            $message = __( 'Settings saved and email sent to admin with the authorization key.', 'stealth-login-page' );
        } else {
            $message = __( 'Settings saved.', 'stealth-login-page' );
        }

        add_settings_error( 'stealth_login_page', 'settings_updated', $message, 'success' );
        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_redirect( admin_url( 'options-general.php?page=stealth-login-page' ) );
        exit;
    }

    /**
     * Validates the authorization key for accessing wp-admin and wp-login.php.
     *
     * Checks if the Stealth Login Page functionality is enabled. If enabled, it verifies the `auth_key`
     * passed in the URL or validates an existing cookie. Redirects the user if the key is missing or incorrect.
     * Sets a cookie for subsequent requests once the key is validated successfully.
     * 
     * @since  5.0.0
     * @return void
     */
    public function check_auth_key() {
        $options = get_option( self::OPTION_NAME, [] );
        $enabled = isset( $options['enabled'] ) ? $options['enabled'] : '';

        // Allow access if disabled or admin with manage_options.
        if ( ! $enabled || ( is_admin() && current_user_can( 'manage_options' ) ) ) {
            return;
        }

        // Skip if the cookie is already set.
        if ( isset( $_COOKIE[ self::AUTH_COOKIE ] ) && $_COOKIE[ self::AUTH_COOKIE ] === '1' ) {
            error_log( 'DEBUG: Auth cookie found. Access granted.' );
            return;
        }

        // Only apply to wp-admin or wp-login.php.
        $request_uri = $_SERVER['REQUEST_URI'];
        if ( strpos( $request_uri, 'wp-login.php' ) === false && strpos( $request_uri, 'wp-admin' ) === false ) {
            return;
        }

        $auth_key     = isset( $options['auth_key'] ) ? trim( $options['auth_key'] ) : '';
        $redirect_url = isset( $options['redirect_url'] ) ? $options['redirect_url'] : home_url();

        if ( isset( $_GET['auth_key'] ) ) {
            $passed_key = trim( sanitize_text_field( wp_unslash( $_GET['auth_key'] ) ) );
            if ( $passed_key === $auth_key ) {
                setcookie( self::AUTH_COOKIE, '1', time() + 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
                return; // Correct key, allow access.
            }
            error_log( 'STEALTH LOGIN ERROR: Auth key mismatch. Redirecting.' );
        } else {
            error_log( 'STEALTH LOGIN ERROR: Auth key is missing from URL. Redirecting.' );
        }
        wp_redirect( $redirect_url );
        exit;
    }
}

new Stealth_Login_Page();
