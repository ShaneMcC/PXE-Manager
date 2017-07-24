<?php

class UserPassAuthProvider_User extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'username' => NULL,
	                             'password' => NULL,
	                             'realname' => NULL,
	                             'enabled' => false,
	                            ];
	protected static $_key = 'id';
	protected static $_table = 'UserPassAuthProvider_Users';

	// Permissions levels for unknown objects.
	protected $_permissions = [];

	public function __construct($db) {
		parent::__construct($db);
	}

	public function setUsername($value) {
		return $this->setData('username', strtolower($value));
	}

	public function setRealName($value) {
		return $this->setData('realname', $value);
	}

	public function setPassword($value) {
		return $this->setData('password', bcrypt::hash($value));
	}

	public function setRawPassword($value) {
		return $this->setData('password', $value);
	}

	public function setEnabled($value) {
		return $this->setData('enabled', parseBool($value));
	}

	public function getID() {
		return $this->getData('id');
	}

	public function getUsername() {
		return $this->getData('username');
	}

	public function getRealName() {
		return $this->getData('realname');
	}

	public function checkPassword($password) {
		$testPass = $this->getData('password');

		return bcrypt::check($password, $testPass);
	}

	public function getRawPassword() {
		return $this->getData('password');
	}

	public function isEnabled() {
		return parseBool($this->getData('enabled'));
	}

	public function getPermissions() {
		return $this->_permissions;
	}

	public function getPermission($permission) {
		return array_key_exists($permission, $this->_permissions) ? $this->_permissions[$permission] : false;
	}

	public function setPermission($permission, $value) {
		$value = parseBool($value);
		if ($permission == 'all') {
			foreach (array_keys(AuthProvider::$VALID_PERMISSIONS) as $p) {
				$this->setPermission($p, $value);
			}
			return $this;
		}

		if (in_array($permission, array_keys(AuthProvider::$VALID_PERMISSIONS))) {
			if ($value && !array_key_exists($permission, $this->_permissions)) {
				$this->_permissions[$permission] = true;
				$this->setChanged();
			} else if (!$value && array_key_exists($permission, $this->_permissions)) {
				unset($this->_permissions[$permission]);
				$this->setChanged();
			}
		}
		return $this;
	}


	/**
	 * How many users do we have?
	 *
	 * @return User count.
	 */
	public static function getUserCount($db) {
		$query = "SELECT count(id) AS `count` FROM `UserPassAuthProvider_Users`";
		$statement = $db->getPDO()->prepare($query);
		$statement->execute();
		$result = $statement->fetch(PDO::FETCH_ASSOC);
		return !isset($result['count']) ? 0 : $result['count'];
	}

	/**
	 * Load an object from the database based on username
	 *
	 * @param $db Database object to load from.
	 * @param $username Username to look for.
	 * @return FALSE if no object exists, else the object.
	 */
	public static function loadFromUsername($db, $username) {
		$result = static::find($db, ['username' => strtolower($username)]);
		if ($result) {
			return $result[0];
		} else {
			return FALSE;
		}
	}

	/**
	 * Validate the user account.
	 *
	 * @return TRUE if validation succeeded
	 * @throws ValidationFailed if there is an error.
	 */
	public function validate() {
		$required = ['password', 'username', 'realname'];
		foreach ($required as $r) {
			if (!$this->hasData($r)) {
				throw new ValidationFailed('Missing required field: '. $r);
			}
		}

		return TRUE;
	}

	public function postSave($result) {
		if ($result) {
			// Persist permission changes
			$setQuery = 'INSERT OR IGNORE INTO UserPassAuthProvider_Permissions (`user_id`, `permission`) VALUES (:user, :permission)';
			$setStatement = $this->getDB()->getPDO()->prepare($setQuery);

			$params = [':user' => $this->getID()];
			$removeParams = [];
			$removeID = 0;
			foreach ($this->_permissions as $permission => $access) {
				$params[':permission'] = $permission;
				$setStatement->execute($params);

				$removeParams[':permission_' . $removeID++] = $permission;
			}

			if (count($removeParams) > 0) {
				$removeQuery = sprintf('DELETE FROM UserPassAuthProvider_Permissions WHERE `user_id` = :user AND `permission` NOT IN (%s)', implode(', ', array_keys($removeParams)));
			} else {
				$removeQuery = sprintf('DELETE FROM UserPassAuthProvider_Permissions WHERE `user_id` = :user');
			}
			$removeStatement = $this->getDB()->getPDO()->prepare($removeQuery);
			$removeStatement->execute(array_merge([':user' => $this->getID()], $removeParams));
		}
	}

	public function postLoad() {
		// Get access levels;
		$query = 'SELECT `permission` FROM UserPassAuthProvider_Permissions WHERE `user_id` = :user';
		$params = [':user' => $this->getID()];
		$statement = $this->getDB()->getPDO()->prepare($query);
		$statement->execute($params);
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);

		foreach ($result as $row) {
			$this->setPermission($row['permission'], true);
		}
	}

	public function toArray() {
		$result = parent::toArray();
		$result['permissions'] = $this->_permissions;

		return $result;
	}
}
