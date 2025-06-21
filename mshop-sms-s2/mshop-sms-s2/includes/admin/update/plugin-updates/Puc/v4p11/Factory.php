<?php
if ( !class_exists('Puc_v4p11_Factory', false) ):
	class Puc_v4p11_Factory {
		protected static $classVersions = array();
		protected static $sorted = false;

		protected static $myMajorVersion = '';
		protected static $latestCompatibleVersion = '';
		public static function buildFromHeader($fullPath, $args = array()) {
			$fullPath = self::normalizePath($fullPath);

			//Set up defaults.
			$defaults = array(
				'metadataUrl'  => '',
				'slug'         => '',
				'checkPeriod'  => 12,
				'optionName'   => '',
				'muPluginFile' => '',
			);
			$args = array_merge($defaults, array_intersect_key($args, $defaults));
			extract($args, EXTR_SKIP);

			//Check for the service URI
			if ( empty($metadataUrl) ) {
				$metadataUrl = self::getServiceURI($fullPath);
			}
			return self::buildUpdateChecker($metadataUrl, $fullPath, $slug, $checkPeriod, $optionName, $muPluginFile);
		}
		public static function buildUpdateChecker($metadataUrl, $fullPath, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = '') {
			$fullPath = self::normalizePath($fullPath);
			$id = null;

			//Plugin or theme?
			$themeDirectory = self::getThemeDirectoryName($fullPath);
			if ( self::isPluginFile($fullPath) ) {
				$type = 'Plugin';
				$id = $fullPath;
			} else if ( $themeDirectory !== null ) {
				$type = 'Theme';
				$id = $themeDirectory;
			} else {
				throw new RuntimeException(sprintf(
					'The update checker cannot determine if "%s" is a plugin or a theme. ' .
					'This is a bug. Please contact the PUC developer.',
					htmlentities($fullPath)
				));
			}

			//Which hosting service does the URL point to?
			$service = self::getVcsService($metadataUrl);

			$apiClass = null;
			if ( empty($service) ) {
				//The default is to get update information from a remote JSON file.
				$checkerClass = $type . '_UpdateChecker';
			} else {
				//You can also use a VCS repository like GitHub.
				$checkerClass = 'Vcs_' . $type . 'UpdateChecker';
				$apiClass = $service . 'Api';
			}

			$checkerClass = self::getCompatibleClassVersion($checkerClass);
			if ( $checkerClass === null ) {
				trigger_error(
					sprintf(
						'PUC %s does not support updates for %ss %s',
						htmlentities(self::$latestCompatibleVersion),
						strtolower($type),
						$service ? ('hosted on ' . htmlentities($service)) : 'using JSON metadata'
					),
					E_USER_ERROR
				);
				return null;
			}

			//Add the current namespace to the class name(s).
			if ( version_compare(PHP_VERSION, '5.3', '>=') ) {
				$checkerClass = __NAMESPACE__ . '\\' . $checkerClass;
			}

			if ( !isset($apiClass) ) {
				//Plain old update checker.
				return new $checkerClass($metadataUrl, $id, $slug, $checkPeriod, $optionName, $muPluginFile);
			} else {
				//VCS checker + an API client.
				$apiClass = self::getCompatibleClassVersion($apiClass);
				if ( $apiClass === null ) {
					trigger_error(sprintf(
						'PUC %s does not support %s',
						htmlentities(self::$latestCompatibleVersion),
						htmlentities($service)
					), E_USER_ERROR);
					return null;
				}

				if ( version_compare(PHP_VERSION, '5.3', '>=') && (strpos($apiClass, '\\') === false) ) {
					$apiClass = __NAMESPACE__ . '\\' . $apiClass;
				}

				return new $checkerClass(
					new $apiClass($metadataUrl),
					$id,
					$slug,
					$checkPeriod,
					$optionName,
					$muPluginFile
				);
			}
		}
		public static function normalizePath($path) {
			if ( function_exists('wp_normalize_path') ) {
				return wp_normalize_path($path);
			}
			$path = str_replace('\\', '/', $path);
			$path = preg_replace('|(?<=.)/+|', '/', $path);
			if ( substr($path, 1, 1) === ':' ) {
				$path = ucfirst($path);
			}
			return $path;
		}
		protected static function isPluginFile($absolutePath) {
			//Is the file inside the "plugins" or "mu-plugins" directory?
			$pluginDir = self::normalizePath(WP_PLUGIN_DIR);
			$muPluginDir = self::normalizePath(WPMU_PLUGIN_DIR);
			if ( (strpos($absolutePath, $pluginDir) === 0) || (strpos($absolutePath, $muPluginDir) === 0) ) {
				return true;
			}

			//Is it a file at all? Caution: is_file() can fail if the parent dir. doesn't have the +x permission set.
			if ( !is_file($absolutePath) ) {
				return false;
			}

			//Does it have a valid plugin header?
			//This is a last-ditch check for plugins symlinked from outside the WP root.
			if ( function_exists('get_file_data') ) {
				$headers = get_file_data($absolutePath, array('Name' => 'Plugin Name'), 'plugin');
				return !empty($headers['Name']);
			}

			return false;
		}
		protected static function getThemeDirectoryName($absolutePath) {
			if ( is_file($absolutePath) ) {
				$absolutePath = dirname($absolutePath);
			}

			if ( file_exists($absolutePath . '/style.css') ) {
				return basename($absolutePath);
			}
			return null;
		}
		private static function getServiceURI($fullPath) {
			//Look for the URI
			if ( is_readable($fullPath) ) {
				$seek = array(
					'github' => 'GitHub URI',
					'gitlab' => 'GitLab URI',
					'bucket' => 'BitBucket URI',
				);
				$seek = apply_filters('puc_get_source_uri', $seek);
				$data = get_file_data($fullPath, $seek);
				foreach ($data as $key => $uri) {
					if ( $uri ) {
						return $uri;
					}
				}
			}

			//URI was not found so throw an error.
			throw new RuntimeException(
				sprintf('Unable to locate URI in header of "%s"', htmlentities($fullPath))
			);
		}
		private static function getVcsService($metadataUrl) {
			$service = null;

			//Which hosting service does the URL point to?
			$host = parse_url($metadataUrl, PHP_URL_HOST);
			$path = parse_url($metadataUrl, PHP_URL_PATH);

			//Check if the path looks like "/user-name/repository".
			//For GitLab.com it can also be "/user/group1/group2/.../repository".
			$repoRegex = '@^/?([^/]+?)/([^/#?&]+?)/?$@';
			if ( $host === 'gitlab.com' ) {
				$repoRegex = '@^/?(?:[^/#?&]++/){1,20}(?:[^/#?&]++)/?$@';
			}
			if ( preg_match($repoRegex, $path) ) {
				$knownServices = array(
					'github.com' => 'GitHub',
					'bitbucket.org' => 'BitBucket',
					'gitlab.com' => 'GitLab',
				);
				if ( isset($knownServices[$host]) ) {
					$service = $knownServices[$host];
				}
			}

			return apply_filters('puc_get_vcs_service', $service, $host, $path, $metadataUrl);
		}
		protected static function getCompatibleClassVersion($class) {
			if ( isset(self::$classVersions[$class][self::$latestCompatibleVersion]) ) {
				return self::$classVersions[$class][self::$latestCompatibleVersion];
			}
			return null;
		}
		public static function getLatestClassVersion($class) {
			if ( !self::$sorted ) {
				self::sortVersions();
			}

			if ( isset(self::$classVersions[$class]) ) {
				return reset(self::$classVersions[$class]);
			} else {
				return null;
			}
		}
		protected static function sortVersions() {
			foreach ( self::$classVersions as $class => $versions ) {
				uksort($versions, array(__CLASS__, 'compareVersions'));
				self::$classVersions[$class] = $versions;
			}
			self::$sorted = true;
		}

		protected static function compareVersions($a, $b) {
			return -version_compare($a, $b);
		}
		public static function addVersion($generalClass, $versionedClass, $version) {
			if ( empty(self::$myMajorVersion) ) {
				$nameParts = explode('_', __CLASS__, 3);
				self::$myMajorVersion = substr(ltrim($nameParts[1], 'v'), 0, 1);
			}

			//Store the greatest version number that matches our major version.
			$components = explode('.', $version);
			if ( $components[0] === self::$myMajorVersion ) {

				if (
					empty(self::$latestCompatibleVersion)
					|| version_compare($version, self::$latestCompatibleVersion, '>')
				) {
					self::$latestCompatibleVersion = $version;
				}

			}

			if ( !isset(self::$classVersions[$generalClass]) ) {
				self::$classVersions[$generalClass] = array();
			}
			self::$classVersions[$generalClass][$version] = $versionedClass;
			self::$sorted = false;
		}
	}

endif;
