<style>
    div.send-no-guide {
        padding: 5px;
    }

    div.send-no-guide p {
        font-size: 11px;
        margin: 0;
    }

    div.send-no-guide p.send-no-guide-title {
        font-weight: bold;
    }

    div.send-no-guide p.send-no-guide-content {
        padding-left: 10px;
    }

</style>
<div class="send-no-guide">
    <p class="send-no-guide-title">[1] 대체문자 설정 안내</p>
    <p class="send-no-guide-content">- 발송설정 메뉴에서 사용되는 대체 문구에 대한 설명입니다.</p>
    <p class="send-no-guide-content">- 아래 내용을 참고하여 대체 문구를 설정하여 이용할 수 있습니다.</p>
    <hr>
    <p class="send-no-guide-title">[2] 문자 발송시</p>
    <p class="send-no-guide-content">- {쇼핑몰명} : “Shop 설정 > 기본 정보 > 쇼핑몰 정보" 페이지에 있는 ”쇼핑몰 이름"에 입력된 내용이 표시됩니다. 우커머스 사용자의 경우, “사이트설정 > 일반” 페이지에 있는 “사이트 제목”에 입력된 내용이 표시됩니다.</p>
    <p class="send-no-guide-content">- {상품명} : 해당 주문건의 상품명이 표시됩니다. 예 : 테니스공</p>
    <p class="send-no-guide-content">- {상품정보} : 해당 주문건의 상품정보가 표시됩니다. 예 : 테니스공 외 2건</p>
    <p class="send-no-guide-content">- {고객명} : 해당 주문건의 청구지 고객명이 표시됩니다. 예 : 홍길동</p>
    <p class="send-no-guide-content">- {아이디} : 해당 주문건의 고객 아이디가 표시됩니다. 예 : admin</p>
    <p class="send-no-guide-content">- {회원등급} : 해당 주문건의 고객 등급이 표시됩니다. 예 : 관리자</p>
    <p class="send-no-guide-content">- {주문일} : 해당 주문건의 결제일자가 표시됩니다. 예 : 2019-10-14</p>
    <p class="send-no-guide-content">- {주문번호} : 해당 주문건의 주문번호가 표시됩니다. 숫자만 기재됩니다. 예 : 1234</p>
    <p class="send-no-guide-content">- {주문금액} : 해당 주문건의 주문금액이 표시됩니다. 숫자만 기재됩니다. 예 : 1,000</p>
    <p class="send-no-guide-content">- {부분환불금액} : 부분환불 진행 시 부분환불된 금액이 표시됩니다. 예 : 1,000</p>
    <p class="send-no-guide-content">- {청구지주소} : 해당 주문건의 청구지 주소가 표시됩니다.</p>
    <p class="send-no-guide-content">- {배송지주소} : 해당 주문건의 배송지 주소가 표시됩니다.</p>
    <hr>
    <p class="send-no-guide-title">[3] 엠샵 전용</p>
    <p class="send-no-guide-content">- {배송업체명} : 각 주문 상세에서 입력한 배송업체명이 대체되어 표시됩니다. 예 : CJ GLS택배</p>
    <p class="send-no-guide-content">- {송장번호} : 각 주문 상세에서 입력한 송장번호가 대체되어 표시됩니다. 예 : 1234567890"</p>
    <hr>
    <p class="send-no-guide-title">[4] 가상계좌 입금안내</p>
    <p class="send-no-guide-content">- 가상계좌 입금에 따른 문자 발송은 코드엠샵의 <a href="<?php echo admin_url( '/plugin-install.php?s=심플페이&tab=search&type=term' ); ?>" target="_blank">심플페이 결제 플러그인</a> 이용 시에만 가능합니다.</p>
    <p class="send-no-guide-content">- {가상계좌은행명} : 고객이 가상계좌 결제시 선택한 입금 은행이 표시됩니다.</p>
    <p class="send-no-guide-content">- {가상계좌번호} : PG사에서 발급된 가상계좌번호가 표시됩니다.</p>
    <p class="send-no-guide-content">- {가상계좌예금주} : PG사에 등록된 가맹점명이 표시됩니다.</p>
    <p class="send-no-guide-content">- {주문금액} : 가상계좌 입금액이 표시됩니다.</p>
    <p class="send-no-guide-content">- {가상계좌입금자} : 가상계좌 입금자명이 표시됩니다.</p>
    <p class="send-no-guide-content">- {가상계좌입금기한} : 가상계좌 입금기한이 표시됩니다.</p>
	<?php if ( class_exists( 'WC_Subscription' ) ) : ?>
		<hr>
        <p class="send-no-guide-title">[정기결제권 전용]</p>
        <p class="send-no-guide-content">- 정기결제권 대체문자는 "<a href="/wp-admin/admin.php?page=mssms_settings_subscription" target="_blank">엠샵 문자 알림톡 => 정기결제권 발송 설정</a>"에만 적용됩니다.</p>
        <p class="send-no-guide-content">- {정기결제권번호} : 정기결제권 번호가 표시됩니다.</p>
        <p class="send-no-guide-content">- {신청일} : 정기결제 신청일자가 표시됩니다.</p>
        <p class="send-no-guide-content">- {만료일} : 정기결제권 만료일이 표시됩니다.</p>
        <p class="send-no-guide-content">- {다음결제일} : 다음 갱신 결제일자가 표시됩니다.</p>
        <p class="send-no-guide-content">- {배송주기} : 정기결제권의 갱신 주기가 정기결제 플러그인의 문자열을 기준으로 표시됩니다. 문자열 번역 방법은 <a href="https://www.codemshop.com/manual/docs/loco-translate/uses-guide/plural-settings" target="_blank">배송주기 문자열 수정 방법</a>을 참고해주세요.</p>
        <p class="send-no-guide-content">- {상품명} : 정기결제권에 등록된 상품명이 표시됩니다.</p>
        <p class="send-no-guide-content">- {결제수단} : 정기결제권에 등록된 결제수단명이 표시됩니다.</p>
        <p class="send-no-guide-content">- {정기결제권금액} : 정기결제권의 금액이 표시됩니다.</p>
		<p class="send-no-guide-content"><b>* (주의) 정기결제권 발송에만 적용됩니다.</b></p>
	<?php endif; ?>
</div>