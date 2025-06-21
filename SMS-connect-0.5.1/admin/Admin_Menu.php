<?php
/**
 * Admin Menu
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Menu
 *
 * Handles the creation of the admin menu pages.
 */
class Admin_Menu {
	/**
	 * The dashboard page handler.
	 *
	 * @var Dashboard
	 */
	private $dashboard_page;

	/**
	 * The general settings page handler.
	 *
	 * @var Settings_Page
	 */
	private $settings_page;

	/**
	 * The SMS settings page handler.
	 *
	 * @var Sms_Settings_Page
	 */
	private $sms_settings_page;

	/**
	 * The Alimtalk settings page handler.
	 *
	 * @var Alimtalk_Settings_Page
	 */
	private $alimtalk_settings_page;

	/**
	 * The Rule settings page handler.
	 *
	 * @var Rule_Settings_Page
	 */
	private $rule_settings_page;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dashboard_page = new Dashboard();
		$this->settings_page = new Settings_Page();
		$this->sms_settings_page = new Sms_Settings_Page();
		$this->alimtalk_settings_page = new Alimtalk_Settings_Page();
		$this->rule_settings_page = new Rule_Settings_Page();

		Rule_Settings_Page::register(); // Handle form submissions for the rule settings page.

		add_action( 'admin_menu', [ $this, 'register_menus' ] );
	}

	/**
	 * Register the admin menus.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'SMS Connect', 'sms-connect' ),
			__( 'SMS Connect', 'sms-connect' ),
			'manage_options',
			'sms-connect-dashboard',
			[ $this->dashboard_page, 'display_page' ],
			'dashicons-email-alt',
			58
		);

		add_submenu_page(
			'sms-connect',
			__( 'History', 'sms-connect' ),
			__( 'History', 'sms-connect' ),
			'manage_options',
			'sms-connect-history',
			[ new History_Page(), 'display_page' ]
		);

		add_submenu_page(
			'sms-connect',
			__( 'General Settings', 'sms-connect' ),
			__( 'General Settings', 'sms-connect' ),
			'manage_options',
			'sms-connect-settings',
			[ $this->settings_page, 'render_page' ]
		);

		add_submenu_page(
			'sms-connect',
			__( 'SMS Settings', 'sms-connect' ),
			__( 'SMS Settings', 'sms-connect' ),
			'manage_options',
			'sms-connect-sms-settings',
			[ $this->sms_settings_page, 'render_page' ]
		);

		add_submenu_page(
			'sms-connect',
			__( 'Alimtalk Settings', 'sms-connect' ),
			__( 'Alimtalk Settings', 'sms-connect' ),
			'manage_options',
			'sms-connect-alimtalk-settings',
			[ $this->alimtalk_settings_page, 'render_page' ]
		);

		add_submenu_page(
			'sms-connect',
			__( '고급 발송 규칙', 'sms-connect' ),
			__( '고급 발송 규칙', 'sms-connect' ),
			'manage_options',
			Rule_Settings_Page::PAGE_SLUG,
			[ $this->rule_settings_page, 'render_page' ]
		);
	}
} 