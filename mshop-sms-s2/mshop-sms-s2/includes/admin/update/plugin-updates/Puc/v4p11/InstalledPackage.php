<?php
if ( !class_exists('Puc_v4p11_InstalledPackage', false) ):
	abstract class Puc_v4p11_InstalledPackage {
		protected $updateChecker;

		public function __construct($updateChecker) {
			$this->updateChecker = $updateChecker;
		}
		abstract public function getInstalledVersion();
		abstract public function getAbsoluteDirectoryPath();
		public function fileExists($relativeFileName) {
			return is_file(
				$this->getAbsoluteDirectoryPath()
				. DIRECTORY_SEPARATOR
				. ltrim($relativeFileName, '/\\')
			);
		}
		public function getFileHeader($content) {
			$content = (string)$content;

			//WordPress only looks at the first 8 KiB of the file, so we do the same.
			$content = substr($content, 0, 8192);
			//Normalize line endings.
			$content = str_replace("\r", "\n", $content);

			$headers = $this->getHeaderNames();
			$results = array();
			foreach ($headers as $field => $name) {
				$success = preg_match('/^[ \t\/*#@]*' . preg_quote($name, '/') . ':(.*)$/mi', $content, $matches);

				if ( ($success === 1) && $matches[1] ) {
					$value = $matches[1];
					if ( function_exists('_cleanup_header_comment') ) {
						$value = _cleanup_header_comment($value);
					}
					$results[$field] = $value;
				} else {
					$results[$field] = '';
				}
			}

			return $results;
		}
		abstract protected function getHeaderNames();
		abstract public function getHeaderValue($headerName);

	}
endif;
