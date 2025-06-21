<?php
/**
 * Base Settings Page
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Class Base_Settings_Page.
 * Provides shared functionality for settings pages.
 */
abstract class Base_Settings_Page {
	/**
	 * The option group name.
	 *
	 * @var string
	 */
	protected $option_group = '';

	/**
	 * The option name for admins in the database.
	 * @var string
	 */
	protected $admin_option_name = 'sms_connect_admins';

	/**
	 * Sanitize admin settings.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_settings( $input ) {
		$sanitized_input = [];
		if ( ! \is_array( $input ) ) {
			return $sanitized_input;
		}

		foreach ( $input as $index => $admin ) {
			if ( empty( $admin['name'] ) && empty( $admin['phone'] ) ) {
				continue;
			}
			$sanitized_input[ $index ]['enable'] = isset( $admin['enable'] ) ? 'yes' : 'no';
			$sanitized_input[ $index ]['name']   = \sanitize_text_field( $admin['name'] );
			$sanitized_input[ $index ]['phone']  = \sanitize_text_field( $admin['phone'] );
		}

		return $sanitized_input;
	}

	/**
	 * Render the info for the admin section.
	 */
	public function render_admin_section_info() {
		echo '<p>' . \esc_html__( '주요 이벤트 발생 시 알림을 받을 관리자 목록을 설정합니다. (예: 신규 주문, 포인트 부족 등)', 'sms-connect' ) . '</p>';
	}

	/**
	 * Render a repeater field for admins.
	 */
	public function render_admin_list_field() {
		$admins = \get_option( $this->admin_option_name, [ [ 'enable' => 'yes', 'name' => '', 'phone' => '' ] ] );
		?>
		<table class="wp-list-table widefat fixed striped" id="sms-connect-admin-list">
			<thead>
			<tr>
				<th style="width: 50px;"><?php \esc_html_e( '활성화', 'sms-connect' ); ?></th>
				<th><?php \esc_html_e( '관리자명', 'sms-connect' ); ?></th>
				<th><?php \esc_html_e( '휴대폰 번호', 'sms-connect' ); ?></th>
				<th style="width: 50px;"></th>
			</tr>
			</thead>
			<tbody id="admin-list-body">
			<?php foreach ( $admins as $index => $admin ) : ?>
				<tr class="admin-row">
					<td>
						<input type="checkbox" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[<?php echo \esc_attr( $index ); ?>][enable]" value="yes" <?php \checked( 'yes', $admin['enable'] ?? 'no' ); ?>>
					</td>
					<td>
						<input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[<?php echo \esc_attr( $index ); ?>][name]" value="<?php echo \esc_attr( $admin['name'] ); ?>" class="regular-text">
					</td>
					<td>
						<input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[<?php echo \esc_attr( $index ); ?>][phone]" value="<?php echo \esc_attr( $admin['phone'] ); ?>" class="regular-text">
					</td>
					<td>
						<button type="button" class="button button-secondary remove-admin-row">-</button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th colspan="4">
					<button type="button" class="button button-primary" id="add-admin-row">+</button>
				</th>
			</tr>
			</tfoot>
		</table>
		<script>
			jQuery(document).ready(function ($) {
				let rowIndex = <?php echo \count( $admins ); ?>;

				$('#add-admin-row').on('click', function () {
					const newRow = `
                        <tr class="admin-row">
                            <td>
                                <input type="checkbox" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[${rowIndex}][enable]" value="yes" checked>
                            </td>
                            <td>
                                <input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[${rowIndex}][name]" value="" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="<?php echo \esc_attr( $this->admin_option_name ); ?>[${rowIndex}][phone]" value="" class="regular-text">
                            </td>
                            <td>
                                <button type="button" class="button button-secondary remove-admin-row">-</button>
                            </td>
                        </tr>
                    `;
					$('#admin-list-body').append(newRow);
					rowIndex++;
				});

				$('#sms-connect-admin-list').on('click', '.remove-admin-row', function () {
					$(this).closest('tr').remove();
				});
			});
		</script>
		<?php
	}

	/**
	 * Render the settings page form.
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				\settings_fields( $this->option_group );
				\do_settings_sections( $this->option_group );
				\submit_button();
				?>
			</form>
		</div>
		<?php
	}
} 