<?php

namespace SMS_Connect\Core;

class Sms_Api_Client {
	/**
	 * @param array  $data   The data to be sent in the request body.
	 *
	 * @return array|\WP_Error The response from the server or a WP_Error on failure.
	 */
	public function send_request( $endpoint, $data = [] ) {
		$api_url = 'https://api.coolsms.co.kr'; // This should be a real API endpoint URL
		$url     = $api_url . $endpoint;

		$api_key    = get_option( 'sms_connect_api_key', '' );
		$api_secret = get_option( 'sms_connect_api_secret', '' );

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			return new \WP_Error( 'api_credentials_missing', __( 'API Key or API Secret is not set.', 'sms-connect' ) );
		}
		
		// CoolSMS API requires a specific authentication method (HMAC signature).
		$date = date('Y-m-d\TH:i:s.v\Z');
		$salt = uniqid();
		$signature = hash_hmac('sha256', $date . $salt, $api_secret);

		$headers = [
			'Authorization' => "HMAC-SHA256 ApiKey={$api_key}, Date={$date}, salt={$salt}, signature={$signature}",
			'Content-Type'  => 'application/json',
		];

		$args = [
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => wp_json_encode( [ 'message' => $data ] ),
			'timeout' => 15,
		];

		$response = wp_remote_post( $url, $args );

		return $response;
	}
	
	/**
	 * Get the account balance.
	 *
	 * @return array|\WP_Error The balance information or a WP_Error on failure.
	 */
	public function get_balance() {
		$api_url = 'https://api.coolsms.co.kr';
		$url     = $api_url . '/cash/v1/balance'; // Balance check endpoint

		$api_key    = get_option( 'sms_connect_api_key', '' );
		$api_secret = get_option( 'sms_connect_api_secret', '' );

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			return new \WP_Error( 'api_credentials_missing', __( 'API Key or API Secret is not set.', 'sms-connect' ) );
		}

		$date      = date( 'Y-m-d\TH:i:s.v\Z' );
		$salt      = uniqid();
		$signature = hash_hmac( 'sha256', $date . $salt, $api_secret );

		$headers = [
			'Authorization' => "HMAC-SHA256 ApiKey={$api_key}, Date={$date}, salt={$salt}, signature={$signature}",
		];
		
		$args = [
			'method'  => 'GET',
			'headers' => $headers,
			'timeout' => 15,
		];

		$response = wp_remote_get( $url, $args );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return $data;
	}
} 