<?php

namespace SMS_Connect\Core;

class Alimtalk_Api_Client {
	/**
	 * @param array  $data      The data for the message body.
	 *
	 * @return array|\WP_Error The response from the server or a WP_Error on failure.
	 */
	public function send_request( $template_code, $recipient, $data = [] ) {
		// Alimtalk APIs often use a different endpoint and authentication.
		// Using a simplified example based on common patterns.
		$api_url = 'https://alimtalk-api.coolsms.co.kr'; // This should be a real Alimtalk API endpoint URL
		$url     = $api_url . '/messages/v4/send'; // Example endpoint

		$api_key    = get_option( 'sms_connect_alimtalk_api_key', '' );
		$api_secret = get_option( 'sms_connect_alimtalk_api_secret', '' );

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			return new \WP_Error( 'alimtalk_api_credentials_missing', __( 'Alimtalk API Key or API Secret is not set.', 'sms-connect' ) );
		}

		// Alimtalk might use the same HMAC auth or a different one (e.g., Bearer token)
		$date = date('Y-m-d\TH:i:s.v\Z');
		$salt = uniqid();
		$signature = hash_hmac('sha256', $date . $salt, $api_secret);

		$headers = [
			'Authorization' => "HMAC-SHA256 ApiKey={$api_key}, Date={$date}, salt={$salt}, signature={$signature}",
			'Content-Type'  => 'application/json',
		];
		
		$message_data = [
			'to'              => $recipient,
			'from'            => get_option( 'sms_connect_alimtalk_sender_key', '' ), // Sender Key (Channel ID)
			'type'            => 'ATA', // Alimtalk Type
			'country'         => '82',
			'templateCode'    => $template_code,
			'customFields'    => $data, // e.g., ['#{고객명}' => '홍길동']
		];

		$args = [
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => wp_json_encode( [ 'message' => $message_data ] ),
			'timeout' => 15,
		];

		$response = wp_remote_post( $url, $args );

		return $response;
	}
} 