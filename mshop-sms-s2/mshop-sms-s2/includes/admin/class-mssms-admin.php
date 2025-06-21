<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'MSSMS_Admin' ) ) :

	class MSSMS_Admin {
		protected $retry_result = array();

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( 'MSSMS_Admin', 'admin_enqueue_scripts' ) );
		}

		function admin_menu() {
			add_menu_page( __( '엠샵 문자 알림톡', 'mshop-sms-s2' ), __( '엠샵 문자 알림톡', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings', '', MSSMS()->plugin_url() . '/assets/images/mshop-icon.png', '20.9387640384' );

			add_submenu_page( 'mssms_settings', __( '문자 기본 설정', 'mshop-sms-s2' ), __( '문자 기본 설정', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings', array( 'MSSMS_Settings_Sms', 'output' ) );
			if ( MSSMS_Manager::is_enabled( 'sms' ) ) {
				if( mssms_woocommerce_activated() ) {
					add_submenu_page( 'mssms_settings', __( '문자 자동 발송', 'mshop-sms-s2' ), __( '문자 자동 발송', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_sms_send', array( 'MSSMS_Settings_SMS_Send', 'output' ) );
				}
				add_submenu_page( 'mssms_settings', __( '문자 개별 발송', 'mshop-sms-s2' ), __( '문자 개별 발송', 'mshop-sms-s2' ), 'manage_options', 'mssms_send_single_message', array( 'MSSMS_Settings_Send', 'output' ) );
			}

			add_submenu_page( 'mssms_settings', __( '알림톡 기본 설정', 'mshop-sms-s2' ), __( '알림톡 기본 설정', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_plusfriend', array( 'MSSMS_Settings_Alimtalk', 'output' ) );
			if ( MSSMS_Manager::is_enabled( 'alimtalk' ) ) {
				add_submenu_page( 'mssms_settings', __( '알림톡 템플릿 설정', 'mshop-sms-s2' ), __( '알림톡 템플릿 설정', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_alimtalk_template', array( 'MSSMS_Settings_Alimtalk_Template', 'output' ) );
				if( mssms_woocommerce_activated() ) {
					add_submenu_page( 'mssms_settings', __( '알림톡 자동 발송', 'mshop-sms-s2' ), __( '알림톡 자동 발송', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_alimtalk_send', array( 'MSSMS_Settings_Alimtalk_Send', 'output' ) );
				}
				add_submenu_page( 'mssms_settings', __( '알림톡 개별 발송', 'mshop-sms-s2' ), __( '알림톡 개별 발송', 'mshop-sms-s2' ), 'manage_options', 'mssms_send_alimtalk_message', array( 'MSSMS_Settings_Alimtalk_Manual', 'output' ) );
			}

			if ( MSSMS_Manager::is_enabled( 'sms' ) || MSSMS_Manager::is_enabled( 'alimtalk' ) ) {
				add_submenu_page( 'mssms_settings', __( '알림 발송 로그', 'mshop-sms-s2' ), __( '알림 발송 로그', 'mshop-sms-s2' ), 'manage_options', 'mssms_logs', array( 'MSSMS_Settings_Logs', 'output' ) );
				if ( mssms_woocommerce_activated() && mssms_woocommerce_subscription_is_active() ) {
					add_submenu_page( 'mssms_settings', __( '정기결제권 발송 설정', 'mshop-sms-s2' ), __( '정기결제권 발송 설정', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_subscription', array ( 'MSSMS_Settings_Subscription', 'output' ) );
				}
				add_submenu_page( 'mssms_settings', __( '사용자 발송 설정', 'mshop-sms-s2' ), __( '사용자 발송 설정', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_user', array ( 'MSSMS_Settings_User', 'output' ) );
				add_submenu_page( 'mssms_settings', __( '기타 발송 설정', 'mshop-sms-s2' ), __( '기타 발송 설정', 'mshop-sms-s2' ), 'manage_options', 'mssms_settings_etc', array ( 'MSSMS_Settings_Etc', 'output' ) );
			}
            add_submenu_page( 'mssms_settings', __( '매뉴얼', 'mshop-sms-s2' ), __( '매뉴얼', 'mshop-sms-s2' ), 'manage_options', 'mshop_sms_manual','' );
		}
		
        static function admin_enqueue_scripts() {
            wp_enqueue_script( 'mssms-admin-menu', MSSMS()->plugin_url() . '/assets/js/admin/admin-menu.js', array( 'jquery' ), MSSMS_VERSION );
            wp_localize_script( 'mssms-admin-menu', '_mssms_admin_menu', array(
                'manual_url' => 'https://manual.codemshop.com/docs/mshop-sms/'
            ) );
        }
	}

	new MSSMS_Admin();

endif;
