<?php
/**
 * Rule Manager
 *
 * @package SmsConnect\Core
 */

namespace SmsConnect\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rule_Manager
 *
 * Handles the logic for advanced sending rules.
 */
class Rule_Manager {

    const RULES_OPTION_NAME = 'sms_connect_advanced_rules';

    /**
     * Finds a matching rule for a given order and new status.
     *
     * @param \WC_Order $order      The WooCommerce order object.
     * @param string    $new_status The new status of the order.
     * @return array|null The matching rule, or null if none is found.
     */
    public static function find_rule_for_order( $order, $new_status ) {
        $rules = \get_option( self::RULES_OPTION_NAME, [] );

        if ( empty( $rules ) || ! is_array( $rules ) ) {
            return null;
        }

        $order_product_ids = [];
        $order_category_ids = [];

        foreach ( $order->get_items() as $item ) {
            $order_product_ids[] = $item->get_product_id();
            $terms = \get_the_terms( $item->get_product_id(), 'product_cat' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $order_category_ids = \array_merge( $order_category_ids, \wp_list_pluck( $terms, 'term_id' ) );
            }
        }
        $order_category_ids = \array_unique( $order_category_ids );

        foreach ( $rules as $rule ) {
            // Check if the order status matches
            if ( $rule['order_status'] !== $new_status ) {
                continue;
            }

            $conditions_met = false;
            $rule_values = (array) $rule['condition_values'];

            if ( 'product' === $rule['condition_type'] ) {
                // Check if any of the rule's product IDs are in the order
                if ( ! empty( \array_intersect( $rule_values, $order_product_ids ) ) ) {
                    $conditions_met = true;
                }
            } elseif ( 'category' === $rule['condition_type'] ) {
                // Check if any of the rule's category IDs are in the order
                if ( ! empty( \array_intersect( $rule_values, $order_category_ids ) ) ) {
                    $conditions_met = true;
                }
            }
            
            if ( $conditions_met ) {
                return $rule; // Return the first matching rule
            }
        }

        return null; // No matching rule found
    }
} 