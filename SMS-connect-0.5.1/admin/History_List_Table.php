<?php
/**
 * History List Table
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class History_List_Table
 *
 * Creates a WP_List_Table for displaying sending history.
 */
class History_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => __( 'Log', 'sms-connect' ),
			'plural'   => __( 'Logs', 'sms-connect' ),
			'ajax'     => false,
		] );
	}

	/**
	 * Retrieve logs data from the database.
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return array
	 */
	public static function get_logs( $per_page = 20, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}sms_connect_logs";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		} else {
			$sql .= ' ORDER BY sent_at DESC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= " OFFSET " . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sms_connect_logs";

		return $wpdb->get_var( $sql );
	}

	/**
	 *  No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No logs found.', 'sms-connect' );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'sent_at'   => __( 'Date', 'sms-connect' ),
			'order_id'  => __( 'Order ID', 'sms-connect' ),
			'recipient' => __( 'Recipient', 'sms-connect' ),
			'type'      => __( 'Type', 'sms-connect' ),
			'status'    => __( 'Status', 'sms-connect' ),
			'message'   => __( 'Message', 'sms-connect' ),
		];
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'sent_at'  => [ 'sent_at', true ],
			'order_id' => [ 'order_id', false ],
			'type'     => [ 'type', false ],
			'status'   => [ 'status', false ],
		];
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		$per_page     = $this->get_items_per_page( 'logs_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
		] );

		$this->items = self::get_logs( $per_page, $current_page );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'sent_at':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item[ $column_name ] ) );
			case 'order_id':
				$order_url = get_edit_post_link( $item[ $column_name ] );
				return "<a href='{$order_url}'>#{$item[$column_name]}</a>";
			case 'recipient':
			case 'type':
			case 'status':
				return esc_html( $item[ $column_name ] );
			case 'message':
				return esc_html( $item[ $column_name ] );
			default:
				return print_r( $item, true ); // Show the whole array for debugging.
		}
	}
} 