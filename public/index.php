<?php
	require_once(__DIR__ . '/../functions.php');

	use shanemcc\phpdb\DB;

	// Router for requests
	$router = new \Bramus\Router\Router();

	// Templating engine
	$displayEngine = getDisplayEngine();

	if ($config['securecookies']) {
		ini_set('session.cookie_secure', True);
	}
	ini_set('session.cookie_httponly', True);

	// Session storage
	if (isset($config['memcached']) && !empty($config['memcached'])) {
		ini_set('session.save_handler', 'memcached');
		ini_set('session.save_path', $config['memcached']);
	}

	// API to interact with backend
	$api = new API(DB::get());

	// Session/Authentication
	if (isset($_SERVER['HTTP_X_SESSION_ID'])) {
		session_id($_SERVER['HTTP_X_SESSION_ID']);
	}
	session::init();
	if (!session::exists('csrftoken')) {
		session::set('csrftoken', genUUID());
	}

	// Routes
	$routeProviders = [];
	$routeProviders[] = new SiteRoutes();
	$routeProviders[] = new ImageRoutes();
	$routeProviders[] = new ServerRoutes();

	if (getAuthProvider() instanceof RouteProvider) {
		$routeProviders[] = getAuthProvider();
	}

	foreach ($routeProviders as $routeProvider) {
		$routeProvider->init($config, $router, $displayEngine);
	}

	// Check login data
	if (session::exists('logindata')) {
		getAuthProvider()->checkSession(session::get('logindata'));
	}

	foreach ($routeProviders as $routeProvider) {
		$routeProvider->addRoutes(getAuthProvider(), $router, $displayEngine, $api);
	}

	// Check CSRF Tokens.
	$router->before('POST', '.*?(.json)?', function($json = false) {
		// Pre-Login, we don't have a CSRF Token assigned.
		if (!session::exists('csrftoken')) { return; }

		if ($json) { $_POST = json_decode(file_get_contents("php://input"), true); }

		if (isset($_SERVER['HTTP_X_CSRFTOKEN']) && (!array_key_exists('csrftoken', $_POST) || empty($_POST['csrftoken']))) {
			$_POST['csrftoken'] = $_SERVER['HTTP_X_CSRFTOKEN'];
		}

		if (!array_key_exists('csrftoken', $_POST) || empty($_POST['csrftoken']) || $_POST['csrftoken'] != session::get('csrftoken')) {
			header('HTTP/1.1 403 Forbidden');
			if ($json) {
				header('Content-Type: application/json');
				die(json_encode(['error' => 'Invalid CSRF Token']));
			} else {
				die('Invalid CSRF Token');
			}
		}
	});

	// Begin!
	$router->run();
