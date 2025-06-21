<?php
/**
 * Plugin Name:       SMS-connect
 * Plugin URI:        https://itconnect.dev/
 * Description:       우커머스 기반 SMS/카카오 알림톡 자동 발송 워드프레스 플러그인
 * Version:           0.5.1
 * Author:            ITConnect
 * Author URI:        https://itconnect.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sms-connect
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main plugin class.
 */
require_once __DIR__ . '/autoloader.php';

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, [ 'SmsConnect\Core\Db_Manager', 'create_tables' ] );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function sms_connect_run() {
	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		// If not, we register an admin notice and bail.
		add_action( 'admin_notices', [ 'SmsConnect\Admin\Admin_Notices', 'show_woocommerce_not_active_notice' ] );
		return;
	}

	// If WooCommerce is active, then we proceed to run the plugin.
	\SmsConnect\Sms_Connect::instance();
}

// Hook the function to plugins_loaded to ensure all plugins are loaded first
add_action( 'plugins_loaded', 'sms_connect_run' ); 