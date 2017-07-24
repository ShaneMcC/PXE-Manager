<?php

	class UserPassAuthProvider_DBChanges implements DBChanger {

		public function getVersionField() {
			return 'UserPassAuthProvider_Version';
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
CREATE TABLE `UserPassAuthProvider_Users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` VARCHAR(250) NOT NULL,
  `password` VARCHAR(250) NOT NULL,
  `realname` VARCHAR(250) NOT NULL,
  `enabled` BOOL NOT NULL
);

CREATE UNIQUE INDEX `UserPassAuthProvider_Users_username` ON `UserPassAuthProvider_Users`(`username`);

CREATE TABLE `UserPassAuthProvider_Permissions` (
  `user_id` INTEGER NOT NULL,
  `permission` varchar(64) NOT NULL,
  PRIMARY KEY (`user_id`,`permission`),
  CONSTRAINT `permissions_user_id` FOREIGN KEY (`user_id`) REFERENCES `UserPassAuthProvider_Users`(`id`) ON DELETE CASCADE
);
DBQUERY
);

			return $dataChanges;
		}
	}
