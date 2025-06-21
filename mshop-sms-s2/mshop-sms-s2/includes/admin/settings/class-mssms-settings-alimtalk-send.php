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

if ( ! class_exists( 'MSSMS_Settings_Alimtalk_Send' ) ) :

	class MSSMS_Settings_Alimtalk_Send {


		static function init() {
			add_filter( 'msshelper_get_mssms_alimtalk_admin_options', array ( __CLASS__, 'get_send_options' ) );
			add_filter( 'msshelper_get_mssms_alimtalk_admin_renewal_options', array ( __CLASS__, 'get_send_options' ) );
			add_filter( 'msshelper_get_mssms_alimtalk_user_options', array ( __CLASS__, 'get_send_options' ) );
			add_filter( 'msshelper_get_mssms_alimtalk_user_renewal_options', array ( __CLASS__, 'get_send_options' ) );
			add_filter( 'msshelper_get_mssms_template_list', array ( __CLASS__, 'get_template_list' ) );
		}
		static function get_send_options() {
			$current_filter  = current_filter();
			$option_name     = str_replace( 'msshelper_get_', '', $current_filter );
			$is_subscription = false !== strpos( $option_name, '_renewal' );
			$target          = preg_replace( '/^mssms_alimtalk_|_options$/', '', $option_name );
			$order_statuses  = MSSMS_Manager::get_order_statuses( $is_subscription );

			$options = get_option( $option_name, array () );
			if ( false !== strpos( $option_name, '_renewal' ) && empty( $options ) ) {
				$options = get_option( str_replace( '_renewal', '', $option_name ), array () );
			}
			if ( empty( $options ) ) {
				foreach ( $order_statuses as $key => $value ) {
					$options[] = array (
						'order_status'      => $key,
						'order_status_name' => $value,
						'template_code'     => ''
					);
				}
			}
			$options = array_combine( array_column( $options, 'order_status' ), $options );

			$options = apply_filters( 'mssms_alimtalk_send_options', $options, $option_name );
			foreach ( $order_statuses as $key => $label ) {
				if ( isset( $options[ $key ] ) ) {
					$options[ $key ]['order_status_name'] = $label;
				} else {
					$options[ $key ] = array (
						'order_status'      => $key,
						'order_status_name' => $label,
						'template_code'     => ''
					);
				}
			}
			$options = array_intersect_key( $options, $order_statuses );

			return array_values( $options );
		}

		static function get_templates() {
			$options   = array ();
			$templates = get_option( 'mssms_template_lists', array () );

			if ( ! empty( $templates ) ) {
				foreach ( $templates as $template ) {
					if ( 'APR' == $template['status'] && ( 'HIDE' != mssms_get( $template, 'visibility', 'SHOW' ) ) ) {
						$options[ $template['code'] ] = $template['name'] . ' (' . $template['plus_id']  . ')';
					}
				}
			}

			return $options;
		}

		static function update_settings() {
			include_once MSSMS()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';

			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			MSSMS_Helper::update_settings( self::get_setting_fields() );

			wp_send_json_success();
		}

		static function get_setting_fields() {
			$tabs = array (
				self::get_admin_setting(),
				self::get_user_setting(),
				self::get_product_settings(),
				self::get_category_settings()
			);

			if ( mssms_woocommerce_subscription_is_active() ) {
				$tabs = array_merge( $tabs, array (
					self::get_admin_renewal_setting(),
					self::get_user_renewal_setting(),
				) );
			}

			return array (
				'type'     => 'Tab',
				'id'       => 'setting-tab',
				'elements' => $tabs
			);
		}

		static function get_setting_element() {
			return array (
				array (
					"id"        => "enable",
					"title"     => "활성화",
					"className" => "center aligned column fluid",
					"type"      => "Toggle",
					"default"   => "yes"
				),
				array (
					"id"            => "order_status_name",
					"title"         => __( "상태", 'mshop-sms-s2' ),
					"className"     => "center aligned four wide column fluid",
					"cellClassName" => "center aligned",
					"type"          => "Label",
				),
				array (
					"id"            => "template_code",
					"title"         => __( "템플릿", 'mshop-sms-s2' ),
					"showIf"        => array ( 'enable' => 'yes' ),
					"className"     => "center aligned six wide column fluid",
					"cellClassName" => "fluid",
					"type"          => "Select",
					"placeholder"   => "알림톡 템플릿을 선택하세요.",
					"options"       => self::get_templates()
				),
				array (
					"id"            => "resend_method",
					"title"         => __( "문자 대체 발송", 'mshop-sms-s2' ),
					"showIf"        => array ( 'enable' => 'yes' ),
					"className"     => "center aligned five wide column fluid",
					"cellClassName" => "fluid",
					"type"          => "Select",
					"default"       => "none",
					"options"       => array (
						'none'     => __( "사용안함", 'mshop-sms-s2' ),
						'alimtalk' => __( "알림톡 내용전달", 'mshop-sms-s2' ),
					)
				)
			);
		}

		static function get_admin_setting() {
			ob_start();
			include( 'html/alimtalk-message-guide-for-admin.php' );
			$guide = ob_get_clean();

			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '관리자 발송 설정', 'mshop-sms-s2' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '알림톡 발송 설정 (일반주문 또는 정기결제 신규주문)', 'mshop-sms-s2' ),
						'elements' => array (
							array (
								"id"        => "mssms_alimtalk_admin_options",
								"className" => "alimtalk_admin_option_table",
								"type"      => "SortableTable",
								"sortable"  => "true",
								"repeater"  => true,
								"default"   => array (),
								"template"  => array (
									"enable" => "yes"
								),
								"elements"  => self::get_setting_element()
							)
						)
					),
					array (
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array (
							array (
								"id"        => "mssms_alimtalk_admin_options_desc",
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

		static function get_admin_renewal_setting() {
			ob_start();
			include( 'html/alimtalk-message-guide-for-admin.php' );
			$guide = ob_get_clean();

			return array (
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '관리자 발송 설정 (갱신결제)', 'mshop-sms-s2' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '알림톡 발송 설정 (정기결제 갱신주문)', 'mshop-sms-s2' ),
						'elements' => array (
							array (
								"id"        => "mssms_alimtalk_admin_renewal_options",
								"className" => "alimtalk_admin_option_table",
								"type"      => "SortableTable",
								"sortable"  => "true",
								"repeater"  => true,
								"default"   => array (),
								"template"  => array (
									"enable" => "yes"
								),
								"elements"  => self::get_setting_element()
							)
						)
					),
					array (
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array (
							array (
								"id"        => "mssms_alimtalk_admin_options_desc",
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

		static function get_user_setting() {
			ob_start();
			include( 'html/alimtalk-message-guide-for-user.php' );
			$guide = ob_get_clean();

			return array (
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '고객 발송 설정', 'mshop-sms-s2' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '알림톡 발송 설정 (일반주문 또는 정기결제 신규주문)', 'mshop-sms-s2' ),
						'elements' => array (
							array (
								"id"        => "mssms_alimtalk_user_options",
								"className" => "alimtalk_admin_option_table",
								"type"      => "SortableTable",
								"sortable"  => "true",
								"repeater"  => true,
								"default"   => array (),
								"template"  => array (
									"enable" => "yes"
								),
								"elements"  => self::get_setting_element()
							)
						)
					),
					array (
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array (
							array (
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

		static function get_user_renewal_setting() {
			ob_start();
			include( 'html/alimtalk-message-guide-for-user.php' );
			$guide = ob_get_clean();

			return array (
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '고객 발송 설정 (갱신결제)', 'mshop-sms-s2' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '알림톡 발송 설정 (정기결제 갱신주문)', 'mshop-sms-s2' ),
						'elements' => array (
							array (
								"id"        => "mssms_alimtalk_user_renewal_options",
								"className" => "alimtalk_admin_option_table",
								"type"      => "SortableTable",
								"sortable"  => "true",
								"repeater"  => true,
								"default"   => array (),
								"template"  => array (
									"enable" => "yes"
								),
								"elements"  => self::get_setting_element()
							)
						)
					),
					array (
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array (
							array (
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

		static function get_product_settings() {
			ob_start();
			include( 'html/sms-message-guide-for-product.php' );
			$guide = ob_get_clean();

			return array (
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '상품별 발송 설정', 'mshop-sms-s2' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '고객 자동 발송 설정', 'mshop-sms-s2' ),
						'elements' => array (
							array (
								"id"        => "mssms_alimtalk_product_options",
								"className" => "sms_admin_option_table",
								"type"      => "SortableTable",
								"repeater"  => true,
								"editable"  => true,
								"sortable"  => true,
								"default"   => array (),
								"template"  => array (
									"enable" => "yes",
									"method" => "replace",
								),
								"elements"  => array (
									array (
										"id"        => "enable",
										"title"     => "활성화",
										"className" => "center aligned one wide column fluid",
										"type"      => "Toggle",
										"default"   => "yes"
									),
									array (
										"id"           => "products",
										"title"        => __( "상품", "mshop-sms-s2" ),
										"className"    => " four wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => 'action=' . MSSMS()->slug() . '-target_search&type=product',
										"placeholder"  => __( "상품을 선택하세요.", "mshop-sms-s2" )
									),
									array (
										"id"          => "order_status",
										"title"       => __( "주문상태", "mshop-sms-s2" ),
										"className"   => " three wide column search fluid",
										"type"        => "Select",
										"default"     => "",
										"options"     => mssms_get_order_statuses(),
										"placeholder" => __( "주문상태", "mshop-sms-s2" )
									),
									array (
										"id"            => "template_code",
										"title"         => __( "템플릿", 'mshop-sms-s2' ),
										"showIf"        => array ( 'enable' => 'yes' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "four wide column fluid",
										"type"          => "Select",
										"placeholder"   => "알림톡 템플릿을 선택하세요.",
										"options"       => self::get_templates()
									),
									array (
										"id"            => "resend_method",
										"title"         => __( "문자 대체 발송", 'mshop-sms-s2' ),
										"showIf"        => array ( 'enable' => 'yes' ),
										"className"     => "center aligned two wide column fluid",
										"cellClassName" => "two wide column fluid",
										"type"          => "Select",
										"default"       => "none",
										"options"       => array (
											'none'     => __( "사용안함", 'mshop-sms-s2' ),
											'alimtalk' => __( "알림톡 전달", 'mshop-sms-s2' ),
										)
									),
									array (
										"id"            => "method",
										"title"         => __( "발송방법", "mshop-sms-s2" ),
										"className"     => "center aligned two wide column fluid",
										"cellClassName" => "two wide column fluid",
										"type"          => "Select",
										'options'       => array (
											'replace'    => __( '교체발송', 'mshop-sms-s2' ),
											'additional' => __( '추가발송', 'mshop-sms-s2' ),
										)
									)
								)
							)
						)
					),
					array (
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array (
							array (
								"id"        => "mssms_sms_product_options_desc",
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

		static function get_category_settings() {
			ob_start();
			include( 'html/sms-message-guide-for-product.php' );
			$guide = ob_get_clean();

			return array (
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '카테고리별 발송 설정', 'mshop-sms-s2' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '고객 자동 발송 설정', 'mshop-sms-s2' ),
						'elements' => array (
							array (
								"id"        => "mssms_alimtalk_category_options",
								"className" => "sms_admin_option_table",
								"type"      => "SortableTable",
								"repeater"  => true,
								"editable"  => true,
								"sortable"  => true,
								"default"   => array (),
								"template"  => array (
									"enable" => "yes",
									"method" => "replace",
								),
								"elements"  => array (
									array (
										"id"        => "enable",
										"title"     => "활성화",
										"className" => "center aligned one wide column fluid",
										"type"      => "Toggle",
										"default"   => "yes"
									),
									array (
										"id"           => "categories",
										"title"        => __( "카테고리", "mshop-sms-s2" ),
										"className"    => " four wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => 'action=' . MSSMS()->slug() . '-target_search&type=category',
										"placeholder"  => __( "카테고리를 선택하세요.", "mshop-sms-s2" )
									),
									array (
										"id"          => "order_status",
										"title"       => __( "주문상태", "mshop-sms-s2" ),
										"className"   => " three wide column search fluid",
										"type"        => "Select",
										"default"     => "",
										"options"     => mssms_get_order_statuses(),
										"placeholder" => __( "주문상태", "mshop-sms-s2" )
									),
									array (
										"id"            => "template_code",
										"title"         => __( "템플릿", 'mshop-sms-s2' ),
										"showIf"        => array ( 'enable' => 'yes' ),
										"className"     => "center aligned three wide column fluid",
										"cellClassName" => "four wide column fluid",
										"type"          => "Select",
										"placeholder"   => "알림톡 템플릿을 선택하세요.",
										"options"       => self::get_templates()
									),
									array (
										"id"            => "resend_method",
										"title"         => __( "문자 대체 발송", 'mshop-sms-s2' ),
										"showIf"        => array ( 'enable' => 'yes' ),
										"className"     => "center aligned two wide column fluid",
										"cellClassName" => "two wide column fluid",
										"type"          => "Select",
										"default"       => "none",
										"options"       => array (
											'none'     => __( "사용안함", 'mshop-sms-s2' ),
											'alimtalk' => __( "알림톡 전달", 'mshop-sms-s2' ),
										)
									),
									array (
										"id"        => "method",
										"title"     => __( "발송방법", "mshop-sms-s2" ),
										"className" => " two wide column search fluid",
										"type"      => "Select",
										'options'   => array (
											'replace'    => __( '교체발송', 'mshop-sms-s2' ),
											'additional' => __( '추가발송', 'mshop-sms-s2' ),
										)
									)
								)
							)
						)
					),
					array (
						'type'           => 'Section',
						'hideSaveButton' => true,
						'title'          => __( '발송 설정 안내', 'mshop-sms-s2' ),
						'elements'       => array (
							array (
								"id"        => "mssms_sms_category_options_desc",
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
			wp_enqueue_script( 'mshop-setting-manager', MSSMS()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array (
				'jquery',
				'jquery-ui-core'
			) );
		}
		public static function output() {

			require_once MSSMS()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';

			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'     => 'mshop-setting-wrapper',
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'action'      => MSSMS()->slug() . '-update_alimtalk_send_settings',
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
                .ui.table.alimtalk_admin_option_table td {
                    border-top: 1px solid rgba(34, 36, 38, 0.1) !important;
                }
            </style>
            <div id="mshop-setting-wrapper"></div>
			<?php
		}
	}

	MSSMS_Settings_Alimtalk_Send::init();
endif;

