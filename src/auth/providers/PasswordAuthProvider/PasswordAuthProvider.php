<?php

class PasswordAuthProvider extends LoginAuthProvider implements RouteProvider {
	protected function providerInit() {
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
