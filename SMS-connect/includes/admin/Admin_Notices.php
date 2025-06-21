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
		$api_key    = get_option( 'sms_connect_api_key' );
		$api_secret = get_option( 'sms_connect_api_secret' );

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			$settings_url = admin_url( 'admin.php?page=sms-connect-settings' );
			$this->render_notice(
				'error',
				sprintf(
					// translators: %s: settings page URL
					__( '<strong>[SMS Connect]</strong> SMS API Key or Secret is missing. Please <a href="%s">enter your credentials</a> to enable SMS sending.', 'sms-connect' ),
					esc_url( $settings_url )
				)
			);
		}

		// Check for Alimtalk API credentials
		$alimtalk_api_key    = get_option( 'sms_connect_alimtalk_api_key' );
		$alimtalk_api_secret = get_option( 'sms_connect_alimtalk_api_secret' );
		$alimtalk_sender_key = get_option( 'sms_connect_alimtalk_sender_key' );

		if ( empty( $alimtalk_api_key ) || empty( $alimtalk_api_secret ) || empty($alimtalk_sender_key) ) {
			$settings_url = admin_url( 'admin.php?page=sms-connect-alimtalk-settings' );
			$this->render_notice(
				'warning',
				sprintf(
					// translators: %s: settings page URL
					__( '<strong>[SMS Connect]</strong> Alimtalk credentials are not fully configured. Please <a href="%s">visit the settings page</a> to enable Alimtalk sending.', 'sms-connect' ),
					esc_url( $settings_url )
				)
			);
		}
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