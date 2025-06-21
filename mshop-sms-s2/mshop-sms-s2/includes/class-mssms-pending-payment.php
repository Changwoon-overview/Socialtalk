<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MSSMS_Pending_Payment' ) ) {

	class MSSMS_Pending_Payment {
		public static function init() {
			if ( self::enabled() ) {
				add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'maybe_register_scheduled_action' ) );
				add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'update_scheduled_action' ), 100, 3 );
				add_action( 'wp_trash_post', array( __CLASS__, 'maybe_deregister_scheduled_action' ) );
				add_action( 'woocommerce_before_trash_order', array( __CLASS__, 'maybe_deregister_scheduled_action' ), 10, 2 );

				add_action( 'mssms_pending_payment_notification', array( __CLASS__, 'send_pending_payment_notification' ) );

			}
		}
		public static function enabled() {
			return 'yes' == get_option( 'mssms_use_pending_payment_notification', 'no' );
		}
		public static function get_interval() {
			return intval( get_option( 'mssms_pending_payment_notification_interval', 30 ) );
		}
		public static function maybe_register_scheduled_action( $order ) {
			if ( 'checkout' == $order->get_created_via() ) {
				self::register_scheduled_action( $order );
			}
		}
		protected static function get_action_group( $order ) {
			return strtoupper( 'MSSMS-PENDING-PAYMENT-' . $order->get_id() );
		}
		protected static function deregister_scheduled_action( $order ) {
			as_unschedule_all_actions( '', array(), self::get_action_group( $order ) );
		}
		protected static function register_scheduled_action( $order ) {
			self::deregister_scheduled_action( $order );

			$scheduled_time = time() + self::get_interval() * MINUTE_IN_SECONDS;

			as_schedule_single_action(
				$scheduled_time,
				'mssms_pending_payment_notification',
				array(
					'order_id' => $order->get_id()
				),
				self::get_action_group( $order )
			);
		}
		public static function update_scheduled_action( $order_id, $old_status, $new_status ) {
			try {
				$order = wc_get_order( $order_id );

				if ( 'checkout' == $order->get_created_via() ) {
					if ( 'pending' == $old_status ) {
						self::deregister_scheduled_action( $order );
					} else if ( 'pending' == $new_status ) {
						self::register_scheduled_action( $order );
					}
				}
			} catch ( Exception $e ) {
			}
		}
		public static function maybe_deregister_scheduled_action( $order_id, $order = null ) {
			if ( is_null( $order ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				self::deregister_scheduled_action( $order );
			}
		}
		protected static function send_sms( $order, $recipients ) {
			try {
				$receivers = array();

				$template_params = MSSMS_Manager::get_template_params( $order );

				foreach ( $recipients as $recipient ) {
					$receivers[] = array(
						'receiver'        => $recipient,
						'template_params' => $template_params
					);
				}

				$message = get_option( 'mssms_pending_payment_notification_sms_template' );

				$type = MSSMS_SMS::get_sms_type( $message, $template_params );

				MSSMS_SMS::send_sms( $type, '', $message, $receivers, '', MSSMS_Manager::get_request_date() );

				$order->add_order_note( __( '<span style="font-size: 12px;">결제대기 주문건에 대한 문자가 발송되었습니다.</span>' ) );
			} catch ( Exception $e ) {
				$order->add_order_note( sprintf( __( '<span style="font-size: 12px; color: #ff0000;">[결제대기 주문알림 발송 실패]<br>%s</span>' ), $e->getMessage() ) );
			}
		}
		protected static function send_alimtalk( $order, $recipients ) {
			try {
				$template_code = get_option( 'mssms_pending_payment_alimtalk_template' );

				$template = MSSMS_Kakao::get_template( $template_code );
				if ( empty( $template ) ) {
					throw new Exception( sprintf( __( '[%s] 유효하지 않은 템플릿 아이디입니다.', 'mshop-sms-s2' ), $template_code ) );
				}

				$profile = MSSMS_Kakao::get_profile( $template['plus_id'] );
				if ( empty( $profile ) ) {
					throw new Exception( sprintf( __( '[%s] 유효하지 않은 플러스친구 아이디입니다.', 'mshop-sms-s2' ), $template['plus_id'] ) );
				}

				$template_params = MSSMS_Manager::get_template_params( $order );

				$messages = array();

				foreach ( $recipients as $recipient ) {
					$messages[] = array(
						'receiver'        => $recipient,
						'template_params' => $template_params,
						"resend"          => array( 'isResend' => 'false' )
					);
				}

				MSSMS_API_Kakao::send_message( $template['plus_id'], $template['code'], MSSMS_Manager::get_request_date(), $messages );

				$order->add_order_note( __( '<span style="font-size: 12px;">결제대기 주문건에 대한 알림톡이 발송되었습니다.</span>' ) );
			} catch ( Exception $e ) {
				$order->add_order_note( sprintf( __( '<span style="font-size: 12px; color: #ff0000;">[결제대기 주문알림 발송 실패]<br>%s</span>' ), $e->getMessage() ) );
			}
		}
		public static function send_pending_payment_notification( $order_id ) {
			try {
				$order = wc_get_order( $order_id );

				if ( is_a( $order, 'WC_Order' ) && 'pending' == $order->get_status() ) {
					$recipients = array();

					$notification_method = get_option( 'mssms_pending_payment_notification_method', 'alimtalk' );
					$target              = get_option( 'mssms_pending_payment_notification_target', 'user' );

					if ( in_array( $target, array( 'all', 'user' ) ) ) {
						$recipients[] = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $order ) : $order->get_billing_phone();
					}

					if ( in_array( $target, array( 'all', 'admin' ) ) ) {
						$recipients = array_merge( $recipients, MSSMS_Manager::get_admin_phone_numbers() );
					}


					if ( 'alimtalk' == $notification_method ) {
						self::send_alimtalk( $order, $recipients );
					} else if ( 'sms' == $notification_method ) {
						self::send_sms( $order, $recipients );
					}
				}
			} catch ( Exception $e ) {

			}
		}
	}

	MSSMS_Pending_Payment::init();

}
