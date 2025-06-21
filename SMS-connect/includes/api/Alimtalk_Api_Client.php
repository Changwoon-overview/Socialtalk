<?php
/**
 * Alimtalk API Client
 *
 * @package SmsConnect\API
 */

namespace SmsConnect\API;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Alimtalk_Api_Client
 *
 * Handles communication with the Kakao Alimtalk API provider.
 */
class Alimtalk_Api_Client {

	/**
	 * The single instance of the class.
	 *
	 * @var Alimtalk_Api_Client|null
	 */
	private static $instance = null;

	/**
	 * The base URL for the API.
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * The API key (or User ID).
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * The Sender Key for Alimtalk.
	 *
	 * @var string
	 */
	private $sender_key = '';

	/**
	 * Alimtalk_Api_Client constructor.
	 *
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// These settings will be loaded from a dedicated Alimtalk settings page.
		$options = get_option( 'sms_connect_alimtalk_options' ); // Assumes new option group

		$this->api_url    = 'https://api.alimtalk.provider.com'; // Placeholder
		$this->api_key    = isset( $options['api_key'] ) ? trim( $options['api_key'] ) : '';
		$this->sender_key = isset( $options['sender_key'] ) ? trim( $options['sender_key'] ) : '';
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @return Alimtalk_Api_Client
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Send an Alimtalk request.
	 *
	 * @param string $template_code The Alimtalk template code.
	 * @param string $recipient The phone number to send to.
	 * @param array  $template_vars The variables to replace in the template.
	 * @return array|\WP_Error The response from the API or a WP_Error on failure.
	 */
	public function send_request( $template_code, $recipient, $template_vars = [] ) {
		$url = $this->api_url . '/send/alimtalk'; // Example endpoint

		$headers = [
			// Headers might differ based on provider, e.g., 'X-API-KEY'
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key, 
		];
		
		$body = [
			'senderKey'    => $this->sender_key,
			'templateCode' => $template_code,
			'recipient'    => $recipient,
			'variables'    => $template_vars,
			// Other provider-specific fields might be needed
		];

		$args = [
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		];

		$response = wp_remote_post( $url, $args );

		// Check for success or failure, potentially with a fallback to SMS
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Trigger fallback to SMS here later
		}

		return $response;
	}
} 