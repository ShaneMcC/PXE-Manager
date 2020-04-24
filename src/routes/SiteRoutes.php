<?php
	class SiteRoutes implements RouteProvider {

		public function init($config, $router, $displayEngine) {
		}

		public function addRoutes($authProvider, $router, $displayEngine, $api) {
			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/'), 'title' => 'Home', 'active' => function($de) { return $de->getPageID() == 'home'; }]);

			$router->get('/', function() use ($displayEngine) {
				$displayEngine->setPageID('home')->setTitle('Home')->display('home.tpl');
			});

			$router->get('/(assets/.*)', function ($asset) use ($displayEngine) {
				$file = $displayEngine->getFile($asset);
				if ($file !== FALSE) {
					header('Content-Type: ' . get_mime_type($file));
					$displayEngine->displayRaw($asset);
				} else {
					header('HTTP/1.1 404 Not Found');
					$displayEngine->setPageID('404')->setTitle('Error 404')->display('404.tpl');
				}
			});

			$router->set404(function() use ($displayEngine, $router) {
				$wanted = $_SERVER['REQUEST_URI'];
				$wanted = preg_replace('#^' . preg_quote($displayEngine->getBasePath()) . '#', '/', $wanted);
				$wanted = preg_replace('#^/+#', '/', $wanted);
				$wanted = ltrim($wanted, '/');

				// Remember wanted pages for post-login if we have
				// an auth provider that cares.
				if (!preg_match('#^(assets)/#', $wanted) && $wanted != 'favicon.ico') {
					session::set('wantedPage', $wanted);
				}

				if (!getAuthProvider()->handle404($router, $displayEngine, $wanted)) {
					header('HTTP/1.1 404 Not Found');
					$displayEngine->setPageID('404')->setTitle('Error 404')->display('404.tpl');
				}
			});

			$router->get('/(?:api/0\.1/)?user\.json', function () use ($authProvider) {
				header('Content-Type: application/json');

				$data = [];
				$data['authenticated'] = $authProvider->isAuthenticated();
				$data['permissions'] = $authProvider->getPermissions();
				$data['csrftoken'] = session::get('csrftoken');

				echo json_encode($data);
			});
		}
	}
