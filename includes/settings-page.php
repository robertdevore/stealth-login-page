<?php
/**
 * Settings Page for Stealth Login Page
 *
 * @package     SLP
 * @subpackage  Settings Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register settings and add settings page.
 */
add_action( 'admin_menu', function() {
    add_options_page(
        esc_html__( 'Stealth Login Page Options', 'stealth-login-page' ),
        esc_html__( 'Stealth Login', 'stealth-login-page' ),
        'manage_options',
        'stealth-login-page',
        'slp_render_settings_page'
    );

    register_setting( 'slp_settings_group', 'slp_settings', [
        'sanitize_callback' => 'slp_sanitize_settings',
    ] );
});

/**
 * Render the settings page.
 */
function slp_render_settings_page() {
    $options = get_option( 'slp_settings', [] );

    add_action( 'admin_post_slp_send_email', function () {
        if ( isset( $_POST['email-admin'] ) && check_admin_referer( 'slp_settings_group-options' ) ) {
            $options = get_option( 'slp_settings', [] );
            $admin_email = get_option( 'admin_email' );
    
            if ( ! empty( $options['auth_key'] ) ) {
                wp_mail(
                    $admin_email,
                    __( 'Stealth Login Authorization Code', 'stealth-login-page' ),
                    sprintf(
                        __( 'Your current authorization code is: %s', 'stealth-login-page' ),
                        sanitize_text_field( $options['auth_key'] )
                    )
                );
            }
            // Provide feedback and redirect back to the settings page.
            wp_redirect( admin_url( 'options-general.php?page=stealth-login-page&email_sent=1' ) );
            exit;
        }
    });
        
    ?>

    <div class="wrap">
        <h2><?php esc_html_e( 'Stealth Login Page Options', 'stealth-login-page' ); ?></h2>
        <form method="post" action="<?php echo admin_url( 'options-general.php?page=stealth-login-page' ); ?>">
            <input type="hidden" name="action" value="slp_send_email">
            <?php
            wp_nonce_field( 'slp_settings_group-options' );

            settings_fields( 'slp_settings_group' );
            do_settings_sections( 'stealth-login-page' );
            ?>

            <h3><?php esc_html_e( 'Enable/Disable Stealth Login Page', 'stealth-login-page' ); ?></h3>
            <p>
                <input id="slp_settings[enable]" type="checkbox" name="slp_settings[enable]" value="1" 
                    <?php checked( isset( $options['enable'] ) ? $options['enable'] : 0, 1 ); ?> />
                <label for="slp_settings[enable]">
                    <?php esc_html_e( 'Enable Stealth Mode', 'stealth-login-page' ); ?>
                </label>
            </p>

            <h3><?php esc_html_e( 'Authorization Code', 'stealth-login-page' ); ?></h3>
            <p>
                <label for="slp_settings[auth_key]">
                    <?php esc_html_e( 'Enter an authorization code:', 'stealth-login-page' ); ?>
                </label>
                <input type="text" id="slp_settings[auth_key]" name="slp_settings[auth_key]" 
                    value="<?php echo esc_attr( $options['auth_key'] ?? '' ); ?>" />
            </p>

            <h3><?php esc_html_e( 'Redirect URL', 'stealth-login-page' ); ?></h3>
            <p>
                <label for="slp_settings[redirect_url]">
                    <?php esc_html_e( 'URL to redirect unauthorized attempts to:', 'stealth-login-page' ); ?>
                </label>
                <input type="url" id="slp_settings[redirect_url]" name="slp_settings[redirect_url]" 
                    value="<?php echo esc_url( $options['redirect_url'] ?? '' ); ?>" />
            </p>

            <h3><?php esc_html_e( 'Email Authorization Code', 'stealth-login-page' ); ?></h3>
            <p>
                <input id="email-admin" type="checkbox" name="email-admin" value="1" />
                <label for="email-admin">
                    <?php esc_html_e( 'Email authorization code to admin', 'stealth-login-page' ); ?>
                </label>
            </p>

            <?php if ( isset( $_GET['settings_updated'] ) ) : ?>
                <p style="color: green;">
                    <?php esc_html_e( 'Settings updated successfully.', 'stealth-login-page' ); ?>
                </p>
            <?php endif; ?>

            <p class="submit">
                <button type="submit" class="button-primary">
                    <?php esc_html_e( 'Save Settings', 'stealth-login-page' ); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Sanitize settings input.
 *
 * @param array $input Settings input.
 * @return array Sanitized settings.
 */
function slp_sanitize_settings( $input ) {
    return [
        'enable'        => isset( $input['enable'] ) ? 1 : 0,
        'auth_key'      => sanitize_text_field( $input['auth_key'] ?? '' ),
        'redirect_url'  => esc_url_raw( $input['redirect_url'] ?? '' ),
    ];
}

add_action( 'admin_post_slp_save_settings', function () {
    if ( check_admin_referer( 'slp_settings_group-options' ) ) {
        $options = get_option( 'slp_settings', [] );

        // Update settings
        $options['enable']       = isset( $_POST['slp_settings']['enable'] ) ? 1 : 0;
        $options['auth_key']     = sanitize_text_field( $_POST['slp_settings']['auth_key'] ?? '' );
        $options['redirect_url'] = esc_url_raw( $_POST['slp_settings']['redirect_url'] ?? '' );

        // Save updated settings
        update_option( 'slp_settings', $options );

        // Handle email checkbox
        $email_admin = isset( $_POST['email-admin'] ) ? 1 : 0;
        if ( $email_admin ) {
            $admin_email = get_option( 'admin_email' );
            if ( ! empty( $options['auth_key'] ) ) {
                $email_result = wp_mail(
                    $admin_email,
                    __( 'Stealth Login Authorization Code', 'stealth-login-page' ),
                    sprintf(
                        __( 'Your current authorization code is: %s', 'stealth-login-page' ),
                        sanitize_text_field( $options['auth_key'] )
                    )
                );

                // Add a transient for the email result
                if ( $email_result ) {
                    set_transient( 'slp_email_sent', __( 'Authorization code emailed to admin.', 'stealth-login-page' ), 30 );
                } else {
                    set_transient( 'slp_email_failed', __( 'Failed to email authorization code.', 'stealth-login-page' ), 30 );
                }
            }
        }

        // Redirect back to settings with a success message
        wp_redirect( admin_url( 'options-general.php?page=stealth-login-page&settings_updated=1' ) );
        exit;
    }
});

// Display transient messages on the settings page
if ( $message = get_transient( 'slp_email_sent' ) ) {
    echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
    delete_transient( 'slp_email_sent' );
}

if ( $message = get_transient( 'slp_email_failed' ) ) {
    echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
    delete_transient( 'slp_email_failed' );
}
