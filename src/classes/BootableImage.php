<?php

use shanemcc\phpdb\DBObject;
use shanemcc\phpdb\ValidationFailed;
use Twig\TwigFunction as Twig_Function;

class BootableImage extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'name' => NULL,
	                             'variables' => NULL,
	                             'pxedata' => NULL,
	                             'script' => NULL,
	                             'postinstall' => NULL,
	                             'lastmodified' => 0,
	                             'available' => FALSE,
	                            ];
	protected static $_json_fields = ['variables'];

	protected static $_key = 'id';
	protected static $_table = 'bootableimages';

	protected static $_VARIABLE_TYPES = ['ipv4', 'ipv6', 'ip', 'integer', 'string', 'text', 'yesno', 'selectoption', 'none'];
	protected static $_VARIABLE_HASDATA = ['string', 'selectoption', 'none'];

	// Used to update servers after we are deleted.
	protected $myServers = [];

	public function __construct($db) {
		parent::__construct($db);
	}

	public function setName($value) {
		return $this->setData('name', $value);
	}

	public function setVariable($name, $description, $type = 'string', $data = '', $required = true, $default = '') {
		$vars = $this->getData('variables');
		$vars[strtolower($name)] = ['description' => $description, 'type' => $type, 'required' => parseBool($required), 'default' => $default];

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

	public function setAvailable($value) {
		return $this->setData('available', parseBool($value));
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

		foreach (array_reverse($this->findParents()) as $id) {
			$imageVars = BootableImage::load($this->getDB(), $id)->getVariables();
			foreach ($imageVars as $k => $v) {
				$vars[$k] = $v;
			}
		}

		return $vars;
	}

	public function findParents() {
		$de = $this->getImageDisplayEngine(true);
		$twig = $de->getTwig();
		$loader = $twig->getLoader();

		$lookedAt = [];
		$wanted = [];

		$wanted[] = $this->getID();

		while (!empty($wanted)) {
			$lookAt = array_shift($wanted);
			$lookedAt[] = $lookAt;

			foreach (['pxedata', 'script', 'postinstall'] as $bit) {
				$code = $loader->getSourceContext($lookAt . '/' . $bit);

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

	public function findChildren() {
		if ($this->getID() == NULL) { return []; }

		$dependants = [];
		$skip = [$this->getID()];
		foreach (BootableImage::getSearch($this->getDB())->find() as $image) {
			if (in_array($image->getID(), $skip)) { continue; }

			$parents = $image->findParents();
			if (in_array($this->getID(), $parents)) {
				// If we're in the parents list, count this image as a dependant
				$dependants[] = $image->getID();
			} else {
				// If we're not in the list, add this image and all images in
				// the list to the ignore list.
				$skip = array_merge($skip, $parents);
			}
		}

		return $dependants;
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

	public function getAvailable() {
		return parseBool($this->getData('available'));
	}

	public function getLastModified() {
		return $this->getData('lastmodified');
	}

	public function getServers() {
		return Server::getSearch($this->getDB())->where('image', $this->getID())->find();
	}

	public function toArray() {
		$arr = parent::toArray();
		$arr['available'] = parseBool($arr['available']);
		return $arr;
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
			$this->findParents();
		} catch (Exception $e) {
			throw new ValidationFailed($e->getMessage());
		}

		// Check that template parses properly.
		$de = $this->getImageDisplayEngine(true);
		$twig = $de->getTwig();
		$twig->addFunction(new Twig_Function('getServerInfo', function ($var, $default = '') { return ''; }));
		$twig->addFunction(new Twig_Function('getVariable', function ($var, $default = '') { return ''; }));
		$twig->addFunction(new Twig_Function('getScriptURL', function () { return ''; }));
		$twig->addFunction(new Twig_Function('getPostInstallURL', function () { return ''; }));
		$twig->addFunction(new Twig_Function('getServiceURL', function () { return ''; }));
		$twig->addFunction(new Twig_Function('getLogUrl', function ($type, $entry) { return ''; }));

		foreach (['pxedata' => 'PXEData', 'script' => 'Preseed/Kickstart', 'postinstall' => 'Post-Install'] as $bit => $nice) {
			$name = $this->getID() . '/' . $bit;
			try {
				ob_start();
				$de->render($name);
			} catch (Exception $ex) {
				throw new ValidationFailed('Error with ' . $nice . ': ' . $ex->getMessage());
			} finally {
				ob_end_clean();
			}
		}

		return TRUE;
	}

	public function getImageDisplayEngine($injectTemplates = false) {
		$de = getDisplayEngine();
		$twig = $de->getTwig();
		$loader = new BootableImageTwigLoader($this->getDB());
		$twig->setLoader($loader);

		if ($injectTemplates) {
			$loader->injectTemplate($this->getID() . '/pxedata', $this->getPXEData());
			$loader->injectTemplate($this->getID() . '/kickstart', $this->getScript());
			$loader->injectTemplate($this->getID() . '/preseed', $this->getScript());
			$loader->injectTemplate($this->getID() . '/script', $this->getScript());
			$loader->injectTemplate($this->getID() . '/postinstall', $this->getPostInstall());
		}

		return $de;
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

			if ($value === '' && $myType != 'none') {
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
		$dependants = $this->findChildren();
		if (!empty($dependants)) {
			throw new ValidationFailed('This image can not be deleted, it has dependants: ' . implode(', ', $dependants));
		}

		$this->myServers = $this->getServers();
	}

	public function postDelete($result) {
		if (!$result) { return; }

		foreach ($this->myServers as $server) {
			$server->generateConfig();
		}
	}

	private function generateServers() {
		// Find any servers that use this image, or any of our child images and
		// regenerate them.
		foreach ($this->getServers() as $server) {
			$server->generateConfig();
		}

		foreach ($this->findChildren() as $imageID) {
			foreach (BootableImage::load($this->getDB(), $imageID)->getServers() as $server) {
				$server->generateConfig();
			}
		}
	}
}
