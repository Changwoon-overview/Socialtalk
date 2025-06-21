<?php
/**
 * Template Variables System
 *
 * @package SmsConnect\Core
 */

namespace SmsConnect\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Template_Variables
 *
 * Handles replacing variables in a string with dynamic data.
 */
class Template_Variables {
	/**
	 * The primary object to source data from (WC_Order, WP_User, etc.).
	 *
	 * @var object|null
	 */
	private $source_object;

	/**
	 * Additional data for replacement.
	 *
	 * @var array
	 */
	private $extra_data = [];

	/**
	 * Replace variables in a string with dynamic data.
	 *
	 * @param string $string        The string containing variables.
	 * @param object $source_object The source object (e.g., WC_Order, WP_User).
	 * @param array  $extra_data    Optional. Extra data for variables.
	 * @return string The string with variables replaced.
	 */
	public function replace_variables( $string, $source_object, $extra_data = [] ) {
		$this->source_object = $source_object;
		$this->extra_data = $extra_data;

		if ( ! is_object( $this->source_object ) ) {
			return $string;
		}

		// Regex to find all {variable_name} occurrences.
		return preg_replace_callback(
			'/\{(.+?)\}/',
			[ $this, 'replace_variable_callback' ],
			$string
		);
	}

	/**
	 * Callback function for preg_replace_callback.
	 *
	 * @param array $matches The matches from the regex.
	 * @return string The replacement value.
	 */
	private function replace_variable_callback( $matches ) {
		$variable = $matches[1];
		$value    = $matches[0]; // Default to the original placeholder {variable}

		// Process based on the type of the source object
		if ( $this->source_object instanceof \WC_Order ) {
			$value = $this->get_order_variable( $variable );
		} elseif ( $this->source_object instanceof \WC_Subscription ) {
			$value = $this->get_subscription_variable( $variable );
        } elseif ( $this->source_object instanceof \WP_User ) {
            $value = $this->get_user_variable( $variable );
        }

		// Check extra data
		if ( isset( $this->extra_data[ $variable ] ) ) {
			$value = $this->extra_data[ $variable ];
		}
		
		// Allow other developers to add their own custom variables.
		return apply_filters( 'sms_connect_replace_variable', $value, $variable, $this->source_object, $this->extra_data );
	}

	/**
	 * Get variable value from a WC_Order object.
	 *
	 * @param string $variable The variable name.
	 * @return string The value.
	 */
	private function get_order_variable( $variable ) {
		$order = $this->source_object;
		$variable_map = [
			'order_id'         => $order->get_id(),
			'order_number'     => $order->get_order_number(),
			'order_date'       => wc_format_datetime( $order->get_date_created() ),
			'order_total'      => $order->get_formatted_order_total(),
			'customer_name'    => $order->get_billing_first_name(),
			'customer_fullname'=> $order->get_formatted_billing_full_name(),
			'billing_phone'    => $order->get_billing_phone(),
			'shop_name'        => get_bloginfo( 'name' ),
		];

		return isset( $variable_map[ $variable ] ) ? $variable_map[ $variable ] : '{' . $variable . '}';
	}

    /**
	 * Get variable value from a WC_Subscription object.
	 *
	 * @param string $variable The variable name.
	 * @return string The value.
	 */
    private function get_subscription_variable( $variable ) {
        $subscription = $this->source_object;
        $order = $subscription->get_parent(); // Get parent order for some details

		$variable_map = [
            'subscription_id'           => $subscription->get_id(),
            'subscription_status'       => wcs_get_subscription_status_name($subscription->get_status()),
            'subscription_start_date'   => wc_format_datetime( $subscription->get_date_created() ),
            'subscription_next_payment' => wc_format_datetime( $subscription->get_date('next_payment') ),
            'customer_name'             => $subscription->get_billing_first_name(),
            'customer_fullname'         => $subscription->get_formatted_billing_full_name(),
			'billing_phone'             => $subscription->get_billing_phone(),
			'shop_name'                 => get_bloginfo( 'name' ),
		];
        
        // Fallback to order variables if not found in subscription
        $value = isset( $variable_map[ $variable ] ) ? $variable_map[ $variable ] : null;
        if ($value === null && $order) {
            $this->source_object = $order; // Temporarily switch source to parent order
            $value = $this->get_order_variable($variable);
            $this->source_object = $subscription; // Switch back
        }

		return $value !== null ? $value : '{' . $variable . '}';
    }

    /**
	 * Get variable value from a WP_User object.
	 *
	 * @param string $variable The variable name.
	 * @return string The value.
	 */
    private function get_user_variable( $variable ) {
        $user = $this->source_object;
		$variable_map = [
			'user_id'           => $user->ID,
			'user_login'        => $user->user_login,
			'user_email'        => $user->user_email,
            'user_display_name' => $user->display_name,
			'billing_phone'     => get_user_meta($user->ID, 'billing_phone', true),
			'shop_name'         => get_bloginfo( 'name' ),
		];

		return isset( $variable_map[ $variable ] ) ? $variable_map[ $variable ] : '{' . $variable . '}';
    }

	/**
	 * Extracts variables from content and returns them as a key-value array for API usage.
	 *
	 * This is for Alimtalk APIs that require variables in a structured format.
	 *
	 * @param string $content       The content with placeholders.
	 * @param object $source_object The source object (e.g., WC_Order, WP_User).
	 * @param array  $extra_data    Optional. Extra data for variables.
	 * @return array The key-value array of variables.
	 */
	public function extract_variables_for_api( $content, $source_object, $extra_data = [] ) {
		$this->source_object = $source_object;
		$this->extra_data    = $extra_data;
		$api_vars            = [];

		// Get a flat list of all possible replaceable values from our system.
		$all_values = $this->get_all_possible_values();

		// Define the mapping from our internal variable names to Alimtalk template variables.
		// This is the single source of truth for Alimtalk variables.
		$variable_mapping = [
			'customer_name'     => '고객명',
			'customer_fullname' => '고객명', // Map both to the same Alimtalk variable
			'order_id'          => '주문ID',
			'order_number'      => '주문번호',
			'order_date'        => '주문일자',
			'order_total'       => '주문금액',
			'billing_phone'     => '연락처',
			'shop_name'         => '상점명',
			'user_id'           => '회원ID',
			'user_login'        => '회원아이디',
			'user_email'        => '이메일',
			'user_display_name' => '회원이름',
			'subscription_id'           => '구독ID',
			'subscription_status'       => '구독상태',
			'subscription_start_date'   => '구독시작일',
			'subscription_next_payment' => '다음결제일',
		];

		// Allow developers to modify the mapping.
		$variable_mapping = apply_filters('sms_connect_alimtalk_variable_mapping', $variable_mapping);

		// Iterate through our internal variables and see if they have a mapping.
		foreach ( $all_values as $internal_key => $value ) {
			if ( isset( $variable_mapping[ $internal_key ] ) ) {
				$alimtalk_key = '#{' . $variable_mapping[ $internal_key ] . '}';
				$api_vars[ $alimtalk_key ] = $value;
			}
		}

		return $api_vars;
	}

	/**
	 * Helper function to get all possible key-value pairs from the current source object.
	 *
	 * @return array
	 */
	private function get_all_possible_values() {
		if ( $this->source_object instanceof \WC_Order ) {
			$order = $this->source_object;
			return array_merge([
				'order_id'         => $order->get_id(),
				'order_number'     => $order->get_order_number(),
				'order_date'       => wc_format_datetime( $order->get_date_created() ),
				'order_total'      => $order->get_formatted_order_total(),
				'customer_name'    => $order->get_billing_first_name(),
				'customer_fullname'=> $order->get_formatted_billing_full_name(),
				'billing_phone'    => $order->get_billing_phone(),
				'shop_name'        => get_bloginfo( 'name' ),
			], $this->extra_data);
		} elseif ( $this->source_object instanceof \WP_User ) {
			$user = $this->source_object;
			return array_merge([
				'user_id'           => $user->ID,
				'user_login'        => $user->user_login,
				'user_email'        => $user->user_email,
				'user_display_name' => $user->display_name,
				'billing_phone'     => get_user_meta($user->ID, 'billing_phone', true),
				'shop_name'         => get_bloginfo( 'name' ),
			], $this->extra_data);
        } elseif ( $this->source_object instanceof \WC_Subscription ) {
            $subscription = $this->source_object;
            return array_merge([
                'subscription_id'           => $subscription->get_id(),
                'subscription_status'       => wcs_get_subscription_status_name($subscription->get_status()),
                'subscription_start_date'   => wc_format_datetime( $subscription->get_date_created() ),
                'subscription_next_payment' => wc_format_datetime( $subscription->get_date('next_payment') ),
                'customer_name'             => $subscription->get_billing_first_name(),
                'customer_fullname'         => $subscription->get_formatted_billing_full_name(),
                'billing_phone'             => $subscription->get_billing_phone(),
                'shop_name'                 => get_bloginfo( 'name' ),
            ], $this->extra_data);
        }
		// Add other object types here if needed
		return $this->extra_data;
	}
} 