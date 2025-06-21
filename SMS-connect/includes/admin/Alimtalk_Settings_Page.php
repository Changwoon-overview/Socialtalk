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
	 * The option name for admins in the database.
	 * @var string
	 */
	private $admin_option_name = 'sms_connect_admins';

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
	 * Sanitize admin settings.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_settings( $input ) {
		$sanitized_input = [];
		if ( ! \is_array( $input ) ) {
			return $sanitized_input;
		}

		foreach ( $input as $index => $admin ) {
			if ( empty( $admin['name'] ) && empty( $admin['phone'] ) ) {
				continue;
			}
			$sanitized_input[ $index ]['enable'] = isset( $admin['enable'] ) ? 'yes' : 'no';
			$sanitized_input[ $index ]['name']   = \sanitize_text_field( $admin['name'] );
			$sanitized_input[ $index ]['phone']  = \sanitize_text_field( $admin['phone'] );
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
	 * Render the info for the admin section.
	 */
	public function render_admin_section_info() {
		echo '<p>' . \esc_html__( '주요 이벤트 발생 시 알림을 받을 관리자 목록을 설정합니다. (예: 신규 주문, 포인트 부족 등)', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render a repeater field for admins.
	 */
	public function render_admin_list_field() {
		$admins = \get_option( $this->admin_option_name, [ [ 'enable' => 'yes', 'name' => '', 'phone' => '' ] ] );
		?>
		<table class="wp-list-table widefat fixed striped" id="sms-connect-admin-list">
			<thead>
			<tr>
				<th style="width: 50px;"><?php \esc_html_e( '활성화', 'sms-connect' ); ?></th>
				<th><?php \esc_html_e( '관리자명', 'sms-connect' ); ?></th>
				<th><?php \esc_html_e( '휴대폰 번호', 'sms-connect' ); ?></th>
				<th style="width: 50px;"></th>
			</tr>
			</thead>
			<tbody id="admin-list-body">
			<?php foreach ( $admins as $index => $admin ) : ?>
				<tr class="admin-row">
					<td>
						<input type="checkbox" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[<?php echo \esc_attr( $index ); ?>][enable]" value="yes" <?php \checked( 'yes', $admin['enable'] ?? 'no' ); ?>>
					</td>
					<td>
						<input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[<?php echo \esc_attr( $index ); ?>][name]" value="<?php echo \esc_attr( $admin['name'] ); ?>" class="regular-text">
					</td>
					<td>
						<input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[<?php echo \esc_attr( $index ); ?>][phone]" value="<?php echo \esc_attr( $admin['phone'] ); ?>" class="regular-text">
					</td>
					<td>
						<button type="button" class="button button-secondary remove-admin-row">-</button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th colspan="4">
					<button type="button" class="button button-primary" id="add-admin-row">+</button>
				</th>
			</tr>
			</tfoot>
		</table>
		<script>
			jQuery(document).ready(function ($) {
				let rowIndex = <?php echo \count( $admins ); ?>;

				$('#add-admin-row').on('click', function () {
					const newRow = `
                        <tr class="admin-row">
                            <td>
                                <input type="checkbox" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[${rowIndex}][enable]" value="yes" checked>
                            </td>
                            <td>
                                <input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[${rowIndex}][name]" value="" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[${rowIndex}][phone]" value="" class="regular-text">
                            </td>
                            <td>
                                <button type="button" class="button button-secondary remove-admin-row">-</button>
                            </td>
                        </tr>
                    `;
					$('#admin-list-body').append(newRow);
					rowIndex++;
				});

				$('#sms-connect-admin-list').on('click', '.remove-admin-row', function () {
					$(this).closest('tr').remove();
				});
			});
		</script>
		<?php
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


	/**
	 * Render the settings page form.
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				\settings_fields( $this->option_group );
				\do_settings_sections( $this->option_group );
				\submit_button();
				?>
			</form>
		</div>
		<?php
	}
} 