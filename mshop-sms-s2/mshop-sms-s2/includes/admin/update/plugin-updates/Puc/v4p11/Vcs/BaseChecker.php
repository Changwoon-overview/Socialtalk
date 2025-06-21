<?php
if ( !interface_exists('Puc_v4p11_Vcs_BaseChecker', false) ):

	interface Puc_v4p11_Vcs_BaseChecker {
		public function setBranch($branch);
		public function setAuthentication($credentials);
		public function getVcsApi();
	}

endif;
