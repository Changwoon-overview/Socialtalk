<?php
/**
 * Main plugin class.
 *
 * @package SmsConnect
 */

namespace SmsConnect;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SmsConnect\API\Sms_Api_Client;
use SmsConnect\Core\Template_Variables;
use SmsConnect\Core\Message_Manager;
use SmsConnect\WooCommerce\WC_Hooks;
use SmsConnect\Admin\Admin_Menu;
use SmsConnect\API\Alimtalk_Api_Client;
use SmsConnect\WooCommerce\WC_Subscriptions_Hooks;
use SmsConnect\Core\User_Hooks;

/**
 * Main Sms_Connect Class.
 *
 * @class Sms_Connect
 */
final class Sms_Connect {
	/**
	 * The single instance of the class.
	 *
	 * @var Sms_Connect|null
	 */
	private static $instance = null;

	/**
	 * Main Sms_Connect Instance.
	 *
	 * Ensures only one instance of Sms_Connect is loaded or can be loaded.
	 *
	 * @static
	 * @return Sms_Connect - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Service handlers.
	 *
	 * @var array
	 */
	public $handlers = [];

	/**
	 * Sms_Connect constructor.
	 */
	private function __construct() {
		$this->setup_handlers();
		$this->add_hooks();
	}

	/**
	 * Set up all the service handlers.
	 */
	private function setup_handlers() {
		$this->handlers['api_client'] = Sms_Api_Client::get_instance();
		$this->handlers['alimtalk_api_client'] = Alimtalk_Api_Client::get_instance();
		$this->handlers['template_variables'] = new Template_Variables();
		$this->handlers['message_manager'] = new Message_Manager();
		$this->handlers['wc_hooks'] = new WC_Hooks();
		$this->handlers['user_hooks'] = new User_Hooks();

		// Load subscriptions hooks only if the plugin is active.
		if ( class_exists( 'WC_Subscriptions' ) ) {
			$this->handlers['wc_subscriptions_hooks'] = new WC_Subscriptions_Hooks();
		}

		if ( is_admin() ) {
			$this->handlers['admin_menu'] = new Admin_Menu();
		}
	}

	/**
	 * Add the hooks and filters.
	 */
	private function add_hooks() {
		// Hooks will be added here.
	}
} 