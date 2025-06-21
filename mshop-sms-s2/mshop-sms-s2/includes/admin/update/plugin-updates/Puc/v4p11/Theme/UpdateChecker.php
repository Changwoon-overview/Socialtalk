<?php

if ( !class_exists('Puc_v4p11_Theme_UpdateChecker', false) ):

	class Puc_v4p11_Theme_UpdateChecker extends Puc_v4p11_UpdateChecker {
		protected $filterSuffix = 'theme';
		protected $updateTransient = 'update_themes';
		protected $translationType = 'theme';
		protected $stylesheet;

		public function __construct($metadataUrl, $stylesheet = null, $customSlug = null, $checkPeriod = 12, $optionName = '') {
			if ( $stylesheet === null ) {
				$stylesheet = get_stylesheet();
			}
			$this->stylesheet = $stylesheet;

			parent::__construct(
				$metadataUrl,
				$stylesheet,
				$customSlug ? $customSlug : $stylesheet,
				$checkPeriod,
				$optionName
			);
		}
		protected function getUpdateListKey() {
			return $this->directoryName;
		}
		public function requestUpdate() {
			list($themeUpdate, $result) = $this->requestMetadata('Puc_v4p11_Theme_Update', 'request_update');

			if ( $themeUpdate !== null ) {
				$themeUpdate->slug = $this->slug;
			}

			$themeUpdate = $this->filterUpdateResult($themeUpdate, $result);
			return $themeUpdate;
		}

		protected function getNoUpdateItemFields() {
			return array_merge(
				parent::getNoUpdateItemFields(),
				array(
					'theme'        => $this->directoryName,
					'requires'     => '',
				)
			);
		}

		public function userCanInstallUpdates() {
			return current_user_can('update_themes');
		}
		protected function createScheduler($checkPeriod) {
			return new Puc_v4p11_Scheduler($this, $checkPeriod, array('load-themes.php'));
		}
		public function isBeingUpgraded($upgrader = null) {
			return $this->upgraderStatus->isThemeBeingUpgraded($this->stylesheet, $upgrader);
		}

		protected function createDebugBarExtension() {
			return new Puc_v4p11_DebugBar_Extension($this, 'Puc_v4p11_DebugBar_ThemePanel');
		}
		public function addQueryArgFilter($callback){
			$this->addFilter('request_update_query_args', $callback);
		}
		public function addHttpRequestArgFilter($callback) {
			$this->addFilter('request_update_options', $callback);
		}
		public function addResultFilter($callback) {
			$this->addFilter('request_update_result', $callback, 10, 2);
		}
		protected function createInstalledPackage() {
			return new Puc_v4p11_Theme_Package($this->stylesheet, $this);
		}
	}

endif;
