<?php
/**
 * Plugin Name: Headless WP Kit
 * Description: A small headless WordPress kit: signed previews, editorial profiles, REST capabilities and CMS hardening.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Headless WP Kit contributors
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: headless-wp-kit
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'HWK_VERSION', '0.1.0' );
define( 'HWK_FILE', __FILE__ );
define( 'HWK_DIR', plugin_dir_path( __FILE__ ) );
define( 'HWK_URL', plugin_dir_url( __FILE__ ) );

require_once HWK_DIR . 'includes/settings.php';
require_once HWK_DIR . 'includes/capabilities.php';
require_once HWK_DIR . 'includes/auth.php';
require_once HWK_DIR . 'includes/audit.php';
require_once HWK_DIR . 'includes/preview.php';
require_once HWK_DIR . 'includes/seo.php';
require_once HWK_DIR . 'includes/redirects.php';
require_once HWK_DIR . 'includes/content-model.php';
require_once HWK_DIR . 'includes/fields.php';
require_once HWK_DIR . 'includes/signature.php';
require_once HWK_DIR . 'includes/editor.php';

register_activation_hook( __FILE__, 'hwk_activate' );
register_deactivation_hook( __FILE__, 'hwk_deactivate' );

/**
 * Activation: default settings and audit table.
 */
function hwk_activate(): void {
	if ( false === get_option( 'hwk_settings', false ) ) {
		add_option( 'hwk_settings', hwk_default_settings() );
	}

	hwk_install_audit_table();
}

/**
 * Deactivation: nothing persistent. Content and settings stay.
 */
function hwk_deactivate(): void {
	// Intentionally empty.
}
