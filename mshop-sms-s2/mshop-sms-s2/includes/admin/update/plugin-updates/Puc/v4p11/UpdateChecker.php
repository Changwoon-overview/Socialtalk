<?php

if ( !class_exists('Puc_v4p11_UpdateChecker', false) ):

	abstract class Puc_v4p11_UpdateChecker {
		protected $filterSuffix = '';
		protected $updateTransient = '';
		protected $translationType = ''; //"plugin" or "theme".
		public $debugMode = null;
		public $optionName = '';
		public $metadataUrl = '';
		public $directoryName = '';
		public $slug = '';
		protected $package;
		public $scheduler;
		protected $upgraderStatus;
		protected $updateState;
		protected $lastRequestApiErrors = array();
		protected $cachedMetadataHost = 0;
		protected $debugBarExtension = null;

		public function __construct($metadataUrl, $directoryName, $slug = null, $checkPeriod = 12, $optionName = '') {
			$this->debugMode = (bool)(constant('WP_DEBUG'));
			$this->metadataUrl = $metadataUrl;
			$this->directoryName = $directoryName;
			$this->slug = !empty($slug) ? $slug : $this->directoryName;

			$this->optionName = $optionName;
			if ( empty($this->optionName) ) {
				//BC: Initially the library only supported plugin updates and didn't use type prefixes
				//in the option name. Lets use the same prefix-less name when possible.
				if ( $this->filterSuffix === '' ) {
					$this->optionName = 'external_updates-' . $this->slug;
				} else {
					$this->optionName = $this->getUniqueName('external_updates');
				}
			}

			$this->package = $this->createInstalledPackage();
			$this->scheduler = $this->createScheduler($checkPeriod);
			$this->upgraderStatus = new Puc_v4p11_UpgraderStatus();
			$this->updateState = new Puc_v4p11_StateStore($this->optionName);

			if ( did_action('init') ) {
				$this->loadTextDomain();
			} else {
				add_action('init', array($this, 'loadTextDomain'));
			}

			$this->installHooks();
		}
		public function loadTextDomain() {
			//We're not using load_plugin_textdomain() or its siblings because figuring out where
			//the library is located (plugin, mu-plugin, theme, custom wp-content paths) is messy.
			$domain = 'plugin-update-checker';
			$locale = apply_filters(
				'plugin_locale',
				(is_admin() && function_exists('get_user_locale')) ? get_user_locale() : get_locale(),
				$domain
			);

			$moFile = $domain . '-' . $locale . '.mo';
			$path = realpath(dirname(__FILE__) . '/../../languages');

			if ($path && file_exists($path)) {
				load_textdomain($domain, $path . '/' . $moFile);
			}
		}

		protected function installHooks() {
			//Insert our update info into the update array maintained by WP.
			add_filter('site_transient_' . $this->updateTransient, array($this,'injectUpdate'));

			//Insert translation updates into the update list.
			add_filter('site_transient_' . $this->updateTransient, array($this, 'injectTranslationUpdates'));

			//Clear translation updates when WP clears the update cache.
			//This needs to be done directly because the library doesn't actually remove obsolete plugin updates,
			//it just hides them (see getUpdate()). We can't do that with translations - too much disk I/O.
			add_action(
				'delete_site_transient_' . $this->updateTransient,
				array($this, 'clearCachedTranslationUpdates')
			);

			//Rename the update directory to be the same as the existing directory.
			if ( $this->directoryName !== '.' ) {
				add_filter('upgrader_source_selection', array($this, 'fixDirectoryName'), 10, 3);
			}

			//Allow HTTP requests to the metadata URL even if it's on a local host.
			add_filter('http_request_host_is_external', array($this, 'allowMetadataHost'), 10, 2);

			//DebugBar integration.
			if ( did_action('plugins_loaded') ) {
				$this->maybeInitDebugBar();
			} else {
				add_action('plugins_loaded', array($this, 'maybeInitDebugBar'));
			}
		}
		public function removeHooks() {
			remove_filter('site_transient_' . $this->updateTransient, array($this,'injectUpdate'));
			remove_filter('site_transient_' . $this->updateTransient, array($this, 'injectTranslationUpdates'));
			remove_action(
				'delete_site_transient_' . $this->updateTransient,
				array($this, 'clearCachedTranslationUpdates')
			);

			remove_filter('upgrader_source_selection', array($this, 'fixDirectoryName'), 10);
			remove_filter('http_request_host_is_external', array($this, 'allowMetadataHost'), 10);
			remove_action('plugins_loaded', array($this, 'maybeInitDebugBar'));

			remove_action('init', array($this, 'loadTextDomain'));

			if ( $this->scheduler ) {
				$this->scheduler->removeHooks();
			}

			if ( $this->debugBarExtension ) {
				$this->debugBarExtension->removeHooks();
			}
		}
		abstract public function userCanInstallUpdates();
		public function allowMetadataHost($allow, $host) {
			if ( $this->cachedMetadataHost === 0 ) {
				$this->cachedMetadataHost = parse_url($this->metadataUrl, PHP_URL_HOST);
			}

			if ( is_string($this->cachedMetadataHost) && (strtolower($host) === strtolower($this->cachedMetadataHost)) ) {
				return true;
			}
			return $allow;
		}
		abstract protected function createInstalledPackage();
		public function getInstalledPackage() {
			return $this->package;
		}
		abstract protected function createScheduler($checkPeriod);
		public function checkForUpdates() {
			$installedVersion = $this->getInstalledVersion();
			//Fail silently if we can't find the plugin/theme or read its header.
			if ( $installedVersion === null ) {
				$this->triggerError(
					sprintf('Skipping update check for %s - installed version unknown.', $this->slug),
					E_USER_WARNING
				);
				return null;
			}

			//Start collecting API errors.
			$this->lastRequestApiErrors = array();
			add_action('puc_api_error', array($this, 'collectApiErrors'), 10, 4);

			$state = $this->updateState;
			$state->setLastCheckToNow()
				->setCheckedVersion($installedVersion)
				->save(); //Save before checking in case something goes wrong

			$state->setUpdate($this->requestUpdate());
			$state->save();

			//Stop collecting API errors.
			remove_action('puc_api_error', array($this, 'collectApiErrors'), 10);

			return $this->getUpdate();
		}
		public function getUpdateState() {
			return $this->updateState->lazyLoad();
		}
		public function resetUpdateState() {
			$this->updateState->delete();
		}
		public function getUpdate() {
			$update = $this->updateState->getUpdate();

			//Is there an update available?
			if ( isset($update) ) {
				//Check if the update is actually newer than the currently installed version.
				$installedVersion = $this->getInstalledVersion();
				if ( ($installedVersion !== null) && version_compare($update->version, $installedVersion, '>') ){
					return $update;
				}
			}
			return null;
		}
		abstract public function requestUpdate();
		protected function filterUpdateResult($update, $httpResult = null) {
			//Let plugins/themes modify the update.
			$update = apply_filters($this->getUniqueName('request_update_result'), $update, $httpResult);

			$this->fixSupportedWordpressVersion($update);

			if ( isset($update, $update->translations) ) {
				//Keep only those translation updates that apply to this site.
				$update->translations = $this->filterApplicableTranslations($update->translations);
			}

			return $update;
		}
		protected function fixSupportedWordpressVersion(Puc_v4p11_Metadata $update = null) {
			if ( !isset($update->tested) || !preg_match('/^\d++\.\d++$/', $update->tested) ) {
				return;
			}

			$actualWpVersions = array();

			$wpVersion = $GLOBALS['wp_version'];

			if ( function_exists('get_core_updates') ) {
				$coreUpdates = get_core_updates();
				if ( is_array($coreUpdates) ) {
					foreach ($coreUpdates as $coreUpdate) {
						if ( isset($coreUpdate->current) ) {
							$actualWpVersions[] = $coreUpdate->current;
						}
					}
				}
			}

			$actualWpVersions[] = $wpVersion;

			$actualWpPatchNumber = null;
			foreach ($actualWpVersions as $version) {
				if ( preg_match('/^(?P<majorMinor>\d++\.\d++)(?:\.(?P<patch>\d++))?/', $version, $versionParts) ) {
					if ( $versionParts['majorMinor'] === $update->tested ) {
						$patch = isset($versionParts['patch']) ? intval($versionParts['patch']) : 0;
						if ( $actualWpPatchNumber === null ) {
							$actualWpPatchNumber = $patch;
						} else {
							$actualWpPatchNumber = max($actualWpPatchNumber, $patch);
						}
					}
				}
			}
			if ( $actualWpPatchNumber === null ) {
				$actualWpPatchNumber = 999;
			}

			if ( $actualWpPatchNumber > 0 ) {
				$update->tested .= '.' . $actualWpPatchNumber;
			}
		}
		public function getInstalledVersion() {
			return $this->package->getInstalledVersion();
		}
		public function getAbsoluteDirectoryPath() {
			return $this->package->getAbsoluteDirectoryPath();
		}
		public function triggerError($message, $errorType) {
			if ( $this->isDebugModeEnabled() ) {
				trigger_error($message, $errorType);
			}
		}
		protected function isDebugModeEnabled() {
			if ( $this->debugMode === null ) {
				$this->debugMode = (bool)(constant('WP_DEBUG'));
			}
			return $this->debugMode;
		}
		public function getUniqueName($baseTag) {
			$name = 'puc_' . $baseTag;
			if ( $this->filterSuffix !== '' ) {
				$name .= '_' . $this->filterSuffix;
			}
			return $name . '-' . $this->slug;
		}
		public function collectApiErrors($error, $httpResponse = null, $url = null, $slug = null) {
			if ( isset($slug) && ($slug !== $this->slug) ) {
				return;
			}

			$this->lastRequestApiErrors[] = array(
				'error'        => $error,
				'httpResponse' => $httpResponse,
				'url'          => $url,
			);
		}
		public function getLastRequestApiErrors() {
			return $this->lastRequestApiErrors;
		}
		public function addFilter($tag, $callback, $priority = 10, $acceptedArgs = 1) {
			add_filter($this->getUniqueName($tag), $callback, $priority, $acceptedArgs);
		}
		public function injectUpdate($updates) {
			//Is there an update to insert?
			$update = $this->getUpdate();

			if ( !$this->shouldShowUpdates() ) {
				$update = null;
			}

			if ( !empty($update) ) {
				//Let plugins filter the update info before it's passed on to WordPress.
				$update = apply_filters($this->getUniqueName('pre_inject_update'), $update);
				$updates = $this->addUpdateToList($updates, $update->toWpFormat());
			} else {
				//Clean up any stale update info.
				$updates = $this->removeUpdateFromList($updates);
				//Add a placeholder item to the "no_update" list to enable auto-update support.
				//If we don't do this, the option to enable automatic updates will only show up
				//when an update is available.
				$updates = $this->addNoUpdateItem($updates);
			}

			return $updates;
		}
		protected function addUpdateToList($updates, $updateToAdd) {
			if ( !is_object($updates) ) {
				$updates = new stdClass();
				$updates->response = array();
			}

			$updates->response[$this->getUpdateListKey()] = $updateToAdd;
			return $updates;
		}
		protected function removeUpdateFromList($updates) {
			if ( isset($updates, $updates->response) ) {
				unset($updates->response[$this->getUpdateListKey()]);
			}
			return $updates;
		}
		protected function addNoUpdateItem($updates) {
			if ( !is_object($updates) ) {
				$updates = new stdClass();
				$updates->response = array();
				$updates->no_update = array();
			} else if ( !isset($updates->no_update) ) {
				$updates->no_update = array();
			}

			$updates->no_update[$this->getUpdateListKey()] = (object) $this->getNoUpdateItemFields();

			return $updates;
		}
		protected function getNoUpdateItemFields() {
			return array(
				'new_version'   => $this->getInstalledVersion(),
				'url'           => '',
				'package'       => '',
				'requires_php'  => '',
			);
		}
		abstract protected function getUpdateListKey();
		protected function shouldShowUpdates() {
			return true;
		}
		protected function requestMetadata($metaClass, $filterRoot, $queryArgs = array()) {
			//Query args to append to the URL. Plugins can add their own by using a filter callback (see addQueryArgFilter()).
			$queryArgs = array_merge(
				array(
					'installed_version' => strval($this->getInstalledVersion()),
					'php' => phpversion(),
					'locale' => get_locale(),
				),
				$queryArgs
			);
			$queryArgs = apply_filters($this->getUniqueName($filterRoot . '_query_args'), $queryArgs);

			//Various options for the wp_remote_get() call. Plugins can filter these, too.
			$options = array(
				'timeout' => 10, //seconds
				'headers' => array(
					'Accept' => 'application/json',
				),
			);
			$options = apply_filters($this->getUniqueName($filterRoot . '_options'), $options);

			//The metadata file should be at 'http://your-api.com/url/here/$slug/info.json'
			$url = $this->metadataUrl;
			if ( !empty($queryArgs) ){
				$url = add_query_arg($queryArgs, $url);
			}

			$result = wp_remote_get($url, $options);

			$result = apply_filters($this->getUniqueName('request_metadata_http_result'), $result, $url, $options);
			
			//Try to parse the response
			$status = $this->validateApiResponse($result);
			$metadata = null;
			if ( !is_wp_error($status) ){
				if ( version_compare(PHP_VERSION, '5.3', '>=') && (strpos($metaClass, '\\') === false) ) {
					$metaClass = __NAMESPACE__ . '\\' . $metaClass;
				}
				$metadata = call_user_func(array($metaClass, 'fromJson'), $result['body']);
			} else {
				do_action('puc_api_error', $status, $result, $url, $this->slug);
				$this->triggerError(
					sprintf('The URL %s does not point to a valid metadata file. ', $url)
					. $status->get_error_message(),
					E_USER_WARNING
				);
			}

			return array($metadata, $result);
		}
		protected function validateApiResponse($result) {
			if ( is_wp_error($result) ) { /** @var WP_Error $result */
				return new WP_Error($result->get_error_code(), 'WP HTTP Error: ' . $result->get_error_message());
			}

			if ( !isset($result['response']['code']) ) {
				return new WP_Error(
					'puc_no_response_code',
					'wp_remote_get() returned an unexpected result.'
				);
			}

			if ( $result['response']['code'] !== 200 ) {
				return new WP_Error(
					'puc_unexpected_response_code',
					'HTTP response code is ' . $result['response']['code'] . ' (expected: 200)'
				);
			}

			if ( empty($result['body']) ) {
				return new WP_Error('puc_empty_response', 'The metadata file appears to be empty.');
			}

			return true;
		}
		protected function filterApplicableTranslations($translations) {
			$languages = array_flip(array_values(get_available_languages()));
			$installedTranslations = $this->getInstalledTranslations();

			$applicableTranslations = array();
			foreach ($translations as $translation) {
				//Does it match one of the available core languages?
				$isApplicable = array_key_exists($translation->language, $languages);
				//Is it more recent than an already-installed translation?
				if ( isset($installedTranslations[$translation->language]) ) {
					$updateTimestamp = strtotime($translation->updated);
					$installedTimestamp = strtotime($installedTranslations[$translation->language]['PO-Revision-Date']);
					$isApplicable = $updateTimestamp > $installedTimestamp;
				}

				if ( $isApplicable ) {
					$applicableTranslations[] = $translation;
				}
			}

			return $applicableTranslations;
		}
		protected function getInstalledTranslations() {
			if ( !function_exists('wp_get_installed_translations') ) {
				return array();
			}
			$installedTranslations = wp_get_installed_translations($this->translationType . 's');
			if ( isset($installedTranslations[$this->directoryName]) ) {
				$installedTranslations = $installedTranslations[$this->directoryName];
			} else {
				$installedTranslations = array();
			}
			return $installedTranslations;
		}
		public function injectTranslationUpdates($updates) {
			$translationUpdates = $this->getTranslationUpdates();
			if ( empty($translationUpdates) ) {
				return $updates;
			}

			//Being defensive.
			if ( !is_object($updates) ) {
				$updates = new stdClass();
			}
			if ( !isset($updates->translations) ) {
				$updates->translations = array();
			}

			//In case there's a name collision with a plugin or theme hosted on wordpress.org,
			//remove any preexisting updates that match our thing.
			$updates->translations = array_values(array_filter(
				$updates->translations,
				array($this, 'isNotMyTranslation')
			));

			//Add our updates to the list.
			foreach($translationUpdates as $update) {
				$convertedUpdate = array_merge(
					array(
						'type' => $this->translationType,
						'slug' => $this->directoryName,
						'autoupdate' => 0,
						//AFAICT, WordPress doesn't actually use the "version" field for anything.
						//But lets make sure it's there, just in case.
						'version' => isset($update->version) ? $update->version : ('1.' . strtotime($update->updated)),
					),
					(array)$update
				);

				$updates->translations[] = $convertedUpdate;
			}

			return $updates;
		}
		public function getTranslationUpdates() {
			return $this->updateState->getTranslations();
		}
		public function clearCachedTranslationUpdates() {
			$this->updateState->setTranslations(array());
		}
		protected function isNotMyTranslation($translation) {
			$isMatch = isset($translation['type'], $translation['slug'])
				&& ($translation['type'] === $this->translationType)
				&& ($translation['slug'] === $this->directoryName);

			return !$isMatch;
		}
		public function fixDirectoryName($source, $remoteSource, $upgrader) {
			global $wp_filesystem;

			//Basic sanity checks.
			if ( !isset($source, $remoteSource, $upgrader, $upgrader->skin, $wp_filesystem) ) {
				return $source;
			}

			//If WordPress is upgrading anything other than our plugin/theme, leave the directory name unchanged.
			if ( !$this->isBeingUpgraded($upgrader) ) {
				return $source;
			}

			//Rename the source to match the existing directory.
			$correctedSource = trailingslashit($remoteSource) . $this->directoryName . '/';
			if ( $source !== $correctedSource ) {
				//The update archive should contain a single directory that contains the rest of plugin/theme files.
				//Otherwise, WordPress will try to copy the entire working directory ($source == $remoteSource).
				//We can't rename $remoteSource because that would break WordPress code that cleans up temporary files
				//after update.
				if ( $this->isBadDirectoryStructure($remoteSource) ) {
					return new WP_Error(
						'puc-incorrect-directory-structure',
						sprintf(
							'The directory structure of the update is incorrect. All files should be inside ' .
							'a directory named <span class="code">%s</span>, not at the root of the ZIP archive.',
							htmlentities($this->slug)
						)
					);
				}
				$upgrader->skin->feedback(sprintf(
					'Renaming %s to %s&#8230;',
					'<span class="code">' . basename($source) . '</span>',
					'<span class="code">' . $this->directoryName . '</span>'
				));

				if ( $wp_filesystem->move($source, $correctedSource, true) ) {
					$upgrader->skin->feedback('Directory successfully renamed.');
					return $correctedSource;
				} else {
					return new WP_Error(
						'puc-rename-failed',
						'Unable to rename the update to match the existing directory.'
					);
				}
			}

			return $source;
		}
		abstract public function isBeingUpgraded($upgrader = null);
		protected function isBadDirectoryStructure($remoteSource) {
			global $wp_filesystem;

			$sourceFiles = $wp_filesystem->dirlist($remoteSource);
			if ( is_array($sourceFiles) ) {
				$sourceFiles = array_keys($sourceFiles);
				$firstFilePath = trailingslashit($remoteSource) . $sourceFiles[0];
				return (count($sourceFiles) > 1) || (!$wp_filesystem->is_dir($firstFilePath));
			}

			//Assume it's fine.
			return false;
		}
		public function maybeInitDebugBar() {
			if ( class_exists('Debug_Bar', false) && file_exists(dirname(__FILE__) . '/DebugBar') ) {
				$this->debugBarExtension = $this->createDebugBarExtension();
			}
		}

		protected function createDebugBarExtension() {
			return new Puc_v4p11_DebugBar_Extension($this);
		}
		public function onDisplayConfiguration($panel) {
			//Do nothing. Subclasses can use this to add additional info to the panel.
		}

	}

endif;
