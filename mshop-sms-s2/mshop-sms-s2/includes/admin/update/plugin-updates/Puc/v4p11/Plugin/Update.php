<?php
if ( !class_exists('Puc_v4p11_Plugin_Update', false) ):
	class Puc_v4p11_Plugin_Update extends Puc_v4p11_Update {
		public $id = 0;
		public $homepage;
		public $upgrade_notice;
		public $tested;
		public $requires_php = false;
		public $icons = array();
		public $filename; //Plugin filename relative to the plugins directory.

		protected static $extraFields = array(
			'id', 'homepage', 'tested', 'requires_php', 'upgrade_notice', 'icons', 'filename',
		);
		public static function fromJson($json){
			//Since update-related information is simply a subset of the full plugin info,
			//we can parse the update JSON as if it was a plugin info string, then copy over
			//the parts that we care about.
			$pluginInfo = Puc_v4p11_Plugin_Info::fromJson($json);
			if ( $pluginInfo !== null ) {
				return self::fromPluginInfo($pluginInfo);
			} else {
				return null;
			}
		}
		public static function fromPluginInfo($info){
			return self::fromObject($info);
		}
		public static function fromObject($object) {
			$update = new self();
			$update->copyFields($object, $update);
			return $update;
		}
		protected function getFieldNames() {
			return array_merge(parent::getFieldNames(), self::$extraFields);
		}
		public function toWpFormat() {
			$update = parent::toWpFormat();

			$update->id = $this->id;
			$update->url = $this->homepage;
			$update->tested = $this->tested;
			$update->requires_php = $this->requires_php;
			$update->plugin = $this->filename;

			if ( !empty($this->upgrade_notice) ) {
				$update->upgrade_notice = $this->upgrade_notice;
			}

			if ( !empty($this->icons) && is_array($this->icons) ) {
				//This should be an array with up to 4 keys: 'svg', '1x', '2x' and 'default'.
				//Docs: https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/#plugin-icons
				$icons = array_intersect_key(
					$this->icons,
					array('svg' => true, '1x' => true, '2x' => true, 'default' => true)
				);
				if ( !empty($icons) ) {
					$update->icons = $icons;

					//It appears that the 'default' icon isn't used anywhere in WordPress 4.9,
					//but lets set it just in case a future release needs it.
					if ( !isset($update->icons['default']) ) {
						$update->icons['default'] = current($update->icons);
					}
				}
			}

			return $update;
		}
	}

endif;
