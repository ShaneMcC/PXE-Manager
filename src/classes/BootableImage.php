<?php

class BootableImage extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'name' => NULL,
	                             'variables' => NULL,
	                             'pxedata' => NULL,
	                             'script' => NULL,
	                            ];
	protected $variables = [];
	protected static $_json_fields = ['variables'];

	protected static $_key = 'id';
	protected static $_table = 'bootableimages';

	protected static $_VARIABLE_TYPES = ['ipv4', 'string', 'text'];

	public function __construct($db) {
		parent::__construct($db);
	}

	public function setName($value) {
		return $this->setData('name', $value);
	}

	public function setVariable($name, $description, $type = 'string') {
		$vars = $this->getData('variables');
		$vars[strtolower($name)] = ['description' => $description, 'type' => $type];

		return $this->setData('variables', $vars);
	}

	public function delVariable($name) {
		$vars = $this->getData('variables');
		unset($vars[strtolower($name)]);

		return $this->setData('variables', $vars);
	}

	public function setVariables($value) {
		return $this->setData('variables', $value);
	}

	public function setPXEData($value) {
		return $this->setData('pxedata', $value);
	}

	public function setScript($value) {
		return $this->setData('script', $value);
	}

	public function getID() {
		return $this->getData('id');
	}

	public function getName() {
		return $this->getData('name');
	}

	public function getVariables() {
		return $this->getData('variables');
	}

	public function getVariable($name) {
		return $this->getData('variables')[strtolower($name)];
	}

	public function getPXEData() {
		return $this->getData('pxedata');
	}

	public function getScript() {
		return $this->getData('script');
	}

	public function getServers() {
		return Server::getSearch($this->getDB())->where('image', $this->getID())->find();
	}

	/**
	 * Validate the image.
	 *
	 * @return TRUE if validation succeeded
	 * @throws ValidationFailed if there is an error.
	 */
	public function validate() {
		$required = ['name', 'pxedata'];
		foreach ($required as $r) {
			if (!$this->hasData($r) && $this->getData($r) != '') {
				throw new ValidationFailed('Missing required field: '. $r);
			}
		}

		foreach ($this->variables as $var => $vardata) {
			if (!in_array($vardata['type'], self::$_VARIABLE_TYPES)) {
				throw new ValidationFailed('Unknown variable type "'. $vardata['type'] .'" for: '. $var);
			}
		}

		return TRUE;
	}
}
