<?php

use shanemcc\phpdb\DBObject;
use shanemcc\phpdb\ValidationFailed;

class BootableImage extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'name' => NULL,
	                             'variables' => NULL,
	                             'pxedata' => NULL,
	                             'script' => NULL,
	                             'postinstall' => NULL,
	                             'lastmodified' => 0,
	                            ];
	protected static $_json_fields = ['variables'];

	protected static $_key = 'id';
	protected static $_table = 'bootableimages';

	protected static $_VARIABLE_TYPES = ['ipv4', 'ipv6', 'ip', 'integer', 'string', 'text', 'yesno', 'selectoption'];
	protected static $_VARIABLE_HASDATA = ['string', 'selectoption'];

	// Used to update servers after we are deleted.
	protected $myServers = [];

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

	public function setLastModified($value) {
		return $this->setData('lastmodified', $value);
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

	public function getRequiredVariables() {
		$vars = [];

		foreach ($this->getAllImageIDs() as $id) {
			$i = BootableImage::load($this->getDB(), $id);
			$vars = $vars + $i->getVariables();
		}

		return $vars;
	}

	private function getAllImageIDs() {
		$de = getDisplayEngine();
		$twig = $de->getTwig();
		$loader = new BootableImageTwigLoader($this->getDB());
		$twig->setLoader($loader);

		$lookedAt = [];
		$wanted = [];

		$wanted[] = $this->getID();

		while (!empty($wanted)) {
			$lookAt = array_shift($wanted);
			$lookedAt[] = $lookAt;

			foreach (['pxedata', 'script', 'postinstall'] as $bit) {
				$name = $lookAt . '/' . $bit;

				if ($lookAt == $this->getID()) {
					if ($bit == 'pxedata') {
						$code = new Twig_Source($this->getPXEData(), $name);
					} else if ($bit == 'script' || $bit == 'kickstart' || $bit == 'preseed') {
						$code = new Twig_Source($this->getScript(), $name);
					} else if ($bit == 'postinstall') {
						$code = new Twig_Source($this->getPostInstall(), $name);
					}
				} else {
					$code = $loader->getSourceContext($name);
				}

				$tokens = $twig->tokenize($code);
				while (!$tokens->isEOF()) {
					$token = $tokens->getCurrent();
					$tokens->next();

					if ($token->getValue() == 'extends' || $token->getValue() == 'use' || $token->getValue() == 'include') {
						$want = explode('/', strtolower($tokens->getCurrent()->getValue()))[0];

						if (in_array($want, $lookedAt)) {
							throw new Exception('Recursion Error (Found ' . $want . ' already).');
						} else if (!in_array($want, $wanted)) {
							$wanted[] = $want;
						}
					}
				}
			}
		}

		return $lookedAt;
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

	public function getLastModified() {
		return $this->getData('lastmodified');
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

		$vars = $this->getData('variables');
		if (is_array($vars)) {
			foreach ($vars as $var => $vardata) {
				if (!in_array($vardata['type'], self::$_VARIABLE_TYPES)) {
					throw new ValidationFailed('Unknown variable type "'. $vardata['type'] .'" for: '. $var);
				}

				if ($vardata['type'] == 'selectoption' && empty($vardata['data'])) {
					throw new ValidationFailed('SelectOption variable type must have data for: '. $var);
				}
			}
		} else if (!empty($vars)) {
			throw new ValidationFailed('Invalid variables data.');
		}

		// Check that any inheritance makes sense.
		try {
			$this->getAllImageIDs();
		} catch (Exception $e) {
			throw new ValidationFailed($e->getMessage());
		}

		return TRUE;
	}

	public function validateVariables($vars) {
		$myVars = $this->getRequiredVariables();

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

	public function preSave() {
		if ($this->hasChanged()) {
			$this->setLastModified(time());
		}
	}

	public function postSave($result) {
		if (!$result) { return; }

		$this->generateServers();
	}

	public function preDelete() {
		$this->myServers = $this->getServers();
	}

	public function postDelete($result) {
		if (!$result) { return; }

		$this->generateServers();
	}

	private function generateServers() {
		// TODO: Find any servers that use this image, or any image that
		//       references this, and regenerate them.
		// foreach ($this->getServers() as $server) {

		foreach (Server::getSearch($this->getDB())->find() as $server) {
			$server->generateConfig();
		}
	}
}
