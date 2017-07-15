<?php
	class SiteRoutes {

		public function addRoutes($router, $displayEngine) {
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
				header('HTTP/1.1 404 Not Found');
				$displayEngine->setPageID('404')->setTitle('Error 404')->display('404.tpl');
			});
		}
	}
