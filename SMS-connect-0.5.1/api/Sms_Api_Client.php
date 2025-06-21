<?php
/**
 * SMS API Client
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
 * Class Sms_Api_Client
 *
 * Handles communication with the SMS provider's API.
 */
class Sms_Api_Client {
	use Point_Check_Trait;

	/**
	 * Singleton instance.
	 * @var Sms_Api_Client
	 */
	private static $instance = null;

	/**
	 * The API key.
	 * @var string
	 */
	private $api_key;

	/**
	 * The API secret.
	 * @var string
	 */
	private $api_secret;

	/**
	 * The API URL.
	 * @var string
	 */
	private $api_url = 'https://api.coolsms.co.kr'; // Example URL

	/**
	 * Private constructor for Singleton pattern.
	 */
	private function __construct() {
		$options = \get_option( 'sms_connect_options' );

		$this->api_key    = $options['api_key'] ?? '';
		$this->api_secret = $options['api_secret'] ?? '';
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return Sms_Api_Client
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the current account balance.
	 *
	 * @return array|\WP_Error
	 */
	public function get_balance() {
		$response = $this->send_request( '/points', [], 'GET' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = \wp_remote_retrieve_body( $response );
		$data = \json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'invalid_json', 'Failed to decode balance response.' );
		}

		if ( isset( $data['point'] ) && isset( $data['cash'] ) ) {
			return [
				'point' => (int) $data['point'],
				'cash'  => (int) $data['cash'],
			];
		}

		return new \WP_Error( 'unexpected_response', 'Unexpected response format for balance check.' );
	}

	/**
	 * Send a request to the API.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $body The request body.
	 * @param string $method The HTTP method (e.g., 'POST', 'GET').
	 * @return array|WP_Error The response from the API or a WP_Error on failure.
	 */
	public function send_request( $endpoint, $body = [], $method = 'POST' ) {
		if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {
			return new \WP_Error( 'invalid_credentials', 'API Key or Secret is missing.' );
		}

		$headers = $this->get_auth_headers();

		$args = [
			'method'  => $method,
			'headers' => $headers,
			'body'    => 'GET' === $method ? null : \wp_json_encode( $body ),
			'timeout' => 45,
		];

		$response = \wp_remote_request( $this->api_url . $endpoint, $args );

		$this->check_points_from_response( $response );

		if ( \is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = \wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$response_body = \wp_remote_retrieve_body( $response );
			return new \WP_Error( 'api_error', 'API request failed.', [
				'status' => $response_code,
				'body'   => $response_body,
			] );
		}

		return $response;
	}

	/**
	 * Generate authentication headers.
	 *
	 * @return array
	 */
	private function get_auth_headers() {
		$salt      = \uniqid();
		$timestamp = \time();
		$signature = \hash_hmac( 'sha256', $timestamp . $salt, $this->api_secret );

		return [
			'Authorization' => "HMAC-SHA256 ApiKey={$this->api_key}, Salt={$salt}, Signature={$signature}, Timestamp={$timestamp}",
			'Content-Type'  => 'application/json',
		];
	}
} 