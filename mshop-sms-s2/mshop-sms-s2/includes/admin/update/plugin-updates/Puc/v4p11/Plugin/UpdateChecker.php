<?php
if ( !class_exists('Puc_v4p11_Plugin_UpdateChecker', false) ):
	class Puc_v4p11_Plugin_UpdateChecker extends Puc_v4p11_UpdateChecker {
		protected $updateTransient = 'update_plugins';
		protected $translationType = 'plugin';

		public $pluginAbsolutePath = ''; //Full path of the main plugin file.
		public $pluginFile = '';  //Plugin filename relative to the plugins directory. Many WP APIs use this to identify plugins.
		public $muPluginFile = ''; //For MU plugins, the plugin filename relative to the mu-plugins directory.
		protected $package;

		private $extraUi = null;
		public function __construct($metadataUrl, $pluginFile, $slug = '', $checkPeriod = 12, $optionName = '', $muPluginFile = ''){
			$this->pluginAbsolutePath = $pluginFile;
			$this->pluginFile = plugin_basename($this->pluginAbsolutePath);
			$this->muPluginFile = $muPluginFile;

			//If no slug is specified, use the name of the main plugin file as the slug.
			//For example, 'my-cool-plugin/cool-plugin.php' becomes 'cool-plugin'.
			if ( empty($slug) ){
				$slug = basename($this->pluginFile, '.php');
			}

			//Plugin slugs must be unique.
			$slugCheckFilter = 'puc_is_slug_in_use-' . $slug;
			$slugUsedBy = apply_filters($slugCheckFilter, false);
			if ( $slugUsedBy ) {
				$this->triggerError(sprintf(
					'Plugin slug "%s" is already in use by %s. Slugs must be unique.',
					htmlentities($slug),
					htmlentities($slugUsedBy)
				), E_USER_ERROR);
			}
			add_filter($slugCheckFilter, array($this, 'getAbsolutePath'));

			parent::__construct($metadataUrl, dirname($this->pluginFile), $slug, $checkPeriod, $optionName);

			//Backwards compatibility: If the plugin is a mu-plugin but no $muPluginFile is specified, assume
			//it's the same as $pluginFile given that it's not in a subdirectory (WP only looks in the base dir).
			if ( (strpbrk($this->pluginFile, '/\\') === false) && $this->isUnknownMuPlugin() ) {
				$this->muPluginFile = $this->pluginFile;
			}

			//To prevent a crash during plugin uninstallation, remove updater hooks when the user removes the plugin.
			//Details: https://github.com/YahnisElsts/plugin-update-checker/issues/138#issuecomment-335590964
			add_action('uninstall_' . $this->pluginFile, array($this, 'removeHooks'));

			$this->extraUi = new Puc_v4p11_Plugin_Ui($this);
		}
		protected function createScheduler($checkPeriod) {
			$scheduler = new Puc_v4p11_Scheduler($this, $checkPeriod, array('load-plugins.php'));
			register_deactivation_hook($this->pluginFile, array($scheduler, 'removeUpdaterCron'));
			return $scheduler;
		}
		protected function installHooks(){
			//Override requests for plugin information
			add_filter('plugins_api', array($this, 'injectInfo'), 20, 3);

			parent::installHooks();
		}
		public function removeHooks() {
			parent::removeHooks();
			$this->extraUi->removeHooks();
			$this->package->removeHooks();

			remove_filter('plugins_api', array($this, 'injectInfo'), 20);
		}
		public function requestInfo($queryArgs = array()) {
			list($pluginInfo, $result) = $this->requestMetadata('Puc_v4p11_Plugin_Info', 'request_info', $queryArgs);

			if ( $pluginInfo !== null ) {
				$pluginInfo->filename = $this->pluginFile;
				$pluginInfo->slug = $this->slug;
			}

			$pluginInfo = apply_filters($this->getUniqueName('request_info_result'), $pluginInfo, $result);
			return $pluginInfo;
		}
		public function requestUpdate() {
			//For the sake of simplicity, this function just calls requestInfo()
			//and transforms the result accordingly.
			$pluginInfo = $this->requestInfo(array('checking_for_updates' => '1'));
			if ( $pluginInfo === null ){
				return null;
			}
			$update = Puc_v4p11_Plugin_Update::fromPluginInfo($pluginInfo);

			$update = $this->filterUpdateResult($update);

			return $update;
		}
		public function injectInfo($result, $action = null, $args = null){
			$relevant = ($action == 'plugin_information') && isset($args->slug) && (
					($args->slug == $this->slug) || ($args->slug == dirname($this->pluginFile))
				);
			if ( !$relevant ) {
				return $result;
			}

			$pluginInfo = $this->requestInfo();
			$this->fixSupportedWordpressVersion($pluginInfo);

			$pluginInfo = apply_filters($this->getUniqueName('pre_inject_info'), $pluginInfo);
			if ( $pluginInfo ) {
				return $pluginInfo->toWpFormat();
			}

			return $result;
		}

		protected function shouldShowUpdates() {
			//No update notifications for mu-plugins unless explicitly enabled. The MU plugin file
			//is usually different from the main plugin file so the update wouldn't show up properly anyway.
			return !$this->isUnknownMuPlugin();
		}
		protected function addUpdateToList($updates, $updateToAdd) {
			if ( $this->package->isMuPlugin() ) {
				//WP does not support automatic update installation for mu-plugins, but we can
				//still display a notice.
				$updateToAdd->package = null;
			}
			return parent::addUpdateToList($updates, $updateToAdd);
		}
		protected function removeUpdateFromList($updates) {
			$updates = parent::removeUpdateFromList($updates);
			if ( !empty($this->muPluginFile) && isset($updates, $updates->response) ) {
				unset($updates->response[$this->muPluginFile]);
			}
			return $updates;
		}
		protected function getUpdateListKey() {
			if ( $this->package->isMuPlugin() ) {
				return $this->muPluginFile;
			}
			return $this->pluginFile;
		}

		protected function getNoUpdateItemFields() {
			return array_merge(
				parent::getNoUpdateItemFields(),
				array(
					'id'            => $this->pluginFile,
					'slug'          => $this->slug,
					'plugin'        => $this->pluginFile,
					'icons'         => array(),
					'banners'       => array(),
					'banners_rtl'   => array(),
					'tested'        => '',
					'compatibility' => new stdClass(),
				)
			);
		}
		public function isPluginBeingUpgraded($upgrader = null) {
			return $this->isBeingUpgraded($upgrader);
		}
		public function isBeingUpgraded($upgrader = null) {
			return $this->upgraderStatus->isPluginBeingUpgraded($this->pluginFile, $upgrader);
		}
		public function getUpdate() {
			$update = parent::getUpdate();
			if ( isset($update) ) {
				$update->filename = $this->pluginFile;
			}
			return $update;
		}
		public function getPluginTitle() {
			return $this->package->getPluginTitle();
		}
		public function userCanInstallUpdates() {
			return current_user_can('update_plugins');
		}
		protected function isMuPlugin() {
			return $this->package->isMuPlugin();
		}
		protected function isUnknownMuPlugin() {
			return empty($this->muPluginFile) && $this->package->isMuPlugin();
		}
		public function getAbsolutePath() {
			return $this->pluginAbsolutePath;
		}
		public function addQueryArgFilter($callback){
			$this->addFilter('request_info_query_args', $callback);
		}
		public function addHttpRequestArgFilter($callback) {
			$this->addFilter('request_info_options', $callback);
		}
		public function addResultFilter($callback) {
			$this->addFilter('request_info_result', $callback, 10, 2);
		}

		protected function createDebugBarExtension() {
			return new Puc_v4p11_DebugBar_PluginExtension($this);
		}
		protected function createInstalledPackage() {
			return new Puc_v4p11_Plugin_Package($this->pluginAbsolutePath, $this);
		}
		public function getInstalledPackage() {
			return $this->package;
		}
	}

endif;
