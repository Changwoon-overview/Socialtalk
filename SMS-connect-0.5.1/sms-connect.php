<?php
/**
 * Plugin Name: SMS 연결
 * Plugin URI: https://example.com/sms-connect
 * Description: 우커머스와 워드프레스를 위한 강력한 SMS 및 알림톡 발송 플러그인입니다. 주문 상태 변경, 회원가입, 구독 관리 등 다양한 이벤트에 대해 자동으로 고객과 관리자에게 알림을 보냅니다.
 * Version: 0.5.1
 * Author: SMS Connect 개발팀
 * Author URI: https://example.com
 * Text Domain: sms-connect
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package SmsConnect
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
function sms_connect_activate() {
	if (class_exists('SmsConnect\\Core\\Db_Manager')) {
		\SmsConnect\Core\Db_Manager::create_tables();
	}
	
	// Set activation notice
	\set_transient( 'sms_connect_activation_notice', true, 30 );
}
register_activation_hook(__FILE__, 'sms_connect_activate');


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
		add_action( 'admin_notices', [ 'SmsConnect\\Admin\\Admin_Notices', 'show_woocommerce_not_active_notice' ] );
		return;
	}
	\SmsConnect\SmsConnect::instance();
}

// Hook the function to plugins_loaded to ensure all plugins are loaded first
\add_action( 'plugins_loaded', 'sms_connect_run' ); 