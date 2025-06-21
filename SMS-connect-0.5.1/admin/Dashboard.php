<?php
/**
 * Admin Dashboard
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dashboard
 *
 * Handles the main dashboard page of the plugin.
 */
class Dashboard {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Actions for dashboard widgets can be added here later.
	}

	/**
	 * Renders the dashboard page.
	 */
	public function display_page() {
		$balance_data = $this->get_balance_data();
		$history_stats = $this->get_history_stats();
		?>
		<div class="wrap sms-connect-dashboard">
			<h1><?php esc_html_e( 'SMS Connect Dashboard', 'sms-connect' ); ?></h1>

			<div class="metabox-holder">
				<div class="postbox-container">
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Account Balance', 'sms-connect' ); ?></span></h2>
						<div class="inside">
							<?php if ( is_wp_error( $balance_data ) ) : ?>
								<p><?php echo esc_html( $balance_data->get_error_message() ); ?></p>
							<?php elseif ( ! empty( $balance_data ) ) : ?>
								<p><strong><?php esc_html_e( 'Point:', 'sms-connect' ); ?></strong> <?php echo esc_html( number_format( $balance_data['point'] ) ); ?> P</p>
								<p><strong><?php esc_html_e( 'Cash:', 'sms-connect' ); ?></strong> <?php echo esc_html( number_format( $balance_data['cash'] ) ); ?> <?php esc_html_e( 'KRW', 'sms-connect' ); ?></p>
							<?php else : ?>
								<p><?php esc_html_e( 'Could not retrieve balance information.', 'sms-connect' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Sending Stats (Last 30 Days)', 'sms-connect' ); ?></span></h2>
						<div class="inside">
							<p><strong><?php esc_html_e( 'Total Sent:', 'sms-connect' ); ?></strong> <?php echo esc_html( $history_stats['total'] ); ?></p>
							<p><strong><?php esc_html_e( 'Success:', 'sms-connect' ); ?></strong> <?php echo esc_html( $history_stats['success'] ); ?></p>
							<p><strong><?php esc_html_e( 'Failure:', 'sms-connect' ); ?></strong> <?php echo esc_html( $history_stats['failure'] ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get balance data from API, with caching.
	 *
	 * @return array|\WP_Error
	 */
	private function get_balance_data() {
		$balance = get_transient( 'sms_connect_balance' );

		if ( false === $balance ) {
			$sms_connect = \SmsConnect\Sms_Connect::instance();
			$balance     = $sms_connect->handlers['api_client']->get_balance();
			set_transient( 'sms_connect_balance', $balance, HOUR_IN_SECONDS );
		}

		return $balance;
	}

	/**
	 * Get sending history stats from the database.
	 *
	 * @return array
	 */
	private function get_history_stats() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sms_connect_logs';

		$stats = get_transient( 'sms_connect_history_stats' );

		if ( false === $stats ) {
			$stats = [
				'total'   => 0,
				'success' => 0,
				'failure' => 0,
			];

			$result = $wpdb->get_results( "
				SELECT status, COUNT(id) as count
				FROM {$table_name}
				WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
				GROUP BY status
			" );

			if ( $result ) {
				foreach ( $result as $row ) {
					if ( 'Success' === $row->status ) {
						$stats['success'] = (int) $row->count;
					} elseif ( 'Failure' === $row->status ) {
						$stats['failure'] = (int) $row->count;
					}
					$stats['total'] += (int) $row->count;
				}
			}
			set_transient( 'sms_connect_history_stats', $stats, HOUR_IN_SECONDS );
		}
		
		return $stats;
	}
} 