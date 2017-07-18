<?php

class NullAuthProvider extends AuthProvider {
	public function checkSession($sessionData) {
		// Do Nothing, always authenticated.
	}

	public function isAuthenticated() {
		return true;
	}

	public function getPermissions() {
		$permissions = [];
		foreach (array_keys(AuthProvider::$VALID_PERMISSIONS) as $p) {
			$permissions[$p] = true;
		}

		return $permissions;
	}
}
