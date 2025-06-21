<?php
/**
 * Settings Page
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings_Page
 *
 * Handles the creation of the settings page and its fields.
 */
class Settings_Page extends Base_Settings_Page {
	/**
	 * The option group name.
	 *
	 * @var string
	 */
	protected $option_group = 'sms_connect_settings';

	/**
	 * The option name in the database.
	 * @var string
	 */
	protected $option_name = 'sms_connect_options';

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

		\add_settings_section(
			'sms_connect_api_section',
			\__( 'API 인증 정보', 'sms-connect' ),
			[ $this, 'render_section_info' ],
			$this->option_group
		);

		\add_settings_field(
			'api_key',
			\__( 'API 키', 'sms-connect' ),
			[ $this, 'render_api_key_field' ],
			$this->option_group,
			'sms_connect_api_section'
		);

		\add_settings_field(
			'api_secret',
			\__( 'API 시크릿', 'sms-connect' ),
			[ $this, 'render_api_secret_field' ],
			$this->option_group,
			'sms_connect_api_section'
		);

		\add_settings_field(
			'sender_number',
			\__( '발송자 번호', 'sms-connect' ),
			[ $this, 'render_sender_number_field' ],
			$this->option_group,
			'sms_connect_api_section'
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

		// Section for Low Point Notifications
		\add_settings_section(
			'sms_connect_low_point_section',
			\__( '포인트 부족 알림', 'sms-connect' ),
			[ $this, 'render_low_point_section_info' ],
			$this->option_group
		);

		\add_settings_field(
			'low_point_threshold',
			\__( '포인트 부족 기준', 'sms-connect' ),
			[ $this, 'render_low_point_threshold_field' ],
			$this->option_group,
			'sms_connect_low_point_section'
		);

		\add_settings_field(
			'low_point_message',
			\__( '알림 메시지', 'sms-connect' ),
			[ $this, 'render_low_point_message_field' ],
			$this->option_group,
			'sms_connect_low_point_section'
		);
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = [];
		if ( isset( $input['api_key'] ) ) {
			$sanitized_input['api_key'] = \sanitize_text_field( $input['api_key'] );
		}
		if ( isset( $input['api_secret'] ) ) {
			$sanitized_input['api_secret'] = \sanitize_text_field( $input['api_secret'] );
		}
		if ( isset( $input['sender_number'] ) ) {
			$sanitized_input['sender_number'] = \sanitize_text_field( $input['sender_number'] );
		}
		if ( isset( $input['low_point_threshold'] ) ) {
			$sanitized_input['low_point_threshold'] = \absint( $input['low_point_threshold'] );
		}
		if ( isset( $input['low_point_message'] ) ) {
			$sanitized_input['low_point_message'] = \sanitize_textarea_field( $input['low_point_message'] );
		}
		return $sanitized_input;
	}

	/**
	 * Render the info for the API section.
	 */
	public function render_section_info() {
		echo '<p>' . \esc_html__( 'SMS 발송을 위한 API 인증 정보를 입력하세요.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the info for the Low Point Notification section.
	 */
	public function render_low_point_section_info() {
		echo '<p>' . \esc_html__( 'SMS 포인트가 부족할 때 관리자에게 보낼 알림을 설정합니다.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Get the settings option array and print one of its fields.
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
	 * Get the settings option array and print one of its fields.
	 */
	public function render_api_secret_field() {
		$options = \get_option( $this->option_name );
		\printf(
			'<input type="password" id="api_secret" name="%s[api_secret]" value="%s" class="regular-text" />',
			\esc_attr( $this->option_name ),
			isset( $options['api_secret'] ) ? \esc_attr( $options['api_secret'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its fields.
	 */
	public function render_sender_number_field() {
		$options = \get_option( $this->option_name );
		\printf(
			'<input type="text" id="sender_number" name="%s[sender_number]" value="%s" class="regular-text" placeholder="%s" />',
			\esc_attr( $this->option_name ),
			isset( $options['sender_number'] ) ? \esc_attr( $options['sender_number'] ) : '',
			\esc_attr__( '발송자 번호 (예: 01012345678)', 'sms-connect' )
		);
		echo '<p class="description">' . \esc_html__( 'SMS 발송 시 표시될 발송자 번호를 입력하세요.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the field for the low point threshold.
	 */
	public function render_low_point_threshold_field() {
		$options = \get_option( $this->option_name );
		\printf(
			'<input type="number" id="low_point_threshold" name="%s[low_point_threshold]" value="%s" class="small-text" />',
			\esc_attr( $this->option_name ),
			isset( $options['low_point_threshold'] ) ? \esc_attr( $options['low_point_threshold'] ) : ''
		);
		echo '<p class="description">' . \esc_html__( '이 값 이하로 포인트가 떨어지면 알림을 보냅니다.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the field for the low point notification message.
	 */
	public function render_low_point_message_field() {
		$options = \get_option( $this->option_name );
		\printf(
			'<textarea id="low_point_message" name="%s[low_point_message]" rows="4" class="large-text">%s</textarea>',
			\esc_attr( $this->option_name ),
			isset( $options['low_point_message'] ) ? \esc_textarea( $options['low_point_message'] ) : ''
		);
		echo '<p class="description">' . \esc_html__( '관리자에게 보낼 메시지입니다. 사용 가능한 변수: {current_points}, {shop_name}', 'sms-connect' ) . '</p>';
	}
} 