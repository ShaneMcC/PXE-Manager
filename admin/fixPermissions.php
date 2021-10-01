<?php
	require_once(dirname(__FILE__) . '/../functions.php');

	if (parseBool(getEnvOrDefault('FIX_PERMISSIONS', 'true'))) {
		echo 'Fixing permissions.', "\n";
		$wantedUser = getEnvOrDefault('FIX_PERMISSIONS_USER', 'www-data');

		$files = [];
		$files[] = '/' . trim($config['tftppath'], '/') . '/pxelinux.cfg/';

		if ($database['type'] == 'sqlite') {
			$files[] = dirname($database['file']);
			$files[] = $database['file'];
		}

		foreach ($files as $file) {
			if (file_exists($file)) {
				echo 'Chowning ', $file, ' to ', $wantedUser, "\n";
				chown($file, $wantedUser);
			}
		}
	} else {
		echo 'Not fixing permissions (FIX_PERMISSIONS env var is not true).', "\n";
	}
