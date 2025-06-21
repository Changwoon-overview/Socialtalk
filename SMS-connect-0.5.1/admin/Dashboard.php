<?php
/**
 * Dashboard Page
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
 * Renders the main dashboard page.
 */
class Dashboard {

	/**
	 * Displays the dashboard page.
	 */
	public function display_page() {
		$balance_data   = $this->get_balance_data();
		$history_stats  = $this->get_history_stats();

		?>
		<div class="wrap sms-connect-dashboard">
			<h1><?php \esc_html_e( 'SMS 연결 대시보드', 'sms-connect' ); ?></h1>

			<div class="metabox-holder">
				<div class="postbox-container">
					<div class="postbox">
						<h2 class="hndle"><span><?php \esc_html_e( '계정 잔액', 'sms-connect' ); ?></span></h2>
						<div class="inside">
							<?php if ( \is_wp_error( $balance_data ) ) : ?>
								<p><?php echo \esc_html( $balance_data->get_error_message() ); ?></p>
							<?php elseif ( ! empty( $balance_data ) ) : ?>
								<p><strong><?php \esc_html_e( '포인트:', 'sms-connect' ); ?></strong> <?php echo \esc_html( \number_format( $balance_data['point'] ) ); ?> P</p>
								<p><strong><?php \esc_html_e( '캐시:', 'sms-connect' ); ?></strong> <?php echo \esc_html( \number_format( $balance_data['cash'] ) ); ?> <?php \esc_html_e( '원', 'sms-connect' ); ?></p>
							<?php else : ?>
								<p><?php \esc_html_e( '잔액 정보를 가져올 수 없습니다.', 'sms-connect' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="postbox">
						<h2 class="hndle"><span><?php \esc_html_e( '발송 통계 (최근 30일)', 'sms-connect' ); ?></span></h2>
						<div class="inside">
							<p><strong><?php \esc_html_e( '총 발송:', 'sms-connect' ); ?></strong> <?php echo \esc_html( $history_stats['total'] ); ?></p>
							<p><strong><?php \esc_html_e( '성공:', 'sms-connect' ); ?></strong> <?php echo \esc_html( $history_stats['success'] ); ?></p>
							<p><strong><?php \esc_html_e( '실패:', 'sms-connect' ); ?></strong> <?php echo \esc_html( $history_stats['failure'] ); ?></p>
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
		$balance = \get_transient( 'sms_connect_balance' );

		if ( false === $balance ) {
			$sms_connect = \SmsConnect\SmsConnect::instance();
			$balance     = $sms_connect->handlers['api_client']->get_balance();
			\set_transient( 'sms_connect_balance', $balance, HOUR_IN_SECONDS );
		}

		return $balance;
	}

	/**
	 * Get sending history statistics.
	 *
	 * @return array
	 */
	private function get_history_stats() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sms_connect_logs';
		$thirty_days_ago = \date( 'Y-m-d H:i:s', \strtotime( '-30 days' ) );

		$total = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE sent_at >= %s",
			$thirty_days_ago
		) );

		$success = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE sent_at >= %s AND status = 'Success'",
			$thirty_days_ago
		) );

		$failure = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE sent_at >= %s AND status = 'Failure'",
			$thirty_days_ago
		) );

		return [
			'total'   => (int) $total,
			'success' => (int) $success,
			'failure' => (int) $failure,
		];
	}
} 