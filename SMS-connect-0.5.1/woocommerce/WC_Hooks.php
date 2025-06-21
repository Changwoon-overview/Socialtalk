<?php
/**
 * WooCommerce Hooks
 *
 * @package SmsConnect\WooCommerce
 */

namespace SmsConnect\WooCommerce;

use SmsConnect\Core\Rule_Manager;
use SmsConnect\Core\Template_Variables;
use SmsConnect\SmsConnect;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Hooks
 *
 * Handles WooCommerce related hooks for sending messages.
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
		// First, check for an advanced rule match.
		$rule = Rule_Manager::find_rule_for_order( $order, $new_status );
		if ( $rule ) {
			// A rule was found. Send notifications based on the rule and stop further processing.
			if ( ! empty( $rule['sms_template_id'] ) ) {
				$this->send_sms( $rule['sms_template_id'], $order );
			}
			if ( ! empty( $rule['alimtalk_template_code'] ) ) {
				$this->send_alimtalk( $rule['alimtalk_template_code'], $order );
			}
			return; // Stop processing to prevent default messages from being sent.
		}
		
		$sms_options = \get_option( 'sms_connect_sms_options', [] );
		$alimtalk_options = \get_option( 'sms_connect_alimtalk_options', [] );

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
	 * @param array     $options The Alimtalk options.
	 */
	private function send_alimtalk( $template_code, $order, $options = [] ) {
		$sms_connect = \SmsConnect\SmsConnect::instance();

		$recipient = $order->get_billing_phone();

		if ( empty( $recipient ) ) {
			return;
		}

		// Extract template variables for Alimtalk API
		$template_vars = $sms_connect->handlers['template_variables']->extract_variables_for_api( '', $order );

		// If options are not passed, get them.
		if ( empty( $options ) || ! \is_array( $options ) ) {
			$options = \get_option( 'sms_connect_alimtalk_options', [] );
		}

		$response = $sms_connect->handlers['alimtalk_api_client']->send_alimtalk( $template_code, $recipient, $template_vars );

		$status = 'Failure';
		$response_body = '';

		if ( ! \is_wp_error( $response ) ) {
			$status = 'Success';
			$response_body = \wp_remote_retrieve_body( $response );
		} else {
			$response_body = $response->get_error_message();
		}

		$this->log_message( $order->get_id(), $recipient, 'Alimtalk', $status, '', $template_code, $response_body );
	}

	/**
	 * Send an SMS message.
	 *
	 * @param string    $message_template The message template string.
	 * @param \WC_Order $order The order object.
	 * @param array     $options The SMS options.
	 */
	private function send_sms( $message_template, $order, $options = [] ) {
		$sms_connect = \SmsConnect\SmsConnect::instance();

		$message      = $sms_connect->handlers['template_variables']->replace_variables( $message_template, $order );
		$message_type = $sms_connect->handlers['message_manager']->get_message_type( $message );
		
		// 발송자 번호는 일반 설정에서 가져옴
		$general_options = \get_option( 'sms_connect_options', [] );
		$sender = $general_options['sender_number'] ?? '';

		$recipients = [ $order->get_billing_phone() ];

		// If options are not passed, get them.
		if ( empty( $options ) || ! \is_array( $options ) ) {
			$options = \get_option( 'sms_connect_sms_options', [] );
		}

		$api_data = [
			'to'   => $recipients[0],
			'from' => $sender,
			'text' => $message,
			'type' => $message_type,
		];

		$response = $sms_connect->handlers['api_client']->send_request( '/messages/v4/send', $api_data );

		$status = 'Failure';
		$response_body = '';

		if ( ! \is_wp_error( $response ) ) {
			$status = 'Success';
			$response_body = \wp_remote_retrieve_body( $response );
		} else {
			$response_body = $response->get_error_message();
		}

		$this->log_message( $order->get_id(), $recipients[0], 'SMS', $status, $message, '', $response_body );
	}

	/**
	 * Log a message to the database.
	 *
	 * @param int    $order_id      The order ID.
	 * @param string $recipient     The recipient phone number.
	 * @param string $type          The message type (SMS/Alimtalk).
	 * @param string $status        The status (Success/Failure).
	 * @param string $message       The message content.
	 * @param string $template_code The template code (for Alimtalk).
	 * @param string $response      The API response.
	 */
	private function log_message( $order_id, $recipient, $type, $status, $message, $template_code = '', $response = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sms_connect_logs';

		$wpdb->insert(
			$table_name,
			[
				'sent_at'       => \current_time( 'mysql' ),
				'order_id'      => $order_id,
				'recipient'     => $recipient,
				'type'          => $type,
				'status'        => $status,
				'message'       => $message,
				'template_code' => $template_code,
				'response'      => $response,
			],
			[
				'%s', // sent_at
				'%d', // order_id
				'%s', // recipient
				'%s', // type
				'%s', // status
				'%s', // message
				'%s', // template_code
				'%s', // response
			]
		);
	}
} 