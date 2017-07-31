<?php

use shanemcc\phpdb\DBObject;
use shanemcc\phpdb\ValidationFailed;

class ServerLog extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'server' => NULL,
	                             'time' => NULL,
	                             'type' => NULL,
	                             'entry' => NULL,
	                            ];
	protected static $_key = 'id';
	protected static $_table = 'serverlogs';

	public function __construct($db) {
		parent::__construct($db);
	}

	public function setServer($value) {
		return $this->setData('server', $value);
	}

	public function setTime($value) {
		return $this->setData('time', $value);
	}

	public function setType($value) {
		return $this->setData('type', $value);
	}

	public function setEntry($value) {
		return $this->setData('entry', $value);
	}

	public function getID() {
		return $this->getData('id');
	}

	public function getServer() {
		return $this->getData('server');
	}

	public function getTime() {
		return $this->getData('time');
	}

	public function getType() {
		return $this->getData('type');
	}

	public function getEntry() {
		return $this->getData('entry');
	}

	public function getServerObject() {
		return Server::load($this->getDB(), $this->getData('server'));
	}

	/**
	 * Validate the Server.
	 *
	 * @return TRUE if validation succeeded
	 * @throws ValidationFailed if there is an error.
	 */
	public function validate() {
		$required = ['server', 'time', 'type', 'entry'];
		foreach ($required as $r) {
			if (!$this->hasData($r) || empty($this->getData($r))) {
				throw new ValidationFailed('Missing required field: '. $r);
			}
		}

		$server = $this->getServerObject();
		if (!($server instanceof Server)) {
			throw new ValidationFailed('Unknown Server.');
		}

		return TRUE;
	}
}
