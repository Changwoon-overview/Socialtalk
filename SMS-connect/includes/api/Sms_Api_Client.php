<?php
/**
 * SMS API Client
 *
 * @package SmsConnect\API
 */

namespace SmsConnect\API;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sms_Api_Client
 *
 * Handles communication with the SMS API provider.
 */
class Sms_Api_Client {

	/**
	 * The single instance of the class.
	 *
	 * @var Sms_Api_Client|null
	 */
	private static $instance = null;

	/**
	 * The base URL for the API.
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * The API key.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * The API secret.
	 *
	 * @var string
	 */
	private $api_secret = '';

	/**
	 * Sms_Api_Client constructor.
	 *
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$options = get_option( 'sms_connect_options' );

		$this->api_url    = 'https://api.example.com'; // This might also come from settings later
		$this->api_key    = isset( $options['api_key'] ) ? trim( $options['api_key'] ) : '';
		$this->api_secret = isset( $options['api_secret'] ) ? trim( $options['api_secret'] ) : '';
	}

	/**
	 * Get the single instance of the class.
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
	 * Send a request to the API.
	 *
	 * @param string $endpoint The API endpoint to call.
	 * @param array  $body The request body.
	 * @param string $method The HTTP method (e.g., 'POST', 'GET').
	 * @return array|WP_Error The response from the API or a WP_Error on failure.
	 */
	public function send_request( $endpoint, $body = [], $method = 'POST' ) {
		$url = $this->api_url . $endpoint;

		$headers = [
			'Authorization' => 'Bearer ' . base64_encode( $this->api_key . ':' . $this->api_secret ),
			'Content-Type'  => 'application/json',
		];

		$args = [
			'method'  => $method,
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 15, // 15 seconds timeout
		];

		if ( 'POST' === $method ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		// After a successful request, check the balance.
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$data = json_decode( $response_body, true );

			// Check for balance/point in the response data. The key might differ by API provider.
			// We'll check for common keys like 'point' or 'balance'.
			$current_points = 0;
			if ( isset( $data['point'] ) ) {
				$current_points = (int) $data['point'];
			} elseif ( isset( $data['balance'] ) ) {
				$current_points = (int) $data['balance'];
			}

			if ( $current_points > 0 ) {
				// Get the Message Manager and run the check.
				$sms_connect = \SmsConnect\Sms_Connect::instance();
				if ( isset( $sms_connect->handlers['message_manager'] ) ) {
					$sms_connect->handlers['message_manager']->check_and_notify_low_points( $current_points );
				}
			}
		}

		return $response;
	}
} 