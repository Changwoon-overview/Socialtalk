<?php
if ( !class_exists('Puc_v4p11_Metadata', false) ):
	abstract class Puc_v4p11_Metadata {
		public static function fromJson(/** @noinspection PhpUnusedParameterInspection */ $json) {
			throw new LogicException('The ' . __METHOD__ . ' method must be implemented by subclasses');
		}
		protected static function createFromJson($json, $target) {
			$apiResponse = json_decode($json);
			if ( empty($apiResponse) || !is_object($apiResponse) ){
				$errorMessage = "Failed to parse update metadata. Try validating your .json file with http://jsonlint.com/";
				do_action('puc_api_error', new WP_Error('puc-invalid-json', $errorMessage));
				trigger_error($errorMessage, E_USER_NOTICE);
				return false;
			}

			$valid = $target->validateMetadata($apiResponse);
			if ( is_wp_error($valid) ){
				do_action('puc_api_error', $valid);
				trigger_error($valid->get_error_message(), E_USER_NOTICE);
				return false;
			}

			foreach(get_object_vars($apiResponse) as $key => $value){
				$target->$key = $value;
			}

			return true;
		}
		protected function validateMetadata(/** @noinspection PhpUnusedParameterInspection */ $apiResponse) {
			return true;
		}
		public static function fromObject(/** @noinspection PhpUnusedParameterInspection */ $object) {
			throw new LogicException('The ' . __METHOD__ . ' method must be implemented by subclasses');
		}
		public function toStdClass() {
			$object = new stdClass();
			$this->copyFields($this, $object);
			return $object;
		}
		abstract public function toWpFormat();
		protected function copyFields($from, $to) {
			$fields = $this->getFieldNames();

			if ( property_exists($from, 'slug') && !empty($from->slug) ) {
				//Let plugins add extra fields without having to create subclasses.
				$fields = apply_filters($this->getPrefixedFilter('retain_fields') . '-' . $from->slug, $fields);
			}

			foreach ($fields as $field) {
				if ( property_exists($from, $field) ) {
					$to->$field = $from->$field;
				}
			}
		}
		protected function getFieldNames() {
			return array();
		}
		protected function getPrefixedFilter($tag) {
			return 'puc_' . $tag;
		}
	}

endif;
