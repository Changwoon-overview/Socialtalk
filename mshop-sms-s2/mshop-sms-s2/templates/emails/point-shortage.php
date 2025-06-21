<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <style>
        div.mssms-guide h2 {
            font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
            font-size: 24px;
            line-height: 24px;
            font-weight: normal;
            text-align: center;
            margin: 30px 0;
            color: #333333;
        }

        div.mssms-guide p {
            text-align: center;
            font-size: 14px;
            line-height: 20px;
            color: #797979;
        }

        div.mssms-guide .button.button-primary {
            display: inline-block;
            padding: 14px 34px;
            background-color: #42839f;
            color: #ffffff;
            font-weight: bold;
            font-size: 14px;
            line-height: 14px;
            border: none;
            border-radius: 0;
            margin: 0;
            text-decoration: none;
        }
    </style>

    <div class="mssms-guide" style="text-align: center;">
        <h2>문자 알림 포인트를 충전 해 주세요.</h2>
        <p style="margin: 0px 0px 16px;">귀사의 사이트에 문자 포인트가 부족하여 문자 알림(카카오 알림톡) 발송이 곧 중지될 예정에 있습니다.<br>보유 포인트를 확인하신 후, 충전 후 이용을 부탁드립니다.</p>
        <p style="margin: 0px 0px 30px;">※ 본 알림은 문자 포인트 잔액이 <?php echo number_format( get_option( 'mssms_point_shortage_threshold', 2000 ) ); ?> 포인트 이하 시 발송됩니다.</p>
        <p style="margin: 0px 0px 16px;"></p>
        <p><?php _e( '<a class="button button-primary" href="https://www.codemshop.com/shop/sms_out/" target=_blank>포인트 충전</a>', 'mshop-sms-s2' ); ?></p>
        <div style="margin:0 0 16px;">
            <img src="<?php echo MSSMS()->plugin_url() . '/assets/images/email.jpg'; ?>" style="width: 100%;">
        </div>
    </div>

<?php
do_action( 'woocommerce_email_footer', $email );
