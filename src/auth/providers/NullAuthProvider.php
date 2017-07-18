<?php

class NullAuthProvider extends AuthProvider {
	public function checkSession($sessionData) {
		// Do Nothing, always authenticated.
	}

	public function isAuthenticated() {
		return false;
	}

	public function getPermissions() {
		return [];
	}
}
