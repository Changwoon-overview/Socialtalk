<?php
/**
 * Plugin Name:       엠샵 문자 알림톡 자동 발송
 * Plugin URI:        
 * Description:       주문 상태에 따른 문자, 알림톡 자동 발송 및 상품, 카테고리별, 개별, 예약, 회원 등급별 발송 등 쇼핑몰에서 필요한 메시지를 발송 합니다.
 * Version:           2.6.2
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            CodeMShop
 * Author URI:        https://www.codemshop.com/
 * License:           Commercial License
 * Text Domain:       mshop-sms-s2
 * Domain Path:       /languages
 */

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
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'MSHOP_SMS_CLIENT' ) ) {

	class MSHOP_SMS_CLIENT {
		protected static $_instance = null;
		protected $logger = null;
		protected $plugin_file = 'mshop-sms-s2/mshop-sms-s2.php';
		protected $slug;
		public $version = '2.0.0';
		public $plugin_url;
		public $plugin_path;
		public $template_url;
		public $license_manager;
		public function __construct() {
			$this->slug = 'mshop-sms-s2';

			define( 'MSSMS_VERSION', $this->version );
			define( 'MSSMS_PLUGIN_FILE', __FILE__ );


			require_once( 'includes/class-mssms-autoloader.php' );
			require_once( 'includes/class-mssms-manager.php' );
			require_once( 'includes/class-mssms-emails.php' );
			require_once( 'includes/class-mssms-pending-payment.php' );
			require_once( 'includes/class-mssms-on-hold-notification.php' );
			require_once( 'includes/mssms-functions.php' );

			$this->init_update();

			add_action( 'plugins_loaded', array ( $this, 'load_plugin_textdomain' ) );

			// Hooks
			add_action( 'init', array ( $this, 'init' ), 0 );
			add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );

			add_filter( 'plugin_row_meta', array ( $this, 'plugin_row_meta' ), 10, 4 );
			add_filter( 'plugin_action_links', array ( $this, 'plugin_action_links' ), 10, 4 );

		}
		public function plugin_url() {
			if ( $this->plugin_url ) {
				return $this->plugin_url;
			}

			return $this->plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
		}
		public function plugin_path() {
			if ( $this->plugin_path ) {
				return $this->plugin_path;
			}

			return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
		}
		public function template_path() {
			return $this->plugin_path() . '/templates/';
		}
		function init_update() {
			require 'includes/admin/update/LicenseManager.php';
			$this->license_manager = new MSHOP_SMS_LicenseManager( $this->slug, __DIR__, __FILE__ );
		}

		function slug() {
			return $this->slug;
		}

		public function admin_includes() {
			require_once( 'includes/admin/class-mssms-admin.php' );
		}

		public function ajax_includes() {
			require_once( 'includes/class-mssms-ajax.php' );
		}

		public function init() {
			if ( mssms_woocommerce_subscription_is_active() ) {
				require_once( 'includes/class-mssms-subscription.php' );
			}

			if ( is_admin() ) {
				$this->admin_includes();
			}

			if ( defined( 'DOING_AJAX' ) ) {
				$this->ajax_includes();
			}
		}
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'mshop-sms-s2', false, dirname( plugin_basename( __FILE__ ) ) . "/languages/" );
		}
        public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
            if ( $this->plugin_file == $plugin_file ) {
                $actions['settings'] = '<a href="' . admin_url( '/admin.php?page=mssms_settings' ) . '">설정</a>';
                $actions['manual']   = '<a target="_blank" href="https://manual.codemshop.com/docs/mshop-sms/">매뉴얼</a>';
            }

            return $actions;
        }
        public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
            if ( $this->plugin_file == $plugin_file ) {
                $plugin_meta[] = '<a target="_blank" href="https://www.codemshop.com/product-category/outside/">함께 사용하면 유용한 플러그인</a>';
                $plugin_meta[] = '<a target="_blank" href="https://manual.codemshop.com/docs/mshop-sms/faq/">FAQ</a>';
                $plugin_meta[] = '<a target="_blank" href="https://www.codemshop.com/support/">기술지원</a>';
            }

            return $plugin_meta;
        }
		public function declare_woocommerce_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

        public function get_logger() {
        	if( mssms_woocommerce_activated() && is_null( $this->logger ) ) {
		        if ( function_exists( 'wc_get_logger' ) ) {
			        $this->logger = wc_get_logger();
		        } else {
			        $this->logger= new WC_Logger();
		        }
	        }

        	return $this->logger;
        }

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

	}

	function MSSMS() {
		return MSHOP_SMS_CLIENT::instance();
	}

	return MSSMS();
}
