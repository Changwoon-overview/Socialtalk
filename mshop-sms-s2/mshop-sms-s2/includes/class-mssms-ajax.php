<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MSSMS_Ajax {

	static $slug;

	public static function init() {
		if ( defined( 'WP_DEBUG' ) && ! WP_DEBUG ) {
			@error_reporting( 0 );
			ini_set( 'display_errors', 'Off' );
		}
		self::$slug = MSSMS()->slug();
		self::add_ajax_events();
	}
	public static function add_ajax_events() {

		$ajax_events = array();

		if ( is_admin() ) {
			$ajax_events = array_merge( $ajax_events, array(
				'update_settings'                   => false,
				'update_sms_settings'               => false,
				'update_sms_send_settings'          => false,
				'update_alimtalk_settings'          => false,
				'update_alimtalk_template_settings' => false,
				'update_alimtalk_send_settings'     => false,
				'update_subscription_settings'      => false,
				'update_user_settings'              => false,
				'update_etc_settings'               => false,
				'alimtalk_authentication_request'   => false,
				'alimtalk_add_profile'              => false,
				'alimtalk_add_template'             => false,
				'alimtalk_modify_template'          => false,
				'alimtalk_request_template'         => false,
				'alimtalk_delete_template'          => false,
				'alimtalk_update_resend'            => false,
				'alimtalk_hide_template'            => false,
				'alimtalk_show_template'            => false,
				'sms_register_send_no'              => false,
				'send_sms'                          => false,
				'get_users_by_role'                 => false,
				'get_users_by_product'              => false,
				'user_search'                       => false,
				'get_send_results'                  => false,
				'send_messages'                     => false,
				'send_alimtalk'                     => false,
				'target_search'                     => false,
				'reset_renewal_notification'        => false,
				'register_renewal_notification'     => false,
				'upload_template_image_file'        => false,
				'upload_mms_attached_file'          => false,
			) );
		}

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			if ( is_string( $nopriv ) ) {
				add_action( 'wp_ajax_' . self::$slug . '-' . $ajax_event, $nopriv );
			} else {
				add_action( 'wp_ajax_' . self::$slug . '-' . $ajax_event, array( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_' . self::$slug . '-' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}
		}
	}

	static function update_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings::update_settings();
	}
	static function update_alimtalk_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_Alimtalk::update_settings();
	}
	static function update_alimtalk_template_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_Alimtalk_Template::update_settings();
	}
	static function update_sms_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_SMS::update_settings();
	}

	static function update_sms_send_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_SMS_Send::update_settings();
	}
	static function update_alimtalk_send_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_Alimtalk_Send::update_settings();
	}
	static function update_subscription_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_Subscription::update_settings();
	}
	static function update_user_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_User::update_settings();
	}
	static function update_etc_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		MSSMS_Settings_Etc::update_settings();
	}
	static function get_send_results() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$mtype    = mssms_get( $_REQUEST, 'mtype', 'all' );
			$sms_type = mssms_get( $_REQUEST, 'sms_type', 'all' );
			$state    = mssms_get( $_REQUEST, 'state', 'all' );
			list( $date_from, $date_to ) = explode( ',', mssms_get( $_REQUEST, 'term' ) );

			$results = MSSMS_API_Kakao::get_send_result( '', 'all' == $mtype ? '' : $mtype, 'all' == $sms_type ? '' : $sms_type, mssms_get( $_REQUEST, 'receiver' ), 'all' == $state ? '' : $state, mssms_get( $_REQUEST, 'page' ), $date_from, $date_to, mssms_get( $_REQUEST, 'message' ) );

			wp_send_json_success( array(
					'total_count' => $results['total_count'],
					'results'     => $results['data']
				)
			);
		} catch ( Exception $e ) {
			wp_Send_json_error( $e->getMessage() );
		}
	}
	static function alimtalk_delete_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			MSSMS_API_Kakao::delete_template( $_REQUEST['plus_id'], $_REQUEST['code'] );

			wp_send_json_success();
		} catch ( Exception $e ) {
			$message = sprintf( '템플릿 삭제 오류 : %s', $e->getMessage() );
			wp_send_json_error( $message );

		}
	}
	static function alimtalk_request_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			MSSMS_API_Kakao::request_template( $_REQUEST['plus_id'], $_REQUEST['templtCode'] );

			wp_send_json_success();
		} catch ( Exception $e ) {
			$message = sprintf( '템플릿 삭제 오류 : %s', $e->getMessage() );
			wp_send_json_error( $message );

		}
	}
	static function alimtalk_modify_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$buttons = array();
			if ( ! empty( $_REQUEST['buttons'] ) ) {
				$idx = 1;
				foreach ( $_REQUEST['buttons'] as $button ) {
					$buttons[] = array(
						'ordering'      => $idx++,
						'type'          => mssms_get( $button, 'type' ),
						'name'          => 'AC' == mssms_get( $button, 'type' ) ? '채널 추가' : mssms_get( $button, 'name' ),
						'linkMo'        => trim( mssms_get( $button, 'linkMo' ) ),
						'linkPc'        => trim( mssms_get( $button, 'linkPc' ) ),
						'schemeIos'     => trim( mssms_get( $button, 'schemeIos' ) ),
						'schemeAndroid' => trim( mssms_get( $button, 'schemeAndroid' ) )
					);
				}
			}

			MSSMS_API_Kakao::modify_template( $_REQUEST, $buttons );

			wp_send_json_success();
		} catch ( Exception $e ) {
			$message = sprintf( '템플릿 수정 오류 : %s', $e->getMessage() );
			wp_send_json_error( $message );

		}
	}
	static function alimtalk_hide_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$hide_template_ids = get_option( 'mssms_hide_template_ids', array() );

			$hide_template_ids = array_merge( $hide_template_ids, array( $_REQUEST['code'] ) );

			update_option( 'mssms_hide_template_ids', $hide_template_ids );

			$templates = get_option( 'mssms_template_lists', array() );

			$templates[ $_REQUEST['code'] ]['visibility'] = 'HIDE';

			update_option( 'mssms_template_lists', $templates );

			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );

		}
	}
	static function alimtalk_show_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$hide_template_ids = get_option( 'mssms_hide_template_ids', array() );

			$hide_template_ids = array_diff( $hide_template_ids, array( $_REQUEST['code'] ) );

			update_option( 'mssms_hide_template_ids', $hide_template_ids );

			$templates = get_option( 'mssms_template_lists', array() );

			$templates[ $_REQUEST['code'] ]['visibility'] = 'SHOW';

			update_option( 'mssms_template_lists', $templates );

			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );

		}
	}
	static function alimtalk_add_template() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			if ( 'IMAGE' == $_REQUEST['emphasize_type'] ) {
				if ( empty( $_REQUEST['image_name'] ) || empty( $_REQUEST['image_url'] ) ) {
					throw new Exception( __( '강조표시타입이 이미지인 경우, 이미지 파일을 먼저 업로드 하셔야 합니다.', '##PKGNAMEW##' ) );
				}
			}
			$buttons = array();
			if ( ! empty( $_REQUEST['buttons'] ) ) {
				$idx = 1;
				foreach ( $_REQUEST['buttons'] as $button ) {
					$buttons[] = array(
						'ordering'      => $idx++,
						'type'          => mssms_get( $button, 'type' ),
						'name'          => 'AC' == mssms_get( $button, 'type' ) ? '채널 추가' : mssms_get( $button, 'name' ),
						'linkMo'        => mssms_get( $button, 'linkMo' ),
						'linkPc'        => mssms_get( $button, 'linkPc' ),
						'schemeIos'     => mssms_get( $button, 'schemeIos' ),
						'schemeAndroid' => mssms_get( $button, 'schemeAndroid' )
					);
				}
			}

			$result = MSSMS_API_Kakao::add_template( $_REQUEST, $buttons );

			wp_send_json_success( $result );
		} catch ( Exception $e ) {
			$message = sprintf( '템플릿 등록 오류 : %s', $e->getMessage() );
			wp_send_json_error( $message );

		}
	}

	static function alimtalk_authentication_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$params = json_decode( stripslashes( mssms_get( $_REQUEST, 'params' ) ), true );

			$plus_id            = $params['plus_id'];
			$admin_phone_number = $params['admin_phone_number'];
			$category           = $params['category'];

			if ( empty( $plus_id ) ) {
				throw new Exception( __( '플러스친구 아이디를 입력해주세요.' ) );
			}

			if ( empty( $admin_phone_number ) ) {
				throw new Exception( __( '관리자 전화번호를 입력해주세요.' ) );
			}

			if ( empty( $category ) ) {
				throw new Exception( __( '카테고리를 선택해주세요.' ) );
			}

			if ( 0 !== strpos( $plus_id, '@' ) ) {
				$plus_id = '@' . $plus_id;
			}

			MSSMS_API_Kakao::authentication_request( $plus_id, $admin_phone_number, $category );

			wp_send_json_success( array( 'message' => __( "인증번호가 전송되었습니다.\n인증번호를 입력하신 후, 심사요청 버튼을 클릭해주세요.", 'mshop-sms-s2' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	static function alimtalk_update_resend() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$params = json_decode( stripslashes( mssms_get( $_REQUEST, 'params' ) ), true );

			$plus_id        = $params['plus_id'];
			$is_resend      = $params['is_resend'];
			$resend_send_no = $params['resend_send_no'];

			if ( 'yes' == $is_resend && empty( $resend_send_no ) ) {
				throw new Exception( __( '발신번호를 입력해주세요.' ) );
			}

			if ( 0 !== strpos( $plus_id, '@' ) ) {
				$plus_id = '@' . $plus_id;
			}

			MSSMS_API_Kakao::update_resend( $plus_id, $is_resend, $resend_send_no );

			wp_send_json_success( array( 'message' => __( "대체발송 설정이 업데이트 되었습니다.", 'mshop-sms-s2' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	static function alimtalk_add_profile() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$params = json_decode( stripslashes( mssms_get( $_REQUEST, 'params' ) ), true );

			$plus_id            = $params['plus_id'];
			$admin_phone_number = $params['admin_phone_number'];
			$auth_number        = $params['auth_number'];
			$category           = $params['category'];

			if ( empty( $plus_id ) ) {
				throw new Exception( __( '플러스친구 아이디를 입력해주세요.' ) );
			}

			if ( empty( $admin_phone_number ) ) {
				throw new Exception( __( '관리자 전화번호를 입력해주세요.' ) );
			}

			if ( empty( $category ) ) {
				throw new Exception( __( '카테고리를 선택해주세요.' ) );
			}

			if ( empty( $auth_number ) ) {
				throw new Exception( __( '인증번호를 입력해주세요.' ) );
			}

			if ( 0 !== strpos( $plus_id, '@' ) ) {
				$plus_id = '@' . $plus_id;
			}

			MSSMS_API_Kakao::add_profile( $plus_id, $admin_phone_number, $category, $auth_number );

			wp_send_json_success( array( 'message' => __( "플러스친구가 등록되었습니다.", 'mshop-sms-s2' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	static function user_search() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		global $wpdb;

		$results = array();

		$keyword = isset( $_REQUEST['args'] ) ? esc_attr( $_REQUEST['args'] ) : '';

		$sql = "SELECT user.ID
				FROM {$wpdb->users} user
				WHERE
				    user.user_login like '%{$keyword}%'
				    OR user.user_nicename like '%{$keyword}%'
				    OR user.display_name like '%{$keyword}%'
				    OR user.user_email like '%{$keyword}%'
				ORDER BY ID
				LIMIT 20";


		$user_ids = $wpdb->get_col( $sql );

		foreach ( $user_ids as $user_id ) {
			$user      = get_user_by( 'id', $user_id );
			$results[] = array(
				"value" => $user_id,
				"name"  => get_user_meta( $user->ID, 'billing_phone', true ) . ',' . $user->data->display_name
			);
		}

		$respose = array(
			'success' => true,
			'results' => $results
		);

		echo json_encode( $respose );

		die();
	}

	protected static function get_upload_dir_path() {
		$upload_dir      = wp_get_upload_dir();
		$upload_dir_path = $upload_dir['basedir'] . '/mshop-sms/';

		if ( ! file_exists( $upload_dir_path ) ) {
			mkdir( $upload_dir_path );
		}

		return $upload_dir_path;
	}

	protected static function get_upload_file_path( $attached_file ) {
		return self::get_upload_dir_path() . $attached_file['name'];
	}
	protected static function move_attached_file( $attached_file ) {
		mkdir( self::get_upload_dir_path() );

		if ( ! move_uploaded_file( $attached_file['tmp_name'], self::get_upload_file_path( $attached_file ) ) ) {
			throw new Exception( '파일 업로드중 오류가 발생했습니다.', '10002' );
		}
	}

	static function sms_register_send_no() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		$attached_file = '';

		try {
			$params = json_decode( stripslashes( $_POST['params'] ), true );

			if ( empty( $params['send_no'] ) ) {
				throw new Exception( __( '발신번호를 입력해주세요.', 'mshop-sms-s2' ) );
			}
			$attached_file = current( $_FILES );
			if ( $attached_file['size'] > 300 * 1024 ) {
				throw new Exception( __( '첨부 파일의 최대 크기는 300K 입니다.', 'mshop-sms-s2' ) );
			}

			self::move_attached_file( $attached_file );

			MSSMS_API_SMS::register_send_no( $params['send_no'], self::get_upload_file_path( $attached_file ) );

			unlink( self::get_upload_file_path( $attached_file ) );
			wp_send_json_success( array( 'message' => __( "발신번호 등록 요청이 접수되었습니다.", 'mshop-sms-s2' ) ) );
		} catch ( Exception $e ) {
			unlink( self::get_upload_file_path( $attached_file ) );

			wp_send_json_error( $e->getMessage() );
		}
	}

	static function send_messages() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$request_date      = '';
			$attached_file_ids = array_filter( array( mssms_get( $_POST, 'file_id_0' ), mssms_get( $_POST, 'file_id_1' ) ) );

			if ( empty( $_REQUEST['sender_number'] ) ) {
				throw new Exception( __( '발신번호를 선택해주세요.', 'mshop-sms-s2' ) );
			}

			if ( empty( $_REQUEST['customer_list'] ) ) {
				throw new Exception( __( '수신자 정보를 입력해주세요.', 'mshop-sms-s2' ) );
			}

			if ( empty( $_REQUEST['messages'] ) ) {
				throw new Exception( __( '고객에게 보낼 메시지를 입력해주세요.', 'mshop-sms-s2' ) );
			}

			if ( empty( $_REQUEST['title'] ) ) {
				throw new Exception( __( '문자 제목을 입력 해 주세요.', 'mshop-sms-s2' ) );
			}

			if ( 'MMS' == $_REQUEST['message_type'] && empty( $attached_file_ids ) ) {
				throw new Exception( __( '첨부 파일을 등록해주세요.', 'mshop-sms-s2' ) );
			}

			$recipients = array_map( function ( $receiver ) {
				return array(
					'receiver'        => mssms_get( $receiver, 'phone_number' ),
					'template_params' => mssms_parse_template_params( mssms_get( $receiver, 'customer_name' ) )
				);
			}, $_REQUEST['customer_list'] );

			if ( 'reserved' == $_REQUEST['send_type'] ) {
				$request_date = $_REQUEST['request_date'];
			}

			MSSMS_SMS::send_sms( $_REQUEST['message_type'], $_REQUEST['title'], stripslashes( $_REQUEST['messages'] ), $recipients, $_REQUEST['sender_number'], $request_date, false, $attached_file_ids );

			if ( 'reserved' == $_REQUEST['send_type'] ) {
				wp_send_json_success( array( 'message' => __( "문자가 예약되었습니다. 발송 결과는 발송 로그에서 확인 해 주세요.", 'mshop-sms-s2' ) ) );
			} else {
				wp_send_json_success( array( 'message' => __( "고객에게 문자가 발송되었습니다. 발송결과는 발송로그에서 확인해주세요.", 'mshop-sms-s2' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	static function send_alimtalk() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		try {
			$request_date = '';

			if ( empty( $_POST['plus_id'] ) ) {
				throw new Exception( __( '플러스친구 아이디를 선택해주세요.', 'mshop-sms-s2' ) );
			}

			if ( empty( $_POST['template'] ) ) {
				throw new Exception( __( '알림톡 템플릿을 선택해주세요.', 'mshop-sms-s2' ) );
			}

			if ( empty( $_POST['customer_list'] ) ) {
				throw new Exception( __( '수신자 정보를 입력해주세요.', 'mshop-sms-s2' ) );
			}

			$profile = MSSMS_Kakao::get_profile( $_POST['plus_id'] );

			if ( empty( $profile ) ) {
				throw new Exception( __( '등록되지 않은 플러스친구 아이디입니다.', 'mshop-sms-s2' ) );
			}

			if ( 'yes' == mssms_get( $profile, 'is_resend' ) ) {
				$resend_params = array(
					'isResend'     => 'true',
					'resendSendNo' => $profile['resend_send_no']
				);
			} else {
				$resend_params = array( 'isResend' => 'false' );
			}


			$messages = array_map( function ( $receiver ) use ( $resend_params ) {
				return array(
					'receiver'        => mssms_get( $receiver, 'phone_number' ),
					'template_params' => mssms_parse_template_params( mssms_get( $receiver, 'customer_name' ) ),
					"resend"          => $resend_params
				);
			}, $_REQUEST['customer_list'] );

			if ( 'reserved' == $_REQUEST['send_type'] ) {
				$request_date = $_REQUEST['request_date'];
			} else {
				$request_date = MSSMS_Manager::get_request_date();
			}

			MSSMS_API_Kakao::send_message( $_POST['plus_id'], $_POST['template'], $request_date, $messages );

			if ( 'reserved' == $_REQUEST['send_type'] ) {
				wp_send_json_success( array( 'message' => __( "알림톡 발송이 예약되었습니다. 발송 결과는 발송 로그에서 확인 해 주세요.", 'mshop-sms-s2' ) ) );
			} else {
				wp_send_json_success( array( 'message' => __( "고객에게 알림톡이 발송되었습니다. 발송결과는 발송로그에서 확인해주세요.", 'mshop-sms-s2' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}
	static function target_search_category( $depth = 0, $parent = 0 ) {
		$args = array();

		if ( ! empty( $_REQUEST['args'] ) ) {
			$args['name__like'] = $_REQUEST['args'];
		}

		$results = self::make_taxonomy_tree( 'product_cat', $args );

		$respose = array(
			'success' => true,
			'results' => $results
		);

		echo json_encode( $respose );
		die();
	}
	static function make_taxonomy_tree( $taxonomy, $args, $depth = 0, $parent = 0, $paths = array() ) {
		$results = array();

		$args['parent'] = $parent;
		$terms          = get_terms( $taxonomy, $args );

		foreach ( $terms as $term ) {
			$current_paths = array_merge( $paths, array( $term->name ) );
			$results[]     = array(
				"name"  => '<span class="tree-indicator-desc">' . implode( '-', $current_paths ) . '</span><span class="tree-indicator" style="margin-left: ' . ( $depth * 8 ) . 'px;">' . $term->name . '</span>',
				"value" => $term->term_id
			);

			$results = array_merge( $results, self::make_taxonomy_tree( $taxonomy, $args, $depth + 1, $term->term_id, $current_paths ) );
		}

		return $results;
	}
	static function target_search_posts_title_like( $where, &$wp_query ) {
		global $wpdb;
		if ( $posts_title = $wp_query->get( 'posts_title' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE "%' . $posts_title . '%"';
		}

		return $where;
	}

	static function target_search_product( $product_type = '' ) {
		$keyword = ! empty( $_REQUEST['args'] ) ? $_REQUEST['args'] : '';

		add_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ), 10, 2 );
		$args = array(
			'post_type'      => 'product',
			'posts_title'    => $keyword,
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => 20
		);

		if ( ! empty( $product_type ) ) {
			if ( 'subscription' == $product_type ) {
				$product_type = array(
					'subscription',
					'variable-subscription'
				);
			}

			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $product_type,
				),
			);
		}

		$query = new WP_Query( $args );

		remove_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ) );

		$results = array();

		foreach ( $query->posts as $post ) {
			$results[] = array(
				"name"  => '[#' . $post->ID . '] ' . $post->post_title,
				"value" => $post->ID
			);
		}
		$respose = array(
			'success' => true,
			'results' => $results
		);

		echo json_encode( $respose );

		die();
	}


	public static function target_search() {
		if ( ! empty( $_REQUEST['type'] ) ) {
			$type = $_REQUEST['type'];

			switch ( $type ) {
				case 'product' :
				case 'product-category' :
					self::target_search_product();
					break;
				case 'subscription' :
					self::target_search_product( 'subscription' );
					break;
				case 'category' :
					self::target_search_category();
					break;
				default:
					die();
					break;
			}
		}
	}

	public static function get_users_by_role() {
		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['search_role'] ) ) {
			die();
		}

		$role = $_POST['search_role'];

		$user_ids = get_users( array(
			'role'       => $role,
			'fields'     => 'ID',
			'number'     => -1,
			'meta_query' => array(
				'relation' => 'OR', // Optional, defaults to "AND"
				array(
					'key'     => 'mssms_agreement',
					'value'   => 'on',
					'compare' => '='
				),
				array(
					'key'     => 'mssms_agreement',
					'compare' => 'NOT EXISTS'
				)
			)
		) );

		$results = array_map( function ( $user_id ) {
			$billing_phone = get_user_meta( $user_id, 'billing_phone', true );

			if ( ! empty( $billing_phone ) /* && 'yes' == get_user_meta( $user_id, '_mssms_agree_to_receive', 'yes' ) */ ) {
				$first_name = get_user_meta( $user_id, 'billing_first_name', true );
				$last_name  = get_user_meta( $user_id, 'billing_last_name', true );

				return $billing_phone . ',' . $first_name . $last_name;
			}
		}, $user_ids );

		$results = array_filter( $results );

		if ( ! empty( $results ) ) {
			wp_send_json_success( array_filter( $results ) );
		} else {
			wp_send_json_success( __( '해당 등급에 해당하는 사용자가 없습니다.', 'mshop-sms-s2' ) );
		}
	}
	public static function get_users_by_product() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['search_product'] ) ) {
			die();
		}

		$product_ids = array_keys( $_POST['search_product'] );

		$customer_ids = $wpdb->get_col( "
						SELECT DISTINCT ordermeta.meta_value
						FROM {$wpdb->prefix}woocommerce_order_items items
						LEFT JOIN {$wpdb->prefix}postmeta ordermeta ON ordermeta.post_id = items.order_id AND ordermeta.meta_key = '_customer_user'
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta itemmeta ON items.order_item_id = itemmeta.order_item_id AND itemmeta.meta_key = '_product_id'
						WHERE 
						    itemmeta.meta_value IN ( " . implode( ",", $product_ids ) . " )" );

		$customer_ids = array_filter( $customer_ids );

		$results = array_map( function ( $customer_id ) {
			$billing_phone = get_user_meta( $customer_id, 'billing_phone', true );

			if ( ! empty( $billing_phone ) ) {
				$first_name = get_user_meta( $customer_id, 'billing_first_name', true );
				$last_name  = get_user_meta( $customer_id, 'billing_last_name', true );

				return $billing_phone . ',' . $first_name . $last_name;
			}
		}, $customer_ids );

		$results = array_filter( $results );

		if ( ! empty( $results ) ) {
			wp_send_json_success( array_filter( $results ) );
		} else {
			wp_send_json_error( __( '해당 상품을 구매한 사용자가 없습니다.', 'mshop-sms-s2' ) );
		}
	}
	public static function reset_renewal_notification() {
		check_ajax_referer( 'reset_renewal_notification' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		as_unschedule_all_actions( 'mssms_renewal_notification' );

		wp_send_json_success( array( 'message' => __( '갱신결제 사전알림 예약작업이 초기화되었습니다.', 'mshop-sms-s2' ) ) );
	}
	public static function register_renewal_notification() {
		check_ajax_referer( 'reset_renewal_notification' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		$ids = wc_get_orders( array(
			'post_type'   => 'shop_subscription',
			'post_status' => array( 'wc-active' ),
			'return'      => 'ids',
			'limit'       => apply_filters( 'mssms_register_renewal_notification_batch_size', 50 ),
			'page'        => $_POST['page']
		) );

		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$subscription = wc_get_order( $id );

				MSSMS_Subscription::maybe_register_notification_action( $subscription, 'next_payment', $subscription->get_date( 'next_payment' ) );
			}
			wp_send_json_success( array( 'continue' => true ) );
		} else {
			wp_send_json_success( array( 'message' => __( '갱신결제 사전알림 예약작업이 재등록되었습니다.', 'mshop-sms-s2' ) ) );
		}
	}

	static function upload_template_image_file() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		$attached_file = '';

		try {
			$attached_file = current( $_FILES );

			self::move_attached_file( $attached_file );

			$response = MSSMS_API_Kakao::upload_template_image_file( self::get_upload_file_path( $attached_file ) );

			unlink( self::get_upload_file_path( $attached_file ) );

			$change_sets = array();

			foreach ( $response as $cid => $value ) {
				$change_sets[] = array(
					'cid'   => $cid,
					'value' => $value
				);
			}

			wp_send_json_success( array( 'change_sets' => $change_sets ) );
		} catch ( Exception $e ) {
			unlink( self::get_upload_file_path( $attached_file ) );

			wp_send_json_error( $e->getMessage() );
		}
	}

	static function upload_mms_attached_file() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}

		$change_sets[] = array();

		try {
			$userdata = get_userdata( get_current_user_id() );

			foreach ( $_FILES as $attached_file ) {
				if ( 'image/jpeg' != $attached_file['type'] ) {
					throw new Exception( __( '첨부파일은 jpg 또는 jpeg 파일만 가능합니다.', 'mshop-sms-s2' ) );
				}

				if ( $attached_file['size'] / 1024 > 300 ) {
					throw new Exception( __( '첨부파일의 최대 크기는 300KB 입니다.', 'mshop-sms-s2' ) );
				}

				$image_info = getimagesize( $attached_file['tmp_name'] );
				if ( $image_info[0] > 1000 || $image_info[1] > 1000 ) {
					throw new Exception( __( '첨부파일의 최대 해상도는 1000x1000 입니다.', 'mshop-sms-s2' ) );
				}

			}

			$attached_file_info = array();
			$idx                = 0;
			foreach ( $_FILES as $attached_file ) {
				self::move_attached_file( $attached_file );

				$content = file_get_contents( self::get_upload_file_path( $attached_file ) );

				$response = MSSMS_API_SMS::upload_mms_attached_file( $attached_file['name'], $userdata->display_name, base64_encode( $content ) );

				$change_sets[] = array(
					'cid'   => 'file_id_' . $idx++,
					'value' => $response['file_id']

				);

				$attached_file_info[] = sprintf( "[#%s] %s", $response['file_id'], $response['file_name'] );

				unlink( self::get_upload_file_path( $attached_file ) );
			}

			if ( 1 == count( $_FILES ) ) {
				$change_sets[] = array(
					'cid'   => 'file_id_1',
					'value' => ''

				);
			}

			$change_sets[] = array(
				'cid'   => 'attached_file',
				'value' => implode( ', ', $attached_file_info )

			);

			wp_send_json_success( array( 'change_sets' => $change_sets ) );
		} catch ( Exception $e ) {
			unlink( self::get_upload_file_path( $attached_file ) );

			wp_send_json_error( $e->getMessage() );
		}
	}
}

MSSMS_Ajax::init();
