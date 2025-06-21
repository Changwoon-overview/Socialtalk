<?php
/**
 * WooCommerce Subscriptions Hooks
 *
 * @package SmsConnect\WooCommerce
 */

namespace SmsConnect\WooCommerce;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WooCommerce Subscriptions 플러그인이 활성화된 경우에만 클래스를 정의합니다.
if ( class_exists( 'WC_Subscriptions' ) ) :

/**
 * Class WC_Subscriptions_Hooks
 *
 * Handles all WooCommerce Subscriptions related hooks.
 */
class WC_Subscriptions_Hooks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_subscription_payment_complete', [ $this, 'on_subscription_payment_complete' ] );
		add_action( 'woocommerce_subscription_status_updated', [ $this, 'on_subscription_status_updated' ], 10, 3 );
	}

	/**
	 * 정기결제 갱신 결제가 완료되었을 때 실행됩니다.
	 *
	 * @param \WC_Subscription $subscription The subscription object.
	 */
	public function on_subscription_payment_complete( $subscription ) {
		$this->send_notification( $subscription, 'subscription-payment-complete' );
	}

	/**
	 * 정기결제 상태가 변경되었을 때 실행됩니다.
	 *
	 * @param \WC_Subscription $subscription The subscription object.
	 * @param string           $new_status  The new status.
	 * @param string           $old_status  The old status.
	 */
	public function on_subscription_status_updated( $subscription, $new_status, $old_status ) {
		$this->send_notification( $subscription, 'subscription-' . $new_status );
	}

    /**
     * 구독 정보와 상태 키를 기반으로 알림을 보냅니다.
     *
     * @param \WC_Subscription $subscription The subscription object.
     * @param string           $status_key_suffix The status key suffix (e.g., 'payment-complete', 'cancelled').
     */
	private function send_notification( $subscription, $status_key_suffix ) {
		if ( ! $subscription ) {
			return;
		}

		$status_key = 'wc-' . $status_key_suffix;

		// 알림톡 설정을 먼저 확인
		$alimtalk_options = get_option( 'sms_connect_alimtalk_options', [] );
		$template_key     = 'template_' . $status_key;

		if ( isset( $alimtalk_options[ $template_key ] ) && ! empty( $alimtalk_options[ $template_key ] ) ) {
			$this->send_alimtalk( $alimtalk_options[ $template_key ], $subscription );
			return; // 알림톡 발송 시 SMS는 보내지 않음
		}

		// 알림톡 설정이 없으면 SMS 설정 확인
		$sms_options = get_option( 'sms_connect_sms_options', [] );
		if ( isset( $sms_options[ $status_key ] ) && ! empty( $sms_options[ $status_key ] ) ) {
			$this->send_sms( $sms_options[ $status_key ], $subscription );
		}
	}

	/**
	 * 알림톡 메시지를 보냅니다.
	 *
	 * @param string           $template_code The Alimtalk template code.
	 * @param \WC_Subscription $subscription  The subscription object.
	 */
	private function send_alimtalk( $template_code, $subscription ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();
		$recipient   = $subscription->get_billing_phone();
        $order       = $subscription->get_parent(); // 템플릿 변수를 위해 주문 객체를 가져옵니다.

		if ( empty( $recipient ) || !$order ) {
			return;
		}
        
		// 템플릿 변수 처리
		$message_with_vars = $sms_connect->handlers['template_variables']->replace_variables( '', $order, $subscription );
		$template_vars     = $sms_connect->handlers['template_variables']->extract_variables_for_api( '', $order, $subscription );

		$response = $sms_connect->handlers['alimtalk_api_client']->send_request( $template_code, $recipient, $template_vars );

		$this->log_response( $response, [
			'order_id'      => $subscription->get_id(), // 로그에는 구독 ID를 기록
			'recipient'     => $recipient,
			'type'          => 'Alimtalk',
			'message'       => $message_with_vars,
			'template_code' => $template_code,
		] );
	}

	/**
	 * SMS 메시지를 보냅니다.
	 *
	 * @param string           $message_template The message template string.
	 * @param \WC_Subscription $subscription     The subscription object.
	 */
	private function send_sms( $message_template, $subscription ) {
		$sms_connect = \SmsConnect\Sms_Connect::instance();
		$recipient   = $subscription->get_billing_phone();
        $order       = $subscription->get_parent(); // 템플릿 변수를 위해 주문 객체를 가져옵니다.

		if ( empty( $recipient ) || !$order ) {
			return;
		}

		$message      = $sms_connect->handlers['template_variables']->replace_variables( $message_template, $order, $subscription );
		$message_type = $sms_connect->handlers['message_manager']->get_message_type( $message );
		$sender       = get_option( 'sms_connect_sender_number', '' );

		$api_data = [
			'to'   => $recipient,
			'from' => $sender,
			'text' => $message,
			'type' => $message_type,
		];

		$response = $sms_connect->handlers['api_client']->send_request( '/messages/v4/send', $api_data );
		
        $this->log_response( $response, [
			'order_id'  => $subscription->get_id(), // 로그에는 구독 ID를 기록
			'recipient' => $recipient,
			'type'      => $message_type,
			'message'   => $message,
		] );
	}
    
    /**
     * API 응답을 데이터베이스에 기록합니다.
     *
     * @param mixed $response The response from the API.
     * @param array $log_data The basic data for logging.
     */
    private function log_response($response, $log_data) {
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

        $log_data['status'] = $status;
        $log_data['response'] = is_array( $response_body ) ? wp_json_encode( $response_body ) : $response_body;

        $this->log_to_db($log_data);
    }

	/**
	 * 메시지를 데이터베이스 테이블에 기록합니다.
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

endif; 