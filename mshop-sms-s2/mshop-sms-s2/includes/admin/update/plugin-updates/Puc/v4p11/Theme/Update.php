<?php

if ( !class_exists('Puc_v4p11_Theme_Update', false) ):

	class Puc_v4p11_Theme_Update extends Puc_v4p11_Update {
		public $details_url = '';

		protected static $extraFields = array('details_url');
		public function toWpFormat() {
			$update = array(
				'theme' => $this->slug,
				'new_version' => $this->version,
				'url' => $this->details_url,
			);

			if ( !empty($this->download_url) ) {
				$update['package'] = $this->download_url;
			}

			return $update;
		}
		public static function fromJson($json) {
			$instance = new self();
			if ( !parent::createFromJson($json, $instance) ) {
				return null;
			}
			return $instance;
		}
		public static function fromObject($object) {
			$update = new self();
			$update->copyFields($object, $update);
			return $update;
		}
		protected function validateMetadata($apiResponse) {
			$required = array('version', 'details_url');
			foreach($required as $key) {
				if ( !isset($apiResponse->$key) || empty($apiResponse->$key) ) {
					return new WP_Error(
						'tuc-invalid-metadata',
						sprintf('The theme metadata is missing the required "%s" key.', $key)
					);
				}
			}
			return true;
		}

		protected function getFieldNames() {
			return array_merge(parent::getFieldNames(), self::$extraFields);
		}

		protected function getPrefixedFilter($tag) {
			return parent::getPrefixedFilter($tag) . '_theme';
		}
	}

endif;
