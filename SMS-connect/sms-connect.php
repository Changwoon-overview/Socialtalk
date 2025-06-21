<?php
/**
 * Plugin Name:       SMS-connect
 * Plugin URI:        https://example.com/
 * Description:       우커머스 기반 SMS/카카오 알림톡 자동 발송 워드프레스 플러그인
 * Version:           0.0.1
 * Author:            Your Name
 * Author URI:        https://example.com/
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
require_once __DIR__ . '/includes/autoloader.php';

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
	return \SmsConnect\Sms_Connect::instance();
}

sms_connect_run(); 