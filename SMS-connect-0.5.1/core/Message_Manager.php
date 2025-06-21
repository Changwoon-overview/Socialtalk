<?php
/**
 * Message Manager
 *
 * @package SmsConnect\Core
 */

namespace SmsConnect\Core;

use SmsConnect\API\Alimtalk_Api_Client;
use SmsConnect\API\Sms_Api_Client;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Message_Manager
 *
 * Manages message properties like type (SMS/LMS) based on content.
 */
class Message_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var Message_Manager|null
	 */
	private static $instance = null;

	/**
	 * SMS API Client.
	 *
	 * @var Sms_Api_Client
	 */
	private $sms_api_client;

	/**
	 * Alimtalk API Client.
	 *
	 * @var Alimtalk_Api_Client
	 */
	private $alimtalk_api_client;

	/**
	 * Constructor.
	 *
	 * @param Sms_Api_Client      $sms_api_client      The SMS API client instance.
	 * @param Alimtalk_Api_Client $alimtalk_api_client The Alimtalk API client instance.
	 */
	public function __construct( Sms_Api_Client $sms_api_client, Alimtalk_Api_Client $alimtalk_api_client ) {
		$this->sms_api_client      = $sms_api_client;
		$this->alimtalk_api_client = $alimtalk_api_client;
		self::$instance = $this;
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @return Message_Manager|null
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Get the message type based on its content length.
	 *
	 * In Korea, SMS length is typically calculated based on EUC-KR encoding.
	 * 1 byte for English/numbers/symbols, 2 bytes for Korean characters.
	 * - SMS: <= 90 bytes
	 * - LMS: > 90 bytes
	 *
	 * @param string $content The message content.
	 * @return string 'SMS' or 'LMS'.
	 */
	public function get_message_type( $content ) {
		$byte_length = $this->calculate_byte_length( $content );

		if ( $byte_length <= 90 ) {
			return 'SMS';
		}

		return 'LMS';
	}

	/**
	 * Calculate the byte length of a string as per EUC-KR encoding rules.
	 *
	 * This is a simplified calculation. `mb_strwidth` with EUC-KR
	 * gives a good approximation for this purpose.
	 *
	 * @param string $string The string to measure.
	 * @return int The calculated byte length.
	 */
	private function calculate_byte_length( $string ) {
		// Check if the mbstring extension is loaded.
		if ( \function_exists( 'mb_strwidth' ) ) {
			// mb_strwidth returns the width of a string, where multi-byte characters
			// are usually counted as 2. This is a common way to estimate byte length
			// for Korean SMS standards without full charset conversion.
			return \mb_strwidth( $string, 'EUC-KR' );
		} else {
			// Fallback if mbstring is not available.
			// This is not as accurate for multi-byte characters but prevents fatal errors.
			return \strlen( $string );
		}
	}

	/**
	 * Get the phone numbers of enabled admins.
	 *
	 * @return array An array of admin phone numbers.
	 */
	public function get_admin_phone_numbers() {
		$admin_option = \get_option( 'sms_connect_admins', [] );
		$phone_numbers = [];

		if ( ! is_array( $admin_option ) ) {
			return $phone_numbers;
		}

		foreach ( $admin_option as $admin ) {
			if ( ! empty( $admin['enable'] ) && 'yes' === $admin['enable'] && ! empty( $admin['phone'] ) ) {
				$phone_numbers[] = $admin['phone'];
			}
		}

		return $phone_numbers;
	}

	/**
	 * Checks the current SMS points and notifies admins if it's below a threshold.
	 *
	 * @param int $current_points The current number of SMS points/balance.
	 */
	public function check_and_notify_low_points( $current_points ) {
		// Check if the notification has been sent recently.
		if ( \get_transient( 'sms_connect_low_point_notified' ) ) {
			return;
		}

		$options = \get_option( 'sms_connect_settings', [] );
		$threshold = isset( $options['low_point_threshold'] ) ? (int) $options['low_point_threshold'] : 0;
		$message_template = isset( $options['low_point_message'] ) ? $options['low_point_message'] : '';

		// Proceed only if the feature is configured and the threshold is met.
		if ( $threshold > 0 && ! empty( $message_template ) && $current_points < $threshold ) {
			$admin_phones = $this->get_admin_phone_numbers();
			if ( empty( $admin_phones ) ) {
				return;
			}

			// Replace variables in the message.
			$message = \str_replace( '{current_points}', $current_points, $message_template );
			$message = \str_replace( '{shop_name}', \get_bloginfo( 'name' ), $message );

			// Send the SMS to all admins.
			$this->send_sms( $admin_phones, $message );
			
			// Set a transient to prevent re-sending the notice for 24 hours.
			\set_transient( 'sms_connect_low_point_notified', true, DAY_IN_SECONDS );
		}
	}

	/**
	 * Sends an SMS to one or more recipients.
	 * This is a direct sending method for internal use.
	 *
	 * @param string|array $recipients A single phone number or an array of phone numbers.
	 * @param string       $message    The message content.
	 * @return void
	 */
	public function send_sms( $recipients, $message ) {
		if ( empty( $recipients ) || empty( $message ) ) {
			return;
		}

		$options      = \get_option( 'sms_connect_settings', [] );
		$sender       = $options['sender_number'] ?? '';
		$message_type = $this->get_message_type( $message );
		$recipients   = (array) $recipients; // Ensure it's an array.

		if ( empty( $sender ) ) {
			return;
		}

		foreach ( $recipients as $recipient ) {
			if ( empty( $recipient ) ) {
				continue;
			}
			$api_data = [
				'to'   => $recipient,
				'from' => $sender,
				'text' => $message,
				'type' => $message_type,
			];
			
			$this->sms_api_client->send_request( '/messages/v4/send', $api_data );

			// Note: We are not logging these internal notifications to the main DB log to avoid clutter.
			// This could be changed if logging is required.
		}
	}

	/**
	 * Sends an Alimtalk to one or more recipients.
	 *
	 * @param string|array $recipients A single phone number or an array of phone numbers.
	 * @param string       $template_code The Alimtalk template code.
	 * @param array        $template_vars Associative array of template variables.
	 * @return void
	 */
	public function send_alimtalk( $recipients, $template_code, $template_vars = [] ) {
		if ( empty( $recipients ) || empty( $template_code ) ) {
			return;
		}

		$recipients = (array) $recipients;

		foreach ( $recipients as $recipient ) {
			if ( empty( $recipient ) ) {
				continue;
			}
			
			$this->alimtalk_api_client->send_alimtalk( $template_code, $recipient, $template_vars );

			// Note: We are not logging these internal notifications to the main DB log to avoid clutter.
			// This could be changed if logging is required.
		}
	}

	/**
	 * Log a message with a specific level.
	 *
	 * @param string $message The message to log
	 * @param string $level   The log level (info, warning, error)
	 */
	private function log_message( $message, $level = 'info' ) {
		if ( \function_exists( 'error_log' ) ) {
			\error_log( "[SMS 연결 - {$level}] {$message}" );
		}
	}
} 