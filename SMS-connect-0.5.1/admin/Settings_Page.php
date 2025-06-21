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
class Settings_Page {
	/**
	 * The option group name.
	 *
	 * @var string
	 */
	private $option_group = 'sms_connect_settings';

	/**
	 * The option name in the database.
	 * @var string
	 */
	private $option_name = 'sms_connect_options';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			$this->option_group,
			$this->option_name,
			[ $this, 'sanitize_settings' ]
		);

		add_settings_section(
			'sms_connect_api_section',
			__( 'API Credentials', 'sms-connect' ),
			[ $this, 'render_section_info' ],
			$this->option_group
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'sms-connect' ),
			[ $this, 'render_api_key_field' ],
			$this->option_group,
			'sms_connect_api_section'
		);

		add_settings_field(
			'api_secret',
			__( 'API Secret', 'sms-connect' ),
			[ $this, 'render_api_secret_field' ],
			$this->option_group,
			'sms_connect_api_section'
		);

		// Section for Low Point Notifications
		add_settings_section(
			'sms_connect_low_point_section',
			__( 'Low Point Notification', 'sms-connect' ),
			[ $this, 'render_low_point_section_info' ],
			$this->option_group
		);

		add_settings_field(
			'low_point_threshold',
			__( 'Low Point Threshold', 'sms-connect' ),
			[ $this, 'render_low_point_threshold_field' ],
			$this->option_group,
			'sms_connect_low_point_section'
		);

		add_settings_field(
			'low_point_message',
			__( 'Notification Message', 'sms-connect' ),
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
			$sanitized_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		if ( isset( $input['api_secret'] ) ) {
			$sanitized_input['api_secret'] = sanitize_text_field( $input['api_secret'] );
		}
		if ( isset( $input['low_point_threshold'] ) ) {
			$sanitized_input['low_point_threshold'] = absint( $input['low_point_threshold'] );
		}
		if ( isset( $input['low_point_message'] ) ) {
			$sanitized_input['low_point_message'] = sanitize_textarea_field( $input['low_point_message'] );
		}
		return $sanitized_input;
	}

	/**
	 * Render the info for the API section.
	 */
	public function render_section_info() {
		echo '<p>' . esc_html__( 'Enter your SMS provider API credentials below.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the info for the Low Point Notification section.
	 */
	public function render_low_point_section_info() {
		echo '<p>' . esc_html__( 'Configure notifications to be sent to administrators when SMS points are low.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Get the settings option array and print one of its fields.
	 */
	public function render_api_key_field() {
		$options = get_option( $this->option_name );
		printf(
			'<input type="text" id="api_key" name="%s[api_key]" value="%s" class="regular-text" />',
			esc_attr( $this->option_name ),
			isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its fields.
	 */
	public function render_api_secret_field() {
		$options = get_option( $this->option_name );
		printf(
			'<input type="password" id="api_secret" name="%s[api_secret]" value="%s" class="regular-text" />',
			esc_attr( $this->option_name ),
			isset( $options['api_secret'] ) ? esc_attr( $options['api_secret'] ) : ''
		);
	}

	/**
	 * Render the field for the low point threshold.
	 */
	public function render_low_point_threshold_field() {
		$options = get_option( $this->option_name );
		printf(
			'<input type="number" id="low_point_threshold" name="%s[low_point_threshold]" value="%s" class="small-text" />',
			esc_attr( $this->option_name ),
			isset( $options['low_point_threshold'] ) ? esc_attr( $options['low_point_threshold'] ) : ''
		);
		echo '<p class="description">' . esc_html__( 'Notify when points fall below this value.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render the field for the low point notification message.
	 */
	public function render_low_point_message_field() {
		$options = get_option( $this->option_name );
		printf(
			'<textarea id="low_point_message" name="%s[low_point_message]" rows="4" class="large-text">%s</textarea>',
			esc_attr( $this->option_name ),
			isset( $options['low_point_message'] ) ? esc_textarea( $options['low_point_message'] ) : ''
		);
		echo '<p class="description">' . esc_html__( 'Message to send to admins. Available variables: {current_points}, {shop_name}', 'sms-connect' ) . '</p>';
	}


	/**
	 * Render the settings page form.
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( $this->option_group );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
} 