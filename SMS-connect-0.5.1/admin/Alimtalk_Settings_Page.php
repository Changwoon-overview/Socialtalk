<?php
/**
 * Alimtalk Settings Page
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Alimtalk_Settings_Page
 *
 * Handles the Alimtalk settings page.
 */
class Alimtalk_Settings_Page extends Base_Settings_Page {
	/**
	 * The option group name.
	 *
	 * @var string
	 */
	protected $option_group = 'sms_connect_alimtalk_settings';

	/**
	 * The option name in the database.
	 * @var string
	 */
	private $option_name = 'sms_connect_alimtalk_options';

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		\register_setting(
			$this->option_group,
			$this->option_name,
			[ $this, 'sanitize_settings' ]
		);

		\register_setting(
			$this->option_group,
			$this->admin_option_name,
			[ $this, 'sanitize_admin_settings' ]
		);

		// Section for API credentials
		\add_settings_section(
			'sms_connect_alimtalk_api_section',
			\__( 'Alimtalk API Credentials', 'sms-connect' ),
			null,
			$this->option_group
		);

		\add_settings_field(
			'api_key',
			\__( 'API Key (User ID)', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_alimtalk_api_section',
			[ 'id' => 'api_key', 'type' => 'text' ]
		);

		\add_settings_field(
			'sender_key',
			\__( 'Sender Key', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_alimtalk_api_section',
			[ 'id' => 'sender_key', 'type' => 'text' ]
		);

		// Admin Settings Section
		\add_settings_section(
			'sms_connect_admin_section',
			\__( '관리자 설정', 'sms-connect' ),
			[ $this, 'render_admin_section_info' ],
			$this->option_group
		);

		\add_settings_field(
			'admin_list',
			\__( '관리자 목록', 'sms-connect' ),
			[ $this, 'render_admin_list_field' ],
			$this->option_group,
			'sms_connect_admin_section'
		);


		// Section for templates
		\add_settings_section(
			'sms_connect_alimtalk_template_section',
			\__( 'Alimtalk Templates', 'sms-connect' ),
			[ $this, 'render_template_section_info' ],
			$this->option_group
		);

		// Dynamically add a field for each WooCommerce order status
		if ( \function_exists( 'wc_get_order_statuses' ) ) {
			$order_statuses = \wc_get_order_statuses();

			// Add subscription statuses if the plugin is active
			if ( \class_exists( 'WC_Subscriptions' ) ) {
				$subscription_statuses = [
					'wc-subscription-payment-complete' => \__( '정기결제 갱신 완료', 'sms-connect' ),
					'wc-subscription-cancelled'        => \__( '정기결제 취소됨', 'sms-connect' ),
					'wc-subscription-on-hold'          => \__( '정기결제 보류됨', 'sms-connect' ),
					'wc-subscription-expired'          => \__( '정기결제 만료됨', 'sms-connect' ),
				];
				$order_statuses = \array_merge( $order_statuses, $subscription_statuses );
			}

			foreach ( $order_statuses as $status => $label ) {
				\add_settings_field(
					$status,
					$label,
					[ $this, 'render_text_field' ],
					$this->option_group,
					'sms_connect_alimtalk_template_section',
					[ 'id' => $status, 'type' => 'text', 'placeholder' => \__( 'Enter Alimtalk template code', 'sms-connect' ) ]
				);
			}
		}

		// Section for user related events
		\add_settings_section(
			'sms_connect_user_events_alimtalk_section',
			\__( '회원 관련 알림 (알림톡)', 'sms-connect' ),
			null,
			$this->option_group
		);

		\add_settings_field(
			'user_register_template_code',
			\__( '신규 회원 가입 시', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_user_events_alimtalk_section',
			[ 'id' => 'user_register_template_code', 'type' => 'text', 'placeholder' => \__( 'Enter Alimtalk template code', 'sms-connect' ) ]
		);

		\add_settings_field(
			'user_role_change_template_code',
			\__( '회원 역할 변경 시', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_user_events_alimtalk_section',
			[ 'id' => 'user_role_change_template_code', 'type' => 'text', 'placeholder' => \__( 'Enter Alimtalk template code', 'sms-connect' ) ]
		);
	}

	/**
	 * Sanitize each setting field.
	 *
	 * @param array $input Contains all settings fields.
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = [];
		if ( ! is_array( $input ) ) {
			return $sanitized_input;
		}

		// First, let's get all possible statuses to check for checkboxes.
		$all_statuses = [];
		if ( \function_exists( 'wc_get_order_statuses' ) ) {
			$all_statuses = \array_keys( \wc_get_order_statuses() );
		}
		if ( \class_exists( 'WC_Subscriptions' ) ) {
			$subscription_statuses = [
				'wc-subscription-payment-complete',
				'wc-subscription-cancelled',
				'wc-subscription-on-hold',
				'wc-subscription-expired',
			];
			$all_statuses = \array_merge( $all_statuses, $subscription_statuses );
		}

		// Sanitize all inputs
		foreach ( $input as $key => $value ) {
			if ( strpos( $key, 'send_to_admin_' ) === 0 ) {
				// It's a checkbox
				$sanitized_input[ $key ] = ( 'yes' === $value ) ? 'yes' : 'no';
			} else {
				// It's a text field
				$sanitized_input[ $key ] = \sanitize_text_field( $value );
			}
		}

		// Now, ensure all 'send_to_admin' checkboxes have a value.
		// If a checkbox was unchecked, it won't be in the $input array.
		foreach ( $all_statuses as $status ) {
			$admin_key = 'send_to_admin_' . $status;
			if ( ! isset( $sanitized_input[ $admin_key ] ) ) {
				$sanitized_input[ $admin_key ] = 'no';
			}
		}

		return $sanitized_input;
	}

	/**
	 * Render the info for the template section.
	 */
	public function render_template_section_info() {
		echo '<p>' . \esc_html__( 'Enter the Alimtalk Template Code for each order status.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render a text field for a given status.
	 *
	 * @param array $args Arguments passed from add_settings_field.
	 */
	public function render_text_field( $args ) {
		$options = \get_option( $this->option_name, [] );
		$id      = $args['id'];
		$type    = $args['type'] ?? 'text';
		$placeholder = $args['placeholder'] ?? '';
		$value   = $options[ $id ] ?? '';

		\printf(
			'<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text" placeholder="%s">',
			\esc_attr( $type ),
			\esc_attr( $id ),
			\esc_attr( $this->option_name ),
			\esc_attr( $id ),
			\esc_attr( $value ),
			\esc_attr( $placeholder )
		);

		// Get all possible statuses to check if this field is an order status.
		$is_order_status = false;
		$all_order_statuses = [];
		if ( \function_exists( 'wc_get_order_statuses' ) ) {
			$all_order_statuses = \wc_get_order_statuses();
			if ( \class_exists( 'WC_Subscriptions' ) ) {
				$subscription_statuses = [
					'wc-subscription-payment-complete' => \__( '정기결제 갱신 완료', 'sms-connect' ),
					'wc-subscription-cancelled'        => \__( '정기결제 취소됨', 'sms-connect' ),
					'wc-subscription-on-hold'          => \__( '정기결제 보류됨', 'sms-connect' ),
					'wc-subscription-expired'          => \__( '정기결제 만료됨', 'sms-connect' ),
				];
				$all_order_statuses = \array_merge( $all_order_statuses, $subscription_statuses );
			}
		}
		if ( \array_key_exists( $id, $all_order_statuses ) ) {
			$is_order_status = true;
		}

		// Add 'Send to Admin' checkbox for order statuses
		if ( $is_order_status ) {
			$send_to_admin_key   = 'send_to_admin_' . $id;
			$send_to_admin_value = $options[ $send_to_admin_key ] ?? 'no';

			\printf(
				'<p><label><input type="checkbox" name="%s[%s]" value="yes" %s> %s</label></p>',
				\esc_attr( $this->option_name ),
				\esc_attr( $send_to_admin_key ),
				\checked( 'yes', $send_to_admin_value, false ),
				\esc_html__( '관리자에게도 발송', 'sms-connect' )
			);
		}
	}
} 