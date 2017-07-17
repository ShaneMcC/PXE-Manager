<?php
	require_once(dirname(__FILE__) . '/../functions.php');

	class DBChange {
		protected $query = '';
		protected $result = null;

		public function __construct($query) {
			$this->query = $query;
		}

		public function run($pdo) {
			if ($pdo->exec($this->query) !== FALSE) {
				$this->result = TRUE;
				echo 'success', "\n";
			} else {
				$ei = $pdo->errorInfo();
				$this->result = $ei[2];
				echo 'failed', "\n";
			}

			return $this->getLastResult();
		}

		public function getLastResult() {
			return ($this->result === TRUE);
		}

		public function getLastError() {
			return ($this->result === TRUE) ? NULL : $this->result;
		}
	}

	function initDataServer($db) {
		global $dataChanges;
		return runChanges($db, $dataChanges, 'dataVersion');
	}

	function runChanges($db, $changes, $versionField) {
		$currentVersion = (int)$db->getMetaData($versionField, 0);

		echo 'Current Version: ', $currentVersion, "\n";

		foreach ($changes as $version => $change) {
			if ($version <= $currentVersion) { continue; }
			echo 'Updating to version ', $version, ': ';

			if ($change->run($db->getPDO())) {
				$db->setMetaData($versionField, $version);
				$currentVersion = $version;
			} else {
				echo "\n", 'Error updating to version ', $version, ': ', $change->getLastError(), "\n";
				return $currentVersion;
			}
		}

		return $currentVersion;
	}



	// -------------------------------------------------------------------------
	// Meta Changes
	// -------------------------------------------------------------------------
	$dataChanges = array();

	// -------------------------------------------------------------------------
	// Create metadata table in DB.
	// -------------------------------------------------------------------------
	$dataChanges[1] = new DBChange(<<<DBQUERY
CREATE TABLE IF NOT EXISTS `__MetaData` (
  `key` VARCHAR(255) NOT NULL,
  `value` VARCHAR(255) NOT NULL
,  PRIMARY KEY (`key`)
);
DBQUERY
);

	// ------------------------------------------------------------------------
	// Initial Schema
	// ------------------------------------------------------------------------
	$dataChanges[2] = new DBChange(<<<DBQUERY
CREATE TABLE `bootableimages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` VARCHAR(250) NOT NULL,
  `variables` TEXT NULL,
  `pxedata` TEXT NOT NULL,
  `script` TEXT NOT NULL
);

CREATE UNIQUE INDEX `bootableimages_name` ON `bootableimages`(`name`);

CREATE TABLE `servers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` VARCHAR(250) NOT NULL,
  `macaddr` VARCHAR(250) NOT NULL,
  `image` INTEGER NOT NULL,
  `variables` TEXT NULL,
  `enabled` BOOL NOT NULL,
  CONSTRAINT `fk_image` FOREIGN KEY (`image`) REFERENCES `bootableimages`(`image`) ON DELETE SET NULL
);

CREATE UNIQUE INDEX `servers_macaddr` ON `servers`(`macaddr`);
DBQUERY
);

	// ------------------------------------------------------------------------
	// Post-Install script
	// ------------------------------------------------------------------------
	$dataChanges[3] = new DBChange(<<<DBQUERY
ALTER TABLE `bootableimages` ADD `postinstall` TEXT;
DBQUERY
);
