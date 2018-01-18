<?php

abstract class AuthProvider {
	public static $VALID_PERMISSIONS = ['view_images' => 'View Images',
	                                    'edit_images' => 'Edit Images',

	                                    'view_servers' => 'View Servers',
	                                    'edit_servers' => 'Edit Servers',
	                                   ];

	abstract public function checkSession($sessionData);
	abstract public function isAuthenticated();
	abstract public function getPermissions();

	/**
	 * If this auth provider provides user identification, this will return
	 * the current username.
	 *
	 * @return Current user username if there is one, else FALSE.
	 */
	public function getAuthName() {
		return FALSE;
	}

	/**
	 * Check permissions.
	 * Check if the user has all of the required permissions.
	 *
	 * @param $permissions Permissions required.
	 */
	public final function checkPermissions($permissions) {
		global $config;
		$access = $this->isAuthenticated() ? $this->getPermissions() : $this->calculatePermissions($config['authProvider']['default']);

		if (!is_array($permissions)) { $permissions = array($permissions); }
		foreach ($permissions as $permission) {
			if ($access === NULL || !array_key_exists($permission, $access) || !parseBool($access[$permission])) {
				return false;
			}
		}

		return true;
	}

	protected function calculatePermissions($allowedPermissions) {
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

	public function handle404($router, $displayEngine, $wanted) { return false; }
}
