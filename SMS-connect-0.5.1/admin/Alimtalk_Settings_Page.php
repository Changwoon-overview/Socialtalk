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
	protected $option_name = 'sms_connect_alimtalk_options';

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

		// Register settings sections
		\add_settings_section(
			'sms_connect_alimtalk_section',
			\__( '알림톡 API 설정', 'sms-connect' ),
			[ $this, 'render_section_info' ],
			$this->option_group
		);

		// Add settings fields
		\add_settings_field(
			'api_key',
			\__( 'API 키', 'sms-connect' ),
			[ $this, 'render_api_key_field' ],
			$this->option_group,
			'sms_connect_alimtalk_section'
		);

		\add_settings_field(
			'sender_key',
			\__( '발송자 키', 'sms-connect' ),
			[ $this, 'render_sender_key_field' ],
			$this->option_group,
			'sms_connect_alimtalk_section'
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

		$all_statuses = $this->get_all_statuses();
		foreach ( $all_statuses as $status => $label ) {
			\add_settings_field(
				$status,
				$label,
				[ $this, 'render_text_field' ],
				$this->option_group,
				'sms_connect_alimtalk_template_section',
				[ 'id' => $status, 'type' => 'text', 'placeholder' => \__( 'Enter Alimtalk template code', 'sms-connect' ) ]
			);
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

		$all_statuses = $this->get_all_statuses();

		// Sanitize all inputs
		foreach ( $input as $key => $value ) {
			if ( strpos( $key, 'send_to_admin_' ) === 0 ) {
				$sanitized_input[ $key ] = 'yes'; // Only 'yes' is stored.
			} else {
				$sanitized_input[ $key ] = \sanitize_text_field( $value );
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
	 * Render the info for the template section.
	 */
	public function render_template_section_info() {
		echo '<p>' . \esc_html__( 'Enter the Alimtalk Template Code for each order status.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the info for the section.
	 */
	public function render_section_info() {
		echo '<p>' . \esc_html__( 'Enter your Alimtalk API credentials.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the API key field.
	 */
	public function render_api_key_field() {
		$options = \get_option( $this->option_name );
		\printf(
			'<input type="text" id="api_key" name="%s[api_key]" value="%s" class="regular-text" />',
			\esc_attr( $this->option_name ),
			isset( $options['api_key'] ) ? \esc_attr( $options['api_key'] ) : ''
		);
	}

	/**
	 * Render the sender key field.
	 */
	public function render_sender_key_field() {
		$options = \get_option( $this->option_name );
		\printf(
			'<input type="text" id="sender_key" name="%s[sender_key]" value="%s" class="regular-text" />',
			\esc_attr( $this->option_name ),
			isset( $options['sender_key'] ) ? \esc_attr( $options['sender_key'] ) : ''
		);
		echo '<p class="description">' . \esc_html__( 'Enter your Kakao Alimtalk sender key.', 'sms-connect' ) . '</p>';
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
} 