<?php
if ( !class_exists('Puc_v4p11_Vcs_BitBucketApi', false) ):

	class Puc_v4p11_Vcs_BitBucketApi extends Puc_v4p11_Vcs_Api {
		private $oauth = null;
		private $username;
		private $repository;

		public function __construct($repositoryUrl, $credentials = array()) {
			$path = parse_url($repositoryUrl, PHP_URL_PATH);
			if ( preg_match('@^/?(?P<username>[^/]+?)/(?P<repository>[^/#?&]+?)/?$@', $path, $matches) ) {
				$this->username = $matches['username'];
				$this->repository = $matches['repository'];
			} else {
				throw new InvalidArgumentException('Invalid BitBucket repository URL: "' . $repositoryUrl . '"');
			}

			parent::__construct($repositoryUrl, $credentials);
		}
		public function chooseReference($configBranch) {
			$updateSource = null;

			//Check if there's a "Stable tag: 1.2.3" header that points to a valid tag.
			$updateSource = $this->getStableTag($configBranch);

			//Look for version-like tags.
			if ( !$updateSource && ($configBranch === 'master') ) {
				$updateSource = $this->getLatestTag();
			}
			//If all else fails, use the specified branch itself.
			if ( !$updateSource ) {
				$updateSource = $this->getBranch($configBranch);
			}

			return $updateSource;
		}

		public function getBranch($branchName) {
			$branch = $this->api('/refs/branches/' . $branchName);
			if ( is_wp_error($branch) || empty($branch) ) {
				return null;
			}

			return new Puc_v4p11_Vcs_Reference(array(
				'name' => $branch->name,
				'updated' => $branch->target->date,
				'downloadUrl' => $this->getDownloadUrl($branch->name),
			));
		}
		public function getTag($tagName) {
			$tag = $this->api('/refs/tags/' . $tagName);
			if ( is_wp_error($tag) || empty($tag) ) {
				return null;
			}

			return new Puc_v4p11_Vcs_Reference(array(
				'name' => $tag->name,
				'version' => ltrim($tag->name, 'v'),
				'updated' => $tag->target->date,
				'downloadUrl' => $this->getDownloadUrl($tag->name),
			));
		}
		public function getLatestTag() {
			$tags = $this->api('/refs/tags?sort=-target.date');
			if ( !isset($tags, $tags->values) || !is_array($tags->values) ) {
				return null;
			}

			//Filter and sort the list of tags.
			$versionTags = $this->sortTagsByVersion($tags->values);

			//Return the first result.
			if ( !empty($versionTags) ) {
				$tag = $versionTags[0];
				return new Puc_v4p11_Vcs_Reference(array(
					'name' => $tag->name,
					'version' => ltrim($tag->name, 'v'),
					'updated' => $tag->target->date,
					'downloadUrl' => $this->getDownloadUrl($tag->name),
				));
			}
			return null;
		}
		protected function getStableTag($branch) {
			$remoteReadme = $this->getRemoteReadme($branch);
			if ( !empty($remoteReadme['stable_tag']) ) {
				$tag = $remoteReadme['stable_tag'];

				//You can explicitly opt out of using tags by setting "Stable tag" to
				//"trunk" or the name of the current branch.
				if ( ($tag === $branch) || ($tag === 'trunk') ) {
					return $this->getBranch($branch);
				}

				return $this->getTag($tag);
			}

			return null;
		}
		protected function getDownloadUrl($ref) {
			return sprintf(
				'https://bitbucket.org/%s/%s/get/%s.zip',
				$this->username,
				$this->repository,
				$ref
			);
		}
		public function getRemoteFile($path, $ref = 'master') {
			$response = $this->api('src/' . $ref . '/' . ltrim($path));
			if ( is_wp_error($response) || !is_string($response) ) {
				return null;
			}
			return $response;
		}
		public function getLatestCommitTime($ref) {
			$response = $this->api('commits/' . $ref);
			if ( isset($response->values, $response->values[0], $response->values[0]->date) ) {
				return $response->values[0]->date;
			}
			return null;
		}
		public function api($url, $version = '2.0') {
			$url = ltrim($url, '/');
			$isSrcResource = Puc_v4p11_Utils::startsWith($url, 'src/');

			$url = implode('/', array(
				'https://api.bitbucket.org',
				$version,
				'repositories',
				$this->username,
				$this->repository,
				$url
			));
			$baseUrl = $url;

			if ( $this->oauth ) {
				$url = $this->oauth->sign($url,'GET');
			}

			$options = array('timeout' => 10);
			if ( !empty($this->httpFilterName) ) {
				$options = apply_filters($this->httpFilterName, $options);
			}
			$response = wp_remote_get($url, $options);
			if ( is_wp_error($response) ) {
				do_action('puc_api_error', $response, null, $url, $this->slug);
				return $response;
			}

			$code = wp_remote_retrieve_response_code($response);
			$body = wp_remote_retrieve_body($response);
			if ( $code === 200 ) {
				if ( $isSrcResource ) {
					//Most responses are JSON-encoded, but src resources just
					//return raw file contents.
					$document = $body;
				} else {
					$document = json_decode($body);
				}
				return $document;
			}

			$error = new WP_Error(
				'puc-bitbucket-http-error',
				sprintf('BitBucket API error. Base URL: "%s",  HTTP status code: %d.', $baseUrl, $code)
			);
			do_action('puc_api_error', $error, $response, $url, $this->slug);

			return $error;
		}
		public function setAuthentication($credentials) {
			parent::setAuthentication($credentials);

			if ( !empty($credentials) && !empty($credentials['consumer_key']) ) {
				$this->oauth = new Puc_v4p11_OAuthSignature(
					$credentials['consumer_key'],
					$credentials['consumer_secret']
				);
			} else {
				$this->oauth = null;
			}
		}

		public function signDownloadUrl($url) {
			//Add authentication data to download URLs. Since OAuth signatures incorporate
			//timestamps, we have to do this immediately before inserting the update. Otherwise
			//authentication could fail due to a stale timestamp.
			if ( $this->oauth ) {
				$url = $this->oauth->sign($url);
			}
			return $url;
		}
	}

endif;
