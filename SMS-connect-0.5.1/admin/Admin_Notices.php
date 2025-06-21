<?php
/**
 * Admin Notices
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Notices
 *
 * Handles the display of admin notices.
 */
class Admin_Notices {

	/**
	 * Hooks into WordPress to display notices.
	 */
	public function init() {
		add_action( 'admin_notices', [ $this, 'display_notices' ] );
	}

	/**
	 * Checks for conditions and displays notices if necessary.
	 */
	public function display_notices() {
		// Check for general API credentials
		$sms_options = \get_option( 'sms_connect_options', [] );
		$api_key     = $sms_options['api_key'] ?? '';
		$api_secret  = $sms_options['api_secret'] ?? '';

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			$settings_url = admin_url( 'admin.php?page=sms-connect-settings' );
			$this->render_notice(
				'error',
				sprintf(
					// translators: %s: settings page URL
					__( '<strong>[SMS 연결]</strong> SMS API 키 또는 시크릿이 누락되었습니다. SMS 발송을 활성화하려면 <a href="%s">인증 정보를 입력</a>하세요.', 'sms-connect' ),
					esc_url( $settings_url )
				)
			);
		}

		// Check for Alimtalk API credentials
		$alimtalk_options    = \get_option( 'sms_connect_alimtalk_options', [] );
		$alimtalk_api_key    = $alimtalk_options['api_key'] ?? '';
		$alimtalk_sender_key = $alimtalk_options['sender_key'] ?? '';

		if ( empty( $alimtalk_api_key ) || empty( $alimtalk_sender_key ) ) {
			$settings_url = admin_url( 'admin.php?page=sms-connect-alimtalk-settings' );
			$this->render_notice(
				'warning',
				sprintf(
					// translators: %s: settings page URL
					__( '<strong>[SMS 연결]</strong> 알림톡 인증 정보가 완전히 설정되지 않았습니다. 알림톡 발송을 활성화하려면 <a href="%s">설정 페이지를 방문</a>하세요.', 'sms-connect' ),
					esc_url( $settings_url )
				)
			);
		}

		// Show activation success notice if this is a fresh activation
		if ( \get_transient( 'sms_connect_activation_notice' ) ) {
			$this->render_notice(
				'success',
				\__( '<strong>[SMS 연결]</strong> 플러그인이 성공적으로 활성화되었습니다! 설정을 완료하여 SMS 및 알림톡 기능을 사용하세요.', 'sms-connect' )
			);
			\delete_transient( 'sms_connect_activation_notice' );
		}
	}

	/**
	 * Renders a notice indicating that WooCommerce is not active.
	 * Static so it can be called without instantiating the class.
	 */
	public static function show_woocommerce_not_active_notice() {
		$message = sprintf(
			// translators: %s: Plugin name
			__( '<strong>%s</strong> requires WooCommerce to be installed and active. Please install and activate WooCommerce.', 'sms-connect' ),
			'SMS-connect'
		);
		?>
		<div class="notice notice-error">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders a single admin notice.
	 *
	 * @param string $type    The notice type (e.g., 'error', 'warning', 'success', 'info').
	 * @param string $message The message content (HTML is allowed).
	 */
	private function render_notice( $type, $message ) {
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}
} 