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
		$api_vars = [];

		preg_match_all( '/#{\s*([^}]+)\s*}/', $content, $matches );

		if ( empty( $matches[1] ) ) {
			return $api_vars;
		}

		// Get a flat list of all possible replaceable values
		$this->source_object = $source_object;
		$this->extra_data = $extra_data;
		$all_values = $this->get_all_possible_values();

		foreach ( $matches[1] as $variable_name ) {
			// Note: Alimtalk variables don't use {}, so we map `고객명` to `customer_name`
			// This part requires a mapping layer if variable names differ.
			// For now, we assume direct mapping is possible or handled elsewhere.
			// Let's create a simple mapping for this example.
			$key_map = [
				'고객명' => 'customer_name',
				'주문번호' => 'order_number',
                // Add other Alimtalk-specific to internal variable mappings here
			];
			$internal_key = isset($key_map[$variable_name]) ? $key_map[$variable_name] : $variable_name;
			
			if ( isset( $all_values[ $internal_key ] ) ) {
				$api_vars[ '#{' . $variable_name . '}' ] = $all_values[ $internal_key ];
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
		}
		// Add other object types like WC_Subscription here if needed
		return $this->extra_data;
	}
} 