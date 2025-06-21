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

if ( ! class_exists( 'MSSMS_Emails' ) ) {

	class MSSMS_Emails {
		static $hook = array ();

		public static function init() {
			add_filter( 'woocommerce_email_classes', array ( __CLASS__, 'woocommerce_email_classes' ) );

			add_action( 'mssms_sms_response', array ( __CLASS__, 'maybe_send_point_shortage_notification' ), 10 );
			add_action( 'mssms_alimtalk_response', array ( __CLASS__, 'maybe_send_point_shortage_notification' ), 10 );

			add_action( 'mssms_process_scheduled_email_action', 'mssms_process_scheduled_email_action', 10 );
		}
		static function woocommerce_email_classes( $emails ) {
			$emails['MSSMS_Email_Point_Shortage'] = include( 'emails/class-mssms-email-point-shortage.php' );

			return $emails;
		}

		static function maybe_send_point_shortage_notification( $response ) {
			if ( MSSMS_Manager::use_point_shortage_notification() && ! empty( $response['data'] ) && isset( $response['data']['remain_count'] ) ) {
				$remain_point = mssms_get( $response['data'], 'remain_count', 0 );

				$threshold = get_option( 'mssms_point_shortage_threshold', 2000 );

				if ( $remain_point <= $threshold && ! get_transient( 'mssms_point_shortage_notification' ) ) {
					set_transient( 'mssms_point_shortage_notification', 'mssms_point_shortage_notification', DAY_IN_SECONDS );
					as_schedule_single_action( time(), 'mssms_process_scheduled_email_action', array ( 'hook' => 'mssms_send_point_shortage' ) );
				}
			}
		}
	}

	function mssms_process_scheduled_email_action( $hook ) {
		WC()->mailer();
		do_action( $hook . '_notification' );
	}

	MSSMS_Emails::init();
}

