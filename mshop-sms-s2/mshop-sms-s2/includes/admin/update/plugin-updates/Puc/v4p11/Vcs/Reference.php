<?php
if ( !class_exists('Puc_v4p11_Vcs_Reference', false) ):
	class Puc_v4p11_Vcs_Reference {
		private $properties = array();

		public function __construct($properties = array()) {
			$this->properties = $properties;
		}
		public function __get($name) {
			return array_key_exists($name, $this->properties) ? $this->properties[$name] : null;
		}
		public function __set($name, $value) {
			$this->properties[$name] = $value;
		}
		public function __isset($name) {
			return isset($this->properties[$name]);
		}

	}

endif;
