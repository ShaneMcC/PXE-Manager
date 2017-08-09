<?php

class API {
	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getBootableImages() {
		$search = BootableImage::getSearch($this->db);

		return $search->find('id');
	}

	public function getBootableImage($id) {
		return BootableImage::load($this->db, $id);
	}

	public function createBootableImage($data) {
		$image = new BootableImage($this->db);
		return $this->doEditImage($image, $data);
	}

	public function editBootableImage($id, $data) {
		$image = $this->getBootableImage($id);
		return $this->doEditImage($image, $data);
	}

	public function doEditImage($image, $data) {
		if (!($image instanceof BootableImage)) { return [false, "No such image."]; }

		if (isset($data['name'])) { $image->setName($data['name']); }

		if (isset($data['var'])) {
			$image->setVariables([]);
			if (is_array($data['var'])) {
				foreach ($data['var'] as $key => $vardata) {
					if (array_key_exists('delete', $vardata)) { continue; }
					if (!array_key_exists('name', $vardata)) { $vardata['name'] = $key; }

					$name = $vardata['name'];
					$desc = $vardata['description'];
					$type = isset($vardata['type']) ? $vardata['type'] : 'string';
					$thisdata = isset($vardata['data']) ? $vardata['data'] : '';
					$required = isset($vardata['required']) ? $vardata['required'] : true;

					$image->setVariable($name, $desc, $type, $thisdata, $required);
				}
			}
		}

		if (isset($data['pxedata'])) { $image->setPXEData($data['pxedata']); }
		if (isset($data['script'])) { $image->setScript($data['script']); }
		if (isset($data['postinstall'])) { $image->setPostInstall($data['postinstall']); }
		if (isset($data['available'])) { $image->setAvailable($data['available']); }

		try {
			$image->validate();

			$image->save();
			return [true, $image->getID()];
		} catch (Exception $ex) {
			return [false, $ex->getMessage()];
		}
	}

	public function getServers($asArray = false) {
		$search = Server::getSearch($this->db);

		if ($asArray) {
			$search->join('bootableimages', '`bootableimages`.`id` = `servers`.`image`', 'LEFT');
			$search->select('bootableimages', 'name', 'imagename');
		}

		return $asArray ? $search->getRows() : $search->find();
	}


	public function getServerFromMAC($macaddr) {
		$search = Server::getSearch($this->db);
		$search->where('macaddr', strtolower($macaddr));
		$result = $search->find();

		return is_array($result) && !empty($result) ? $result[0] : null;
	}

	public function getServer($id) {
		return Server::load($this->db, $id);
	}

	public function createServer($data) {
		$server = new Server($this->db);
		return $this->doEditServer($server, $data);
	}

	public function editServer($id, $data) {
		$server = $this->getServer($id);
		if (!($server instanceof Server)) { return [false, "No such server."]; }
		return $this->doEditServer($server, $data);
	}

	public function doEditServer($server, $data) {
		if (isset($data['name'])) { $server->setName($data['name']); }
		if (isset($data['macaddr'])) { $server->setMacAddr($data['macaddr']); }
		if (isset($data['image'])) { $server->setImage($data['image']); }
		if (isset($data['enabled'])) { $server->setEnabled($data['enabled']); }

		if (isset($data['var'])) {
			$server->setVariables([]);
			if (is_array($data['var'])) {
				foreach ($data['var'] as $k => $v) {
					$server->setVariable($k, $v);
				}
			}
		}

		try {
			$server->validate();

			$server->save();
			return [true, $server->getID()];
		} catch (Exception $ex) {
			return [false, $ex->getMessage()];
		}
	}

	public function createServerLog($serverid, $logtype, $entry = NULL) {
		$log = new ServerLog($this->db);
		$log->setServer($serverid);
		$log->setTime(time());
		$log->setType($logtype);
		if ($entry !== null) {
			$log->setEntry($entry);
		}

		try {
			$log->validate();

			$log->save();
			return [true, $log->getID()];
		} catch (Exception $ex) {
			return [false, $ex->getMessage()];
		}
	}
}
