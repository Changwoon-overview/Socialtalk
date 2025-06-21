<?php
wp_enqueue_script( 'jquery' );
wp_enqueue_script( 'jquery-ui' );
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui-style' );

$list_table = new MSSMS_Logs_Table();

$list_table->prepare_items();
?>
<style>
    table.mssms_log,
    table.mssms_log th,
    table.mssms_log td {
        font-size: 0.95em;
    }

    table.mssms_log th.column-id {
        width: 50px;
    }
    table.mssms_log th.column-date {
        width: 70px;
        text-align: center;
    }
    table.mssms_log th.column-sender,
    table.mssms_log th.column-receiver {
        width: 100px;
    }
    table.mssms_log th.column-msg_type{
        width: 30px;
    }
    table.mssms_log th.column-subject{
        width: 80px;
    }
    table.mssms_log th.column-message {
        width: 200px;
    }
</style>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery(function($){
			$.datepicker.regional['ko'] = {
				closeText: '닫기',
				prevText: '이전달',
				nextText: '다음달',
				currentText: '오늘',
				monthNames: ['1월','2월','3월','4월','5월','6월',
					'7월','8월','9월','10월','11월','12월'],
				monthNamesShort: ['1월','2월','3월','4월','5월','6월',
					'7월','8월','9월','10월','11월','12월'],
				dayNames: ['일요일','월요일','화요일','수요일','목요일','금요일','토요일'],
				dayNamesShort: ['일','월','화','수','목','금','토'],
				dayNamesMin: ['일','월','화','수','목','금','토'],
				weekHeader: 'Wk',
				dateFormat: 'yy-mm-dd',
				firstDay: 0,
				isRTL: false,
				showMonthAfterYear: true,
				yearSuffix: '년'};
			$.datepicker.setDefaults($.datepicker.regional['ko']);
		});

		jQuery(function() {
			jQuery( "input.mssms_datepicker" ).datepicker();
		});
	});
</script>

<div class="wrap mssms-logs-table">
	<h1 class="wp-heading-inline"><?php _e( '엠샵 문자 서비스 로그', 'mshop-sms-s2' ); ?></h1>
	<form method="POST">
		<?php $list_table->display() ?>
	</form>
</div>
