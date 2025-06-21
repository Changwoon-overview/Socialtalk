<?php
/**
 * Database Manager
 *
 * @package SmsConnect\Core
 */

namespace SmsConnect\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Db_Manager
 *
 * Handles database table creation and management.
 */
class Db_Manager {
	/**
	 * Create the custom database table for logs.
	 *
	 * This method is called on plugin activation.
	 */
	public static function create_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sms_connect_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			sent_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			order_id bigint(20) UNSIGNED NOT NULL,
			recipient varchar(100) NOT NULL,
			type varchar(20) NOT NULL, -- e.g., 'SMS', 'LMS', 'Alimtalk'
			status varchar(20) NOT NULL, -- e.g., 'Success', 'Failure'
			message text NOT NULL,
			template_code varchar(100) DEFAULT '' NOT NULL,
			response text DEFAULT '' NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
} 