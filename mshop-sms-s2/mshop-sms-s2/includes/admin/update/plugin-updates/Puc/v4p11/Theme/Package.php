<?php
if ( !class_exists('Puc_v4p11_Theme_Package', false) ):

	class Puc_v4p11_Theme_Package extends Puc_v4p11_InstalledPackage {
		protected $stylesheet;
		protected $theme;

		public function __construct($stylesheet, $updateChecker) {
			$this->stylesheet = $stylesheet;
			$this->theme = wp_get_theme($this->stylesheet);

			parent::__construct($updateChecker);
		}

		public function getInstalledVersion() {
			return $this->theme->get('Version');
		}

		public function getAbsoluteDirectoryPath() {
			if ( method_exists($this->theme, 'get_stylesheet_directory') ) {
				return $this->theme->get_stylesheet_directory(); //Available since WP 3.4.
			}
			return get_theme_root($this->stylesheet) . '/' . $this->stylesheet;
		}
		public function getHeaderValue($headerName, $defaultValue = '') {
			$value = $this->theme->get($headerName);
			if ( ($headerName === false) || ($headerName === '') ) {
				return $defaultValue;
			}
			return $value;
		}

		protected function getHeaderNames() {
			return array(
				'Name'        => 'Theme Name',
				'ThemeURI'    => 'Theme URI',
				'Description' => 'Description',
				'Author'      => 'Author',
				'AuthorURI'   => 'Author URI',
				'Version'     => 'Version',
				'Template'    => 'Template',
				'Status'      => 'Status',
				'Tags'        => 'Tags',
				'TextDomain'  => 'Text Domain',
				'DomainPath'  => 'Domain Path',
			);
		}
	}

endif;
