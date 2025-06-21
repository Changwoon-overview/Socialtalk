<?php
if ( !class_exists('Puc_v4p11_Scheduler', false) ):
	class Puc_v4p11_Scheduler {
		public $checkPeriod = 12; //How often to check for updates (in hours).
		public $throttleRedundantChecks = false; //Check less often if we already know that an update is available.
		public $throttledCheckPeriod = 72;

		protected $hourlyCheckHooks = array('load-update.php');
		protected $updateChecker;

		private $cronHook = null;
		public function __construct($updateChecker, $checkPeriod, $hourlyHooks = array('load-plugins.php')) {
			$this->updateChecker = $updateChecker;
			$this->checkPeriod = $checkPeriod;

			//Set up the periodic update checks
			$this->cronHook = $this->updateChecker->getUniqueName('cron_check_updates');
			if ( $this->checkPeriod > 0 ){

				//Trigger the check via Cron.
				//Try to use one of the default schedules if possible as it's less likely to conflict
				//with other plugins and their custom schedules.
				$defaultSchedules = array(
					1  => 'hourly',
					12 => 'twicedaily',
					24 => 'daily',
				);
				if ( array_key_exists($this->checkPeriod, $defaultSchedules) ) {
					$scheduleName = $defaultSchedules[$this->checkPeriod];
				} else {
					//Use a custom cron schedule.
					$scheduleName = 'every' . $this->checkPeriod . 'hours';
					add_filter('cron_schedules', array($this, '_addCustomSchedule'));
				}

				if ( !wp_next_scheduled($this->cronHook) && !defined('WP_INSTALLING') ) {
					//Randomly offset the schedule to help prevent update server traffic spikes. Without this
					//most checks may happen during times of day when people are most likely to install new plugins.
					$firstCheckTime = time() - rand(0, max($this->checkPeriod * 3600 - 15 * 60, 1));
					$firstCheckTime = apply_filters(
						$this->updateChecker->getUniqueName('first_check_time'),
						$firstCheckTime
					);
					wp_schedule_event($firstCheckTime, $scheduleName, $this->cronHook);
				}
				add_action($this->cronHook, array($this, 'maybeCheckForUpdates'));

				//In case Cron is disabled or unreliable, we also manually trigger
				//the periodic checks while the user is browsing the Dashboard.
				add_action( 'admin_init', array($this, 'maybeCheckForUpdates') );

				//Like WordPress itself, we check more often on certain pages.
				add_action('load-update-core.php', array($this, 'maybeCheckForUpdates'));
				//"load-update.php" and "load-plugins.php" or "load-themes.php".
				$this->hourlyCheckHooks = array_merge($this->hourlyCheckHooks, $hourlyHooks);
				foreach($this->hourlyCheckHooks as $hook) {
					add_action($hook, array($this, 'maybeCheckForUpdates'));
				}
				//This hook fires after a bulk update is complete.
				add_action('upgrader_process_complete', array($this, 'upgraderProcessComplete'), 11, 2);

			} else {
				//Periodic checks are disabled.
				wp_clear_scheduled_hook($this->cronHook);
			}
		}
		public function upgraderProcessComplete(
			$upgrader, $upgradeInfo
		) {
			//Cancel all further actions if the current version of PUC has been deleted or overwritten
			//by a different version during the upgrade. If we try to do anything more in that situation,
			//we could trigger a fatal error by trying to autoload a deleted class.
			clearstatcache();
			if ( !file_exists(__FILE__) ) {
				$this->removeHooks();
				$this->updateChecker->removeHooks();
				return;
			}

			//Sanity check and limitation to relevant types.
			if (
				!is_array($upgradeInfo) || !isset($upgradeInfo['type'], $upgradeInfo['action'])
				|| 'update' !== $upgradeInfo['action'] || !in_array($upgradeInfo['type'], array('plugin', 'theme'))
			) {
				return;
			}

			//Filter out notifications of upgrades that should have no bearing upon whether or not our
			//current info is up-to-date.
			if ( is_a($this->updateChecker, 'Puc_v4p11_Theme_UpdateChecker') ) {
				if ( 'theme' !== $upgradeInfo['type'] || !isset($upgradeInfo['themes']) ) {
					return;
				}

				//Letting too many things going through for checks is not a real problem, so we compare widely.
				if ( !in_array(
					strtolower($this->updateChecker->directoryName),
					array_map('strtolower', $upgradeInfo['themes'])
				) ) {
					return;
				}
			}

			if ( is_a($this->updateChecker, 'Puc_v4p11_Plugin_UpdateChecker') ) {
				if ( 'plugin' !== $upgradeInfo['type'] || !isset($upgradeInfo['plugins']) ) {
					return;
				}

				//Themes pass in directory names in the information array, but plugins use the relative plugin path.
				if ( !in_array(
					strtolower($this->updateChecker->directoryName),
					array_map('dirname', array_map('strtolower', $upgradeInfo['plugins']))
				) ) {
					return;
				}
			}

			$this->maybeCheckForUpdates();
		}
		public function maybeCheckForUpdates() {
			if ( empty($this->checkPeriod) ){
				return;
			}

			$state = $this->updateChecker->getUpdateState();
			$shouldCheck = ($state->timeSinceLastCheck() >= $this->getEffectiveCheckPeriod());

			//Let plugin authors substitute their own algorithm.
			$shouldCheck = apply_filters(
				$this->updateChecker->getUniqueName('check_now'),
				$shouldCheck,
				$state->getLastCheck(),
				$this->checkPeriod
			);

			if ( $shouldCheck ) {
				$this->updateChecker->checkForUpdates();
			}
		}
		protected function getEffectiveCheckPeriod() {
			$currentFilter = current_filter();
			if ( in_array($currentFilter, array('load-update-core.php', 'upgrader_process_complete')) ) {
				//Check more often when the user visits "Dashboard -> Updates" or does a bulk update.
				$period = 60;
			} else if ( in_array($currentFilter, $this->hourlyCheckHooks) ) {
				//Also check more often on /wp-admin/update.php and the "Plugins" or "Themes" page.
				$period = 3600;
			} else if ( $this->throttleRedundantChecks && ($this->updateChecker->getUpdate() !== null) ) {
				//Check less frequently if it's already known that an update is available.
				$period = $this->throttledCheckPeriod * 3600;
			} else if ( defined('DOING_CRON') && constant('DOING_CRON') ) {
				//WordPress cron schedules are not exact, so lets do an update check even
				//if slightly less than $checkPeriod hours have elapsed since the last check.
				$cronFuzziness = 20 * 60;
				$period = $this->checkPeriod * 3600 - $cronFuzziness;
			} else {
				$period = $this->checkPeriod * 3600;
			}

			return $period;
		}
		public function _addCustomSchedule($schedules) {
			if ( $this->checkPeriod && ($this->checkPeriod > 0) ){
				$scheduleName = 'every' . $this->checkPeriod . 'hours';
				$schedules[$scheduleName] = array(
					'interval' => $this->checkPeriod * 3600,
					'display' => sprintf('Every %d hours', $this->checkPeriod),
				);
			}
			return $schedules;
		}
		public function removeUpdaterCron() {
			wp_clear_scheduled_hook($this->cronHook);
		}
		public function getCronHookName() {
			return $this->cronHook;
		}
		public function removeHooks() {
			remove_filter('cron_schedules', array($this, '_addCustomSchedule'));
			remove_action('admin_init', array($this, 'maybeCheckForUpdates'));
			remove_action('load-update-core.php', array($this, 'maybeCheckForUpdates'));

			if ( $this->cronHook !== null ) {
				remove_action($this->cronHook, array($this, 'maybeCheckForUpdates'));
			}
			if ( !empty($this->hourlyCheckHooks) ) {
				foreach ($this->hourlyCheckHooks as $hook) {
					remove_action($hook, array($this, 'maybeCheckForUpdates'));
				}
			}
		}
	}

endif;
