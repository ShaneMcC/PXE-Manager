<?php
	class ServerRoutes {

		public function showUnknown($displayEngine) {
			$displayEngine->setPageID('servers')->setTitle('Servers :: Unknown');
			$displayEngine->display('servers/unknown.tpl');
		}

		public function addRoutes($router, $displayEngine, $api) {
			$router->get('/servers', function() use ($displayEngine, $api) {
				$displayEngine->setPageID('servers')->setTitle('Servers');

				$servers = $api->getServers(true);
				$displayEngine->setVar('servers', $servers);

				$displayEngine->display('servers/index.tpl');
			});

			$router->get('/servers/create', function() use ($displayEngine, $api) {
				$displayEngine->setPageID('servers')->setTitle('Servers :: Create');

				$displayEngine->display('servers/create.tpl');
			});

			$router->get('/servers/([0-9]+)', function($serverid) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				$displayEngine->setVar('server', $server->toArray());

				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					$displayEngine->setVar('image', $image->toArray());
				}

				$displayEngine->setPageID('servers')->setTitle('Servers :: ' . $server->getName());

				$displayEngine->display('servers/view.tpl');
			});

			$router->post('/servers/([0-9]+)/delete', function($serverid) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				if (isset($_POST['confirm']) && parseBool($_POST['confirm'])) {
					$result = $server->delete();
					if ($result) {
						$displayEngine->flash('success', '', 'Server ' . $server->getName() . ' has been deleted.');
						header('Location: ' . $displayEngine->getURL('/servers'));
						return;
					} else {
						$displayEngine->flash('error', '', 'There was an error deleting the server.');
						header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
						return;
					}
				} else {
					header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
					return;
				}
			});

			$router->get('/servers/([0-9]+)/preview', function($serverid) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					$displayEngine->setVar('server', $server->toArray());
					$displayEngine->setPageID('servers')->setTitle('Servers :: ' . $server->getName() . ' :: Preview');

					$te = $server->getDisplayEngine();

					$displayEngine->setVar('pxedata', $te->renderString($image->getPXEData()));
					$displayEngine->setVar('kickstart', $te->renderString($image->getScript()));
				}

				$displayEngine->display('servers/preview.tpl');
			});

			$router->get('/servers/([0-9]+)/service/([^/]+)/([^/]+)', function($serverid, $servicehash, $action) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				if ($servicehash != $server->getServiceHash()) {
					return $this->showUnknown($displayEngine);
				}

				if ($action == 'disable') {
					$server->setEnabled(false)->save();
					die('OK');
				} else if ($action == 'script') {
					$image = $server->getBootableImage();
					if ($image instanceof BootableImage) {
						die($server->getDisplayEngine()->renderString($image->getScript()));
					} else {
						die();
					}
				}
			});

			$router->get('/servers/(-1|[0-9]+)/variables(?:/([0-9]+)?)?', function($serverid, $imageid = NULL) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				$image = null;

				if ($server instanceof Server) {
					$displayEngine->setVar('server', $server->toArray());
					$image = $api->getBootableImage($imageid === NULL ? $server->getImage() : $imageid);
				} else if ($imageid !== NULL) {
					$displayEngine->setVar('server', []);
					$image = $api->getBootableImage($imageid);
				}

				if ($image instanceof BootableImage) {
					$displayEngine->setVar('image', $image->toArray());
				}

				$displayEngine->displayRaw('servers/variables.tpl');
			});

			$router->post('/servers/create.json', function() use ($router, $displayEngine, $api) {
				$this->doCreateOrEdit($api, $displayEngine, NULL, $_POST);
			});


			$router->post('/servers/([0-9]+)/edit.json', function($serverid) use ($router, $displayEngine, $api) {
				$this->doCreateOrEdit($api, $displayEngine, $serverid, $_POST);
			});
		}

		function doCreateOrEdit($api, $displayEngine, $serverid, $data) {
			if ($serverid !== NULL) {
				[$result,$resultdata] = $api->editServer($serverid, $_POST);
			} else {
				[$result,$resultdata] = $api->createServer($_POST);
			}

			if ($result) {
				$displayEngine->flash('success', '', 'Your changes have been saved.');

				header('Content-Type: application/json');
				echo json_encode(['success' => 'Your changes have been saved.', 'location' => $displayEngine->getURL('/servers/' . $resultdata)]);
				return;
			} else {
				header('Content-Type: application/json');
				echo json_encode(['error' => 'There was an error with the data provided: ' . $resultdata]);
				return;
			}
		}
	}
