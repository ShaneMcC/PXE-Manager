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
  `value` VARCHAR(255) NOT NULL,
   PRIMARY KEY (`key`)
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

			// ------------------------------------------------------------------------
			// Fix invalid Constraint
			// ------------------------------------------------------------------------
			$dataChanges[4] = new DBChange(<<<DBQUERY
PRAGMA foreign_keys = OFF;

CREATE TABLE `servers2` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` VARCHAR(250) NOT NULL,
  `macaddr` VARCHAR(250) NOT NULL,
  `image` INTEGER NOT NULL,
  `variables` TEXT NULL,
  `enabled` BOOL NOT NULL,
  CONSTRAINT `fk_image` FOREIGN KEY (`image`) REFERENCES `bootableimages`(`id`) ON DELETE SET NULL
);

INSERT INTO servers2 (id, name, macaddr, image, variables, enabled) SELECT id, name, macaddr, image, variables, enabled FROM servers;
DROP TABLE servers;
ALTER TABLE servers2 RENAME TO servers;
PRAGMA foreign_keys = ON;

CREATE UNIQUE INDEX `servers_macaddr` ON `servers`(`macaddr`);
DBQUERY
);
			// ------------------------------------------------------------------------
			// Server Logs
			// ------------------------------------------------------------------------
			$dataChanges[5] = new DBChange(<<<DBQUERY
CREATE TABLE `serverlogs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `server` INTEGER NOT NULL,
  `time` INTEGER NOT NULL,
  `type` TEXT NOT NULL,
  `entry` TEXT NOT NULL,
  CONSTRAINT `fk_server` FOREIGN KEY (`server`) REFERENCES `servers`(`id`) ON DELETE CASCADE
);
DBQUERY
);

			// ------------------------------------------------------------------------
			// Last Modified Times
			// ------------------------------------------------------------------------
			$dataChanges[6] = new DBChange(<<<DBQUERY
ALTER TABLE `bootableimages` ADD `lastmodified` INT;

ALTER TABLE `servers` ADD `lastmodified` INT;
DBQUERY
);

			return $dataChanges;
		}
	}
