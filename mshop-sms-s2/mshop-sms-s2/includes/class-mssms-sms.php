<?php

/*
=====================================================================================
                ﻿엠샵 문자 알림톡 자동 발송 / Copyright 2015 by CodeM(c)
=====================================================================================

  [ 우커머스 버전 지원 안내 ]

   워드프레스 버전 : WordPress 4.3.1

   우커머스 버전 : WooCommerce 2.4.7


  [ 코드엠 플러그인 라이센스 규정 ]

   (주)코드엠에서 개발된 워드프레스  플러그인을 사용하시는 분들에게는 다음 사항에 대한 동의가 있는 것으로 간주합니다.

   1. 코드엠에서 개발한 워드프레스 우커머스용 엠샵 문자 알림톡 자동 발송 플러그인의 저작권은 (주)코드엠에게 있습니다.
   
   2. 플러그인은 사용권을 구매하는 것이며, 프로그램 저작권에 대한 구매가 아닙니다.

   3. 플러그인을 구입하여 다수의 사이트에 복사하여 사용할 수 없으며, 1개의 라이센스는 1개의 사이트에만 사용할 수 있습니다. 
      이를 위반 시 지적 재산권에 대한 손해 배상 의무를 갖습니다.

   4. 플러그인은 구입 후 1년간 업데이트를 지원합니다.

   5. 플러그인은 워드프레스, 테마, 플러그인과의 호환성에 대한 책임이 없습니다.

   6. 플러그인 설치 후 버전에 관련한 운용 및 관리의 책임은 사이트 당사자에게 있습니다.

   7. 다운로드한 플러그인은 환불되지 않습니다.

=====================================================================================
*/

if ( ! class_exists( 'MSSMS_SMS' ) ) {

	class MSSMS_SMS {
		protected static $profiles = null;
		protected static $settings = null;
		protected static $templates = null;
		protected static $params = null;
		protected static function get_setting_by_target( $target ) {
			$options = get_option( 'mssms_sms_' . $target . '_options', array() );

			$options = array_filter( $options, function ( $option ) {
				return 'yes' == mssms_get( $option, 'enable' );
			} );

			$order_statuses = array_column( $options, 'order_status' );

			return apply_filters( 'mssms_sms_send_options', array_combine( $order_statuses, $options ) );
		}
		protected static function get_settings( $target, $order = null ) {
			$postfix = mssms_order_contains_renewal( $order ) ? '_renewal' : '';

			self::$settings['admin']              = self::get_setting_by_target( 'admin' . $postfix );
			self::$settings['user']               = self::get_setting_by_target( 'user' . $postfix );
			self::$settings['admin_subscription'] = self::get_setting_by_target( 'admin_subscription' );
			self::$settings['user_subscription']  = self::get_setting_by_target( 'user_subscription' );

			return self::$settings[ $target ];
		}
		protected static function get_template( $code ) {
			if ( empty( $code ) ) {
				return null;
			}

			if ( is_null( self::$templates ) ) {
				self::$templates = get_option( 'mssms_template_lists', array() );
			}

			return mssms_get( self::$templates, $code );
		}
		public static function get_message_by_order_status( $order_status, $target, $order = null ) {
			$settings = self::get_settings( $target, $order );

			$message = mssms_get( $settings, $order_status );

			return mssms_get( $message, 'message' );
		}
		public static function send_sms( $type, $title, $message, $recipients, $send_no = '', $request_date = '', $is_auth = false, $attached_file_ids = array() ) {
			if ( empty( $send_no ) ) {
				$send_no = get_option( 'mssms_rep_send_no' );
			}

			if ( empty( $message ) || empty( $recipients ) || empty( $send_no ) ) {
				return;
			}

			return MSSMS_API_SMS::send_message( $type, $send_no, $title, $message, $recipients, $request_date, $is_auth, $attached_file_ids );
		}

		public static function get_sms_type( $message, $template_params ) {
			foreach ( $template_params as $key => $value ) {
				$message = str_replace( '{' . $key . '}', $value, $message );
			}

			return mb_strwidth( $message ) > 90 ? 'LMS' : 'SMS';
		}
		public static function send_message( $order_id, $old_status, $new_status, $target ) {
			$order = wc_get_order( $order_id );

			if ( ! $order || ! apply_filters( 'mssms_maybe_send_message', true, $order_id, $old_status, $new_status, $target ) ) {
				return;
			}
			if ( ! apply_filters( 'mshop_sms_order_payment_method_check', true, $order->get_payment_method(), $new_status, $order ) ) {
				return;
			}

			if ( apply_filters( 'mssms_send_message_only_parent_order', ! mssms_is_subscription( $order ) && $order->get_parent_id(), $order ) ) {
				return;
			}

			$new_status = apply_filters( 'mssms_send_message_new_status', $new_status, $order );

			$message = self::get_message_by_order_status( $new_status, $target, $order );

			if ( apply_filters( 'mssms_apply_sms_template_by_rule', true, $message, $order ) ) {
				$messages = apply_filters( 'mssms_sms_message_template', array( $message ), $old_status, $new_status, $target, $order );
			} else {
				$messages = array( $message );
			}

			$messages = array_filter( $messages );

			if ( ! empty( $messages ) ) {
				if ( 0 === strpos( $target, 'admin' ) ) {
					$recipients = MSSMS_Manager::get_admin_phone_numbers();
				} else {
					$recipients[] = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $order ) : $order->get_billing_phone();
				}

				$template_params = MSSMS_Manager::get_template_params( $order );

				$recipients = array_map( function ( $receiver ) use ( $template_params ) {
					return array(
						'receiver'        => $receiver,
						'template_params' => $template_params
					);
				}, $recipients );

				foreach ( $messages as $message ) {
					$type = self::get_sms_type( $message, $template_params );
					self::send_sms( $type, '', $message, $recipients, '', MSSMS_Manager::get_request_date() );
				}
			}
		}
		public static function send_message_to_admin( $order_id, $old_status, $new_status ) {
			try {
				self::send_message( $order_id, $old_status, $new_status, 'admin' );
			} catch ( Exception $e ) {

			}
		}
		public static function send_subscription_message_to_admin( $subscription_id, $old_status, $new_status, $subscription = null ) {
			try {
				if ( ( 'pending' == $old_status || ! $subscription->get_date_paid() ) && 'cancelled' == $new_status ) {
					return;
				}

				self::send_message( $subscription_id, $old_status, $new_status, 'admin_subscription' );
			} catch ( Exception $e ) {
			}
		}
		public static function send_subscription_message_to_user( $subscription_id, $old_status, $new_status, $subscription = null ) {
			try {
				if ( ( 'pending' == $old_status || ! $subscription->get_date_paid() ) && 'cancelled' == $new_status ) {
					return;
				}

				self::send_message( $subscription_id, $old_status, $new_status, 'user_subscription' );
			} catch ( Exception $e ) {
			}
		}
		public static function send_message_to_user( $order_id, $old_status, $new_status ) {
			try {
				self::send_message( $order_id, $old_status, $new_status, 'user' );
			} catch ( Exception $e ) {
			}
		}
		protected static function maybe_send_vact_info( $order_id, $recipients, $params, $target ) {
			try {
				$order   = wc_get_order( $order_id );
				$message = self::get_message_by_order_status( 'pafw-send-vact-info', $target, $order );

				if ( apply_filters( 'mssms_apply_sms_template_by_rule', true, $message, $order ) ) {
					$messages = apply_filters( 'mssms_sms_message_template', array( $message ), 'pafw-send-vact-info', 'pafw-send-vact-info', $target, $order );
				} else {
					$messages = array( $message );
				}

				$message = array_filter( $messages );

				if ( $order && ! empty( $message ) ) {
					$template_params = array_merge( MSSMS_Manager::get_template_params( $order ), array(
						'가상계좌은행명'  => $params['vacc_bank_name'],
						'가상계좌번호'   => $params['vacc_num'],
						'가상계좌예금주'  => $params['vacc_name'],
						'가상계좌입금자'  => $params['vacc_input_name'],
						'가상계좌입금기한' => $params['vacc_date']
					) );

					$recipients = array_map( function ( $receiver ) use ( $template_params ) {
						return array(
							'receiver'        => $receiver,
							'template_params' => $template_params
						);
					}, $recipients );

					foreach ( $messages as $message ) {
						$type = self::get_sms_type( $message, $template_params );
						self::send_sms( $type, '', $message, $recipients, '', MSSMS_Manager::get_request_date() );
					}
				}
			} catch ( Exception $e ) {

			}
		}

		public static function send_vact_info( $order_id, $rcv_num, $vacc_bank_name, $vacc_num, $vacc_name, $vacc_input_name, $vacc_date ) {
			$params = array(
				'vacc_bank_name'  => $vacc_bank_name,
				'vacc_num'        => $vacc_num,
				'vacc_name'       => $vacc_name,
				'vacc_input_name' => $vacc_input_name,
				'vacc_date'       => $vacc_date,
			);

			self::maybe_send_vact_info( $order_id, array( $rcv_num ), $params, 'user' );
			self::maybe_send_vact_info( $order_id, MSSMS_Manager::get_admin_phone_numbers(), $params, 'admin' );
		}
		public static function send_custom_message( $receiver, $send_no, $message, $subject, $template_params = array() ) {
			try {
				$type = self::get_sms_type( $message, $template_params );

				$recipients = array(
					array(
						'receiver'        => $receiver,
						'template_params' => $template_params
					)
				);

				self::send_sms( $type, $subject, $message, $recipients, $send_no, MSSMS_Manager::get_request_date() );
			} catch ( Exception $e ) {

			}
		}
		public static function add_partial_refund_template_params( $template_params, $order ) {
			$template_params['부분환불금액'] = number_format( floatval( self::$params['cancel_info']['amount'] ), wc_get_price_decimals() );

			return $template_params;
		}
		public static function maybe_send_partial_refund_notification( $params, $order, $gateway ) {
			add_filter( 'mssms_get_template_params', array( __CLASS__, 'add_partial_refund_template_params' ), 10, 2 );

			self::$params = $params;

			self::send_message_to_admin( $order->get_id(), 'pafw-partial-refund', 'pafw-partial-refund' );
			self::send_message_to_user( $order->get_id(), 'pafw-partial-refund', 'pafw-partial-refund' );

			self::$params = null;

			remove_filter( 'mssms_get_template_params', array( __CLASS__, 'add_partial_refund_template_params' ) );
		}
	}
}


