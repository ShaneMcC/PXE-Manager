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

		$image->setVariables([]);
		if (isset($data['var'])) {
			foreach ($data['var'] as $vardata) {
				if (array_key_exists('delete', $vardata)) { continue; }
				$image->setVariable($vardata['name'], $vardata['description'], isset($vardata['type']) ? $vardata['type'] : 'string');
			}
		}

		if (isset($data['pxedata'])) { $image->setPXEData($data['pxedata']); }
		if (isset($data['script'])) { $image->setScript($data['script']); }

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
			$search->join('bootableimages', '`bootableimages`.`id` = `servers`.`image`');
			$search->select('bootableimages', 'name', 'imagename');
		}

		return $asArray ? $search->getRows() : $search->find();
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

		$server->setVariables([]);
		if (isset($data['var'])) {
			foreach ($data['var'] as $k => $v) {
				$server->setVariable($k, $v);
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
}
