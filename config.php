<?php
	// Database Details

	$config['templates']['dir'] = getEnvOrDefault('TEMPLATE_DIR', __DIR__ . '/templates');
	$config['templates']['theme'] = getEnvOrDefault('TEMPLATE_THEME', 'default');
	$config['templates']['cache'] = getEnvOrDefault('TEMPLATE_CACHE', __DIR__ . '/templates_c');

	$config['sitename'] = getEnvOrDefault('SITE_NAME', 'PXE-Manager');
	$config['memcached'] = getEnvOrDefault('MEMCACHED', '');
	$config['securecookies'] = getEnvOrDefault('SECURE_COOKIES', false);

	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
