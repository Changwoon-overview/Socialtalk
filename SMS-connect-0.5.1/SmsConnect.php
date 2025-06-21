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
use SmsConnect\Admin\Admin_Notices;
use SmsConnect\API\Alimtalk_Api_Client;
use SmsConnect\WooCommerce\WC_Subscriptions_Hooks;
use SmsConnect\Core\User_Hooks;

/**
 * Main SmsConnect Class.
 *
 * @class SmsConnect
 */
final class SmsConnect {
	/**
	 * The single instance of the class.
	 *
	 * @var SmsConnect|null
	 */
	private static $instance = null;

	/**
	 * Main SmsConnect Instance.
	 *
	 * Ensures only one instance of SmsConnect is loaded or can be loaded.
	 *
	 * @static
	 * @return SmsConnect - Main instance.
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
	 * SmsConnect constructor.
	 */
	private function __construct() {
		$this->setup_handlers();
		$this->add_hooks();
	}

	/**
	 * Set up all the service handlers.
	 */
	private function setup_handlers() {
		// API 클라이언트들을 먼저 초기화
		$this->handlers['api_client'] = Sms_Api_Client::get_instance();
		$this->handlers['alimtalk_api_client'] = Alimtalk_Api_Client::get_instance();
		
		// 템플릿 변수 핸들러 초기화
		$this->handlers['template_variables'] = new Template_Variables();
		
		// Message_Manager는 API 클라이언트들을 의존성으로 받음
		$this->handlers['message_manager'] = new Message_Manager(
			$this->handlers['api_client'],
			$this->handlers['alimtalk_api_client']
		);
		
		// WooCommerce 훅 초기화
		$this->handlers['wc_hooks'] = new WC_Hooks();
		
		// 사용자 관련 훅 초기화
		$this->handlers['user_hooks'] = new User_Hooks();

		// Load subscriptions hooks only if the plugin is active.
		if ( class_exists( 'WC_Subscriptions' ) ) {
			$this->handlers['wc_subscriptions_hooks'] = new WC_Subscriptions_Hooks();
		}

		if ( is_admin() ) {
			$this->handlers['admin_menu'] = new Admin_Menu();
			$this->handlers['admin_notices'] = new Admin_Notices();
			$this->handlers['admin_notices']->init();
		}
	}

	/**
	 * Add the hooks and filters.
	 */
	private function add_hooks() {
		// Hooks will be added here.
	}
} 