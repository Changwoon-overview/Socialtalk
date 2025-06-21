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
			// 참고: 현재 send_sms는 메시지 내용을 직접 받습니다. 규칙 설정의 'SMS 템플릿 ID' 필드에 메시지 내용을 직접 입력해야 합니다.
			if ( ! empty( $rule['sms_template_id'] ) ) {
				$this->send_sms( $rule['sms_template_id'], $order );
			}
			return; // 고급 규칙이 처리되었으므로, 기본 로직은 실행하지 않습니다.
		}

		// --- 고급 규칙이 없을 경우, 기존 기본 로직을 실행합니다 ---
		// Get Alimtalk settings first to prioritize it
		$alimtalk_options = get_option( 'sms_connect_alimtalk_options', [] );
		$status_key = 'wc-' . $new_status;
		$template_key = 'template_' . $status_key;

		// --- Try to send Alimtalk first ---
		if ( isset( $alimtalk_options[ $template_key ] ) && ! empty( $alimtalk_options[ $template_key ] ) ) {
			$this->send_alimtalk( $alimtalk_options[ $template_key ], $order );
			return; // Stop further processing if Alimtalk is attempted
		}

		// --- Fallback to SMS if Alimtalk is not configured ---
		$sms_options = get_option( 'sms_connect_sms_options', [] );
		if ( isset( $sms_options[ $status_key ] ) && ! empty( $sms_options[ $status_key ] ) ) {
			$this->send_sms( $sms_options[ $status_key ], $order );
		}
	}

	/**
	 * Send an Alimtalk message.
	 *
	 * @param string    $template_code The Alimtalk template code.
	 * @param \WC_Order $order The order object.
	 */
	private function send_alimtalk( $template_code, $order ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();
		$recipient = $order->get_billing_phone();

		// Alimtalk templates use specific variables. We get the raw template first to find the variables.
		$alimtalk_templates = get_option( 'sms_connect_alimtalk_templates', [] );
		$message_template = isset($alimtalk_templates[ $order->get_status() ]['message']) ? $alimtalk_templates[ $order->get_status() ]['message'] : '';
		
		// This will replace variables like #{고객명} with actual data.
		$message_with_vars = $sms_connect->handlers['template_variables']->replace_variables( $message_template, $order );
		
		// The API requires the variables in a structured way, not the final message.
		$template_vars = $sms_connect->handlers['template_variables']->extract_variables_for_api( $message_template, $order );

		// Actually send the request
		$response = $sms_connect->handlers['alimtalk_api_client']->send_request( $template_code, $recipient, $template_vars );
		
		$status = 'Success';
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
			'message'       => $message_with_vars, // Log the final message for clarity
			'template_code' => $template_code,
			'response'      => is_array( $response_body ) ? wp_json_encode( $response_body ) : $response_body,
		] );
	}

	/**
	 * Send an SMS message.
	 *
	 * @param string    $message_template The message template string.
	 * @param \WC_Order $order The order object.
	 */
	private function send_sms( $message_template, $order ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();

		$message      = $sms_connect->handlers['template_variables']->replace_variables( $message_template, $order );
		$message_type = $sms_connect->handlers['message_manager']->get_message_type( $message );
		$recipient    = $order->get_billing_phone();
		$sender       = get_option( 'sms_connect_sender_number', '' );

		$api_data = [
			'to'   => $recipient,
			'from' => $sender,
			'text' => $message,
			'type' => $message_type,
		];

		// Actually send the request
		$response = $sms_connect->handlers['api_client']->send_request( '/messages/v4/send', $api_data );

		$status = 'Success';
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