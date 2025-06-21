<?php
if ( !class_exists('Puc_v4p11_Vcs_Api') ):

	abstract class Puc_v4p11_Vcs_Api {
		protected $tagNameProperty = 'name';
		protected $slug = '';
		protected $repositoryUrl = '';
		protected $credentials = null;
		protected $httpFilterName = '';
		protected $localDirectory = null;
		public function __construct($repositoryUrl, $credentials = null) {
			$this->repositoryUrl = $repositoryUrl;
			$this->setAuthentication($credentials);
		}
		public function getRepositoryUrl() {
			return $this->repositoryUrl;
		}
		abstract public function chooseReference($configBranch);
		public function getRemoteReadme($ref = 'master') {
			$fileContents = $this->getRemoteFile($this->getLocalReadmeName(), $ref);
			if ( empty($fileContents) ) {
				return array();
			}

			$parser = new PucReadmeParser();
			return $parser->parse_readme_contents($fileContents);
		}
		public function getLocalReadmeName() {
			static $fileName = null;
			if ( $fileName !== null ) {
				return $fileName;
			}

			$fileName = 'readme.txt';
			if ( isset($this->localDirectory) ) {
				$files = scandir($this->localDirectory);
				if ( !empty($files) ) {
					foreach ($files as $possibleFileName) {
						if ( strcasecmp($possibleFileName, 'readme.txt') === 0 ) {
							$fileName = $possibleFileName;
							break;
						}
					}
				}
			}
			return $fileName;
		}
		abstract public function getBranch($branchName);
		abstract public function getTag($tagName);
		abstract public function getLatestTag();
		protected function looksLikeVersion($name) {
			//Tag names may be prefixed with "v", e.g. "v1.2.3".
			$name = ltrim($name, 'v');

			//The version string must start with a number.
			if ( !is_numeric(substr($name, 0, 1)) ) {
				return false;
			}

			//The goal is to accept any SemVer-compatible or "PHP-standardized" version number.
			return (preg_match('@^(\d{1,5}?)(\.\d{1,10}?){0,4}?($|[abrdp+_\-]|\s)@i', $name) === 1);
		}
		protected function isVersionTag($tag) {
			$property = $this->tagNameProperty;
			return isset($tag->$property) && $this->looksLikeVersion($tag->$property);
		}
		protected function sortTagsByVersion($tags) {
			//Keep only those tags that look like version numbers.
			$versionTags = array_filter($tags, array($this, 'isVersionTag'));
			//Sort them in descending order.
			usort($versionTags, array($this, 'compareTagNames'));

			return $versionTags;
		}
		protected function compareTagNames($tag1, $tag2) {
			$property = $this->tagNameProperty;
			if ( !isset($tag1->$property) ) {
				return 1;
			}
			if ( !isset($tag2->$property) ) {
				return -1;
			}
			return -version_compare(ltrim($tag1->$property, 'v'), ltrim($tag2->$property, 'v'));
		}
		abstract public function getRemoteFile($path, $ref = 'master');
		abstract public function getLatestCommitTime($ref);
		public function getRemoteChangelog($ref, $localDirectory) {
			$filename = $this->findChangelogName($localDirectory);
			if ( empty($filename) ) {
				return null;
			}

			$changelog = $this->getRemoteFile($filename, $ref);
			if ( $changelog === null ) {
				return null;
			}
			return Parsedown::instance()->text($changelog);
		}
		protected function findChangelogName($directory = null) {
			if ( !isset($directory) ) {
				$directory = $this->localDirectory;
			}
			if ( empty($directory) || !is_dir($directory) || ($directory === '.') ) {
				return null;
			}

			$possibleNames = array('CHANGES.md', 'CHANGELOG.md', 'changes.md', 'changelog.md');
			$files = scandir($directory);
			$foundNames = array_intersect($possibleNames, $files);

			if ( !empty($foundNames) ) {
				return reset($foundNames);
			}
			return null;
		}
		public function setAuthentication($credentials) {
			$this->credentials = $credentials;
		}

		public function isAuthenticationEnabled() {
			return !empty($this->credentials);
		}
		public function signDownloadUrl($url) {
			return $url;
		}
		public function setHttpFilterName($filterName) {
			$this->httpFilterName = $filterName;
		}
		public function setLocalDirectory($directory) {
			if ( empty($directory) || !is_dir($directory) || ($directory === '.') ) {
				$this->localDirectory = null;
			} else {
				$this->localDirectory = $directory;
			}
		}
		public function setSlug($slug) {
			$this->slug = $slug;
		}
	}

endif;
