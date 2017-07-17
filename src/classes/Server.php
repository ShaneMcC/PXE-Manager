<?php

class Server extends DBObject {
	protected static $_fields = ['id' => NULL,
	                             'name' => NULL,
	                             'macaddr' => NULL,
	                             'image' => NULL,
	                             'variables' => NULL,
	                             'enabled' => FALSE,
	                            ];
	protected $variables = [];
	protected static $_json_fields = ['variables'];

	protected static $_key = 'id';
	protected static $_table = 'servers';

	public function __construct($db) {
		parent::__construct($db);
	}

	public function setName($value) {
		return $this->setData('name', $value);
	}

	public function setMacAddr($value) {
		return $this->setData('macaddr', strtolower($value));
	}

	public function setImage($value) {
		return $this->setData('image', $value);
	}

	public function setVariable($name, $value) {
		$vars = $this->getData('variables');
		$vars[strtolower($name)] = $value;

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

	public function setEnabled($value) {
		return $this->setData('enabled', parseBool($value));
	}

	public function getID() {
		return $this->getData('id');
	}

	public function getName() {
		return $this->getData('name');
	}

	public function getMacAddr() {
		return $this->getData('macaddr');
	}

	public function getImage() {
		return $this->getData('image');
	}

	public function getVariables() {
		$vars = $this->getData('variables');
		return is_array($vars) ? $vars : [];
	}

	public function getVariable($name) {
		return $this->getData('variables')[strtolower($name)];
	}

	public function getEnabled() {
		return parseBool($this->getData('enabled'));
	}

	public function getBootableImage() {
		return BootableImage::load($this->getDB(), $this->getData('image'));
	}

	public function getValidVariables() {
		// Ensure that our getVariable() function only returns variables
		// that are valid for this image.
		$validVars = [];
		$myVars = $this->getVariables();

		$image = $this->getBootableImage();
		if ($image instanceof BootableImage) {
			foreach ($image->getVariables() as $v => $vd) {
				if (array_key_exists($v, $myVars)) {
					$validVars[$v] = $myVars[$v];

					if ($vd['type'] == 'yesno') { $validVars[$v] = parseBool($validVars[$v]); }
				}
			}
		}

		return $validVars;
	}

	public function getDisplayEngine($injectVars = true) {
		$de = getDisplayEngine();

		$validVars = $this->getValidVariables();
		if ($injectVars) {
			foreach ($validVars as $k => $v) {
				$de->setVar($k, $v);
			}
		}

		$twig = $de->getTwig();
		$twig->addFunction(new Twig_Function('getVariable', function ($var, $default = '') use ($validVars) {
			return array_key_exists($var, $validVars) && $validVars[$var] !== "" ? $validVars[$var] : $default;
		}));

		$twig->addFunction(new Twig_Function('getScriptURL', function () use ($de) {
			return $de->getFullURL('/servers/' . $this->getID() . '/service/' . $this->getServiceHash() . '/script');
		}));

		$twig->addFunction(new Twig_Function('getPostInstallURL', function () use ($de) {
			return $de->getFullURL('/servers/' . $this->getID() . '/service/' . $this->getServiceHash() . '/postinstall');
		}));

		$twig->addFunction(new Twig_Function('getServiceURL', function () use ($de) {
			return $de->getFullURL('/servers/' . $this->getID() . '/service/' . $this->getServiceHash());
		}));

		return $de;
	}

	public function getServiceHash() {
		$hashData = $this->toArray();
		unset($hashData['enabled']);

		return base_convert(crc32(json_encode($hashData)), 10, 32) . '_' . base_convert(crc32(sha1(json_encode($hashData))), 10, 16);
	}

	/**
	 * Validate the Server.
	 *
	 * @return TRUE if validation succeeded
	 * @throws ValidationFailed if there is an error.
	 */
	public function validate() {
		$required = ['image'];
		foreach ($required as $r) {
			if (!$this->hasData($r)) {
				throw new ValidationFailed('Missing required field: '. $r);
			}
		}

		return TRUE;
	}

	public function postSave($result) {
		if (!$result) { return; }

		$this->generateConfig();
	}

	public function generateConfig() {
		global $config;

		$image = $this->getBootableImage();

		$filename = $this->getMacAddr();
		if (isValidMac($filename)) {
			$filename = preg_replace('#[^0-9A-F]#i', '', strtolower($filename));
			$filename = '01-' . join('-', str_split($filename, 2));
		}

		$file = rtrim($config['tftppath'], '/') . '/pxelinux.cfg/' . $filename;

		if ($this->getEnabled() && $image instanceof BootableImage) {
			$contents = [];
			$contents[] = 'default install';
			$contents[] = 'prompt 0';
			$contents[] = '';
			$contents[] = 'LABEL install';
			$first = true;
			$de = $this->getDisplayEngine();
			$serviceURL = $de->getFullURL('/servers/' . $this->getID() . '/service/' . $this->getServiceHash());

			foreach (explode("\n", $de->renderString($image->getPXEData())) as $line) {
				if ($first && $line == "#!ipxe") {
					$contents[] = '    KERNEL ipxe.lkrn';
    				$contents[] = '    APPEND dhcp && chain --autofree ' . $serviceURL . '/pxedata';
					break;
				}

				$contents[] = '    ' . $line;
				$first = false;
			}
			$contents[] = '';

			@mkdir(dirname($file), 0777, true);
			@file_put_contents($file, implode("\n", $contents));
			@chmod($file, 0777);
		} else {
			@unlink($file);
		}
	}
}
