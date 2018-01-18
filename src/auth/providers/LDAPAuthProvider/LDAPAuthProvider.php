<?php

class LDAPAuthProvider extends LoginAuthProvider implements RouteProvider {
	protected $currentUser = NULL;

	protected function providerInit() {
		$this->setFields(['user' => ['type' => 'text', 'label' => 'Username', 'placeholder' => 'Username'],
		                  'pass' => ['type' => 'password', 'label' => 'Password', 'placeholder' => 'Password'],
		                 ]);
	}

	protected function providerCheckSession($sessionData) {
		// If we get this far, assume we are authenticated.
		$this->setAuthenticated(true);
		$this->setPermissions($sessionData['permissions']);
		$this->currentUser = $sessionData['username'];
	}

	public function getAuthName() {
		return $this->currentUser;
	}

	protected function checkAuth($vars) {
		$server = $this->getConfig('server');
		$binduser = $this->getConfig('binduser', '');
		$bindpass = $this->getConfig('bindpass', '');
		$usernameprop = $this->getConfig('usernameprop', 'sAMAccountName');
		$basedn = $this->getConfig('basedn', '');
		$wantuser = $vars['user'];
		$wantpass = $vars['pass'];
		$permissionMap = $this->getConfig('permissions', []);

		$groups = $this->getLDAPGroups($server, $binduser, $bindpass, $usernameprop, $basedn, $wantuser, $wantpass);

		if ($groups !== FALSE) {
			$permissions = [];

			foreach ($groups as $group) {
				if (isset($permissionMap[$group])) {
					$permissions = array_merge($permissions, $permissionMap[$group]);
				}
			}

			return ['permissions' => $this->calculatePermissions($permissions), 'username' => $wantuser];
		}

		return FALSE;
	}


	private function getLDAPGroups($server, $binduser, $bindpass, $usernameprop, $basedn, $wantuser, $wantpass = FALSE) {
		$returnResult = false;

		$ldap = ldap_connect($server);

		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

		if ($bind = @ldap_bind($ldap, $binduser, $bindpass)) {
			$filter = '(' . $usernameprop . '=' . $wantuser . ')';
			$attr = ['memberof'];
			$result = @ldap_search($ldap, $basedn, $filter, $attr);
			$permissions = [];
			if ($result !== false) {
				$entries = ldap_get_entries($ldap, $result);

				if (isset($entries[0])) {
					$userdn = $entries[0]['dn'];

					if (isset($entries[0]['memberof'])) {
						$usergroups = $entries[0]['memberof'];
					} else {
						$usergroups = [];
						$filter = '(member=' . $userdn . ')';
						$attr = ['member'];
						$result = @ldap_search($ldap, $basedn, $filter, $attr);
						if ($result !== false) {
							$entries = ldap_get_entries($ldap, $result);

							foreach ($entries as $entry) {
								if (isset($entry['dn'])) {
									$usergroups[] = $entry['dn'];
								}
							}
						}
					}

					if ($wantpass === FALSE || $userbind = @ldap_bind($ldap, $userdn, $wantpass)) {
						$returnResult = $usergroups;
					}
				}
			}
		}

		@ldap_unbind($ldap);
		return $returnResult;
	}
}
