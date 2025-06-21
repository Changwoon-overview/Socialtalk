<?php
/**
 * SMS Settings Page
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sms_Settings_Page
 *
 * Handles the SMS settings page.
 */
class Sms_Settings_Page extends Base_Settings_Page {
	/**
	 * The option group name.
	 *
	 * @var string
	 */
	protected $option_group = 'sms_connect_sms_settings';

	/**
	 * The option name in the database.
	 * @var string
	 */
	protected $option_name = 'sms_connect_sms_options';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Add hooks.
	 */
	private function add_hooks() {
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

		// Register settings sections
		\add_settings_section(
			'sms_connect_sms_section',
			\__( 'SMS 발송 설정', 'sms-connect' ),
			[ $this, 'render_section_info' ],
			$this->option_group
		);

		// Add settings fields
		\add_settings_field(
			'enable_order_status_notifications',
			\__( '주문 상태 변경 알림', 'sms-connect' ),
			[ $this, 'render_checkbox_field' ],
			$this->option_group,
			'sms_connect_sms_section',
			[
				'option_name' => $this->option_name,
				'field_name'  => 'enable_order_status_notifications',
				'label'       => \__( '주문 상태가 변경될 때 고객에게 SMS 알림 발송', 'sms-connect' ),
			]
		);

		\add_settings_field(
			'enable_new_user_notifications',
			\__( '신규 회원가입 알림', 'sms-connect' ),
			[ $this, 'render_checkbox_field' ],
			$this->option_group,
			'sms_connect_sms_section',
			[
				'option_name' => $this->option_name,
				'field_name'  => 'enable_new_user_notifications',
				'label'       => \__( '신규 회원가입 시 관리자에게 SMS 알림 발송', 'sms-connect' ),
			]
		);

		\add_settings_field(
			'enable_subscription_notifications',
			\__( '구독 상태 알림', 'sms-connect' ),
			[ $this, 'render_checkbox_field' ],
			$this->option_group,
			'sms_connect_sms_section',
			[
				'option_name' => $this->option_name,
				'field_name'  => 'enable_subscription_notifications',
				'label'       => \__( '구독 상태가 변경될 때 고객에게 SMS 알림 발송', 'sms-connect' ),
			]
		);

		\add_settings_section(
			'sms_connect_order_status_section',
			\__( 'Order Status Messages', 'sms-connect' ),
			[ $this, 'render_section_info' ],
			$this->option_group
		);

		$all_statuses = $this->get_all_statuses();
		foreach ( $all_statuses as $status => $label ) {
			\add_settings_field(
				$status,
				$label,
				[ $this, 'render_textarea_field' ],
				$this->option_group,
				'sms_connect_order_status_section',
				[ 'id' => $status ]
			);
		}

		// Section for user related events
		\add_settings_section(
			'sms_connect_user_events_section',
			\__( '회원 관련 알림', 'sms-connect' ),
			null,
			$this->option_group
		);

		\add_settings_field(
			'user_register_message',
			\__( '신규 회원 가입 시', 'sms-connect' ),
			[ $this, 'render_textarea_field' ],
			$this->option_group,
			'sms_connect_user_events_section',
			[ 'id' => 'user_register_message' ]
		);

		\add_settings_field(
			'user_role_change_message',
			\__( '회원 역할 변경 시', 'sms-connect' ),
			[ $this, 'render_textarea_field' ],
			$this->option_group,
			'sms_connect_user_events_section',
			[ 'id' => 'user_role_change_message' ]
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

		$all_statuses = $this->get_all_statuses();

		// Sanitize all inputs
		foreach ( $input as $key => $value ) {
			if ( strpos( $key, 'send_to_admin_' ) === 0 ) {
				$sanitized_input[ $key ] = 'yes'; // Only 'yes' is stored.
			} else {
				$sanitized_input[ $key ] = \sanitize_textarea_field( $value );
			}
		}

		// Ensure checkboxes that are not checked are saved as 'no'
		foreach ( $all_statuses as $status => $label ) {
			$admin_key = 'send_to_admin_' . $status;
			if ( ! isset( $input[ $admin_key ] ) ) {
				$sanitized_input[ $admin_key ] = 'no';
			}
		}

		return $sanitized_input;
	}

	/**
	 * Render the info for the section.
	 */
	public function render_section_info() {
		echo '<p>' . \esc_html__( 'Set the SMS message to be sent for each order status. Use template variables like {customer_name}, {order_number}, etc.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render a textarea field for a given status.
	 *
	 * @param array $args Arguments passed from add_settings_field.
	 */
	public function render_textarea_field( $args ) {
		$options = \get_option( $this->option_name, [] );
		$id      = $args['id'];
		$value   = $options[ $id ] ?? '';

		\printf(
			'<textarea id="%s" name="%s[%s]" rows="5" cols="50" class="large-text">%s</textarea>',
			\esc_attr( $id ),
			\esc_attr( $this->option_name ),
			\esc_attr( $id ),
			\esc_textarea( $value )
		);

		// Add 'Send to Admin' checkbox for order statuses
		$all_statuses = $this->get_all_statuses();
		if ( \array_key_exists( $id, $all_statuses ) ) {
			$send_to_admin_key   = 'send_to_admin_' . $id;
			$send_to_admin_value = $options[ $send_to_admin_key ] ?? 'no';

			\printf(
				'<p><label><input type="checkbox" name="%s[%s]" value="yes" %s> %s</label></p>',
				\esc_attr( $this->option_name ), // Save to the same option name
				\esc_attr( $send_to_admin_key ),
				\checked( 'yes', $send_to_admin_value, false ),
				\esc_html__( '관리자에게도 발송', 'sms-connect' )
			);
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Arguments passed from add_settings_field.
	 */
	public function render_checkbox_field( $args ) {
		$options     = \get_option( $args['option_name'], [] );
		$field_name  = $args['field_name'];
		$label       = $args['label'];
		$value       = $options[ $field_name ] ?? 'no';

		\printf(
			'<label><input type="checkbox" name="%s[%s]" value="yes" %s> %s</label>',
			\esc_attr( $args['option_name'] ),
			\esc_attr( $field_name ),
			\checked( 'yes', $value, false ),
			\esc_html( $label )
		);
	}

	/**
	 * Get all order and subscription statuses.
	 *
	 * @return array
	 */
	private function get_all_statuses() {
		$all_statuses = [];
		if ( \function_exists( 'wc_get_order_statuses' ) ) {
			$all_statuses = \wc_get_order_statuses();
		}

		if ( \class_exists( 'WC_Subscriptions' ) ) {
			$subscription_statuses = [
				'wc-subscription-payment-complete' => \__( '정기결제 갱신 완료', 'sms-connect' ),
				'wc-subscription-cancelled'        => \__( '정기결제 취소됨', 'sms-connect' ),
				'wc-subscription-on-hold'          => \__( '정기결제 보류됨', 'sms-connect' ),
				'wc-subscription-expired'          => \__( '정기결제 만료됨', 'sms-connect' ),
			];
			$all_statuses = \array_merge( $all_statuses, $subscription_statuses );
		}

		return $all_statuses;
	}
} 