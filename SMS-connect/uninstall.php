<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file is responsible for cleaning up all data created by the plugin.
 *
 * @package SmsConnect
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Future cleanup logic will go here.
// For example, deleting options saved in the database:
// delete_option( 'sms_connect_settings' );

// Or deleting custom database tables:
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}custom_sms_logs" ); 