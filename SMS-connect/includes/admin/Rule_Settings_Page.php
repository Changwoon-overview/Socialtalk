<?php

namespace SMS_Connect\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 고급 발송 규칙 설정 페이지를 관리하는 클래스
 */
class Rule_Settings_Page
{
    const RULES_OPTION_NAME = 'sms_connect_advanced_rules';
    const PAGE_SLUG = 'sms-connect-rule-settings';

    /**
     * 페이지를 렌더링합니다.
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('고급 발송 규칙', 'sms-connect'); ?></h1>
            <p><?php _e('특정 상품이나 카테고리가 포함된 주문에 대해 별도의 알림 템플릿을 설정합니다.', 'sms-connect'); ?></p>

            <hr class="wp-header-end">

            <h2><?php _e('새 규칙 추가', 'sms-connect'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('sms_connect_save_rule_settings'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="condition_type"><?php _e('조건 유형', 'sms-connect'); ?></label></th>
                            <td>
                                <select name="condition_type" id="condition_type" required>
                                    <option value="product"><?php _e('특정 상품', 'sms-connect'); ?></option>
                                    <option value="category"><?php _e('특정 카테고리', 'sms-connect'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="condition_values"><?php _e('조건 값 (ID)', 'sms-connect'); ?></label></th>
                            <td>
                                <input name="condition_values" type="text" id="condition_values" class="regular-text" placeholder="<?php _e('예: 12, 15, 20', 'sms-connect'); ?>" required>
                                <p class="description"><?php _e('상품 ID 또는 카테고리 ID를 쉼표(,)로 구분하여 입력하세요.', 'sms-connect'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="order_status"><?php _e('주문 상태', 'sms-connect'); ?></label></th>
                            <td>
                                <select name="order_status" id="order_status" required>
                                    <?php foreach (wc_get_order_statuses() as $status => $label) : ?>
                                        <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sms_template_id"><?php _e('SMS 템플릿 ID', 'sms-connect'); ?></label></th>
                            <td>
                                <input name="sms_template_id" type="text" id="sms_template_id" class="regular-text">
                                 <p class="description"><?php _e('이 규칙에 사용할 SMS 템플릿 ID를 입력하세요. 비워두면 발송하지 않습니다.', 'sms-connect'); ?></p>
                            </td>
                        </tr>
                         <tr>
                            <th scope="row"><label for="alimtalk_template_code"><?php _e('알림톡 템플릿 코드', 'sms-connect'); ?></label></th>
                            <td>
                                <input name="alimtalk_template_code" type="text" id="alimtalk_template_code" class="regular-text">
                                <p class="description"><?php _e('이 규칙에 사용할 알림톡 템플릿 코드를 입력하세요. 비워두면 발송하지 않습니다.', 'sms-connect'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(__('새 규칙 저장', 'sms-connect'), 'primary', 'sms_connect_add_rule'); ?>
            </form>

            <hr>

            <h2><?php _e('저장된 규칙 목록', 'sms-connect'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('sms_connect_delete_rule_settings'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 5%;"><?php _e('ID', 'sms-connect'); ?></th>
                            <th><?php _e('조건 유형', 'sms-connect'); ?></th>
                            <th><?php _e('조건 값 (IDs)', 'sms-connect'); ?></th>
                            <th><?php _e('주문 상태', 'sms-connect'); ?></th>
                            <th><?php _e('SMS 템플릿', 'sms-connect'); ?></th>
                            <th><?php _e('알림톡 템플릿', 'sms-connect'); ?></th>
                            <th style="width: 10%;"><?php _e('작업', 'sms-connect'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rules = get_option(self::RULES_OPTION_NAME, []);
                        if (empty($rules)) :
                        ?>
                            <tr>
                                <td colspan="7"><?php _e('저장된 규칙이 없습니다.', 'sms-connect'); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($rules as $rule_id => $rule) : ?>
                                <tr>
                                    <td><?php echo esc_html($rule_id); ?></td>
                                    <td><?php echo $rule['condition_type'] === 'product' ? __('특정 상품', 'sms-connect') : __('특정 카테고리', 'sms-connect'); ?></td>
                                    <td><?php echo esc_html(is_array($rule['condition_values']) ? implode(', ', $rule['condition_values']) : ''); ?></td>
                                    <td><?php echo esc_html(wc_get_order_status_name($rule['order_status'])); ?></td>
                                    <td><?php echo esc_html($rule['sms_template_id']); ?></td>
                                    <td><?php echo esc_html($rule['alimtalk_template_code']); ?></td>
                                    <td>
                                        <button type="submit" name="sms_connect_delete_rule" value="<?php echo esc_attr($rule_id); ?>" class="button button-link-delete" onclick="return confirm('<?php _e('정말 이 규칙을 삭제하시겠습니까?', 'sms-connect'); ?>');">
                                            <?php _e('삭제', 'sms-connect'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     * 규칙 저장 및 삭제 액션을 처리합니다.
     */
    public function handle_actions()
    {
        // 새 규칙 추가 처리
        if (isset($_POST['sms_connect_add_rule']) && check_admin_referer('sms_connect_save_rule_settings')) {
            $condition_type = isset($_POST['condition_type']) ? sanitize_text_field($_POST['condition_type']) : '';
            $condition_values_raw = isset($_POST['condition_values']) ? sanitize_text_field($_POST['condition_values']) : '';
            $order_status = isset($_POST['order_status']) ? sanitize_text_field($_POST['order_status']) : '';
            $sms_template_id = isset($_POST['sms_template_id']) ? sanitize_text_field($_POST['sms_template_id']) : '';
            $alimtalk_template_code = isset($_POST['alimtalk_template_code']) ? sanitize_text_field($_POST['alimtalk_template_code']) : '';

            // 입력값 검증
            if (empty($condition_values_raw) || (empty($sms_template_id) && empty($alimtalk_template_code))) {
                // TODO: 사용자에게 에러 메시지 표시
                return;
            }

            $condition_values = array_map('intval', explode(',', $condition_values_raw));

            $rules = get_option(self::RULES_OPTION_NAME, []);

            // 새 규칙 생성
            $new_rule = [
                'condition_type'       => $condition_type,
                'condition_values'     => $condition_values,
                'order_status'         => $order_status,
                'sms_template_id'      => $sms_template_id,
                'alimtalk_template_code' => $alimtalk_template_code,
            ];

            // 규칙 배열에 추가
            $rules[] = $new_rule;

            update_option(self::RULES_OPTION_NAME, $rules);
        }

        // 규칙 삭제 처리
        if (isset($_POST['sms_connect_delete_rule']) && check_admin_referer('sms_connect_delete_rule_settings')) {
            $rule_id_to_delete = intval($_POST['sms_connect_delete_rule']);
            $rules = get_option(self::RULES_OPTION_NAME, []);

            if (isset($rules[$rule_id_to_delete])) {
                unset($rules[$rule_id_to_delete]);
                update_option(self::RULES_OPTION_NAME, array_values($rules)); // 배열 인덱스 재정렬
            }
        }
    }

    /**
     * 클래스 초기화 시 액션을 등록합니다.
     */
    public static function register()
    {
        $instance = new self();
        // admin_init 훅에 핸들러를 등록하여 form 제출을 처리
        add_action('admin_init', [$instance, 'handle_actions']);
    }
} 