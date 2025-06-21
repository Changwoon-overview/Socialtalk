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

if ( ! class_exists( 'MSSMS_User' ) ) {

	class MSSMS_User {
		static $sms_rules = null;
		static $alimtalk_rules = null;
		protected static function get_sms_rules() {
			if ( is_null( self::$sms_rules ) ) {
				$rules = get_option( 'mssms_sms_user_option', array() );

				self::$sms_rules = array_map( function ( $rule ) {
					$rule['previous_roles'] = explode( ',', $rule['previous_roles'] );
					$rule['new_roles']      = explode( ',', $rule['new_roles'] );

					return $rule;
				}, array_filter( $rules, function ( $rule ) {
					return 'yes' == $rule['enable'];
				} ) );
			}

			return self::$sms_rules;
		}
		protected static function get_alimtalk_rules() {
			if ( is_null( self::$sms_rules ) ) {
				$rules = get_option( 'mssms_alimtalk_user_option', array() );

				self::$alimtalk_rules = array_map( function ( $rule ) {
					$rule['previous_roles'] = explode( ',', $rule['previous_roles'] );
					$rule['new_roles']      = explode( ',', $rule['new_roles'] );

					return $rule;
				}, array_filter( $rules, function ( $rule ) {
					return 'yes' == $rule['enable'];
				} ) );
			}

			return self::$alimtalk_rules;
		}
		protected static function get_filtered_sms_user_rules( $role, $old_roles ) {
			$filtered_rules = array_filter( self::get_sms_rules(), function ( $rule ) use ( $role, $old_roles ) {
				return in_array( $role, $rule['new_roles'] ) && ! empty( array_intersect( $old_roles, $rule['previous_roles'] ) );
			} );

			return apply_filters( 'mssms_get_filtered_sms_user_rules', $filtered_rules, $role, $old_roles );
		}
		protected static function get_filtered_alimtalk_user_rules( $role, $old_roles ) {
			$filtered_rules = array_filter( self::get_alimtalk_rules(), function ( $rule ) use ( $role, $old_roles ) {
				return in_array( $role, $rule['new_roles'] ) && ! empty( array_intersect( $old_roles, $rule['previous_roles'] ) );
			} );

			return apply_filters( 'mssms_get_filtered_alimtalk_user_rules', $filtered_rules, $role, $old_roles );
		}
		public static function maybe_send_alimtalk( $user_id, $role, $old_roles ) {
			$rules = self::get_filtered_alimtalk_user_rules( $role, $old_roles );

			if ( ! empty( $rules ) ) {
				$roles = mssms_get_roles();
				$rule  = reset( $rules );

				$phone_number = get_user_meta( $user_id, 'billing_phone', true );

				if ( ! empty( $phone_number ) ) {
					$recipients[] = $phone_number;

					$resend_params = array(
						'isResend' => 'false'
					);

					$user        = get_userdata( $user_id );
					$wc_customer = new WC_Customer( $user_id );

					$template = MSSMS_Kakao::get_template( $rule['template_code'] );

					$profile = MSSMS_Kakao::get_profile( $template['plus_id'] );
					if ( 'alimtalk' == $rule['resend_method'] ) {
						$resend_params = array(
							'isResend'     => 'true',
							'resendSendNo' => $profile['resend_send_no']
						);
					}

					$template_params = array(
						'쇼핑몰명' => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
						'고객명'  => $user->display_name,
						'아이디'  => $user->user_login,
						'청구자명' => $wc_customer->get_billing_first_name() . $wc_customer->get_billing_last_name(),
						'신규역할' => $roles[ $role ],
						'기존역할' => $roles[ reset( $old_roles ) ]
					);

					MSSMS_Kakao::send_alimtalk( $rule['template_code'], $recipients, $template_params, $resend_params );
				}
			}
		}

		public static function maybe_send_sms( $user_id, $role, $old_roles ) {
			$rules = self::get_filtered_sms_user_rules( $role, $old_roles );

			if ( ! empty( $rules ) ) {
				$roles = mssms_get_roles();
				$rule  = reset( $rules );

				$recipients[] = get_user_meta( $user_id, 'billing_phone', true );

				if ( empty( $recipients ) ) {
					return;
				}

				$user = get_userdata( $user_id );

				$template_params = array(
					'쇼핑몰명' => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
					'고객명'  => $user->display_name,
					'아이디'  => $user->user_login,
					'신규역할' => $roles[ $role ],
					'기존역할' => $roles[ reset( $old_roles ) ]
				);

				$recipients = array_map( function ( $receiver ) use ( $template_params ) {
					return array(
						'receiver'        => $receiver,
						'template_params' => $template_params
					);
				}, $recipients );

				$type = MSSMS_SMS::get_sms_type( $rule['message'], $template_params );

				MSSMS_SMS::send_sms( $type, '', $rule['message'], $recipients, '', MSSMS_Manager::get_request_date() );
			}
		}
	}
}


