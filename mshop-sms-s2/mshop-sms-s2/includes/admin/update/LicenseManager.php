<?php

if ( ! class_exists( 'MSHOP_SMS_LicenseManager' ) ) {

	class MSHOP_SMS_LicenseManager {
		private $rev = '2020';

		private $slug;

		private $license_server_url;
		private $update_server_url;

		public function __construct( $slug, $dir, $file ) {
			$this->slug = $slug;

			$this->license_server_url = 'https://lic.codemshop.com/manager_' . $this->rev;
			$this->update_server_url  = 'https://lic.codemshop.com/update';

			require 'plugin-updates/plugin-update-checker.php';

			$license_info = get_option( 'msl_license_' . $this->slug, null );
			if ( $license_info ) {
				$license_info = json_decode( $license_info );
			}

			Puc_v4_Factory::buildUpdateChecker(
				$this->update_server_url . '?action=get_metadata&slug=' . $this->slug . '&license_key=' . ( $license_info ? $license_info->license_key : '' ) . '&activation_key=' . ( $license_info ? $license_info->activation_key : '' ) . '&domain=' . $this->get_site_url(),
				$file,
				$this->slug
			);

			add_action( "in_plugin_update_message-" . basename( $dir ) . '/' . basename( $file ), array( $this, "in_plugin_update_message" ), 10, 2 );
			add_action( 'wp_ajax_msl_activation_' . $this->slug, array( &$this, 'msl_activation' ) );
			add_action( 'wp_ajax_msl_verify_' . $this->slug, array( &$this, 'msl_verify' ) );
			add_action( 'wp_ajax_msl_reset_' . $this->slug, array( &$this, 'msl_reset' ) );
		}

		protected function get_site_url() {
			$site_url = home_url();

			return preg_replace( '#^https?://#', '', $site_url );
		}

		public function in_plugin_update_message( $plugin_data, $r ) {
			if ( ! empty( $plugin_data['upgrade_notice'] ) ) {
				echo '<br>' . $plugin_data['upgrade_notice'];
			}
		}

		public function msl_activation() {
			$license_key = $_REQUEST['msl_license_key'];
			$site_url    = $this->get_site_url();
			$data        = empty( $_REQUEST['msl_data'] ) ? '' : json_encode( $_REQUEST['msl_data'] );

			$response = wp_remote_post( $this->license_server_url, array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array( 'action' => 'activation', 'slug' => $this->slug, 'license_key' => $license_key, 'domain' => $site_url, 'data' => $data ),
					'cookies'     => array()
				)
			);

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$result        = $error_message;
			} else {
				$result = json_decode( $response['body'] );
			}

			if ( ! empty( $result ) && $result->result >= 0 ) {
				$license_info = array(
					'site_url'       => $site_url,
					'slug'           => $this->slug,
					'license_key'    => $license_key,
					'activation_key' => $result->activation_key,
					'expire_date'    => $result->expire_date,
					'point'          => number_format( intval( $result->point ) ),
					'status'         => $result->result,
					'data'           => ! empty( $_REQUEST['msl_data'] ) ? json_encode( $_REQUEST['msl_data'] ) : ''
				);

				update_option( 'msl_license_' . $this->slug, json_encode( $license_info ) );

				wp_send_json_success( array( 'message' => $result->notice, 'licenseInfo' => $license_info ) );
			} else {
				wp_send_json_error( array( 'message' => ! empty( $result->notice ) ? $result->notice : '오류가 발생했습니다. 잠시 후 다시 시도해주세요.' ) );
			}
		}

		public function msl_reset() {
			delete_option( 'msl_license_' . $this->slug );

			wp_send_json_success();
		}
		public function get_license() {
			$license_info = apply_filters( 'mshop_get_license', null, $this->slug );

			if ( is_null( $license_info ) ) {
				$license_info = get_option( 'msl_license_' . $this->slug, null );
				$site_url     = $this->get_site_url();

				if ( $license_info ) {
					$license_info = json_decode( $license_info, true );

					$response = wp_remote_post( $this->license_server_url, array(
							'method'      => 'POST',
							'timeout'     => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'blocking'    => true,
							'headers'     => array(),
							'body'        => array( 'action' => 'verify', 'slug' => $license_info['slug'], 'license_key' => $license_info['license_key'], 'domain' => $site_url, 'activation_key' => $license_info['activation_key'] ),
							'cookies'     => array()
						)
					);

					if ( is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						throw new Exception( '[9000] 라이센스정보를 확인할 수 없습니다. 잠시 후, 다시 시도해주세요.(' . $error_message . ')' );
					} else {
						$result = json_decode( $response['body'] );

						$license_info['status'] = $result->result;
						$license_info['point']  = number_format( intval( $result->point ) );

						update_option( 'msl_license_' . $this->slug, json_encode( $license_info ) );

						return $license_info;
					}
				}

				throw new Exception( '[90001] 라이센스정보가 없습니다.' );
			}

			return $license_info;
		}

		public function msl_verify() {
			try {
				$license_info = self::get_license();
				wp_send_json_success( $license_info );
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}
	}

}