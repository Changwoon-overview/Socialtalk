<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
function mssms_woocommerce_activated() {
	return class_exists( 'woocommerce' );
}

function mssms_log( $message ) {
	if ( mssms_woocommerce_activated() ) {
		MSSMS()->get_logger()->add( MSSMS()->slug(), $message );
	}
}
function mssms_get( $array, $key, $default = '' ) {
	return ! empty( $array[ $key ] ) ? $array[ $key ] : $default;
}
function mssms_get_roles() {
	$roles = array();

	foreach ( wp_roles()->roles as $role => $object ) {
		$roles[ $role ] = translate_user_role( $object['name'] );
	}

	return $roles;
}
function mssms_get_user_role( $user_id = null ) {
    if ( class_exists( 'MSMS_S2' ) ) {
        $role       = mshop_membership_get_user_role( $user_id );
        $role_name  = msms_get_role_name( $role );
    } else {
        if ( $user_id === null && is_user_logged_in() ) {
            $user_id = wp_get_current_user();
        }

        if ( is_numeric( $user_id ) ) {
            $user       = new WP_User( $user_id );
            $user_roles = $user->roles;
        } else if ( $user_id instanceof WP_User ) {
            $user_roles = $user_id->roles;
        }

        $roles     = mssms_get_roles();
        $role      = ! empty( $user_roles ) ? array_shift( $user_roles ) : '';
        $role_name = $roles[ $role ];
    }

    return apply_filters( 'mssms_get_user_role', $role_name, $role, $user_id );
}
function mssms_get_shipping_phone( $order ) {
	return is_callable( array( $order, 'get_shipping_phone' ) ) ? $order->get_shipping_phone() : $order->get_meta( '_shipping_phone' );
}
function mssms_get_alimtalk_template_list( $statuses = array( 'APR' ) ) {
	$options   = array();
	$templates = get_option( 'mssms_template_lists', array() );

	if ( ! empty( $templates ) ) {
		foreach ( $templates as $template ) {
			if ( in_array( $template['status'], $statuses ) ) {
				$options[ $template['code'] ] = $template['name'] . ' (' . $template['plus_id'] . ')';
			}
		}
	}

	return $options;
}
function mssms_wpml_get_default_language() {
	global $sitepress;

	if ( has_filter( 'wpml_object_id' ) && $sitepress && is_callable( array( $sitepress, 'get_default_language' ) ) ) {
		return $sitepress->get_default_language();
	} else {
		return '';
	}
}
function mssms_order_contains_renewal( $order ) {
	if ( is_a( $order, 'WC_Abstract_Order' ) && function_exists( 'wcs_is_subscription' ) && ! wcs_is_subscription( $order ) ) {
		return wcs_order_contains_renewal( $order );
	}

	return false;
}
function mssms_get_order_statuses( $subscription = true ) {
	$order_statuses = array();

	foreach ( wc_get_order_statuses() as $status => $status_name ) {
		$status                    = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
		$order_statuses[ $status ] = $status_name;
	}

    $order_statuses[ 'pafw-send-vact-info' ] = __( '가상계좌 무통장 입금 알림', 'mshop-sms-s2' );
    $order_statuses[ 'pafw-partial-refund' ] = __( '부분환불', 'mshop-sms-s2' );

	return apply_filters( 'mssms_get_order_statuses', $order_statuses, $subscription );
}
function mssms_is_subscription( $order ) {
	return function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order );
}
function mssms_order_contains_subscription( $order ) {
	return function_exists( 'wcs_get_subscriptions_for_order' ) && function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order, $order_type = array( 'parent', 'renewal', 'resubscribe', 'switch' ) );
}

function mssms_parse_template_params( $template_params, $key_for_single = '고객명' ) {

	if ( preg_match_all( '/=|\|/', $template_params ) ) {
		$params = explode( '|', $template_params );

		foreach ( $params as $param ) {
			$args = explode( '=', $param );
			if ( count( $args ) >= 2 ) {
				$params[ $args[0] ] = $args[1];
			}
		}
	} else {
		$params = array(
			$key_for_single => $template_params
		);
	}

	return $params;
}

function mssms_home_url() {
	return apply_filters( 'mssms_home_url', home_url() );
}
function mssms_woocommerce_subscription_is_active() {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	return is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
}
function mssms_register_scheduled_action( $subscription, $scheduled_time ) {
	mssms_deregister_scheduled_action( $subscription->get_id() );

	if ( $scheduled_time > time() ) {
		as_schedule_single_action(
			$scheduled_time,
			'mssms_renewal_notification',
			array( 'subscription_id' => $subscription->get_id() )
		);
	}
}
function mssms_deregister_scheduled_action( $subscription_id ) {
	as_unschedule_all_actions( 'mssms_renewal_notification', array( 'subscription_id' => $subscription_id ) );
}
function mssms_get_next_payment_date( $subscription ) {
	if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $subscription ) ) {
		return date( 'Y-m-d', strtotime( $subscription->get_date( 'next_payment_date' ) ) + get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
	}

	return '';
}

function mssms_get_next_round_of_subscription( $subscription ) {
	$round = 1;
	$ids   = $subscription->get_related_orders( 'ids', array( 'renewal' ) );

	foreach ( $ids as $id ) {
		$related_order = wc_get_order( $id );
		if ( $related_order && apply_filters( 'msms_is_renewal_order', $related_order->has_status( array( 'completed' ) ), $related_order ) ) {
			$round ++;
		}
	}

	return $round + 1;
}

if ( MSSMS_Manager::is_enabled( 'alimtalk' ) ) {
	add_action( 'woocommerce_order_status_changed', array( 'MSSMS_Kakao', 'send_message_to_admin' ), 100, 3 );
	add_action( 'woocommerce_order_status_changed', array( 'MSSMS_Kakao', 'send_message_to_user' ), 100, 3 );

	add_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_Kakao', 'send_subscription_message_to_admin' ), 100, 4 );
	add_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_Kakao', 'send_subscription_message_to_user' ), 100, 4 );

	add_action( 'mssms_send_alimtalk', array( 'MSSMS_Kakao', 'send_alimtalk' ), 10, 4 );
	add_action( 'send_vact_info', array( 'MSSMS_Kakao', 'send_vact_info' ), 10, 7 );
	add_action( 'pafw_partial_refund', array( 'MSSMS_Kakao', 'maybe_send_partial_refund_notification' ), 10, 3 );

	add_filter( 'mssms_alimtalk_message_template', array( 'MSSMS_Message_By_Rule', 'get_alimtalk_message_template' ), 10, 5 );

	add_action( 'set_user_role', array( 'MSSMS_User', 'maybe_send_alimtalk' ), 10, 3 );

	add_action( 'msm_user_registered', array( 'MSSMS_Members', 'send_created_customer_alimtalk' ), 10, 3 );
}

if ( MSSMS_Manager::is_enabled( 'sms' ) ) {
	add_action( 'woocommerce_order_status_changed', array( 'MSSMS_SMS', 'send_message_to_admin' ), 100, 3 );
	add_action( 'woocommerce_order_status_changed', array( 'MSSMS_SMS', 'send_message_to_user' ), 100, 3 );

	add_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_SMS', 'send_subscription_message_to_admin' ), 100, 4 );
	add_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_SMS', 'send_subscription_message_to_user' ), 100, 4 );

	add_action( 'mssms_send_message', array( 'MSSMS_SMS', 'send_message' ), 10, 6 );
	add_action( 'send_vact_info', array( 'MSSMS_SMS', 'send_vact_info' ), 10, 7 );
	add_action( 'pafw_partial_refund', array( 'MSSMS_SMS', 'maybe_send_partial_refund_notification' ), 10, 3 );

	add_filter( 'mssms_sms_message_template', array( 'MSSMS_Message_By_Rule', 'get_sms_message_template' ), 10, 5 );
	add_action( 'mshop_send_sms', array( 'MSSMS_SMS', 'send_custom_message' ), 10, 4 );

	add_action( 'set_user_role', array( 'MSSMS_User', 'maybe_send_sms' ), 10, 3 );

	add_action( 'msm_user_registered', array( 'MSSMS_Members', 'send_created_customer_sms' ), 10, 3 );
}
function mssms_skip_bacs_on_hold_notification_for_vbank( $flag, $payment_method, $new_status, $order ) {
	if ( is_a( $order, 'WC_Order' ) && 'on-hold' == $new_status ) {
		$payment_gateway = wc_get_payment_gateway_by_order( $order );

		if ( $payment_gateway && $payment_gateway->supports( 'pafw-vbank' ) ) {
			$flag = false;
		}
	}

	return $flag;
}

add_filter( 'mshop_sms_order_payment_method_check', 'mssms_skip_bacs_on_hold_notification_for_vbank', 10, 4 );
add_filter( 'msm_post_actions', array( 'MSSMS_Members', 'register_post_actions' ) );
add_filter( 'msm_post_actions_settings', array( 'MSSMS_Members', 'register_post_actions_settings' ) );
add_filter( 'msm-post-actions-send_sms', array( 'MSSMS_Members', 'send_sms' ), 10, 4 );
add_filter( 'msm-post-actions-send_alimtalk', array( 'MSSMS_Members', 'send_alimtalk' ), 10, 4 );
add_action( 'msm_user_register', array( 'MSSMS_Members', 'save_user_info' ), 10, 3 );


function mssms_detach_subscription_filter( $subscription_id = 0 ) {
	remove_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_Kakao', 'send_subscription_message_to_admin' ), 100 );
	remove_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_Kakao', 'send_subscription_message_to_user' ), 100 );
	remove_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_SMS', 'send_subscription_message_to_admin' ), 100 );
	remove_action( 'woocommerce_subscription_status_changed', array( 'MSSMS_SMS', 'send_subscription_message_to_user' ), 100 );
}

add_action( 'woocommerce_scheduled_subscription_payment', 'mssms_detach_subscription_filter', 1 );
add_action( 'woocommerce_subscriptions_pre_update_payment_method', 'mssms_detach_subscription_filter', 1 );
function mssms_maybe_remove_subscription_filter( $order, $data ) {
	if ( function_exists( 'wcs_cart_contains_early_renewal' ) && wcs_cart_contains_early_renewal() ) {
		mssms_detach_subscription_filter();
	}
}

add_action( 'woocommerce_checkout_create_order', 'mssms_maybe_remove_subscription_filter', 10, 2 );


function mssms_get_subscription_total ( $subscription ) {

    if( apply_filters( 'mssms_get_subscription_total_use_renewal_order', false ) ) {
        $renewal_order = wcs_create_renewal_order( $subscription );

        if ( wc_prices_include_tax() ) {
            $item_total = $renewal_order->get_subtotal() - $renewal_order->get_total_discount();
            $item_total += $renewal_order->get_total_tax() - floatval( $renewal_order->get_shipping_tax() );
        } else {
            $item_total = $renewal_order->get_total();
        }

        $renewal_order->delete( true );

        return number_format( $item_total, wc_get_price_decimals() );
    } else {
        return number_format( $subscription->get_total(), wc_get_price_decimals() );
    }
}