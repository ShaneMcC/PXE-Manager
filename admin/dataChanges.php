<?php
	require_once(dirname(__FILE__) . '/../functions.php');

	class DataChanges implements DBChanger {

		public function getVersionField() {
			return 'dataVersion';
		}

		public function getChanges() {
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

			return $dataChanges;
		}
	}
