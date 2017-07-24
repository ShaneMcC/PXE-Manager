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

	// Default permissions for anyone not authenticated?
	// By default we allow view access.
	$config['authProvider']['default'] = ['view_(servers|images)'];

	// Alternative providers:
	//
	// - PasswordAuthProvider - Grants permissions based on a given password,
	// - LDAPAuthProvider - Grants permissions based on ldap login and groups.

	// =========================================================================
	// Password Auth Provider
	// =========================================================================
	// $config['authProvider']['name'] = 'PasswordAuthProvider';

	// What passwords are valid?
	// This is an array of password => [<permissions>] where <permissions>
	// are regex entries, any permission name that matches an entry is granted.
	//
	// As examples, "admin123" can do anything, but "user123" can only edit
	// servers but not images.
	//
	// These permissions are used *instead* of the default not in addition to
	// so must include all the permissions you want the password to grant.
	$config['PasswordAuthProvider']['passwords'] = ['admin123' => ['.*'],
	                                                'user123' => ['view_.*', 'edit_servers']
	                                               ];

	// =========================================================================
	// LDAP Auth Provider
	// =========================================================================
	// $config['authProvider']['name'] = 'LDAPAuthProvider';

	// Server to authenticate with?
	$config['LDAPAuthProvider']['server'] = 'ldap.localhost.localdomain';

	// LDAP Bind User
	$config['LDAPAuthProvider']['binduser'] = 'CN=PXE Manager,OU=Applications,DC=localhost,DC=localdomain';
	// LDAP Bind Password
	$config['LDAPAuthProvider']['bindpass'] = 'SomePassword';

	// Base DN to check for users.
	$config['LDAPAuthProvider']['basedn'] = 'OU=Users,DC=localhost,DC=localdomain';

	// Property to use for username?
	// This will probably be 'sAMAccountName' for Active Directory
	$config['LDAPAuthProvider']['usernameprop'] = 'uid';

	// This is an array of Group => [<permissions>] where <permissions>
	// are regex entries, any permission name that matches an entry is granted.
	//
	// As examples, "PXE Admin" can do anything, but "PXE User" can only edit
	// servers but not images.
	//
	// These permissions are used *instead* of the default not in addition to
	// so must include all the permissions you want the password to grant.
	$config['LDAPAuthProvider']['permissions'] = ['CN=PXE Admin,OU=Groups,DC=localhost,DC=localdomain' => ['.*'],
	                                              'CN=PXE User,OU=Groups,DC=localhost,DC=localdomain' => ['view_.*', 'edit_servers'],
	                                             ];

	// =========================================================================
	// User/Pass Auth Provider
	// =========================================================================
	// $config['authProvider']['name'] = 'UserPassAuthProvider';


	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
