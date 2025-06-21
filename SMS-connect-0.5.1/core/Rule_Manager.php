<?php

namespace SMS_Connect\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 고급 발송 규칙을 관리하고 조회하는 클래스
 */
class Rule_Manager
{
    const RULES_OPTION_NAME = 'sms_connect_advanced_rules';

    /**
     * 저장된 모든 규칙을 가져옵니다.
     * @return array
     */
    public static function get_rules()
    {
        return get_option(self::RULES_OPTION_NAME, []);
    }

    /**
     * 주문 객체와 주문 상태를 받아 매칭되는 규칙을 찾습니다.
     *
     * @param \WC_Order $order The order object.
     * @param string    $new_status The new status of the order (without 'wc-' prefix).
     * @return array|null 매칭되는 규칙 또는 null
     */
    public static function find_rule_for_order($order, $new_status)
    {
        $rules = self::get_rules();
        if (empty($rules) || !$order) {
            return null;
        }

        foreach ($rules as $rule) {
            // 1. 주문 상태가 일치하는지 확인
            // $rule['order_status']는 'wc-' 접두사를 포함하여 저장됩니다 (예: 'wc-completed')
            // $new_status는 접두사가 없습니다 (예: 'completed')
            if ($rule['order_status'] !== 'wc-' . $new_status) {
                continue;
            }
            
            // 2. 조건 유형에 따라 분기
            if ($rule['condition_type'] === 'product') {
                foreach ($order->get_items() as $item) {
                    // 기본 상품 및 옵션 상품 ID 확인
                    if (in_array($item->get_product_id(), $rule['condition_values'])) {
                        return $rule; // 규칙 찾음
                    }
                     if ($item->get_variation_id() && in_array($item->get_variation_id(), $rule['condition_values'])) {
                        return $rule; // 옵션 상품에서 규칙 찾음
                    }
                }
            } elseif ($rule['condition_type'] === 'category') {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $term_ids = wc_get_product_term_ids($product_id, 'product_cat');
                    
                    // array_intersect는 두 배열의 공통된 값으로 새 배열을 만듭니다.
                    if (!empty(array_intersect($term_ids, $rule['condition_values']))) {
                        return $rule; // 규칙 찾음
                    }
                }
            }
        }

        return null; // 매칭되는 규칙 없음
    }
} 