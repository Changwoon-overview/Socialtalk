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

if ( ! class_exists( 'MSSMS_API_Kakao' ) ) {

	class MSSMS_API_Kakao {
		protected static function get_api_url() {
			return 'https://message.codemshop.com/';
		}
		protected static function get_default_args() {
			$license_info = json_decode( get_option( 'msl_license_' . MSSMS()->slug(), null ) );

			return array(
				'service'        => 'alimtalk',
				'version'        => '2.2.1',
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
						do_action( 'mssms_alimtalk_response', $response );

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
		public static function authentication_request( $plus_id, $phone_number, $category_id ) {
			$result = self::call( array(
				'command'       => 'auth_request',
				'plus_id'       => $plus_id,
				'phone_number'  => $phone_number,
				'category_code' => $category_id
			) );

			return $result;
		}
		public static function update_resend( $plus_id, $is_resend, $resend_send_no ) {
			$result = self::call( array(
				'command'        => 'update_resend',
				'plus_id'        => $plus_id,
				'is_resend'      => $is_resend,
				'resend_send_no' => $resend_send_no
			) );

			return $result;
		}
		public static function get_category() {
			$result = self::call( array(
				'command' => 'get_category'
			) );

			return $result;
		}
		public static function add_profile( $plus_id, $phone_number, $category_id, $auth_number ) {
			$result = self::call( array(
				'command'       => 'add_profile',
				'plus_id'       => $plus_id,
				'phone_number'  => $phone_number,
				'category_code' => $category_id,
				'auth_number'   => $auth_number,
			) );

			return $result;
		}
		public static function get_profile_list() {
			$result = self::call( array(
				'command' => 'get_profile_list'
			) );

			return $result;
		}
		public static function get_template_list( $plus_id ) {
			$result = self::call( array(
				'command' => 'get_template_list',
				'plus_id' => $plus_id
			) );

			return $result;
		}
		public static function add_template( $args, $template_button ) {
			$result = self::call( array(
				'command'                 => 'add_template',
				'plus_id'                 => $args['plus_id'],
				'template_name'           => stripslashes( $args['name'] ),
				'template_content'        => stripslashes( $args['content'] ),
				'template_message_type'   => $args['message_type'],
				'template_extra'          => $args['extra'],
				'template_ad'             => $args['ad'],
				'template_emphasize_type' => $args['emphasize_type'],
				'template_title'          => stripslashes( $args['title'] ),
				'template_subtitle'       => stripslashes( $args['subtitle'] ),
				'template_image_name'     => stripslashes( $args['image_name'] ),
				'template_image_url'      => stripslashes( $args['image_url'] ),
				'template_buttons'        => $template_button,
			) );

			return $result;
		}
		public static function modify_template( $args, $template_button ) {
			$result = self::call( array(
				'command'                 => 'modify_template',
				'template_code'           => $args['code'],
				'plus_id'                 => $args['plus_id'],
				'template_name'           => stripslashes( $args['name'] ),
				'template_content'        => stripslashes( $args['content'] ),
				'template_message_type'   => $args['message_type'],
				'template_extra'          => $args['extra'],
				'template_ad'             => $args['ad'],
				'template_emphasize_type' => $args['emphasize_type'],
				'template_title'          => stripslashes( $args['title'] ),
				'template_subtitle'       => stripslashes( $args['subtitle'] ),
				'template_image_name'     => stripslashes( $args['image_name'] ),
				'template_image_url'      => stripslashes( $args['image_url'] ),
				'template_button'         => $template_button,
			) );

			return $result;
		}
		public static function delete_template( $plus_id, $template_code ) {
			$result = self::call( array(
				'command'       => 'delete_template',
				'plus_id'       => $plus_id,
				'template_code' => $template_code,
			) );

			return $result;
		}
		public static function request_template( $plus_id, $template_code ) {
			$result = self::call( array(
				'command'       => 'request_template',
				'plus_id'       => $plus_id,
				'template_code' => $template_code,
			) );

			return $result;
		}
		public static function send_alimtalk( $plus_id, $template_code, $request_data, $messages ) {
			$result = self::call( array(
				'command'       => 'send_alimtalk',
				'plus_id'       => $plus_id,
				'template_code' => $template_code,
				'request_date'  => $request_data,
				'messages'      => $messages
			) );

			return $result;
		}
		public static function send_raw_message( $plus_id, $template_code, $request_data, $recipients ) {
			$result = self::call( array(
				'command'       => 'send_raw_message',
				'plus_id'       => $plus_id,
				'template_code' => $template_code,
				'request_date'  => $request_data,
				'recipients'    => $recipients
			) );

			return $result;
		}
		public static function send_message( $plus_id, $template_code, $request_data, $recipients, $is_auth = false ) {
			$result = self::call( array(
				'command'       => 'send_message',
				'plus_id'       => $plus_id,
				'template_code' => $template_code,
				'request_date'  => $request_data,
				'recipients'    => $recipients,
				'is_auth'       => $is_auth
			) );

			return $result;
		}
		public static function get_send_result( $sender, $type, $sms_type, $receiver, $state, $page, $date_from, $date_to, $message ) {
			$result = self::call( array(
				'command'   => 'get_send_result',
				'sender'    => $sender,
				'type'      => $type,
				'sms_type'  => $sms_type,
				'receiver'  => $receiver,
				'state'     => $state,
				'page'      => $page,
				'date_from' => $date_from,
				'date_to'   => $date_to,
				'message'   => $message
			) );

			return $result;
		}
		public static function upload_template_image_file( $attached_file ) {
			$attached_file = curl_file_create( $attached_file );

			return self::curl_call( array(
				'command'       => 'upload_template_image_file',
				'attached_file' => $attached_file
			) );
		}
	}
}


