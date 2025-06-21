<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MSSMS_Subscription' ) ) {

	class MSSMS_Subscription {
		protected static $rules = null;

		protected static $retry_orders = array();

		public static function init() {
			add_filter( 'mssms_order_statuses', array( __CLASS__, 'add_status_for_subscription' ), 10, 2 );
			add_filter( 'mssms_get_order_statuses', array( __CLASS__, 'add_status_for_subscription' ), 10, 2 );
			add_filter( 'mssms_send_message_new_status', array( __CLASS__, 'maybe_change_new_status' ), 10, 2 );
			add_action( 'woocommerce_subscriptions_before_payment_retry', array( __CLASS__, 'save_payment_retry_order' ), 10, 2 );
			add_action( 'woocommerce_subscriptions_after_payment_retry', array( __CLASS__, 'delete_payment_retry_order' ), 10, 2 );
			add_action( 'woocommerce_subscription_date_updated', array( __CLASS__, 'maybe_register_notification_action' ), 100, 3 );
			add_action( 'woocommerce_subscription_date_deleted', array( __CLASS__, 'maybe_deregister_notification_action' ), 100, 2 );

			add_action( 'mssms_renewal_notification', array( __CLASS__, 'process_renewal_notification' ), 10, 2 );

			add_action( 'woocommerce_subscription_status_changed', array( __CLASS__, 'maybe_update_notification_action' ), 99, 4 );
		}
		public static function add_status_for_subscription( $order_statuses, $subscription ) {
			if ( $subscription ) {
				$order_statuses['failed_retry'] = __( '재결제시도 실패', 'mshop-sms-s2' );
			}

			return $order_statuses;
		}
		public static function maybe_change_new_status( $new_status, $order ) {
			if ( 'failed' == $new_status ) {
				if ( in_array( $order->get_id(), self::$retry_orders ) ) {
					$new_status = 'failed_retry';
				}
			}

			return $new_status;
		}
		public static function save_payment_retry_order( $last_retry, $last_order ) {
			self::$retry_orders[] = $last_order->get_id();
		}
		public static function delete_payment_retry_order( $last_retry, $last_order ) {
			self::$retry_orders = array_diff( self::$retry_orders, array( $last_order->get_id() ) );
		}
		protected static function load_rule( $option_name ) {
			$rules = get_option( $option_name, array() );

			$rules = array_filter( $rules, function ( $rule ) {
				return 'yes' == mssms_get( $rule, 'enable' );
			} );

			return $rules;
		}
		protected static function get_rules( $type ) {
			if ( is_null( self::$rules ) ) {
				self::$rules = array(
					'sms'      => array(
						'product'  => self::load_rule( 'mssms_renewal_product_sms_options' ),
						'category' => self::load_rule( 'mssms_renewal_category_sms_options' )
					),
					'alimtalk' => array(
						'product'  => self::load_rule( 'mssms_renewal_product_alimtalk_options' ),
						'category' => self::load_rule( 'mssms_renewal_category_alimtalk_options' )
					),

				);
			}

			return self::$rules[ $type ];
		}
		protected static function make_rule_data( $subscription, $rule, $type ) {
			if ( 'alimtalk' == $type ) {
				return array(
					'type'            => $type,
					'subscription_id' => $subscription->get_id(),
					'template_code'   => $rule['template_code'],
					'resend_method'   => $rule['resend_method'],
					'day'             => $rule['day']
				);
			} else {
				return array(
					'type'            => $type,
					'subscription_id' => $subscription->get_id(),
					'message'         => $rule['message'],
					'day'             => $rule['day']
				);
			}
		}
		protected static function get_matched_rule_by_subscription( $rules, $subscription, $type ) {
			$matched_rule = array();
			$order_product_ids = array_map( function ( $item ) {
				return $item->get_product_id();
			}, $subscription->get_items() );

			$order_product_ids = array_filter( $order_product_ids );

			if ( ! empty( $rules['product'] ) ) {
				foreach ( $rules['product'] as $product_rule ) {
					if ( is_array( $product_rule['products'] ) && 'yes' == $product_rule['enable'] && ! empty( array_intersect( $order_product_ids, array_keys( $product_rule['products'] ) ) ) ) {
						$matched_rule[] = self::make_rule_data( $subscription, $product_rule, $type );
					}
				}
			}

			if ( empty( $matched_rule ) && ! empty( $rules['category'] ) ) {
				$category_ids = array();

				foreach ( $order_product_ids as $product_id ) {
					$terms = get_the_terms( $product_id, 'product_cat' );

					if ( ! empty( $terms ) ) {
						$term_ids = array_map( function ( $term ) {
							return apply_filters( 'wpml_object_id', $term->term_id, 'product_cat', true, mssms_wpml_get_default_language() );
						}, $terms );

						$category_ids = array_merge( $category_ids, $term_ids );
					}
				}

				$category_ids = array_filter( $category_ids );

				if ( ! empty( $category_ids ) ) {
					foreach ( $rules['category'] as $category_rule ) {
						if ( is_array( $category_rule['categories'] ) && 'yes' == $category_rule['enable'] && ! empty( array_intersect( $category_ids, array_keys( $category_rule['categories'] ) ) ) ) {
							$matched_rule[] = self::make_rule_data( $subscription, $category_rule, $type );
						}
					}
				}
			}

			return $matched_rule;
		}
		protected static function get_resend_params( $profile, $resend_method ) {
			$resend_params = array(
				'isResend' => 'false'
			);

			if ( 'yes' == $profile['is_resend'] && ! empty( $profile['resend_send_no'] ) ) {
				if ( 'alimtalk' == $resend_method ) {
					$resend_params = array(
						'isResend'     => 'true',
						'resendSendNo' => $profile['resend_send_no']
					);
				} else if ( 'sms' == $resend_method ) {
					$resend_params = array(
						'isResend'      => 'true',
						'resendSendNo'  => $profile['resend_send_no'],
						'resendTitle'   => '',
						'resendContent' => '',
					);
				}
			}

			return $resend_params;
		}
		protected static function deregister_renewal_notification( $subscription ) {
			$group = strtoupper( 'MSSMS-RENEWAL-NOTIFICATION-' . $subscription->get_id() );
			as_unschedule_all_actions( '', array(), $group );
		}
		protected static function register_renewal_notification( $subscription, $scheduled_time, $rule ) {
			$group = strtoupper( 'MSSMS-RENEWAL-NOTIFICATION-' . $subscription->get_id() );

			as_schedule_single_action(
				$scheduled_time,
				'mssms_renewal_notification',
				array(
					'subscription_id' => $subscription->get_id(),
					'rule'            => $rule
				),
				$group
			);
		}
		public static function maybe_register_notification_action( $subscription, $date_type, $datetime ) {
			if ( $subscription && 'next_payment' == $date_type ) {
				self::deregister_renewal_notification( $subscription );

				if ( ! empty( $datetime ) ) {
					$rules = self::get_matched_rule_by_subscription( self::get_rules( 'alimtalk' ), $subscription, 'alimtalk' );
					if ( empty( $rules ) ) {
						$rules = self::get_matched_rule_by_subscription( self::get_rules( 'sms' ), $subscription, 'sms' );
					}

					if ( ! empty( $rules ) ) {
						$next_payment_date = strtotime( $datetime );

						foreach ( $rules as $rule ) {
							$scheduled_time = strtotime( date( 'Y-m-d H:i:s', $next_payment_date ) . ' -' . $rule['day'] . ' days' );

							if ( $scheduled_time > time() ) {
								self::register_renewal_notification( $subscription, $scheduled_time, $rule );
							}
						}
					}
				}
			}
		}
		public static function maybe_deregister_notification_action( $subscription, $date_type ) {
			if ( $subscription && 'next_payment' == $date_type ) {
				self::deregister_renewal_notification( $subscription );
			}
		}
		protected static function send_sms( $subscription, $rule ) {
			try {
				$template_params = MSSMS_Manager::get_template_params( $subscription );
				$receiver        = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $subscription ) : $subscription->get_billing_phone();

				$recipients = array(
					array(
						'receiver'        => $receiver,
						'template_params' => $template_params
					)
				);

				$type = MSSMS_SMS::get_sms_type( $rule['message'], $template_params );

				MSSMS_SMS::send_sms( $type, '', $rule['message'], $recipients, '', MSSMS_Manager::get_request_date() );

				$subscription->add_order_note( __( '<span style="font-size: 12px;">갱신결제 사전알림 문자가 발송되었습니다.</span>' ) );
			} catch ( Exception $e ) {
				$subscription->add_order_note( sprintf( __( '<span style="font-size: 12px; color: #ff0000;">[갱신결제 사전알림 실패]<br>%s</span>' ), $e->getMessage() ) );
			}
		}
		protected static function send_alimtalk( $subscription, $rule ) {
			try {
				$template_code = $rule['template_code'];

				$template = MSSMS_Kakao::get_template( $template_code );
				if ( empty( $template ) ) {
					throw new Exception( sprintf( __( '[%s] 유효하지 않은 템플릿 아이디입니다.', 'mshop-sms-s2' ), $template_code ) );
				}

				$profile = MSSMS_Kakao::get_profile( $template['plus_id'] );
				if ( empty( $template ) ) {
					throw new Exception( sprintf( __( '[%s] 유효하지 않은 플러스친구 아이디입니다.', 'mshop-sms-s2' ), $template['plus_id'] ) );
				}

				$receiver        = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $subscription ) : $subscription->get_billing_phone();
				$template_params = MSSMS_Manager::get_template_params( $subscription );

				$messages = array();

				if ( 'alimtalk' == $rule['resend_method'] ) {
					$resend_params = array(
						'isResend'     => 'true',
						'resendSendNo' => $profile['resend_send_no']
					);
				} else {
					$resend_params = array( 'isResend' => 'false' );
				}

				$recipients[] = array(
					'receiver'        => $receiver,
					'template_params' => $template_params,
					"resend"          => $resend_params
				);

				MSSMS_API_Kakao::send_message( $template['plus_id'], $template['code'], MSSMS_Manager::get_request_date(), $recipients );

				$subscription->add_order_note( __( '<span style="font-size: 12px;">갱신결제 사전알림 알림톡이 발송되었습니다.</span>' ) );
			} catch ( Exception $e ) {
				$subscription->add_order_note( sprintf( __( '<span style="font-size: 12px; color: #ff0000;">[갱신결제 사전알림 실패]<br>%s</span>' ), $e->getMessage() ) );
			}
		}
		public static function process_renewal_notification( $subscription_id, $rule ) {
			$subscription = wc_get_order( $subscription_id );

			if ( apply_filters( 'mssms_process_renewal_notification', true, $subscription, $rule ) ) {
				if ( 'alimtalk' == $rule['type'] ) {
					self::send_alimtalk( $subscription, $rule );
				} else if ( 'sms' == $rule['type'] ) {
					self::send_sms( $subscription, $rule );
				}
			}
		}
		static function maybe_update_notification_action( $subscription_id, $from_status, $to_status, $subscription ) {
			if ( 'active' == $to_status ) {
				self::maybe_register_notification_action( $subscription, 'next_payment', $subscription->get_date( 'next_payment' ) );
			} else {
				self::maybe_deregister_notification_action( $subscription, 'next_payment' );
			}
		}
	}

	MSSMS_Subscription::init();

}
