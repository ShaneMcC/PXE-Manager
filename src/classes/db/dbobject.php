<?php

/**
 * Class representing a database object.
 */
abstract class DBObject implements Serializable, JsonSerializable {
	/** Known database fields for this object. */
	protected static $_fields = [];
	/** Array of fields to be encoded/decoded when saving/loading. */
	protected static $_json_fields = [];
	/** Key field name for this object. */
	protected static $_key = 'id';
	/** Table name for this object. */
	protected static $_table = NULL;

	/** Known database values for this object. */
	private $data = [];
	/** Has any of the data been changed since it was last saved/loaded. */
	private $changed = FALSE;
	/** last error for this object. */
	private $lastError = NULL;
	/** The DB Object that owns this DBObject */
	private $myDB = NULL;

	/**
	 * Create a new DBObect
	 *
	 * @param $db Database we belong to
	 */
	public function __construct($db) {
		$this->myDB = $db;
	}

	/**
	 * Get our database.
	 *
	 * @return Database we belong to
	 */
	public function getDB() {
		return $this->myDB;
	}

	/**
	 * Set a data value.
	 *
	 * @param $key Data key (field name)
	 * @param $value Data value.
	 * @return $this for chaining.
	 */
	protected function setData($key, $value) {
		if (static::isField($key)) {
			$this->setChanged($this->changed || $this->getData($key) !== $value);
			if ($value === NULL) {
				unset($this->data[$key]);
			} else {
				$this->data[$key] = $value;
			}
		}

		return $this;
	}

	/**
	 * Get a data value.
	 *
	 * @param $key Data key (field name)
	 * @return Value for $key (Or the default value if not-known).
	 */
	public function getData($key) {
		return static::isField($key) ? ($this->hasData($key) ? $this->data[$key] : static::$_fields[$key]) : NULL;
	}

	/**
	 * Do we have a known value for the given key?
	 *
	 * @param $key Data key (field name)
	 * @return True if we have a specific (non-default) value for the key.
	 */
	protected function hasData($key) {
		return array_key_exists($key, $this->data);
	}

	/**
	 * Is this a known field?
	 *
	 * @param $key Data key (field name)
	 * @return True if this is a known field.
	 */
	protected static function isField($key) {
		return array_key_exists($key, static::$_fields);
	}

	/**
	 * Is this object known in the database yet?
	 * (This is a check to see if we have a known value for the key field)
	 *
	 * @return True if this object is known in the database.
	 */
	public function isKnown() {
		return $this->hasData(static::$_key);
	}

	/**
	 * Get the value of the primary key.
	 *
	 * @return The value of the primary key.
	 */
	public function getKeyValue() {
		return $this->getData(static::$_key);
	}

	/**
	 * Load an object from the database.
	 *
	 * @param $db Database object to load from.
	 * @param $key Primary key value to look for.
	 * @return FALSE if no object exists, else the object.
	 */
	public static function load($db, $key) {
		$result = static::find($db, [static::$_key => $key]);
		if ($result) {
			return $result[0];
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the data fields for this object as an array.
	 *
	 * @return An array with all the field values in it as they are currently
	 *         known. (This will display defaults.)
	 */
	public function toArray() {
		$result = array();
		foreach (array_keys(static::$_fields) as $key) {
			$result[$key] = $this->getData($key);
		}
		return $result;
	}

	/**
	 * Set the data fields as per the given array.
	 * Fields that are not known will be ignored.
	 *
	 * @param $data Array of data to set.
	 */
	private function setFromArray($data, $jsondecode = false) {
		foreach ($data as $key => $value) {
			if ($jsondecode && in_array($key, static::$_json_fields)) {
				$value = json_decode($value, true);
			}
			$this->setData($key, $value);
		}
	}

	/**
	 * Create a clone of this object.
	 *
	 * @return New clone of this object
	 */
	public function clone() {
		$class = get_called_class();
		$obj = new $class($this->_db);
		$obj->setFromArray($this->toArray());
		$obj->setChanged($this->hasChanged());
		return $obj;
	}

	/**
	 * Find an object from the database.
	 *
	 * @param $db Database object to load from.
	 * @param $fields Fields to look for (Array of field => value)
	 * @param $comparators (Optional Array) Comparators to use for fields.
	 * @return FALSE if we were able to find objects, else an array of objects.
	 */
	public static function find($db, $fields, $comparators = []) {
		return self::getSearch($db)->search($fields, $comparators);
	}

	/**
	 * Get a Search class for this object.
	 *
	 * @return new Search class that finds instances of this object.
	 */
	public static function getSearch($db) {
		return new SearchToObject($db, static::$_table, static::$_fields, get_called_class());
	}

	/**
	 * Verify that this object is good for saving.
	 * This is called after pre-save, before attempting to save.
	 *
	 * @return True if ok to save, else false or throw a ValidationFailed
	 *         exception with a reason.
	 * @throws ValidationFailed exception if validation failed.
	 */
	public function validate() { return TRUE; }


	/**
	 * Has this object changed?
	 *
	 * @return True if we have changed.
	 */
	public function hasChanged() { return $this->changed; }

	/**
	 * Set that this object has changed.
	 *
	 * @param $changed Value for changed (Default: true)
	 */
	public function setChanged($changed = true) { $this->changed = $changed; }

	/**
	 * Save this object to the database.
	 * This will attempt an INSERT if isKnown() is false, else an UPDATE.
	 *
	 * @return TRUE if we saved successfully, else false.
	 */
	public function save() {
		$this->presave();

		try {
			if (!$this->validate()) { return FALSE; }
		} catch (Exception $ex) {
			return FALSE;
		}
		if (!$this->hasChanged()) { return TRUE; }

		$keys = [];
		$placeholders = [];
		$sets = [];
		$params = [];

		foreach (array_keys(static::$_fields) as $key) {
			if ($key == static::$_key) { continue; }

			$keys[] = '`' . $key . '`';
			$placeholders[] = ':' . $key;
			$sets[] = '`' . $key . '` = :' . $key;

			$params[':' . $key] = $this->getData($key);
			if (is_bool($params[':' . $key])) { $params[':' . $key] = $params[':' . $key] ? 'true' : 'false'; }
			if (in_array($key, static::$_json_fields)) { $params[':' . $key] = json_encode($params[':' . $key]); }
		}

		if ($this->isKnown()) {
			$keyKey = '`' . static::$_key . '`';
			$keyPlaceholder = ':' . static::$_key;
			$params[$keyPlaceholder] = $this->getData(static::$_key);

			$query = sprintf('UPDATE `%s` SET %s WHERE %s = %s', static::$_table, implode(', ', $sets), $keyKey, $keyPlaceholder);
		} else {
			$query = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', static::$_table, implode(', ', $keys), implode(', ', $placeholders));
		}

		$statement = $this->myDB->getPDO()->prepare($query);
		$result = $statement->execute($params);
		if ($result) {
			if (!$this->isKnown()) {
				$this->setData(static::$_key, $this->myDB->getPDO()->lastInsertId());
			}
			$this->postSave($result);
			$this->setChanged(false);
			return TRUE;
		} else {
			$this->lastError = $statement->errorInfo();
			$this->postSave($result);
			return FALSE;
		}

		return FALSE;
	}

	/**
	 * Delete this object from the database.
	 *
	 * @return TRUE if we were deleted, else false.
	 */
	public function delete() {
		if (!$this->isKnown()) { return FALSE; }
		$query = sprintf('DELETE FROM %s WHERE `%s` = :key', static::$_table, static::$_key);
		$statement = $this->myDB->getPDO()->prepare($query);
		$params[':key'] = $this->getData(static::$_key);
		$result = $statement->execute($params);
		if (!$result) {
			$this->lastError = $statement->errorInfo();
		}

		return $result;
	}

	/**
	 * Get the last error we encountered with the database.
	 *
	 * @return last error.
	 */
	public function getLastError() {
		return $this->lastError;
	}

	/** Hook for after data has been loaded into the object. */
	public function postLoad() { }

	/** Hook for before the object is saved to the database. */
	public function preSave() { }

	/** Hook for after the object is saved to the database. */
	public function postSave($result) { }

	/**
	 * Serialise this object.
	 *
	 * @return String that can be used to recreate this object.
	 */
	public function serialize() {
		return serialize(['data' => $this->toArray(), 'changed' => $this->hasChanged()]);
	}

	/**
	 * Unserialise this object.
	 *
	 * @param $data Data from previous serialization to restore this object.
	 */
	public function unserialize($data) {
		$data = unserialize($data);
		$this->setFromArray($data['data']);
		$this->setChanged($data['changed']);
	}

	/**
	 * Serialise this object to json
	 *
	 * @return JSON Representation of this object.
	 */
    public function jsonSerialize() {
        return $this->toArray();
    }

	/**
	 * Set our database.
	 * This needs to be used on any unserialised objects as PDO can not be
	 * serialised.
	 *
	 * This can only be called if the DB is null.
	 *
	 * @param $db DB to attach this object to.
	 * @return $this for chaining.
	 */
	public function setDB($db) {
		if ($this->myDB != NULL) { throw new Exception('Database can not be changed.'); }
		$this->myDB = $db;
		return $this;
	}
}

class ValidationFailed extends Exception { }
