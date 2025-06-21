<?php
if ( !class_exists('Puc_v4p11_Plugin_Ui', false) ):
	class Puc_v4p11_Plugin_Ui {
		private $updateChecker;
		private $manualCheckErrorTransient = '';
		public function __construct($updateChecker) {
			$this->updateChecker = $updateChecker;
			$this->manualCheckErrorTransient = $this->updateChecker->getUniqueName('manual_check_errors');

			add_action('admin_init', array($this, 'onAdminInit'));
		}

		public function onAdminInit() {
			if ( $this->updateChecker->userCanInstallUpdates() ) {
				$this->handleManualCheck();

				add_filter('plugin_row_meta', array($this, 'addViewDetailsLink'), 10, 3);
				add_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10, 2);
				add_action('all_admin_notices', array($this, 'displayManualCheckResult'));
			}
		}
		public function addViewDetailsLink($pluginMeta, $pluginFile, $pluginData = array()) {
			if ( $this->isMyPluginFile($pluginFile) && !isset($pluginData['slug']) ) {
				$linkText = apply_filters($this->updateChecker->getUniqueName('view_details_link'), __('View details'));
				if ( !empty($linkText) ) {
					$viewDetailsLinkPosition = 'append';

					//Find the "Visit plugin site" link (if present).
					$visitPluginSiteLinkIndex = count($pluginMeta) - 1;
					if ( $pluginData['PluginURI'] ) {
						$escapedPluginUri = esc_url($pluginData['PluginURI']);
						foreach ($pluginMeta as $linkIndex => $existingLink) {
							if ( strpos($existingLink, $escapedPluginUri) !== false ) {
								$visitPluginSiteLinkIndex = $linkIndex;
								$viewDetailsLinkPosition = apply_filters(
									$this->updateChecker->getUniqueName('view_details_link_position'),
									'before'
								);
								break;
							}
						}
					}

					$viewDetailsLink = sprintf('<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
						esc_url(network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . urlencode($this->updateChecker->slug) .
							'&TB_iframe=true&width=600&height=550')),
						esc_attr(sprintf(__('More information about %s'), $pluginData['Name'])),
						esc_attr($pluginData['Name']),
						$linkText
					);
					switch ($viewDetailsLinkPosition) {
						case 'before':
							array_splice($pluginMeta, $visitPluginSiteLinkIndex, 0, $viewDetailsLink);
							break;
						case 'after':
							array_splice($pluginMeta, $visitPluginSiteLinkIndex + 1, 0, $viewDetailsLink);
							break;
						case 'replace':
							$pluginMeta[$visitPluginSiteLinkIndex] = $viewDetailsLink;
							break;
						case 'append':
						default:
							$pluginMeta[] = $viewDetailsLink;
							break;
					}
				}
			}
			return $pluginMeta;
		}
		public function addCheckForUpdatesLink($pluginMeta, $pluginFile) {
			if ( $this->isMyPluginFile($pluginFile) ) {
				$linkUrl = wp_nonce_url(
					add_query_arg(
						array(
							'puc_check_for_updates' => 1,
							'puc_slug'              => $this->updateChecker->slug,
						),
						self_admin_url('plugins.php')
					),
					'puc_check_for_updates'
				);

				$linkText = apply_filters(
					$this->updateChecker->getUniqueName('manual_check_link'),
					__('Check for updates', 'plugin-update-checker')
				);
				if ( !empty($linkText) ) {
					$pluginMeta[] = sprintf('<a href="%s">%s</a>', esc_attr($linkUrl), $linkText);
				}
			}
			return $pluginMeta;
		}

		protected function isMyPluginFile($pluginFile) {
			return ($pluginFile == $this->updateChecker->pluginFile)
				|| (!empty($this->updateChecker->muPluginFile) && ($pluginFile == $this->updateChecker->muPluginFile));
		}
		public function handleManualCheck() {
			$shouldCheck =
				isset($_GET['puc_check_for_updates'], $_GET['puc_slug'])
				&& $_GET['puc_slug'] == $this->updateChecker->slug
				&& check_admin_referer('puc_check_for_updates');

			if ( $shouldCheck ) {
				$update = $this->updateChecker->checkForUpdates();
				$status = ($update === null) ? 'no_update' : 'update_available';
				$lastRequestApiErrors = $this->updateChecker->getLastRequestApiErrors();

				if ( ($update === null) && !empty($lastRequestApiErrors) ) {
					//Some errors are not critical. For example, if PUC tries to retrieve the readme.txt
					//file from GitHub and gets a 404, that's an API error, but it doesn't prevent updates
					//from working. Maybe the plugin simply doesn't have a readme.
					//Let's only show important errors.
					$foundCriticalErrors = false;
					$questionableErrorCodes = array(
						'puc-github-http-error',
						'puc-gitlab-http-error',
						'puc-bitbucket-http-error',
					);

					foreach ($lastRequestApiErrors as $item) {
						$wpError = $item['error'];
						if ( !in_array($wpError->get_error_code(), $questionableErrorCodes) ) {
							$foundCriticalErrors = true;
							break;
						}
					}

					if ( $foundCriticalErrors ) {
						$status = 'error';
						set_site_transient($this->manualCheckErrorTransient, $lastRequestApiErrors, 60);
					}
				}

				wp_redirect(add_query_arg(
					array(
						'puc_update_check_result' => $status,
						'puc_slug'                => $this->updateChecker->slug,
					),
					self_admin_url('plugins.php')
				));
				exit;
			}
		}
		public function displayManualCheckResult() {
			if ( isset($_GET['puc_update_check_result'], $_GET['puc_slug']) && ($_GET['puc_slug'] == $this->updateChecker->slug) ) {
				$status = strval($_GET['puc_update_check_result']);
				$title = $this->updateChecker->getInstalledPackage()->getPluginTitle();
				$noticeClass = 'updated notice-success';
				$details = '';

				if ( $status == 'no_update' ) {
					$message = sprintf(_x('The %s plugin is up to date.', 'the plugin title', 'plugin-update-checker'), $title);
				} else if ( $status == 'update_available' ) {
					$message = sprintf(_x('A new version of the %s plugin is available.', 'the plugin title', 'plugin-update-checker'), $title);
				} else if ( $status === 'error' ) {
					$message = sprintf(_x('Could not determine if updates are available for %s.', 'the plugin title', 'plugin-update-checker'), $title);
					$noticeClass = 'error notice-error';

					$details = $this->formatManualCheckErrors(get_site_transient($this->manualCheckErrorTransient));
					delete_site_transient($this->manualCheckErrorTransient);
				} else {
					$message = sprintf(__('Unknown update checker status "%s"', 'plugin-update-checker'), htmlentities($status));
					$noticeClass = 'error notice-error';
				}
				printf(
					'<div class="notice %s is-dismissible"><p>%s</p>%s</div>',
					$noticeClass,
					apply_filters($this->updateChecker->getUniqueName('manual_check_message'), $message, $status),
					$details
				);
			}
		}
		protected function formatManualCheckErrors($errors) {
			if ( empty($errors) ) {
				return '';
			}
			$output = '';

			$showAsList = count($errors) > 1;
			if ( $showAsList ) {
				$output .= '<ol>';
				$formatString = '<li>%1$s <code>%2$s</code></li>';
			} else {
				$formatString = '<p>%1$s <code>%2$s</code></p>';
			}
			foreach ($errors as $item) {
				$wpError = $item['error'];
				$output .= sprintf(
					$formatString,
					$wpError->get_error_message(),
					$wpError->get_error_code()
				);
			}
			if ( $showAsList ) {
				$output .= '</ol>';
			}

			return $output;
		}

		public function removeHooks() {
			remove_action('admin_init', array($this, 'onAdminInit'));
			remove_filter('plugin_row_meta', array($this, 'addViewDetailsLink'), 10);
			remove_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10);
			remove_action('all_admin_notices', array($this, 'displayManualCheckResult'));
		}
	}
endif;
