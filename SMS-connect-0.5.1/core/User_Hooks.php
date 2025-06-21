<?php
/**
 * User related Hooks
 *
 * @package SmsConnect\Core
 */

namespace SmsConnect\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class User_Hooks
 *
 * Handles user-related hooks for sending notifications.
 */
class User_Hooks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'user_register', [ $this, 'on_user_register' ], 10, 1 );
		add_action( 'set_user_role', [ $this, 'on_set_user_role' ], 10, 3 );
	}

	/**
	 * Fires after a new user is registered.
	 *
	 * @param int $user_id User ID.
	 */
	public function on_user_register( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		$this->send_notification( $user, 'user_register' );
	}

	/**
	 * Fires after the user's role has been changed.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $new_role  The new role.
	 * @param array  $old_roles An array of the user's previous roles.
	 */
	public function on_set_user_role( $user_id, $new_role, $old_roles ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$old_role_names = array_map( 'translate_user_role', $old_roles );
		$extra_data = [
			'new_role' => translate_user_role( $new_role ),
			'old_role' => implode( ', ', $old_role_names ),
		];
		$this->send_notification( $user, 'user_role_change', $extra_data );
	}

	/**
	 * Sends notification based on user object and event type.
	 *
	 * @param \WP_User $user       The user object.
	 * @param string   $event_key  The key for the event (e.g., 'user_register').
	 * @param array    $extra_data Optional extra data for template variables.
	 */
	private function send_notification( $user, $event_key, $extra_data = [] ) {
		// Alimtalk first
		$alimtalk_options = get_option( 'sms_connect_alimtalk_options', [] );
		$template_code = isset( $alimtalk_options[ $event_key . '_template_code' ] ) ? $alimtalk_options[ $event_key . '_template_code' ] : '';
		
		if ( ! empty( $template_code ) ) {
			$this->send_alimtalk( $template_code, $user, $extra_data );
			return;
		}

		// Fallback to SMS
		$sms_options = get_option( 'sms_connect_sms_options', [] );
		$message_template = isset( $sms_options[ $event_key . '_message' ] ) ? $sms_options[ $event_key . '_message' ] : '';

		if ( ! empty( $message_template ) ) {
			$this->send_sms( $message_template, $user, $extra_data );
		}
	}

	/**
	 * Sends an Alimtalk message to a user.
	 *
	 * @param string   $template_code The Alimtalk template code.
	 * @param \WP_User $user          The user object.
	 * @param array    $extra_data    Optional extra data.
	 */
	private function send_alimtalk( $template_code, $user, $extra_data = [] ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();
		$recipient = get_user_meta( $user->ID, 'billing_phone', true );

		if ( empty( $recipient ) ) {
			return;
		}

		$template_vars = $sms_connect->handlers['template_variables']->extract_variables_for_api( '', $user, $extra_data );
		$response = $sms_connect->handlers['alimtalk_api_client']->send_request( $template_code, $recipient, $template_vars );

		$this->log_response( $response, [
			'order_id'      => $user->ID, // Log user ID in place of order ID
			'recipient'     => $recipient,
			'type'          => 'Alimtalk (User)',
			'template_code' => $template_code,
		] );
	}

	/**
	 * Sends an SMS message to a user.
	 *
	 * @param string   $message_template The message template.
	 * @param \WP_User $user             The user object.
	 * @param array    $extra_data       Optional extra data.
	 */
	private function send_sms( $message_template, $user, $extra_data = [] ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();
		$recipient = get_user_meta( $user->ID, 'billing_phone', true );

		if ( empty( $recipient ) ) {
			return;
		}

		$message = $sms_connect->handlers['template_variables']->replace_variables( $message_template, $user, $extra_data );
		$message_type = $sms_connect->handlers['message_manager']->get_message_type( $message );
		$sender = get_option( 'sms_connect_sender_number', '' );

		$api_data = [
			'to'   => $recipient,
			'from' => $sender,
			'text' => $message,
			'type' => $message_type,
		];
		
		$response = $sms_connect->handlers['api_client']->send_request( '/messages/v4/send', $api_data );

		$this->log_response( $response, [
			'order_id'  => $user->ID, // Log user ID
			'recipient' => $recipient,
			'type'      => $message_type . ' (User)',
			'message'   => $message,
		] );
	}

	/**
     * Logs the API response to the database.
     *
     * @param mixed $response The response from the API.
     * @param array $log_data The basic data for logging.
     */
    private function log_response( $response, $log_data ) {
        $status = 'Success';
		$response_body = '';

		if ( is_wp_error( $response ) ) {
			$status = 'Failure';
			$response_body = $response->get_error_message();
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$status = 'Failure';
			$response_body = wp_remote_retrieve_body( $response );
		} else {
			$response_body = wp_remote_retrieve_body( $response );
		}

        $log_data['status'] = $status;
        $log_data['response'] = is_array( $response_body ) ? wp_json_encode( $response_body ) : $response_body;
        
        // Log to DB
		global $wpdb;
		$table_name = $wpdb->prefix . 'sms_connect_logs';
		$defaults = [
			'sent_at'       => current_time( 'mysql' ),
			'recipient'     => '',
			'type'          => '',
			'status'        => 'Unknown',
			'message'       => '',
			'template_code' => '',
			'response'      => '',
		];
		$data = wp_parse_args( $log_data, $defaults );
		$wpdb->insert( $table_name, $data );
    }
} 