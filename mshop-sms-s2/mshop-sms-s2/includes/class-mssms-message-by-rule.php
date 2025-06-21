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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MSSMS_Message_By_Rule' ) ) :

	class MSSMS_Message_By_Rule {

		protected static $rules = null;
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
						'product'  => self::load_rule( 'mssms_sms_product_options' ),
						'category' => self::load_rule( 'mssms_sms_category_options' )
					),
					'alimtalk' => array(
						'product'  => self::load_rule( 'mssms_alimtalk_product_options' ),
						'category' => self::load_rule( 'mssms_alimtalk_category_options' )
					),

				);
			}

			return self::$rules[ $type ];
		}
		public static function get_sms_message_template( $message, $old_status, $new_status, $target, $order ) {
			if ( 'user' == $target ) {
				$rules           = self::get_rules( 'sms' );
				$matched_rules   = self::get_matched_rule_by_order( $rules, $order, $new_status );
				$matched_message = array();

				if ( ! empty( $matched_rules ) ) {
					foreach ( $matched_rules as $matched_rule ) {
						if ( 'replace' == $matched_rule['method'] ) {
							$message           = array();
							$matched_message[] = $matched_rule['message'];
						} else if ( 'concat' == $matched_rule['method'] && ! empty( $message ) ) {
							$message = array( sprintf( "%s\n%s", current( $message ), $matched_rule['message'] ) );
						} else if ( 'additional' == $matched_rule['method'] ) {
							$matched_message[] = $matched_rule['message'];
						}
					}
				}

				$message = array_filter( array_merge( $message, $matched_message ) );
			}

			return $message;
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
		public static function get_alimtalk_message_template( $template_infos, $old_status, $new_status, $target, $order ) {
			if ( 'user' == $target ) {
				$rules             = self::get_rules( 'alimtalk' );
				$matched_rules     = self::get_matched_rule_by_order( $rules, $order, $new_status );
				$matched_templates = array();

				if ( ! empty( $matched_rules ) ) {
					foreach ( $matched_rules as $matched_rule ) {
						$template = MSSMS_Kakao::get_template( $matched_rule['template_code'] );

						$profile = MSSMS_Kakao::get_profile( $template['plus_id'] );

						$resend_params = self::get_resend_params( $profile, $matched_rule['resend_method'] );

						if ( 'replace' == $matched_rule['method'] ) {
							$template_infos = array();
						}

						$matched_templates[] = array(
							'template_code' => $matched_rule['template_code'],
							'resend_params' => $resend_params
						);
					}
				}

				$template_infos = array_filter( array_merge( $template_infos, $matched_templates ) );
			}

			return $template_infos;
		}
		protected static function get_matched_rule_by_order( $rules, $order, $order_status ) {
			$matched_rules = array();
			$order_product_ids = array_map( function ( $item ) {
				return $item->get_product_id();
			}, $order->get_items() );

			$order_product_ids = array_filter( $order_product_ids );

			if ( ! empty( $rules['product'] ) ) {
				$filtered_rules = array_filter( $rules['product'], function ( $rule ) use ( $order_status ) {
					return $rule['order_status'] == $order_status;
				} );

				$filtered_rules = array_filter( $filtered_rules );

				if ( ! empty( $filtered_rules ) ) {
					foreach ( $filtered_rules as $product_rule ) {
						$product_ids = array_keys( $product_rule['products'] );

						if ( ! empty( array_intersect( $order_product_ids, $product_ids ) ) ) {
							$matched_rules[] = $product_rule;
						}
					}
				}
			}

			if ( ! empty( $rules['category'] ) ) {
				$filtered_rules = array_filter( $rules['category'], function ( $rule ) use ( $order_status ) {
					return $rule['order_status'] == $order_status;
				} );
				$filtered_rules = array_filter( $filtered_rules );

				if ( ! empty( $filtered_rules ) ) {
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
						foreach ( $filtered_rules as $category_rule ) {
							if ( ! empty( array_intersect( $category_ids, array_keys( $category_rule['categories'] ) ) ) ) {
								$matched_rules[] = $category_rule;
							}
						}
					}
				}
			}

			return $matched_rules;
		}
	}

endif;
