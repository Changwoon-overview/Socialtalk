<?php

if ( !class_exists('Puc_v4p11_Utils', false) ):

	class Puc_v4p11_Utils {
		public static function get($collection, $path, $default = null, $separator = '.') {
			if ( is_string($path) ) {
				$path = explode($separator, $path);
			}

			//Follow the $path into $input as far as possible.
			$currentValue = $collection;
			foreach ($path as $node) {
				if ( is_array($currentValue) && isset($currentValue[$node]) ) {
					$currentValue = $currentValue[$node];
				} else if ( is_object($currentValue) && isset($currentValue->$node) ) {
					$currentValue = $currentValue->$node;
				} else {
					return $default;
				}
			}

			return $currentValue;
		}
		public static function findNotEmpty($values, $default = null) {
			if ( empty($values) ) {
				return $default;
			}

			foreach ($values as $value) {
				if ( !empty($value) ) {
					return $value;
				}
			}

			return $default;
		}
		public static function startsWith($input, $prefix) {
			$length = strlen($prefix);
			return (substr($input, 0, $length) === $prefix);
		}
	}

endif;
