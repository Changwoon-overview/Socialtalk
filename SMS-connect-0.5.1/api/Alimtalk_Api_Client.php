<?php
/**
 * Alimtalk API Client
 *
 * @package SmsConnect\API
 */

namespace SmsConnect\API;

use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Alimtalk_Api_Client
 *
 * Handles sending Alimtalk messages via a 3rd party API.
 */
class Alimtalk_Api_Client {
	use Point_Check_Trait;

	/**
	 * The single instance of the class.
	 *
	 * @var Alimtalk_Api_Client|null
	 */
	private static $instance = null;

	/**
	 * The API URL.
	 * @var string
	 */
	private $api_url = 'https://api.coolsms.co.kr'; // Using the same provider for consistency

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
		$options = \get_option( 'sms_connect_alimtalk_options' );

		$this->api_key    = $options['api_key'] ?? '';
		$this->sender_key = $options['sender_key'] ?? '';
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
	 * @param array  $template_vars Associative array of template variables.
	 * @return array|\WP_Error The response from the API.
	 */
	public function send_alimtalk( $template_code, $recipient, $template_vars = [] ) {
		if ( empty( $this->api_key ) || empty( $this->sender_key ) ) {
			return new \WP_Error( 'invalid_credentials', 'Alimtalk API key or Sender Key is missing.' );
		}

		$endpoint = '/alimtalk/v1/send'; // Example endpoint
		
		// This body structure is an example. It will vary by provider.
		$body = [
			'senderKey'    => $this->sender_key,
			'templateCode' => $template_code,
			'recipient'    => $recipient,
			'variables'    => $template_vars,
		];
		
		$args = [
			'method'  => 'POST',
			'headers' => [
				'Authorization' => "Bearer {$this->api_key}",
				'Content-Type'  => 'application/json',
			],
			'body'    => \wp_json_encode( $body ),
			'timeout' => 45,
		];

		$response = \wp_remote_post( $this->api_url . $endpoint, $args );

		$this->check_points_from_response( $response );

		if ( \is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = \wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$response_body = \wp_remote_retrieve_body( $response );
			return new \WP_Error( 'api_error', 'Alimtalk API request failed.', [
				'status' => $response_code,
				'body'   => $response_body,
			] );
		}

		return $response;
	}
} 