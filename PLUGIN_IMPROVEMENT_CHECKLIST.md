# SMS-connect 플러그인 개선 체크리스트

이 문서는 `SMS-connect-0.5.1` 플러그인의 안정성과 품질 향상을 위한 수정 사항 목록입니다.

## 1. 치명적인 오류 (Fatal Errors)

- [ ] **미정의된 변수 사용 문제 해결**
    - **파일:** `woocommerce/WC_Subscriptions_Hooks.php`
    - **내용:** `send_alimtalk`, `send_sms` 메소드 내에 `$sms_connect` 변수 정의 추가 (`$sms_connect = \SmsConnect\Sms_Connect::instance();`).

- [ ] **존재하지 않는 메소드 호출 문제 해결**
    - **파일:** `woocommerce/WC_Hooks.php`, `core/User_Hooks.php`
    - **내용:** `Alimtalk_Api_Client` 객체의 `->send_request(...)` 호출을 `->send_alimtalk(...)`으로 수정.

- [ ] **존재하지 않는 초기화 함수 호출 문제 해결**
    - **파일:** `api/Sms_Api_Client.php`, `api/Alimtalk_Api_Client.php`
    - **내용:** 생성자(`__construct`) 내 `init_point_check()` 메소드 호출 라인 삭제 또는 구현.

## 2. 버그 및 비일관성 (Bugs & Inconsistencies)

- [ ] **잘못된 옵션 값 조회 로직 수정**
    - **파일:** `admin/Admin_Notices.php`
    - **내용:** 개별 옵션(`get_option('sms_connect_api_key')`) 대신 배열 옵션(`get_option('sms_connect_options')`)에서 값을 읽도록 수정하고, 불필요한 `alimtalk_api_secret` 확인 로직 제거.

- [ ] **불필요한 중복 API 클라이언트 파일 제거**
    - **파일:** `core/Sms_Api_Client.php`, `core/Alimtalk_Api_Client.php`
    - **내용:** `api/` 디렉토리의 파일들과 중복되므로 해당 파일들 삭제.

- [ ] **네임스페이스 통일**
    - **대상:** 프로젝트 전체
    - **내용:** `SMS_Connect`와 `SmsConnect`로 혼용된 네임스페이스를 `SmsConnect`로 통일.

## 3. 개선 제안 (Suggested Improvements)

- [ ] **WooCommerce 의존성 검사 로직 추가**
    - **파일:** `sms-connect.php` 또는 `Sms_Connect.php`
    - **내용:** 플러그인 실행 시 WooCommerce 활성화 여부를 확인하고, 비활성화 시 알림을 표시하고 기능 로드를 중단하는 로직 추가. (`Admin_Notices::show_woocommerce_not_active_notice()` 활용)

- [ ] **규칙 설정 페이지 폼 처리 개선**
    - **파일:** `admin/Rule_Settings_Page.php`
    - **내용:** 규칙 추가/삭제 폼을 하나로 통합하고, 처리 후 Post-Redirect-Get 패턴을 적용하여 중복 제출 방지. 