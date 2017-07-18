<?php
	// Database Details
	// only sqlite is supported at the moment.
	$database['type'] = getEnvOrDefault('DB_SERVER_TYPE', 'sqlite');

	// Used only if type is sqlite.
	$database['file'] = getEnvOrDefault('DB_FILE', __DIR__ . '/data/db.sqlite');

	// Used only if type is not sqlite.
	// Untested. Probably won't work. Use sqlite for now.
	$database['server'] = getEnvOrDefault('DB_SERVER', '');
	$database['username'] = getEnvOrDefault('DB_SERVER_USERNAME', '');
	$database['password'] = getEnvOrDefault('DB_SERVER_PASSWORD', '');
	$database['database'] = getEnvOrDefault('DB_SERVER_DATABASE', '');

	$config['templates']['dir'] = getEnvOrDefault('TEMPLATE_DIR', __DIR__ . '/templates');
	$config['templates']['theme'] = getEnvOrDefault('TEMPLATE_THEME', 'default');
	$config['templates']['cache'] = getEnvOrDefault('TEMPLATE_CACHE', __DIR__ . '/templates_c');

	$config['sitename'] = getEnvOrDefault('SITE_NAME', 'PXE-Manager');
	$config['memcached'] = getEnvOrDefault('MEMCACHED', '');
	$config['securecookies'] = getEnvOrDefault('SECURE_COOKIES', false);

	// Where to output per-server pxe configs.
	$config['tftppath'] = getEnvOrDefault('TFTPPATH', '/var/lib/tftpboot/');

	// Name of auth provider to use.
	// Note: URLs required for main functionality are always available without
	// authentication. This includes "Service" urls and /pxedata/<MAC>
	$config['authProvider']['name'] = 'FullAuthProvider';

	// Alternative providers:
	//
	// - PasswordAuthProvider - Grants permissions based on a given password,
	//
	// $config['authProvider']['name'] = 'PasswordAuthProvider';

	// Default permissions for anyone not authenticated?
	// By default we allow view access.
	$config['PasswordAuthProvider']['default'] = ['view_(servers|images)'];

	// If using PasswordAuthProvider, what passwords are valid?
	// This is an array of password => [<permissions>] where <permissions>
	// are regex entries, any permission name that matches an entry is granted.
	//
	// As examples, "admin123" can do anything, but "user123" can only edit
	// servers but not images.
	//
	// These permissions are used *instead* of the default not in addition to
	// so must include all the permissions you want the password to grant.
	$config['PasswordAuthProvider']['passwords'] = ['admin123' => ['.*'],
	                                                'user123' => ['view_.*', '.*_servers']
	                                               ];

	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
