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

if ( ! class_exists( 'MSSMS_Settings_User' ) ) :

	class MSSMS_Settings_User {

		static function update_settings() {
			include_once MSSMS()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';

			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			MSSMS_Helper::update_settings( self::get_setting_fields() );

			wp_send_json_success();
		}

		static function get_setting_fields() {
			$tabs = array();

			$default = true;

			if ( MSSMS_Manager::is_enabled( 'sms' ) ) {
				$tabs = array_merge( $tabs, array(
					self::get_user_sms_setting( $default ),
				) );

				$default = false;
			}

			if ( MSSMS_Manager::is_enabled( 'alimtalk' ) ) {
				$tabs = array_merge( $tabs, array(
					self::get_user_alimtalk_setting( $default ),
				) );
			}

			return array(
				'type'     => 'Tab',
				'id'       => 'setting-tab',
				'elements' => $tabs
			);
		}

		static function get_user_alimtalk_setting( $default ) {
			ob_start();
			include( 'html/user-alimtalk-guide.php' );
			$guide = ob_get_clean();

			return array(
				'type'     => 'Page',
				'class'    => $default ? 'active' : '',
				'title'    => __( '알림톡 설정', 'mshop-sms-s2' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '등급 변경 시 알림톡 발송', 'mshop-sms-s2' ),
						'elements' => array(
							array(
								"id"        => "mssms_alimtalk_user_option",
								"className" => "alimtalk_user_option_table",
								"type"      => "SortableTable",
								"sortable"  => true,
								"repeater"  => true,
								"editable"  => true,
								"default"   => array(),
								"template"  => array(
									"enable" => "yes"
								),
								"elements"  => array(
									array(
										"id"        => "enable",
										"title"     => "활성화",
										"className" => "center aligned one wide column fluid",
										"type"      => "Toggle",
										"default"   => "yes"
									),
									array(
										"id"            => "previous_roles",
										"title"         => "기존역할",
										"type"          => "Select",
										"placeholder"   => __( '회원등급 선택', 'mshop-sms-s2' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "fluid",
										"multiple"      => true,
										"options"       => mssms_get_roles()
									),
									array(
										"id"            => "new_roles",
										"title"         => "신규역할",
										"type"          => "Select",
										"placeholder"   => __( '회원등급 선택', 'mshop-sms-s2' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "fluid",
										"multiple"      => true,
										"options"       => mssms_get_roles()
									),
									array(
										"id"            => "template_code",
										"title"         => __( "템플릿", 'mshop-sms-s2' ),
										"showIf"        => array( 'enable' => 'yes' ),
										"className"     => "center aligned four wide column fluid",
										"cellClassName" => "fluid",
										"type"          => "Select",
										"placeholder"   => "알림톡 템플릿을 선택하세요.",
										"options"       => MSSMS_Settings_Alimtalk_Send::get_templates()
									),
									array(
										"id"            => "resend_method",
										"title"         => __( "문자 대체 발송", 'mshop-sms-s2' ),
										"showIf"        => array( 'enable' => 'yes' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "fluid",
										"type"          => "Select",
										"default"       => "none",
										"options"       => array(
											'none'     => __( "사용안함", 'mshop-sms-s2' ),
											'alimtalk' => __( "알림톡 내용전달", 'mshop-sms-s2' ),
										)
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '회원가입 시 알림톡 발송', 'mshop-sms-s2' ),
						'elements' => array(
							array(
								"id"        => "mssms_alimtalk_created_customer",
								"className" => "alimtalk_created_customer_table",
								"type"      => "SortableTable",
								"sortable"  => true,
								"repeater"  => true,
								"editable"  => true,
								"default"   => array(),
								"template"  => array(
									"enable" => "yes"
								),
								"elements"  => array(
									array(
										"id"        => "enable",
										"title"     => "활성화",
										"className" => "center aligned one wide column fluid",
										"type"      => "Toggle",
										"default"   => "yes"
									),
									array(
										"id"            => "template_code",
										"title"         => __( "템플릿", 'mshop-sms-s2' ),
										"showIf"        => array( 'enable' => 'yes' ),
										"className"     => "center aligned seven wide column fluid",
										"cellClassName" => "fluid",
										"type"          => "Select",
										"placeholder"   => "알림톡 템플릿을 선택하세요.",
										"options"       => MSSMS_Settings_Alimtalk_Send::get_templates()
									),
									array(
										"id"            => "resend_method",
										"title"         => __( "문자 대체 발송", 'mshop-sms-s2' ),
										"showIf"        => array( 'enable' => 'yes' ),
										"className"     => "center aligned six wide column fluid",
										"cellClassName" => "fluid",
										"type"          => "Select",
										"default"       => "none",
										"options"       => array(
											'none'     => __( "사용안함", 'mshop-sms-s2' ),
											'alimtalk' => __( "알림톡 내용전달", 'mshop-sms-s2' ),
										)
									)
								)
							)
						)
					),
					array(
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array(
							array(
								"id"        => "mssms_alimtalk_user_options_desc",
								"className" => "fluid",
								"type"      => "Label",
								"readonly"  => "yes",
								"default"   => $guide
							)
						)
					)
				)
			);
		}

		static function get_user_sms_setting( $default ) {
			ob_start();
			include( 'html/user-sms-guide.php' );
			$guide = ob_get_clean();

			return array(
				'type'     => 'Page',
				'class'    => $default ? 'active' : '',
				'title'    => __( '문자 설정', 'mshop-sms-s2' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '등급 변경 시 문자 발송', 'mshop-sms-s2' ),
						'elements' => array(
							array(
								"id"        => "mssms_sms_user_option",
								"className" => "sms_user_option_table",
								"type"      => "SortableTable",
								"sortable"  => true,
								"repeater"  => true,
								"editable"  => true,
								"default"   => array(),
								"template"  => array(
									"enable" => "yes"
								),
								"elements"  => array(
									array(
										"id"        => "enable",
										"title"     => "활성화",
										"className" => "center aligned one wide column fluid",
										"type"      => "Toggle",
										"default"   => "yes"
									),
									array(
										"id"            => "previous_roles",
										"title"         => "기존역할",
										"type"          => "Select",
										"placeholder"   => __( '회원등급 선택', 'mshop-sms-s2' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "fluid",
										"multiple"      => true,
										"options"       => mssms_get_roles()
									),
									array(
										"id"            => "new_roles",
										"title"         => "신규역할",
										"type"          => "Select",
										"placeholder"   => __( '회원등급 선택', 'mshop-sms-s2' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "fluid",
										"multiple"      => true,
										"options"       => mssms_get_roles()
									),
									array(
										"id"          => "message",
										"title"       => __( "문자내용", 'mshop-sms-s2' ),
										"className"   => "center aligned seven wide fluid",
										"type"        => "TextArea",
										"placeholder" => "발송 문구를 입력하신 후 사용해주세요.",
										"rows"        => 3
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '회원가입 시 문자 발송', 'mshop-sms-s2' ),
						'elements' => array(
							array(
								"id"        => "mssms_sms_created_customer",
								"className" => "sms_created_customer_table",
								"type"      => "SortableTable",
								"sortable"  => true,
								"repeater"  => true,
								"editable"  => true,
								"default"   => array(),
								"template"  => array(
									"enable" => "yes"
								),
								"elements"  => array(
									array(
										"id"            => "enable",
										"title"         => "활성화",
										"className"     => "center aligned one wide column fluid",
										"cellClassName" => "fluid",
										"type"          => "Toggle",
										"default"       => "yes"
									),
									array(
										"id"            => "message",
										"title"         => __( "문자내용", 'mshop-sms-s2' ),
										"className"     => "center aligned thirteen wide column fluid",
										"cellClassName" => "fluid",
										"type"          => "TextArea",
										"placeholder"   => "발송 문구를 입력하신 후 사용해주세요.",
										"rows"          => 3
									)
								)
							)
						)
					),
					array(
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array(
							array(
								"id"        => "mssms_sms_user_options_desc",
								"className" => "fluid",
								"type"      => "Label",
								"readonly"  => "yes",
								"default"   => $guide
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
				'action'      => MSSMS()->slug() . '-update_user_settings',
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

