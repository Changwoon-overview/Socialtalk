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
class Alimtalk_Settings_Page {
	/**
	 * The option group name.
	 *
	 * @var string
	 */
	private $option_group = 'sms_connect_alimtalk_settings';

	/**
	 * The option name in the database.
	 * @var string
	 */
	private $option_name = 'sms_connect_alimtalk_options';

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

		// Section for API credentials
		add_settings_section(
			'sms_connect_alimtalk_api_section',
			__( 'Alimtalk API Credentials', 'sms-connect' ),
			null,
			$this->option_group
		);

		add_settings_field(
			'api_key',
			__( 'API Key (User ID)', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_alimtalk_api_section',
			[ 'id' => 'api_key', 'type' => 'text' ]
		);

		add_settings_field(
			'sender_key',
			__( 'Sender Key', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_alimtalk_api_section',
			[ 'id' => 'sender_key', 'type' => 'text' ]
		);


		// Section for templates
		add_settings_section(
			'sms_connect_alimtalk_template_section',
			__( 'Alimtalk Templates', 'sms-connect' ),
			[ $this, 'render_template_section_info' ],
			$this->option_group
		);

		// Dynamically add a field for each WooCommerce order status
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$order_statuses = wc_get_order_statuses();

			// Add subscription statuses if the plugin is active
			if ( class_exists( 'WC_Subscriptions' ) ) {
				$subscription_statuses = [
					'wc-subscription-payment-complete' => __( '정기결제 갱신 완료', 'sms-connect' ),
					'wc-subscription-cancelled'        => __( '정기결제 취소됨', 'sms-connect' ),
					'wc-subscription-on-hold'          => __( '정기결제 보류됨', 'sms-connect' ),
					'wc-subscription-expired'          => __( '정기결제 만료됨', 'sms-connect' ),
				];
				$order_statuses = array_merge( $order_statuses, $subscription_statuses );
			}

			foreach ( $order_statuses as $status => $label ) {
				add_settings_field(
					'template_' . $status,
					$label,
					[ $this, 'render_text_field' ],
					$this->option_group,
					'sms_connect_alimtalk_template_section',
					[ 'id' => 'template_' . $status, 'type' => 'text', 'placeholder' => __( 'Enter Alimtalk template code', 'sms-connect' ) ]
				);
			}
		}

		// Section for user related events
		add_settings_section(
			'sms_connect_user_events_alimtalk_section',
			__( '회원 관련 알림 (알림톡)', 'sms-connect' ),
			null,
			$this->option_group
		);

		add_settings_field(
			'user_register_template_code',
			__( '신규 회원 가입 시', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_user_events_alimtalk_section',
			[ 'id' => 'user_register_template_code', 'type' => 'text', 'placeholder' => __( 'Enter Alimtalk template code', 'sms-connect' ) ]
		);

		add_settings_field(
			'user_role_change_template_code',
			__( '회원 역할 변경 시', 'sms-connect' ),
			[ $this, 'render_text_field' ],
			$this->option_group,
			'sms_connect_user_events_alimtalk_section',
			[ 'id' => 'user_role_change_template_code', 'type' => 'text', 'placeholder' => __( 'Enter Alimtalk template code', 'sms-connect' ) ]
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
		foreach ( $input as $key => $value ) {
			$sanitized_input[ $key ] = sanitize_text_field( $value );
		}
		return $sanitized_input;
	}

	/**
	 * Render the info for the template section.
	 */
	public function render_template_section_info() {
		echo '<p>' . esc_html__( 'Enter the Alimtalk Template Code for each order status.', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render a generic text field.
	 *
	 * @param array $args Arguments passed from add_settings_field.
	 */
	public function render_text_field( $args ) {
		$options = get_option( $this->option_name, [] );
		$id = $args['id'];
		$type = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$value = isset( $options[ $id ] ) ? $options[ $id ] : '';
		
		printf(
			'<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $type ),
			esc_attr( $id ),
			esc_attr( $this->option_name ),
			esc_attr( $id ),
			esc_attr( $value ),
			esc_attr( $placeholder )
		);

		// Add description for user related fields
		if ( 'user_register_template_code' === $id ) {
			echo '<p class="description">' . esc_html__( '사용 가능한 변수: {user_login}, {user_email}, {user_display_name}, {shop_name}', 'sms-connect' ) . '</p>';
		} elseif ( 'user_role_change_template_code' === $id ) {
			echo '<p class="description">' . esc_html__( '사용 가능한 변수: {user_login}, {user_display_name}, {new_role}, {old_role}, {shop_name}', 'sms-connect' ) . '</p>';
		}
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