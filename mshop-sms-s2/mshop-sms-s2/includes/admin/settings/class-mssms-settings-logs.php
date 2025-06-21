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

if ( ! class_exists( 'MSSMS_Settings_Logs' ) ) :

	class MSSMS_Settings_Logs {
		public static $number_per_page = 20;
		public static $navigation_size = 5;

		public static function get_setting_fields() {
			return array(
				'type'         => 'ListPage',
				'title'        => __( '포인트 로그', 'mshop-sms-s2' ),
				'id'           => 'mshop_point_log',
				'searchConfig' => array(
					'action'   => MSSMS()->slug() . '-get_send_results',
					'pageSize' => self::$number_per_page
				),
				'elements'     => array(
					array(
						'type'              => 'MShopListTableFilter',
						'id'                => 'mshop_point_log_filter',
						'hideSectionHeader' => true,
						'elements'          => array(
							array(
								'id'        => 'term',
								"type"      => "DateRange",
								"title"     => __( "조회기간", 'mshop-sms-s2' ),
								"className" => "mshop-daterange",
							),
							array(
								"id"        => "mtype",
								"title"     => __( "알림 종류", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "Select",
								"default"   => "all",
								'options'   => array(
									'all' => '전체',
									'SMS' => '문자',
									'AT'  => '알림톡',
								)
							),
							array(
								"id"        => "sms_type",
								"title"     => __( "문자 종류", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "Select",
								"default"   => "all",
								'options'   => array(
									'all' => '전체',
									'SMS' => '단문(SMS)',
									'LMS' => '장문(LMS)',
								)
							),
							array(
								"id"        => "state",
								"title"     => __( "상태", 'mshop-sms-s2' ),
								"className" => "",
								"type"      => "Select",
								"default"   => "all",
								'options'   => array(
									'all'       => '모두',
									'REQUEST'   => '전송요청',
									'COMPLETED' => '전송완료',
									'RESEND'    => '대체발송중',
									'FAIL'      => '전송실패',
								)
							),
							array(
								"id"        => "receiver",
								"title"     => __( "수신번호", 'mshop-sms-s2' ),
								"className" => "fluid",
								"type"      => "Text",
								"eventType" => "blur",
								"default"   => "",
								"desc2"     => __( "<div class='desc2'>복수개의 수신번호를 검색하려면 ','로 구분해서 입력해주세요. 예) 01012341234,01056785678</div>", "mshop-sms-s2" ),
							),
							array(
								"id"        => "message",
								"title"     => __( "문자내용", 'mshop-sms-s2' ),
								"className" => "fluid",
								"type"      => "Text",
								"eventType" => "blur",
								"default"   => "",
								"desc2"     => __( "<div class='desc2'>특정 문자열이 포함된 발송 내력을 검색합니다.</div>", "mshop-sms-s2" ),
							),
						)
					),
					array(
						'type' => 'MShopListTableNavigator',
					),
					array(
						'type'     => 'MShopListTable',
						'id'       => 'mshop_point_log_target',
						'default'  => array(),
						"repeater" => true,
						'elements' => array(
							'type'        => 'SortableTable',
							'className'   => 'sortable',
							'noResultMsg' => __( '검색 결과가 없습니다.', 'mshop-sms-s2' ),
							"repeater"    => true,
							"elements"    => array(
								array(
									"id"            => "no",
									"title"         => __( "", 'mshop-sms-s2' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "no"
								),
								array(
									"id"            => "mtype",
									"title"         => __( "타입", 'mshop-sms-s2' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "id"
								),
								array(
									"id"            => "point",
									"title"         => __( "포인트", 'mshop-sms-s2' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								),
								array(
									"id"            => "sender",
									"title"         => __( "발신", 'mshop-sms-s2' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "user_id"
								),
								array(
									"id"            => "receiver",
									"title"         => __( "수신", 'mshop-sms-s2' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "wallet_id"
								),
								array(
									"id"        => "message",
									"title"     => __( "메시지", 'mshop-sms-s2' ),
									"className" => "center aligned",
									"type"      => "Label",
									"sortKey"   => "amount"
								),
								array(
									"id"            => "state",
									"title"         => __( "상태", 'mshop-sms-s2' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label"
								),
								array(
									"id"            => "request_date",
									"title"         => __( "요청일자", 'mshop-sms-s2' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label"
								),
								array(
									"id"            => "receive_date",
									"title"         => __( "수신일자", 'mshop-sms-s2' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								)
							)
						)
					),
					array(
						'type' => 'MShopListTableNavigator',
					),
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
			include_once MSSMS()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';
			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => 'mshop_point_update_settings',
				'settings' => $settings,
				'values'   => MSSMS_Helper::get_settings( $settings ),
				'locale'   => get_locale(),
			) );

			?>
            <style>
                .mshop-setting-section .ui.table.sortable td {
                    height: 50px;
                }
            </style>
            <script>
                jQuery( document ).ready( function () {
                    jQuery( this ).trigger( 'mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( MSSMS_Helper::get_settings( $settings ) ); ?>  ] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>

			<?php
		}
	}

endif;
