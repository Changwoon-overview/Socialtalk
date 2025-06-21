<?php
/**
 * History Page
 *
 * @package SmsConnect\Admin
 */

namespace SmsConnect\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class History_Page
 *
 * Renders the sending history page.
 */
class History_Page {

	/**
	 * The list table instance.
	 *
	 * @var History_List_Table
	 */
	private $list_table;

	/**
	 * Displays the history page.
	 */
	public function display_page() {
		$this->list_table = new History_List_Table();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Sending History', 'sms-connect' ); ?></h1>
			
			<form method="post">
				<?php
				$this->list_table->prepare_items();
				$this->list_table->display();
				?>
			</form>
		</div>
		<?php
	}
} 