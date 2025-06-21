<?php
/**
 * Plugin Name:       SMS Connect
 * Plugin URI:        https://example.com/
 * Description:       WooCommerce SMS 자동 발송 플러그인 (MVP 버전)
 * Version:           0.6.0
 * Author:            SMS Connect Team
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sms-connect
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 플러그인 상수 정의
define('SMS_CONNECT_VERSION', '0.6.0');
define('SMS_CONNECT_PLUGIN_FILE', __FILE__);
define('SMS_CONNECT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMS_CONNECT_PLUGIN_URL', plugin_dir_url(__FILE__));

// 오토로더 로드
require_once SMS_CONNECT_PLUGIN_DIR . 'includes/class-autoloader.php';

// 플러그인 활성화 훅
register_activation_hook(__FILE__, 'sms_connect_activate');

function sms_connect_activate() {
    // 데이터베이스 테이블 생성
    if (class_exists('SmsConnect\Core\Database')) {
        SmsConnect\Core\Database::create_tables();
    }
}

// 플러그인 초기화
add_action('plugins_loaded', 'sms_connect_init');

function sms_connect_init() {
    // WooCommerce 의존성 체크
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'sms_connect_woocommerce_missing_notice');
        return;
    }
    
    // 메인 플러그인 클래스 실행
    SmsConnect\Plugin::instance();
}

function sms_connect_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>SMS Connect:</strong> 이 플러그인은 WooCommerce가 필요합니다. WooCommerce를 먼저 설치하고 활성화해주세요.</p>
    </div>
    <?php
} 