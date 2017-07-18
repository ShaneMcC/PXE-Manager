<?php

abstract class AuthProvider {
	protected static $VALID_PERMISSIONS = ['view_images' => 'View Images',
	                                       'edit_images' => 'Edit Images',

	                                       'view_servers' => 'View Servers',
	                                       'edit_servers' => 'Edit Servers',
	                                      ];

	abstract public function checkSession($sessionData);
	abstract public function isAuthenticated();
	abstract public function getPermissions();

	/**
	 * Check permissions.
	 * Check if the user has all of the required permissions.
	 *
	 * @param $permissions Permissions required.
	 */
	public function checkPermissions($permissions) {
		$access = $this->getPermissions();

		if (!is_array($permissions)) { $permissions = array($permissions); }
		foreach ($permissions as $permission) {
			if ($access === NULL || !array_key_exists($permission, $access) || !parseBool($access[$permission])) {
				return false;
			}
		}

		return true;
	}

	public function handle404($router, $displayEngine, $wanted) { return false; }
}
