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

if ( ! class_exists( 'MSSMS_Settings_Alimtalk_Template' ) ) :

	class MSSMS_Settings_Alimtalk_Template {
		static function init() {
			add_filter( 'msshelper_get_mssms_template_list', array( __CLASS__, 'get_template_list' ) );
		}

		static function get_plus_ids() {
			$profiles = get_option( 'mssms_profile_lists', array() );

			$plus_ids = array_column( $profiles, 'plus_id' );

			return array_combine( $plus_ids, $plus_ids );
		}

		static function get_template_list() {
			try {
				$profiles  = get_option( 'mssms_profile_lists', array() );
				$templates = array();

				$hide_template_ids = get_option( 'mssms_hide_template_ids', array() );

				if ( empty( $templates ) ) {
					$templates = array();

					foreach ( $profiles as $profile ) {
						$templates = array_merge( $templates, MSSMS_API_Kakao::get_template_list( $profile['plus_id'] ) );
					}
					$templates = array_combine( array_column( $templates, 'code' ), $templates );

					$saved_templates = array();
					foreach ( $templates as $code => &$template ) {
						$template['visibility'] = in_array( $code, $hide_template_ids ) ? 'HIDE' : 'SHOW';

						$saved_templates[ $code ] = $template;
						unset( $saved_templates[ $code ]['content'] );
						unset( $saved_templates[ $code ]['comments'] );
					}

					update_option( 'mssms_template_lists', json_decode( json_encode( $saved_templates ), true ), 'no' );
				}

				$status_filter     = get_option( 'mssms_filter_template_status', 'ALL' );
				$visibility_filter = get_option( 'mssms_filter_template_visibility', 'ALL' );

				$templates = array_filter( $templates, function ( $template ) use ( $status_filter, $visibility_filter ) {
					return ( 'ALL' == $status_filter || $status_filter == $template['status'] ) && ( 'ALL' == $visibility_filter || $visibility_filter == $template['visibility'] );
				} );

				return array_values( $templates );
			} catch ( Exception $e ) {

			}
		}

		static function update_settings() {
			$params = json_decode( stripslashes( $_REQUEST['values'] ), true );

			update_option( 'mssms_filter_template_status', mssms_get( $params, 'mssms_filter_template_status' ), 'ALL' );
			update_option( 'mssms_filter_template_visibility', mssms_get( $params, 'mssms_filter_template_visibility' ), 'ALL' );

			wp_send_json_success( array( 'reload' => true ) );
		}

		static function get_template_setting() {
			ob_start();
			include( 'html/alimtalk-template-guide.php' );
			$guide = ob_get_clean();

			return array(
				'type'     => 'Page',
				'class'    => '',
				"showIf"   => array( "mssms_use_alimtalk" => "yes" ),
				'title'    => __( '템플릿 설정', 'mshop-sms-s2' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '템플릿 필터', 'mshop-sms-s2' ),
						'elements' => array(
							array(
								"id"          => "mssms_filter_template_status",
								"title"       => __( "템플릿 상태", 'mshop-sms-s2' ),
								"className"   => "",
								"type"        => "Select",
								"placeholder" => "알림톡 템플릿을 선택하세요.",
								"default"     => 'ALL',
								"options"     => array(
									'ALL' => __( "모두", 'mshop-sms-s2' ),
									'REQ' => __( "검수중", 'mshop-sms-s2' ),
									'REG' => __( "검수요청", 'mshop-sms-s2' ),
									'APR' => __( "승인완료", 'mshop-sms-s2' ),
									'REJ' => __( "반려됨", 'mshop-sms-s2' ),
								)
							),
							array(
								"id"          => "mssms_filter_template_visibility",
								"title"       => __( "템플릿 가시성", 'mshop-sms-s2' ),
								"className"   => "",
								"type"        => "Select",
								"placeholder" => "알림톡 템플릿을 선택하세요.",
								"default"     => 'ALL',
								"options"     => array(
									'ALL'  => __( "모두", 'mshop-sms-s2' ),
									'SHOW' => __( "표시", 'mshop-sms-s2' ),
									'HIDE' => __( "숨김", 'mshop-sms-s2' ),
								)
							),
						)
					),
					array(
						"id"           => "mssms_template_list",
						"type"         => "SortableList",
						"title"        => __( "템플릿 목록", 'mshop-sms-s2' ),
						"listItemType" => "AlimtalkTemplateManager",
						"repeater"     => true,
						"slug"         => MSSMS()->slug(),
						"ajaxurl"      => admin_url( 'admin-ajax.php' ),
						"template"     => array(
							'rule_type'      => 'alimtalk',
							'status'         => '',
							'message_type'   => 'BA',
							'emphasize_type' => 'NONE',
							'buttons'        => array()
						),
						"keyFields"    => array(
							'plus_id' => array(
								'type'  => 'text',
								'label' => __( '플러스친구 아이디', 'mshop-sms-s2' ),
							),
							'name'    => array(
								'type'  => 'text',
								'label' => __( '제목', 'mshop-sms-s2' ),
							),
						),
						"default"      => array(),
						"elements"     => array(
							'left'     => array(
								'type'              => 'Section',
								"hideSectionHeader" => true,
								"class"             => "eight wide column",
								'elements'          => array(
									array(
										"id"        => "code",
										"title"     => __( "템플릿코드", 'mshop-sms-s2' ),
										"className" => "fluid",
										"readOnly"  => "true",
										"type"      => "Label",
									),
									array(
										"id"          => "plus_id",
										"title"       => __( "플러스친구 아이디", 'mshop-sms-s2' ),
										"className"   => "fluid",
										"type"        => "Select",
										"placeholder" => __( "플러스친구 아이디", 'mshop-sms-s2' ),
										"options"     => self::get_plus_ids()
									),
									array(
										"id"        => "name",
										"title"     => __( "제목", 'mshop-sms-s2' ),
										"className" => "fluid",
										"type"      => "Text"
									),
									array(
										"id"        => "content",
										"title"     => __( "내용", 'mshop-sms-s2' ),
										"className" => "fluid",
										"type"      => "TextArea",
										"rows"      => 10
									),
									array(
										"id"          => "message_type",
										"title"       => __( "메시지 유형", 'mshop-sms-s2' ),
										"className"   => "fluid",
										"type"        => "Select",
										"placeholder" => "메시지 유형을 선택하세요",
										"default"     => "BA",
										"options"     => array(
											'BA' => __( '기본형', 'mshop-sms-s2' ),
											'EX' => __( '부가정보형', 'mshop-sms-s2' ),
											'AD' => __( '광고추가형', 'mshop-sms-s2' ),
											'MI' => __( '복합형', 'mshop-sms-s2' ),
										)
									),
									array(
										"id"        => "extra",
										"title"     => __( "부가정보", 'mshop-sms-s2' ),
										"showIf"    => array( 'message_type' => 'EX,MI' ),
										"className" => "fluid",
										"type"      => "TextArea",
										"rows"      => 5,
										"desc2"     => __( "<div class='desc2' style='font-size: 11px;font-style: normal;'>본문 내용이외에 추가로 전달사항을 기재할 수 있습니다.<br>부가정보 영역의 텍스트는 본문의 텍스트 색상과 다르며, 사이트 URL 을 기재할 수 있습니다</div>", "mshop-sms-s2" ),
									),
									array(
										"id"        => "ad",
										"title"     => __( "광고문구", 'mshop-sms-s2' ),
										"showIf"    => array( 'message_type' => 'AD,MI' ),
										"className" => "fluid",
										"type"      => "TextArea",
										"rows"      => 5,
										"desc2"     => __( "<div class='desc2' style='font-size: 11px;font-style: normal;'>본문 내용이외에 추가로 이벤트 등의 정보를 기재할 수 있습니다. 광고문구 영역의 텍스트는 본문의 텍스트 색상과 다르며, 사이트 URL 을 기재할 수 없습니다.</div>", "mshop-sms-s2" ),
									),
									array(
										"id"          => "emphasize_type",
										"title"       => __( "강조표시 타입", 'mshop-sms-s2' ),
										"className"   => "fluid",
										"type"        => "Select",
										"placeholder" => "강조표시 타입을 선택하세요",
										"default"     => "NONE",
										"options"     => array(
											'NONE'  => __( '기본', 'mshop-sms-s2' ),
											'IMAGE' => __( '이미지형', 'mshop-sms-s2' ),
											'TEXT'  => __( '강조표시', 'mshop-sms-s2' ),
										),
										"desc2"       => __( "<div class='desc2' style='font-size: 11px;font-style: normal;'>[1] 이미지형<br>- 이미지 파일을 선택한 후 업로드 버튼을 클릭해주세요.<br>- 이미지 파일의 가로:세로 비율은 2:1 이며, 최소 크기는 가로 500px, 세로 250px 입니다.<br>- 이미지 제작과 관련된 자세한 내용은 카카오의 <a style='color:red;' href='https://kakaobusiness.gitbook.io/main/ad/bizmessage/notice-friend/content-guide/image/' target='_blank'>이미지형 제작 가이드</a>를 참고해주세요.<br>[2] 강조표시형<br>- 메시지 최상단에 강조표시 타이틀 및 보조 문구가 기재됩니다.<br>- 강조표시 타이틀 및 보조 문구 이용 시 본문에 강조표시 타이틀 및 보조 문구와 동일한 문구가 포함되어 있어야 합니다.</div>", "mshop-sms-s2" ),
									),
									array(
										"id"        => "title",
										"title"     => __( "템플릿 제목", 'mshop-sms-s2' ),
										"showIf"    => array( 'emphasize_type' => 'TEXT' ),
										"className" => "fluid",
										"type"      => "Text"
									),
									array(
										"id"        => "subtitle",
										"title"     => __( "템플릿 보조 문구", 'mshop-sms-s2' ),
										"showIf"    => array( 'emphasize_type' => 'TEXT' ),
										"className" => "fluid",
										"type"      => "Text"
									),
									array(
										"id"         => "upload_image",
										"title"      => __( "이미지 업로드", 'mshop-sms-s2' ),
										"label"      => __( "업로드", 'mshop-sms-s2' ),
										"showIf"     => array( array( 'emphasize_type' => 'IMAGE' ), array('status' => ',REJ,APR' ) ),
										"className"  => "fluid",
										"type"       => "Upload",
										"actionType" => "ajax",
										'ajaxurl'    => admin_url( 'admin-ajax.php' ),
										'action'     => MSSMS()->slug() . '-upload_template_image_file',
									),
									array(
										"id"        => "image_name",
										"title"     => __( "이미지명", 'mshop-sms-s2' ),
										"showIf"    => array( 'emphasize_type' => 'IMAGE' ),
										"className" => "fluid disabled",
										"type"      => "Text"
									),
									array(
										"id"        => "image_url",
										"title"     => __( "이미지 URL", 'mshop-sms-s2' ),
										"showIf"    => array( 'emphasize_type' => 'false' ),
										"className" => "fluid disabled",
										"type"      => "Text"
									),

								)
							),
							'alimtalk' => array(
								"id"           => "buttons",
								"type"         => "SortableList",
								"class"        => "eight wide column",
								"title"        => __( "버튼설정", 'mshop-sms-s2' ),
								"listItemType" => "MShopRuleSortableList",
								"repeater"     => true,
								"template"     => array(
									'rule_type' => 'button_config',
								),
								"keyFields"    => array(
									'type' => array(
										'type'   => 'select',
										'label'  => '버튼타입',
										'option' => array(
											'DS' => __( '배송조회', 'mshop-sms-s2' ),
											'WL' => __( '웹링크', 'mshop-sms-s2' ),
											'AL' => __( '앱링크', 'mshop-sms-s2' ),
											'BK' => __( '봇키워드', 'mshop-sms-s2' ),
											'MD' => __( '메세지전달', 'mshop-sms-s2' ),
											'BC' => __( '상담톡 전환', 'mshop-sms-s2' ),
											'BT' => __( '봇 전환', 'mshop-sms-s2' ),
											'AC' => __( '채널추가', 'mshop-sms-s2' ),
										),
									),
									'name' => array(
										'type'  => 'text',
										'label' => __( '버튼명', 'mshop-sms-s2' ),
									),
								),
								"default"      => array(),
								"elements"     => array(
									'type'              => 'Section',
									'class'             => 'eight wide column',
									"hideSectionHeader" => true,
									'elements'          => array(
										array(
											"id"          => "type",
											"title"       => __( "버튼타입", 'mshop-sms-s2' ),
											"className"   => "fluid",
											"type"        => "Select",
											"placeholder" => "버튼타입을 선택하세요",
											"options"     => array(
												'DS' => __( '배송조회', 'mshop-sms-s2' ),
												'WL' => __( '웹링크', 'mshop-sms-s2' ),
												'AL' => __( '앱링크', 'mshop-sms-s2' ),
												'BK' => __( '봇키워드', 'mshop-sms-s2' ),
												'MD' => __( '메세지전달', 'mshop-sms-s2' ),
												'BC' => __( '상담톡 전환', 'mshop-sms-s2' ),
												'BT' => __( '봇 전환', 'mshop-sms-s2' ),
												'AC' => __( '채널추가( 메시지 유형이 광고 추가/복합형인 경우만 사용 가능)', 'mshop-sms-s2' ),
											)
										),
										array(
											"id"        => "name",
											"showIf"    => array( 'type' => 'DS,WL,AL,BK,MD,BC,BT,AC' ),
											"title"     => __( '버튼 이름', 'mshop-sms-s2' ),
											"type"      => "Text",
											"className" => "fluid",
											'default'   => ''
										),
										array(
											"id"          => "linkMo",
											"showIf"      => array( 'type' => 'WL' ),
											"title"       => __( '링크(모바일웹)', 'mshop-sms-s2' ),
											"type"        => "Text",
											"className"   => "fluid",
											'default'     => '',
											"placeholder" => "URL을 입력하세요.",
										),
										array(
											"id"          => "linkPc",
											"showIf"      => array( 'type' => 'WL' ),
											"title"       => __( '링크(PC)', 'mshop-sms-s2' ),
											"type"        => "Text",
											"className"   => "fluid",
											'default'     => '',
											"placeholder" => "URL을 입력하세요.",
										),
										array(
											"id"          => "schemeIos",
											"showIf"      => array( 'type' => 'AL' ),
											"title"       => __( '링크(안드로이드)', 'mshop-sms-s2' ),
											"type"        => "Text",
											"className"   => "fluid",
											'default'     => '',
											"placeholder" => "링크 주소를 입력하세요.",
										),
										array(
											"id"          => "schemeAndroid",
											"showIf"      => array( 'type' => 'AL' ),
											"title"       => __( '링크(아이폰)', 'mshop-sms-s2' ),
											"type"        => "Text",
											"className"   => "fluid",
											'default'     => '',
											"placeholder" => "링크 주소를 입력하세요.",
										)
									)
								)
							),
							'comments' => array(
								"id"        => "comments",
								"className" => "template-comments",
								"repeater"  => true,
								"type"      => "SortableTable",
								"template"  => array(
									"user_roles"   => "",
									"edit_form_id" => ""
								),
								"elements"  => array(
									array(
										"id"            => "status",
										"title"         => __( '검수결과', 'mshop-sms-s2' ),
										"type"          => "Label",
										"className"     => "center aligned two wide column fluid",
										'cellClassName' => " center aligned two wide column fluid",
										'default'       => ''
									),
									array(
										"id"            => "createdAt",
										"title"         => __( '검수일', 'mshop-sms-s2' ),
										"type"          => "Label",
										"className"     => "center aligned three wide column fluid",
										'cellClassName' => " center aligned three wide column fluid",
										'default'       => ''
									),
									array(
										"id"            => "content",
										"title"         => __( '검수내용', 'mshop-sms-s2' ),
										"type"          => "Label",
										"className"     => "center aligned fluid",
										'cellClassName' => "",
										'default'       => ''
									),
								)
							)

						)
					),
					array(
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '템플릿 작성 안내', 'mshop-sms-s2' ),
						'elements'       => array(
							array(
								"id"        => "alimtalk_template_guide",
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

			$settings = self::get_template_setting();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'     => 'mshop-setting-wrapper',
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'action'      => MSSMS()->slug() . '-update_alimtalk_template_settings',
				'settings'    => $settings,
				'slug'        => MSSMS()->slug(),
				'domain'      => preg_replace( '#^https?://#', '', mssms_home_url() ),
				'licenseInfo' => get_option( 'msl_license_' . MSSMS()->slug(), null ),
				'locale'      => get_locale(),
			) );

			?>
            <style>
                table.template-comments {
                    margin-top: 10px !important;
                }
            </style>
            <script>
                jQuery( document ).ready( function () {
                    jQuery( this ).trigger( 'mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( MSSMS_Helper::get_settings( $settings ) ); ?>, null, null] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php
		}
	}

	MSSMS_Settings_Alimtalk_Template::init();

endif;






