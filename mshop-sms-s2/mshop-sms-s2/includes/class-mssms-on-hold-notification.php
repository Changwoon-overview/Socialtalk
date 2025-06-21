<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MSSMS_On_Hold_Notification' ) ) {

	class MSSMS_On_Hold_Notification {
		public static function init() {
			if ( self::enabled() ) {
				add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'update_scheduled_action' ), 100, 3 );
				add_action( 'wp_trash_post', array( __CLASS__, 'maybe_deregister_scheduled_action' ) );

				add_action( 'mssms_on_hold_notification', array( __CLASS__, 'send_notification' ), 10, 2 );
			}
		}
		public static function enabled( $method = 'all' ) {
			if ( 'all' == $method ) {
				return 'yes' == get_option( 'mssms_use_bacs_notification', 'no' ) || 'yes' == get_option( 'mssms_use_vbank_notification', 'no' );
			} else {
				return 'yes' == get_option( "mssms_use_{$method}_notification", 'no' );
			}
		}
		public static function get_interval( $method ) {
			return intval( get_option( "mssms_{$method}_notification_interval", 30 ) );
		}
		public static function get_payment_method( $order ) {
			$payment_method = pafw_get_payment_gateway_from_order( $order );

			if ( $payment_method ) {
				if ( 'bacs' == $payment_method->id ) {
					return 'bacs';
				} else if ( $payment_method->supports( 'pafw-vbank' ) ) {
					return 'vbank';
				}
			}

			return '';
		}
		protected static function deregister_scheduled_action( $order, $payment_method ) {
			as_unschedule_all_actions( '', array( 'order_id' => $order->get_id(), 'payment_method' => $payment_method ) );
		}
		protected static function register_scheduled_action( $order, $payment_method ) {
			self::deregister_scheduled_action( $order, $payment_method );

			$scheduled_time = time() + self::get_interval( $payment_method ) * MINUTE_IN_SECONDS;

			as_schedule_single_action(
				$scheduled_time,
				'mssms_on_hold_notification',
				array(
					'order_id'       => $order->get_id(),
					'payment_method' => $payment_method
				),
				'MSSMS_ON_HOLD_NOTIFICATION'
			);
		}
		public static function update_scheduled_action( $order_id, $old_status, $new_status ) {
			try {
				$order          = wc_get_order( $order_id );
				$payment_method = self::get_payment_method( $order );

				if ( self::enabled( $payment_method ) ) {
					if ( 'on-hold' == $old_status ) {
						self::deregister_scheduled_action( $order, $payment_method );
					} else if ( 'on-hold' == $new_status ) {
						self::register_scheduled_action( $order, $payment_method );
					}
				}
			} catch ( Exception $e ) {
			}
		}
		public static function maybe_deregister_scheduled_action( $post_id ) {
			if ( 'shop_order' == get_post_type( $post_id ) ) {
				$order          = wc_get_order( $post_id );
				$payment_method = self::get_payment_method( $order );

				if ( ! empty( $payment_method ) ) {
					self::deregister_scheduled_action( $order, $payment_method );
				}
			}
		}
		protected static function send_sms( $order, $payment_method, $recipients ) {
			try {
				$receivers = array();

				$template_params = MSSMS_Manager::get_template_params( $order );

				if ( 'vbank' == $payment_method ) {
					$template_params = array_merge( $template_params, array(
						'가상계좌은행명'  => $order->get_meta( '_pafw_vacc_bank_name' ),
						'가상계좌번호'   => $order->get_meta( '_pafw_vacc_num' ),
						'가상계좌예금주'  => $order->get_meta( '_pafw_vacc_holder' ),
						'가상계좌입금자'  => $order->get_meta( '_pafw_vacc_depositor' ),
						'가상계좌입금기한' => $order->get_meta( '_pafw_vacc_date' )
					) );
				}

				foreach ( $recipients as $recipient ) {
					$receivers[] = array(
						'receiver'        => $recipient,
						'template_params' => $template_params
					);
				}

				$message = get_option( "mssms_{$payment_method}_notification_sms_template" );

				$type = MSSMS_SMS::get_sms_type( $message, $template_params );

				MSSMS_SMS::send_sms( $type, '', $message, $receivers, '', MSSMS_Manager::get_request_date() );

				$order->add_order_note( __( '<span style="font-size: 12px;">입금대기 주문건에 대한 문자가 발송되었습니다.</span>' ) );
			} catch ( Exception $e ) {
				$order->add_order_note( sprintf( __( '<span style="font-size: 12px; color: #ff0000;">[입금대기 주문알림 발송 실패]<br>%s</span>' ), $e->getMessage() ) );
			}
		}
		protected static function send_alimtalk( $order, $payment_method, $recipients ) {
			try {
				$template_code = get_option( "mssms_{$payment_method}_notification_alimtalk_template" );

				$template = MSSMS_Kakao::get_template( $template_code );
				if ( empty( $template ) ) {
					throw new Exception( sprintf( __( '[%s] 유효하지 않은 템플릿 아이디입니다.', 'mshop-sms-s2' ), $template_code ) );
				}

				$profile = MSSMS_Kakao::get_profile( $template['plus_id'] );
				if ( empty( $profile ) ) {
					throw new Exception( sprintf( __( '[%s] 유효하지 않은 플러스친구 아이디입니다.', 'mshop-sms-s2' ), $template['plus_id'] ) );
				}

				$template_params = MSSMS_Manager::get_template_params( $order );

				if ( 'vbank' == $payment_method ) {
					$template_params = array_merge( $template_params, array(
						'가상계좌은행명'  => $order->get_meta( '_pafw_vacc_bank_name' ),
						'가상계좌번호'   => $order->get_meta( '_pafw_vacc_num' ),
						'가상계좌예금주'  => $order->get_meta( '_pafw_vacc_holder' ),
						'가상계좌입금자'  => $order->get_meta( '_pafw_vacc_depositor' ),
						'가상계좌입금기한' => $order->get_meta( '_pafw_vacc_date' )
					) );
				}

				$messages = array();

				foreach ( $recipients as $recipient ) {
					$messages[] = array(
						'receiver'        => $recipient,
						'template_params' => $template_params,
						"resend"          => array( 'isResend' => 'false' )
					);
				}

				MSSMS_API_Kakao::send_message( $template['plus_id'], $template['code'], MSSMS_Manager::get_request_date(), $messages );

				$order->add_order_note( __( '<span style="font-size: 12px;">입금대기 주문건에 대한 알림톡이 발송되었습니다.</span>' ) );
			} catch ( Exception $e ) {
				$order->add_order_note( sprintf( __( '<span style="font-size: 12px; color: #ff0000;">[입금대기 주문알림 발송 실패]<br>%s</span>' ), $e->getMessage() ) );
			}
		}
		public static function send_notification( $order_id, $payment_method ) {
			try {
				$order = wc_get_order( $order_id );

				if ( is_a( $order, 'WC_Order' ) && 'on-hold' == $order->get_status() ) {
					$recipients = array();

					$notification_method = get_option( "mssms_{$payment_method}_notification_method", 'alimtalk' );
					$target              = get_option( "mssms_{$payment_method}_notification_target", 'user' );

					if ( in_array( $target, array( 'all', 'user' ) ) ) {
						$recipients[] = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $order ) : $order->get_billing_phone();
					}

					if ( in_array( $target, array( 'all', 'admin' ) ) ) {
						$recipients = array_merge( $recipients, MSSMS_Manager::get_admin_phone_numbers() );
					}


					if ( 'alimtalk' == $notification_method ) {
						self::send_alimtalk( $order, $payment_method, $recipients );
					} else if ( 'sms' == $notification_method ) {
						self::send_sms( $order, $payment_method, $recipients );
					}
				}
			} catch ( Exception $e ) {

			}
		}
	}

	MSSMS_On_Hold_Notification::init();

}
