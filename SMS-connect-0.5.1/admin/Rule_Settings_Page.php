<?php

namespace SmsConnect\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 고급 발송 규칙 설정 페이지를 관리하는 클래스
 */
class Rule_Settings_Page extends Base_Settings_Page
{
    /**
     * Option name for the rules.
     * @var string
     */
    protected $option_name = 'sms_connect_advanced_rules';

    /**
     * Option group for the settings page.
     * @var string
     */
    protected $option_group = 'sms_connect_rule_settings';

    /**
     * Page slug.
     * @var string
     */
    const PAGE_SLUG = 'sms-connect-rule-settings';

    /**
     * Constructor.
     */
    public function __construct() {
        \add_action('admin_init', [$this, 'register_settings_sections']);
        \add_action('admin_init', [$this, 'handle_form_actions']);
    }

    /**
     * Register settings sections and fields.
     */
    public function register_settings_sections() {
        \add_settings_section(
            'sms_connect_rules_section',
            \__( '저장된 규칙 목록', 'sms-connect' ),
            [$this, 'render_rules_table'],
            $this->option_group
        );

        \add_settings_section(
            'sms_connect_add_rule_section',
            \__( '새 규칙 추가', 'sms-connect' ),
            null,
            $this->option_group
        );

        \add_settings_field(
            'new_rule',
            '',
            [$this, 'render_new_rule_form'],
            $this->option_group,
            'sms_connect_add_rule_section'
        );
    }

    /**
     * Render the form for adding a new rule.
     */
    public function render_new_rule_form() {
        ?>
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="condition_type"><?php echo \esc_html__( '조건 유형', 'sms-connect' ); ?></label></th>
                <td>
                    <select name="condition_type" id="condition_type" required>
                        <option value="product"><?php echo \esc_html__( '특정 상품', 'sms-connect' ); ?></option>
                        <option value="category"><?php echo \esc_html__( '특정 카테고리', 'sms-connect' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="condition_values"><?php echo \esc_html__( '조건 값 (ID)', 'sms-connect' ); ?></label></th>
                <td>
                    <input name="condition_values" type="text" id="condition_values" class="regular-text" placeholder="<?php echo \esc_attr__( '예: 12, 15, 20', 'sms-connect' ); ?>" required>
                    <p class="description"><?php echo \esc_html__( '상품 ID 또는 카테고리 ID를 쉼표(,)로 구분하여 입력하세요.', 'sms-connect' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="order_status"><?php echo \esc_html__( '주문 상태', 'sms-connect' ); ?></label></th>
                <td>
                    <select name="order_status" id="order_status" required>
                        <?php foreach (\wc_get_order_statuses() as $status => $label) : ?>
                            <option value="<?php echo \esc_attr($status); ?>"><?php echo \esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sms_template_id"><?php echo \esc_html__( 'SMS 내용', 'sms-connect' ); ?></label></th>
                <td>
                    <textarea name="sms_template_id" id="sms_template_id" class="large-text" rows="5"></textarea>
                    <p class="description"><?php echo \esc_html__( '이 규칙에 사용할 SMS 내용을 입력하세요. 비워두면 발송하지 않습니다.', 'sms-connect' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="alimtalk_template_code"><?php echo \esc_html__( '알림톡 템플릿 코드', 'sms-connect' ); ?></label></th>
                <td>
                    <input name="alimtalk_template_code" type="text" id="alimtalk_template_code" class="regular-text">
                    <p class="description"><?php echo \esc_html__( '이 규칙에 사용할 알림톡 템플릿 코드를 입력하세요. 비워두면 발송하지 않습니다.', 'sms-connect' ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render the table of existing rules.
     */
    public function render_rules_table() {
        $rules = \get_option($this->option_name, []);
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th style="width: 5%;"><?php echo \esc_html__( 'ID', 'sms-connect' ); ?></th>
                    <th><?php echo \esc_html__( '조건 유형', 'sms-connect' ); ?></th>
                    <th><?php echo \esc_html__( '조건 값 (IDs)', 'sms-connect' ); ?></th>
                    <th><?php echo \esc_html__( '주문 상태', 'sms-connect' ); ?></th>
                    <th><?php echo \esc_html__( 'SMS 내용', 'sms-connect' ); ?></th>
                    <th><?php echo \esc_html__( '알림톡 템플릿', 'sms-connect' ); ?></th>
                    <th style="width: 10%;"><?php echo \esc_html__( '작업', 'sms-connect' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rules)) : ?>
                    <tr>
                        <td colspan="7"><?php echo \esc_html__( '저장된 규칙이 없습니다.', 'sms-connect' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rules as $rule_id => $rule) : ?>
                        <tr>
                            <td><?php echo \esc_html($rule_id); ?></td>
                            <td><?php echo $rule['condition_type'] === 'product' ? \esc_html__( '특정 상품', 'sms-connect' ) : \esc_html__( '특정 카테고리', 'sms-connect' ); ?></td>
                            <td><?php echo \esc_html(\is_array($rule['condition_values']) ? \implode(', ', $rule['condition_values']) : ''); ?></td>
                            <td><?php echo \esc_html(\wc_get_order_status_name($rule['order_status'])); ?></td>
                            <td><?php echo \esc_html($rule['sms_template_id']); ?></td>
                            <td><?php echo \esc_html($rule['alimtalk_template_code']); ?></td>
                            <td>
                                <button type="submit" name="sms_connect_delete_rule" value="<?php echo \esc_attr($rule_id); ?>" class="button button-link-delete" onclick="return confirm('<?php echo \esc_js(\__( '정말 이 규칙을 삭제하시겠습니까?', 'sms-connect' ) ); ?>');">
                                    <?php echo \esc_html__( '삭제', 'sms-connect' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        <?php
    }


    /**
     * Override the render_page to structure it with a single form and PRG pattern.
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php \esc_html_e( '고급 발송 규칙', 'sms-connect' ); ?></h1>
            
            <?php \settings_errors( 'sms_connect_rules' ); ?>

            <!-- 저장된 규칙 목록 -->
            <?php $this->render_rules_table(); ?>

            <!-- 새 규칙 추가 폼 -->
            <h2><?php \esc_html_e( '새 규칙 추가', 'sms-connect' ); ?></h2>
            <form method="post" action="">
                <?php \wp_nonce_field( 'sms_connect_rule_actions', '_wpnonce' ); ?>
                <?php $this->render_new_rule_form(); ?>
                <?php \submit_button( \__( '새 규칙 추가', 'sms-connect' ), 'primary', 'sms_connect_add_rule' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handles add and delete actions for rules with PRG pattern.
     */
    public function handle_form_actions() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_wpnonce'] ) ), 'sms_connect_rule_actions' ) ) {
            return;
        }

        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }

        // 새 규칙 추가 처리
        if (isset($_POST['sms_connect_add_rule'])) {
            $rules = \get_option($this->option_name, []);

            $new_rule = [
                'condition_type'       => isset($_POST['condition_type']) ? \sanitize_text_field($_POST['condition_type']) : '',
                'condition_values_raw' => isset($_POST['condition_values']) ? \sanitize_text_field($_POST['condition_values']) : '',
                'order_status'         => isset($_POST['order_status']) ? \sanitize_text_field($_POST['order_status']) : '',
                'sms_template_id'      => isset($_POST['sms_template_id']) ? \sanitize_textarea_field($_POST['sms_template_id']) : '',
                'alimtalk_template_code' => isset($_POST['alimtalk_template_code']) ? \sanitize_text_field($_POST['alimtalk_template_code']) : '',
            ];

            if (empty($new_rule['condition_values_raw']) || (empty($new_rule['sms_template_id']) && empty($new_rule['alimtalk_template_code']))) {
                \add_settings_error('sms_connect_rules', 'rule_error', __('조건 값과 SMS 또는 알림톡 템플릿 중 하나는 반드시 입력해야 합니다.', 'sms-connect'));
            } else {
                $new_rule['condition_values'] = \array_map('intval', \explode(',', $new_rule['condition_values_raw']));
                unset($new_rule['condition_values_raw']);
                $rules[] = $new_rule;
                \update_option($this->option_name, $rules);
                \add_settings_error('sms_connect_rules', 'rule_saved', __('새 규칙이 저장되었습니다.', 'sms-connect'), 'updated');
            }

            \wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
            exit;
        }

        // 규칙 삭제 처리
        if (isset($_POST['sms_connect_delete_rule'])) {
            $rule_id_to_delete = \intval($_POST['sms_connect_delete_rule']);
            $rules             = \get_option($this->option_name, []);

            if (isset($rules[$rule_id_to_delete])) {
                unset($rules[$rule_id_to_delete]);
                \update_option($this->option_name, \array_values($rules)); // 배열 인덱스 재정렬
                \add_settings_error('sms_connect_rules', 'rule_deleted', __('규칙이 삭제되었습니다.', 'sms-connect'), 'updated');
            }

            \wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
            exit;
        }
    }
} 