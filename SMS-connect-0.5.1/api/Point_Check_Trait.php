<?php
/**
 * Point Check Trait
 *
 * @package SmsConnect\API
 */

namespace SmsConnect\API;

use SmsConnect\Core\Message_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Point_Check_Trait
 *
 * Provides a shared method to check points from an API response.
 */
trait Point_Check_Trait {
	/**
	 * Checks the remaining points from the API response and notifies the admin if low.
	 *
	 * @param array|\WP_Error $response The response from the API.
	 */
	private function check_points_from_response( $response ) {
		if ( \is_wp_error( $response ) ) {
			return;
		}

		$body = \wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return;
		}

		$data = \json_decode( $body, true );
		if ( ! empty( $data['point'] ) ) {
			$message_manager = Message_Manager::get_instance();
			$message_manager->check_and_notify_low_points( (int) $data['point'] );
		}
	}
} 