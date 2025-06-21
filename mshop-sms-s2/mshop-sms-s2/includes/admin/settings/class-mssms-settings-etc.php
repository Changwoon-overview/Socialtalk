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

if ( ! class_exists( 'MSSMS_Settings_Etc' ) ) :

	class MSSMS_Settings_Etc {

		static function update_settings() {
			include_once MSSMS()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';

			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			MSSMS_Helper::update_settings( self::get_setting_fields() );

			wp_send_json_success();
		}

		static function get_setting_fields() {
			return array(
				'type'     => 'Tab',
				'id'       => 'setting-tab',
				'elements' => array(
					self::get_pending_payment_setting()
				)
			);
		}

		static function get_pending_payment_setting() {
			return array(
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '알림 설정', 'mshop-sms-s2' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => '결제대기 주문알림',
						'elements' => array(
							array(
								"id"        => "mssms_use_pending_payment_notification",
								"title"     => __( "알림 기능 사용", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "<div class='desc2'>결제대기 상태의 주문에 대해 고객 및 관리자에게 알림을 전달합니다.</div>", "mshop-sms-s2" )
							),
							array(
								"id"        => "mssms_pending_payment_notification_interval",
								'showIf'    => array( 'mssms_use_pending_payment_notification' => 'yes' ),
								"title"     => __( "발송대기시간", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "LabeledInput",
								"default"   => "30",
								"label"     => __( "분", 'mshop-sms-s2' ),
								"desc2"     => __( "<div class='desc2'>주문이 결제대기 상태로 변경된 후, 지정된 시간이 경과하면 알림을 발송합니다.</div>", 'mshop-sms-s2' ),
							),
							array(
								"id"        => "mssms_pending_payment_notification_target",
								"title"     => __( "발송 대상", 'mshop-sms-s2' ),
								'showIf'    => array( 'mssms_use_pending_payment_notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'user',
								'options'   => array(
									'admin' => __( '관리자', 'mshop-sms-s2' ),
									'user'  => __( '고객', 'mshop-sms-s2' ),
									'all'   => __( '모두', 'mshop-sms-s2' ),
								),
							),
							array(
								"id"        => "mssms_pending_payment_notification_method",
								"title"     => "발송 수단",
								'showIf'    => array( 'mssms_use_pending_payment_notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'alimtalk',
								'options'   => array(
									'sms'      => __( '문자 (LMS)', 'mshop-sms-s2' ),
									'alimtalk' => __( '알림톡', 'mshop-sms-s2' ),
								),
							),
							array(
								"id"        => "mssms_pending_payment_notification_sms_template",
								"title"     => __( "문자(LMS) 템플릿", 'mshop-sms-s2' ),
								'showIf'    => array( array( 'mssms_use_pending_payment_notification' => 'yes' ), array( 'mssms_pending_payment_notification_method' => 'sms' ) ),
								"className" => "center aligned fluid",
								"type"      => "TextArea",
								"rows"      => 3
							),
							array(
								"id"          => "mssms_pending_payment_alimtalk_template",
								'showIf'      => array( array( 'mssms_use_pending_payment_notification' => 'yes' ), array( 'mssms_pending_payment_notification_method' => 'alimtalk' ) ),
								"title"       => __( "알림톡 템플릿", 'mshop-sms-s2' ),
								"placeholder" => __( "템플릿을 선택해주세요.", 'mshop-sms-s2' ),
								"className"   => "fluid",
								"type"        => "Select",
								'options'     => MSSMS_Manager::get_alimtalk_templates()
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => '무통장 입금확인중 알림',
						'elements' => array(
							array(
								"id"        => "mssms_use_bacs_notification",
								"title"     => __( "알림 기능 사용", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "<div class='desc2'>입금대기중 상태의 주문에 대해 고객 및 관리자에게 알림을 전달합니다.</div>", "mshop-sms-s2" )
							),
							array(
								"id"        => "mssms_bacs_notification_interval",
								'showIf'    => array( 'mssms_use_bacs_notification' => 'yes' ),
								"title"     => __( "발송대기시간", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "LabeledInput",
								"default"   => "30",
								"label"     => __( "분", 'mshop-sms-s2' ),
								"desc2"     => __( "<div class='desc2'>주문이 입금대기중 상태로 변경된 후, 지정된 시간이 경과하면 알림을 발송합니다.</div>", 'mshop-sms-s2' ),
							),
							array(
								"id"        => "mssms_bacs_notification_target",
								"title"     => __( "발송 대상", 'mshop-sms-s2' ),
								'showIf'    => array( 'mssms_use_bacs_notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'user',
								'options'   => array(
									'admin' => __( '관리자', 'mshop-sms-s2' ),
									'user'  => __( '고객', 'mshop-sms-s2' ),
									'all'   => __( '모두', 'mshop-sms-s2' ),
								),
							),
							array(
								"id"        => "mssms_bacs_notification_method",
								"title"     => "발송 수단",
								'showIf'    => array( 'mssms_use_bacs_notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'alimtalk',
								'options'   => array(
									'sms'      => __( '문자 (LMS)', 'mshop-sms-s2' ),
									'alimtalk' => __( '알림톡', 'mshop-sms-s2' ),
								),
							),
							array(
								"id"        => "mssms_bacs_notification_sms_template",
								"title"     => __( "문자(LMS) 템플릿", 'mshop-sms-s2' ),
								'showIf'    => array( array( 'mssms_use_bacs_notification' => 'yes' ), array( 'mssms_bacs_notification_method' => 'sms' ) ),
								"className" => "center aligned fluid",
								"type"      => "TextArea",
								"rows"      => 3
							),
							array(
								"id"          => "mssms_bacs_notification_alimtalk_template",
								'showIf'      => array( array( 'mssms_use_bacs_notification' => 'yes' ), array( 'mssms_bacs_notification_method' => 'alimtalk' ) ),
								"title"       => __( "알림톡 템플릿", 'mshop-sms-s2' ),
								"placeholder" => __( "템플릿을 선택해주세요.", 'mshop-sms-s2' ),
								"className"   => "fluid",
								"type"        => "Select",
								'options'     => MSSMS_Manager::get_alimtalk_templates()
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => '가상계좌 입금확인중 알림',
						'elements' => array(
							array(
								"id"        => "mssms_use_vbank_notification",
								"title"     => __( "알림 기능 사용", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( "<div class='desc2'>가상계좌 입금대기중 상태의 주문에 대해 고객 및 관리자에게 알림을 전달합니다.</div>", "mshop-sms-s2" )
							),
							array(
								"id"        => "mssms_vbank_notification_interval",
								'showIf'    => array( 'mssms_use_vbank_notification' => 'yes' ),
								"title"     => __( "발송대기시간", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "LabeledInput",
								"default"   => "30",
								"label"     => __( "분", 'mshop-sms-s2' ),
								"desc2"     => __( "<div class='desc2'>가상계좌 주문이 결제된 후, 지정된 시간이 경과하면 알림을 발송합니다.</div>", 'mshop-sms-s2' ),
							),
							array(
								"id"        => "mssms_vbank_notification_target",
								"title"     => __( "발송 대상", 'mshop-sms-s2' ),
								'showIf'    => array( 'mssms_use_vbank_notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'user',
								'options'   => array(
									'admin' => __( '관리자', 'mshop-sms-s2' ),
									'user'  => __( '고객', 'mshop-sms-s2' ),
									'all'   => __( '모두', 'mshop-sms-s2' ),
								),
							),
							array(
								"id"        => "mssms_vbank_notification_method",
								"title"     => "발송 수단",
								'showIf'    => array( 'mssms_use_vbank_notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'alimtalk',
								'options'   => array(
									'sms'      => __( '문자 (LMS)', 'mshop-sms-s2' ),
									'alimtalk' => __( '알림톡', 'mshop-sms-s2' ),
								),
							),
							array(
								"id"        => "mssms_vbank_notification_sms_template",
								"title"     => __( "문자(LMS) 템플릿", 'mshop-sms-s2' ),
								'showIf'    => array( array( 'mssms_use_vbank_notification' => 'yes' ), array( 'mssms_vbank_notification_method' => 'sms' ) ),
								"className" => "center aligned fluid",
								"type"      => "TextArea",
								"rows"      => 3
							),
							array(
								"id"          => "mssms_vbank_notification_alimtalk_template",
								'showIf'      => array( array( 'mssms_use_vbank_notification' => 'yes' ), array( 'mssms_vbank_notification_method' => 'alimtalk' ) ),
								"title"       => __( "알림톡 템플릿", 'mshop-sms-s2' ),
								"placeholder" => __( "템플릿을 선택해주세요.", 'mshop-sms-s2' ),
								"className"   => "fluid",
								"type"        => "Select",
								'options'     => MSSMS_Manager::get_alimtalk_templates()
							)
						)
					)
				)
			);
		}


		static function enqueue_scripts() {
			wp_enqueue_script( 'underscore' );
			wp_enqueue_style( 'mshop-setting-manager', MSSMS()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', MSSMS()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array(
				'jquery',
				'jquery-ui-core'
			) );
		}
		public static function output() {

			require_once MSSMS()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';

			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'     => 'mshop-setting-wrapper',
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'action'      => MSSMS()->slug() . '-update_etc_settings',
				'settings'    => $settings,
				'slug'        => MSSMS()->slug(),
				'domain'      => preg_replace( '#^https?://#', '', mssms_home_url() ),
				'licenseInfo' => get_option( 'msl_license_' . MSSMS()->slug(), null ),
				'locale'      => get_locale(),
			) );

			?>
            <script>
                jQuery( document ).ready( function () {
                    jQuery( this ).trigger( 'mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( MSSMS_Helper::get_settings( $settings ) ); ?>, null, null] );
                } );
            </script>
            <style>
                .ui.table.sms_admin_option_table td,
                .ui.table.sms_user_option_table td {
                    border-top: 1px solid rgba(34, 36, 38, 0.1) !important;
                }

                .ui.table.alimtalk_admin_option_table td,
                .ui.table.alimtalk_user_option_table td {
                    border-top: 1px solid rgba(34, 36, 38, 0.1) !important;
                }

            </style>
            <div id="mshop-setting-wrapper"></div>
			<?php
		}
	}
endif;

