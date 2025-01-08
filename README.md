# Stealth Login Page

Stealth Login Page protects your WordPress® site by locking down access to the `wp-admin` and `wp-login.php` pages unless a secret `auth_key` parameter is passed. 

This plugin ensures a layer of obscurity for your login page, reducing brute-force attacks and unauthorized access attempts.

## Features

- Lock down `wp-admin` and `wp-login.php`.
- Custom `auth_key` validation for secure access.
- Configurable redirect URL for failed attempts.
- Optional email notification to send the `auth_key` to the admin.
- Cookie-based session management for seamless user experience.
- Modern settings page with clear configuration options.

## Installation

1. Download the plugin and upload the folder to your WordPress® installation at `wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress®.
3. Go to **Settings > Stealth Login Page** to configure the plugin.

## Configuration

### Settings Page

Navigate to **Settings > Stealth Login Page** to configure:

1. **Enable Stealth Login Page**: Check this box to activate the plugin functionality.
2. **Authorization Key**: Enter a secret key that will grant access to the login page.
    - Example: `https://yourdomain.com/wp-admin/?auth_key=YOUR_SECRET_KEY`
3. **Redirect URL**: Specify the URL where users will be redirected if they fail to provide a valid `auth_key`.
    - Default: Home URL of your site.
4. **Email the Authorization Key**: Check this box to send the `auth_key` to the admin email address.

### Workflow

1. Enable the plugin.
2. Set a secure `auth_key` (e.g., a random alphanumeric string).
3. Optionally, configure a redirect URL for unauthorized attempts.
4. Share the `auth_key` securely with trusted users.

## How It Works

1. The plugin hooks into the `init` action to validate requests to `wp-admin` and `wp-login.php`.
2. It checks for the presence of the `auth_key` parameter in the URL.
3. If the `auth_key` matches the saved key:
    - A secure cookie is set for the session.
    - The user is granted access.
4. If the `auth_key` is missing or incorrect, the user is redirected to the configured URL.

## A Tribute to Jesse Petersen

This plugin is a reimagined version of Jesse Petersen's original "Stealth Login Page" plugin. Jesse was a beloved member of the WordPress® community, known for his kindness, generosity, and dedication to helping others. Although his plugin was closed years ago, this version is a tribute to his work and legacy.

Jesse, thank you for inspiring us with your contributions. May your memory continue to shine in the WordPress® community and beyond.

## Support

For documentation and support, visit:  
[Documentation](https://robertdevore.com/articles/stealth-login-page/)  
[Contact Support](https://robertdevore.com/contact/)

## License

This plugin is licensed under the GPLv2 or later. See the LICENSE file for more information.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue.