<?php
/**
 * WooCommerce Hooks
 *
 * @package SmsConnect\WooCommerce
 */

namespace SmsConnect\WooCommerce;

use SmsConnect\Core\Rule_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Hooks
 *
 * Handles all WooCommerce related hooks.
 */
class WC_Hooks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', [ $this, 'on_order_status_changed' ], 10, 4 );
	}

	/**
	 * Fired when the order status is changed.
	 *
	 * @param int      $order_id    The order ID.
	 * @param string   $old_status  The old order status.
	 * @param string   $new_status  The new order status.
	 * @param \WC_Order $order       The order object.
	 */
	public function on_order_status_changed( $order_id, $old_status, $new_status, $order ) {
		// --- 고급 발송 규칙을 먼저 확인합니다 ---
		$rule = Rule_Manager::find_rule_for_order( $order, $new_status );

		if ( $rule ) {
			// 일치하는 규칙이 있으면 해당 규칙의 템플릿을 사용합니다.
			if ( ! empty( $rule['alimtalk_template_code'] ) ) {
				$this->send_alimtalk( $rule['alimtalk_template_code'], $order );
			}
			if ( ! empty( $rule['sms_template_id'] ) ) {
				$this->send_sms( $rule['sms_template_id'], $order );
			}
			return; // 고급 규칙이 처리되었으므로, 기본 로직은 실행하지 않습니다.
		}

		// --- 고급 규칙이 없을 경우, 기존 기본 로직을 실행합니다 ---
		$alimtalk_options = get_option( 'sms_connect_alimtalk_options', [] );
		$sms_options      = get_option( 'sms_connect_sms_options', [] );

		// --- Try to send Alimtalk first ---
		if ( ! empty( $alimtalk_options[ $new_status ] ) ) {
			$this->send_alimtalk( $alimtalk_options[ $new_status ], $order, $alimtalk_options );
			return; // Stop further processing if Alimtalk is attempted
		}

		// --- Fallback to SMS if Alimtalk is not configured ---
		if ( ! empty( $sms_options[ $new_status ] ) ) {
			$this->send_sms( $sms_options[ $new_status ], $order, $sms_options );
		}
	}

	/**
	 * Send an Alimtalk message.
	 *
	 * @param string    $template_code The Alimtalk template code.
	 * @param \WC_Order $order The order object.
	 * @param array     $options The alimtalk options.
	 */
	private function send_alimtalk( $template_code, $order, $options = [] ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();
		$recipients  = [ $order->get_billing_phone() ];

		// If options are not passed, get them.
		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = get_option( 'sms_connect_alimtalk_options', [] );
		}

		// Check if we need to send to admin as well
		$send_to_admin_key = 'send_to_admin_' . $order->get_status();

		if ( ! empty( $options[ $send_to_admin_key ] ) && 'yes' === $options[ $send_to_admin_key ] ) {
			$admin_phones = $sms_connect->handlers['message_manager']->get_admin_phone_numbers();
			$recipients   = array_merge( $recipients, $admin_phones );
			$recipients   = array_unique( $recipients );
		}

		// For Alimtalk, we just need to extract variables for the API, not build a message.
		$template_vars     = $sms_connect->handlers['template_variables']->extract_variables_for_api( '', $order );
		$message_with_vars = sprintf( __( 'Alimtalk sent for order %s, status %s.', 'sms-connect' ), $order->get_id(), $order->get_status() );


		foreach ( $recipients as $recipient ) {
			if ( empty( $recipient ) ) {
				continue;
			}
			// Actually send the request
			$response = $sms_connect->handlers['alimtalk_api_client']->send_request( $template_code, $recipient, $template_vars );

			$status        = 'Success';
			$response_body = '';

			if ( is_wp_error( $response ) ) {
				$status        = 'Failure';
				$response_body = $response->get_error_message();
			} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$status        = 'Failure';
				$response_body = wp_remote_retrieve_body( $response );
			} else {
				$response_body = wp_remote_retrieve_body( $response );
			}

			$this->log_to_db( [
				'order_id'      => $order->get_id(),
				'recipient'     => $recipient,
				'type'          => 'Alimtalk',
				'status'        => $status,
				'message'       => $message_with_vars, // Log a reference message.
				'template_code' => $template_code,
				'response'      => is_array( $response_body ) ? wp_json_encode( $response_body ) : $response_body,
			] );
		}
	}

	/**
	 * Send an SMS message.
	 *
	 * @param string    $message_template The message template string.
	 * @param \WC_Order $order The order object.
	 * @param array     $options The SMS options.
	 */
	private function send_sms( $message_template, $order, $options = [] ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();

		$message      = $sms_connect->handlers['template_variables']->replace_variables( $message_template, $order );
		$message_type = $sms_connect->handlers['message_manager']->get_message_type( $message );
		$sender       = get_option( 'sms_connect_sender_number', '' );

		$recipients = [ $order->get_billing_phone() ];

		// If options are not passed, get them.
		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = get_option( 'sms_connect_sms_options', [] );
		}
		
		// Check if we need to send to admin as well
		$send_to_admin_key = 'send_to_admin_' . $order->get_status();

		if ( ! empty( $options[ $send_to_admin_key ] ) && 'yes' === $options[ $send_to_admin_key ] ) {
			$admin_phones = $sms_connect->handlers['message_manager']->get_admin_phone_numbers();
			$recipients   = array_merge( $recipients, $admin_phones );
			$recipients   = array_unique( $recipients );
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

			// Actually send the request
			$response = $sms_connect->handlers['api_client']->send_request( '/messages/v4/send', $api_data );

			$status        = 'Success';
			$response_body = '';

			if ( is_wp_error( $response ) ) {
				$status        = 'Failure';
				$response_body = $response->get_error_message();
			} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$status        = 'Failure';
				$response_body = wp_remote_retrieve_body( $response );
			} else {
				$response_body = wp_remote_retrieve_body( $response );
			}


			$this->log_to_db( [
				'order_id'  => $order->get_id(),
				'recipient' => $recipient,
				'type'      => $message_type,
				'status'    => $status,
				'message'   => $message,
				'response'  => is_array( $response_body ) ? wp_json_encode( $response_body ) : $response_body,
			] );
		}
	}

	/**
	 * Log a message to the custom database table.
	 *
	 * @param array $data The data to log.
	 */
	private function log_to_db( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sms_connect_logs';

		$defaults = [
			'sent_at'       => current_time( 'mysql' ),
			'order_id'      => 0,
			'recipient'     => '',
			'type'          => '',
			'status'        => 'Unknown',
			'message'       => '',
			'template_code' => '',
			'response'      => '',
		];

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert( $table_name, $data );
	}
} 