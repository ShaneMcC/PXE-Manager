<?php

class PasswordAuthProvider extends LoginAuthProvider implements RouteProvider {
	protected function providerInit() {
		$possiblePermissions = $this->getConfig('default', []);

		$this->setPermissions($this->calculatePermissions($possiblePermissions));
		$this->setAuthenticated(false);

		$this->setFields(['pass' => ['type' => 'password', 'label' => 'Password', 'placeholder' => 'Password']]);
	}

	protected function providerCheckSession($sessionData) {
		if (isset($sessionData['checkPass'])) {
			foreach ($this->getConfig('passwords', []) as $password => $permissions) {
				if ($sessionData['checkPass'] == sha1($password)) {
					$this->setAuthenticated(true);
					$this->setPermissions($this->calculatePermissions($permissions));
					break;
				}
			}
		}
	}

	private function calculatePermissions($allowedPermissions) {
		$permissions = [];

		if (is_array($allowedPermissions)) {
			foreach (array_keys(AuthProvider::$VALID_PERMISSIONS) as $p) {
			// Check if permission matches a permission in the array.
				foreach ($allowedPermissions as $permission) {
					if (preg_match('#' . str_replace('#', '\\#', $permission) . '#', $p)) {
						$permissions[$p] = true;
					}
				}
			}
		}

		return $permissions;
	}


	protected function checkAuth($vars) {
		$pass = $vars['pass'];

		foreach ($this->getConfig('passwords', []) as $password => $permissions) {
			if ($pass == $password) {
				return ['checkPass' => sha1($pass)];
			}
		}

		return false;
	}

}
