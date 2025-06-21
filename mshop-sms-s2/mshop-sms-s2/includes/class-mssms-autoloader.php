<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class MSSMS_Autoloader {
	private $include_path = '';
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( MSSMS_PLUGIN_FILE ) ) . '/includes/';
	}
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( strpos( $class, 'mssms_') === FALSE ){
			return;
		}

		if ( strpos( $class, 'mssms_admin' ) === 0 ) {
			$this->load_file( $this->include_path . 'admin/' . $file );
			return;
		}elseif ( strpos( $class, 'mssms_api' ) === 0 ) {
			$path = $this->include_path . 'api/';
		}elseif ( strpos( $class, 'mssms_settings' ) === 0 ) {
			$path = $this->include_path . 'admin/settings/';
		}elseif ( strpos( $class, 'mssms_message' ) === 0 ) {
			$path = $this->include_path . 'message/';
		}elseif ( strpos( $class, 'mssms_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		}

		if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && strpos( $class, 'mssms_' ) === 0 ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new MSSMS_Autoloader();
