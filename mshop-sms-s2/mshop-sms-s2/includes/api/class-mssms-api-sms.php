<?php

/*
=====================================================================================
                ﻿엠샵 문자 알림톡 자동 발송 / Copyright 2015 by CodeM(c)
=====================================================================================

  [ 우커머스 버전 지원 안내 ]

   워드프레스 버전 : WordPress 4.3.1

   우커머스 버전 : WooCommerce 2.4.7


  [ 코드엠 플러그인 라이센스 규정 ]

   (주)코드엠에서 개발된 워드프레스  플러그인을 사용하시는 분들에게는 다음 사항에 대한 동의가 있는 것으로 간주합니다.

   1. 코드엠에서 개발한 워드프레스 우커머스용 엠샵 문자 알림톡 자동 발송 플러그인의 저작권은 (주)코드엠에게 있습니다.
   
   2. 플러그인은 사용권을 구매하는 것이며, 프로그램 저작권에 대한 구매가 아닙니다.

   3. 플러그인을 구입하여 다수의 사이트에 복사하여 사용할 수 없으며, 1개의 라이센스는 1개의 사이트에만 사용할 수 있습니다. 
      이를 위반 시 지적 재산권에 대한 손해 배상 의무를 갖습니다.

   4. 플러그인은 구입 후 1년간 업데이트를 지원합니다.

   5. 플러그인은 워드프레스, 테마, 플러그인과의 호환성에 대한 책임이 없습니다.

   6. 플러그인 설치 후 버전에 관련한 운용 및 관리의 책임은 사이트 당사자에게 있습니다.

   7. 다운로드한 플러그인은 환불되지 않습니다.

=====================================================================================
*/

if ( ! class_exists( 'MSSMS_API_SMS' ) ) {

	class MSSMS_API_SMS {
		protected static function get_api_url() {
			return 'https://message.codemshop.com/';
		}
		protected static function get_default_args() {
			$license_info = json_decode( get_option( 'msl_license_' . MSSMS()->slug(), null ) );

			return array(
				'service'        => 'sms',
				'version'        => '3.0.1',
				'license_key'    => $license_info->license_key,
				'activation_key' => $license_info->activation_key,
				'domain'         => $license_info->site_url,
				'slug'           => MSSMS()->slug()
			);
		}
		public static function call( $args = array() ) {
			$args = array_merge( $args, self::get_default_args() );

			$response = wp_remote_post( self::get_api_url(), array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => $args,
				'cookies'     => array()
			) );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			} else {
				if ( '200' == $response['response']['code'] ) {
					$response = json_decode( $response['body'], true );

					if ( '0000' == $response['code'] ) {
						do_action( 'mssms_sms_response', $response );

						return $response['data'];
					} else {
						$message = sprintf( '[%s] %s', $response['code'], $response['message'] );
						throw new Exception( $message );
					}
				} else {
					$message = sprintf( '[%s] %s', $response['response']['code'], $response['response']['message'] );
					throw new Exception( $message );
				}
			}
		}
		public static function curl_call( $args = array() ) {
			$args = array_merge( $args, self::get_default_args() );

			$cl = curl_init();

			curl_setopt( $cl, CURLOPT_URL, self::get_api_url() );
			curl_setopt( $cl, CURLOPT_POST, 1 );
			curl_setopt( $cl, CURLOPT_POSTFIELDS, $args );
			curl_setopt( $cl, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $cl, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $cl, CURLOPT_RETURNTRANSFER, true );

			$result = curl_exec( $cl );

			curl_close( $cl );

			$response = json_decode( $result, true );

			if ( $response ) {
				if ( '0000' == $response['code'] ) {
					return $response['data'];
				} else {
					$message = sprintf( '[%s] %s', $response['code'], $response['message'] );
					throw new Exception( $message );
				}
			} else {
				$message = curl_error( $cl );
				throw new Exception( $message );
			}
		}
		public static function get_send_no_list() {
			return self::call( array(
				'command' => 'get_send_no_list'
			) );
		}
		public static function register_send_no( $send_no, $attached_file ) {
			$attached_file = curl_file_create( $attached_file );

			return self::curl_call( array(
				'command'       => 'register_send_no',
				'send_no'       => $send_no,
				'attached_file' => $attached_file
			) );
		}
		public static function get_send_no_request_list() {
			return self::call( array(
				'command' => 'get_send_no_request_list'
			) );
		}
		public static function send_message( $type, $send_no, $title, $message, $recipients, $request_date, $is_auth = false, $attached_file_ids = array() ) {
			return self::call( array(
				'command'           => 'send_message',
				'type'              => $type,
				'send_no'           => $send_no,
				'title'             => empty( $title ) ? sprintf( '[%s]', apply_filters( 'mssms_sms_title', get_option( "blogname" ) ) ) : $title,
				'message'           => $message,
				'recipients'        => $recipients,
				'request_date'      => $request_date,
				'is_auth'           => $is_auth,
				'attached_file_ids' => $attached_file_ids
			) );
		}
		public static function upload_mms_attached_file( $file_name, $create_user, $file_body ) {
			return self::call( array(
				'command'     => 'upload_mms_attached_file',
				'file_name'   => $file_name,
				'create_user' => $create_user,
				'file_body'   => $file_body,
			) );
		}

	}
}


