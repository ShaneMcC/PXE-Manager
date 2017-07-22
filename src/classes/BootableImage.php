<?php

class BootableImage extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'name' => NULL,
	                             'variables' => NULL,
	                             'pxedata' => NULL,
	                             'script' => NULL,
	                             'postinstall' => NULL,
	                            ];
	protected static $_json_fields = ['variables'];

	protected static $_key = 'id';
	protected static $_table = 'bootableimages';

	protected static $_VARIABLE_TYPES = ['ipv4', 'ipv6', 'ip', 'integer', 'string', 'text', 'yesno', 'selectoption'];
	protected static $_VARIABLE_HASDATA = ['string', 'selectoption'];

	public function __construct($db) {
		parent::__construct($db);
	}

	public function setName($value) {
		return $this->setData('name', $value);
	}

	public function setVariable($name, $description, $type = 'string', $data = '', $required = true) {
		$vars = $this->getData('variables');
		$vars[strtolower($name)] = ['description' => $description, 'type' => $type, 'required' => parseBool($required)];

		if (in_array(strtolower($type), self::$_VARIABLE_HASDATA)) {
			$vars[strtolower($name)]['data'] = $data;
		}

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

	public function setPostInstall($value) {
		return $this->setData('postinstall', $value);
	}

	public function getID() {
		return $this->getData('id');
	}

	public function getName() {
		return $this->getData('name');
	}

	public function getVariables() {
		$vars = $this->getData('variables');
		return is_array($vars) ? $vars : [];
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

	public function getPostInstall() {
		return $this->getData('postinstall');
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

		foreach ($this->getData('variables') as $var => $vardata) {
			if (!in_array($vardata['type'], self::$_VARIABLE_TYPES)) {
				throw new ValidationFailed('Unknown variable type "'. $vardata['type'] .'" for: '. $var);
			}

			if ($vardata['type'] == 'selectoption' && empty($vardata['data'])) {
				throw new ValidationFailed('SelectOption variable type must have data for: '. $var);
			}
		}

		return TRUE;
	}

	public function validateVariables($vars) {
		$myVars = $this->getData('variables');

		foreach ($vars as $var => $value) {
			if (!array_key_exists($var, $myVars)) {
				throw new ValidationFailed('Unknown variable name: '. $var);
			}
			$myDesc = $myVars[$var]['description'];
			$myType = $myVars[$var]['type'];
			$myData = isset($myVars[$var]['data']) ? $myVars[$var]['data'] : '';
			$valueRequired = isset($myVars[$var]['required']) ? parseBool($myVars[$var]['required']) : true;

			if ($value === '') {
				if ($valueRequired) {
					throw new ValidationFailed('Variable value required for: '. $myDesc);
				} else {
					continue;
				}
			} else if ($myType == 'selectoption') {
				$options = explode("|", $myData);

				if (!in_array($value, $options)) {
					throw new ValidationFailed('Unknown variable value "' . $value . '" for: '. $myDesc);
				}
			} else if ($myType == 'string') {
				$regex = $myData;
				$fullRegex = '/^' . $regex . '$/';

				if (!empty($regex) && !preg_match($fullRegex, $value)) {
					throw new ValidationFailed('Variable value "' . $value . '" does not match "' . $fullRegex . '" for: '. $myDesc);
				}
			} else if ($myType == 'integer') {
				if (filter_var($value, FILTER_VALIDATE_INT) === FALSE) {
					throw new ValidationFailed('Variable value "' . $value . '" is not a valid integer: '. $myDesc);
				}
			} else if ($myType == 'ipv4') {
				if (!$valueRequired && empty($value)) { continue; }

				if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === FALSE) {
					throw new ValidationFailed('Variable value "' . $value . '" is not a valid IPv4 Address for: '. $myDesc);
				}
			} else if ($myType == 'ipv6') {
				if (!$valueRequired && empty($value)) { continue; }

				if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === FALSE) {
					throw new ValidationFailed('Variable value "' . $value . '" is not a valid IPv6 Address for: '. $myDesc);
				}
			} else if ($myType == 'ip') {
				if (!$valueRequired && empty($value)) { continue; }

				if (filter_var($value, FILTER_VALIDATE_IP) === FALSE) {
					throw new ValidationFailed('Variable value "' . $value . '" is not a valid IP Address for: '. $myDesc);
				}
			}
		}

		return TRUE;
	}

	public function postSave($result) {
		if (!$result) { return; }

		foreach ($this->getServers() as $server) {
			$server->generateConfig();
		}
	}
}
