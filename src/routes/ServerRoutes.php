<?php
	class ServerRoutes implements RouteProvider {

		public function showUnknown($displayEngine) {
			$displayEngine->setPageID('servers')->setTitle('Servers :: Unknown');
			$displayEngine->display('servers/unknown.tpl');
			die();
		}

		public function init($config, $router, $displayEngine) {
		}

		public function checkServiceHash($api, $displayEngine, $serverid, $servicehash) {
			$server = $api->getServer($serverid);
			if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

			if ($servicehash != $server->getServiceHash()) {
				return $this->showUnknown($displayEngine);
			}

			return $server;
		}

		public function addRoutes($authProvider, $router, $displayEngine, $api) {

			$router->get('/servers/([0-9]+)/service/([^/]+)/disable', function($serverid, $servicehash) use ($router, $displayEngine, $api) {
				$server = $this->checkServiceHash($api, $displayEngine, $serverid, $servicehash);
				$server->setEnabled(false)->save();
				die('OK');
			});

			$router->get('/servers/([0-9]+)/service/([^/]+)/script', function($serverid, $servicehash) use ($router, $displayEngine, $api) {
				$server = $this->checkServiceHash($api, $displayEngine, $serverid, $servicehash);
				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					die($server->getDisplayEngine()->render($image->getID() . '/script'));
				} else {
					die();
				}
			});

			$router->get('/servers/([0-9]+)/service/([^/]+)/postinstall', function($serverid, $servicehash) use ($router, $displayEngine, $api) {
				$server = $this->checkServiceHash($api, $displayEngine, $serverid, $servicehash);
				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					die($server->getDisplayEngine()->render($image->getID() . '/postinstall'));
				} else {
					die();
				}
			});

			$router->get('/servers/([0-9]+)/service/([^/]+)/pxedata', function($serverid, $servicehash) use ($router, $displayEngine, $api) {
				$server = $this->checkServiceHash($api, $displayEngine, $serverid, $servicehash);
				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					die($server->getDisplayEngine()->render($image->getID() . '/pxedata'));
				} else {
					die();
				}
			});

			$router->match('GET|POST', '/servers/([0-9]+)/service/([^/]+)/serverlog/([a-z]+)', function($serverid, $servicehash, $logtype) use ($router, $displayEngine, $api) {
				$server = $this->checkServiceHash($api, $displayEngine, $serverid, $servicehash);

				[$result,$resultdata] = $api->createServerLog($serverid, $logtype, isset($_REQUEST['entry']) ? $_REQUEST['entry'] : null);

				if ($result) {
					echo 'OK';
				} else {
					echo 'ERROR: ' . $resultdata;
				}
			});

			$router->get('/pxedata/([^/]+)', function($macaddr) use ($router, $displayEngine, $api) {
				$server = $api->getServerFromMAC($macaddr);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					die($server->getDisplayEngine()->render($image->getID() . '/pxedata'));
				} else {
					die();
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
					$arr = $image->toArray();
					$arr['requiredvariables'] = $image->getRequiredVariables();
					$displayEngine->setVar('image', $arr);
				}

				$displayEngine->displayRaw('servers/variables.tpl');
			});

			if (!$authProvider->checkPermissions(['view_servers'])) { return; }

			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/servers'), 'title' => 'Servers', 'active' => function($de) { return $de->getPageID() == 'servers'; }]);

			$router->get('/servers', function() use ($displayEngine, $api) {
				$displayEngine->setPageID('servers')->setTitle('Servers');

				$servers = $api->getServers(true);
				$displayEngine->setVar('servers', $servers);

				$displayEngine->display('servers/index.tpl');
			});

			$router->get('/servers/([0-9]+)', function($serverid) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				$displayEngine->setVar('server', $server->toArray());
				$displayEngine->setVar('serverlogs', $server->getServerLogs());

				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					$displayEngine->setVar('image', $image->toArray());
				}

				$displayEngine->setPageID('servers')->setTitle('Servers :: ' . $server->getName());

				$displayEngine->display('servers/view.tpl');
			});

			$router->get('/servers/([0-9]+)/preview', function($serverid) use ($router, $displayEngine, $api) {
				$server = $api->getServer($serverid);
				if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

				$displayEngine->setVar('server', $server->toArray());
				$displayEngine->setPageID('servers')->setTitle('Servers :: ' . $server->getName() . ' :: Preview');

				$image = $server->getBootableImage();
				if ($image instanceof BootableImage) {
					$te = $server->getDisplayEngine();

					$displayEngine->setVar('pxedata', $te->render($image->getID() . '/pxedata'));
					$displayEngine->setVar('kickstart', $te->render($image->getID() . '/script'));
					$displayEngine->setVar('postinstall', $te->render($image->getID() . '/postinstall'));

					$displayEngine->setVar('validvars', $server->getValidVariables());
				}

				$displayEngine->display('servers/preview.tpl');
			});

			if ($authProvider->checkPermissions(['edit_servers'])) {
				$router->get('/servers/create', function() use ($displayEngine, $api) {
					$displayEngine->setPageID('servers')->setTitle('Servers :: Create');

					$displayEngine->display('servers/create.tpl');
				});

				$router->post('/servers/([0-9]+)/clearlogs', function($serverid) use ($router, $displayEngine, $api) {
					$server = $api->getServer($serverid);
					if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

					if (isset($_POST['confirm']) && parseBool($_POST['confirm'])) {
						$result = $server->clearServerLogs();

						if ($result) {
							$displayEngine->flash('success', '', 'Logs for server ' . $server->getName() . ' have been deleted.');
							header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
							return;
						} else {
							$displayEngine->flash('error', '', 'There was an error deleting the server logs.');
							header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
							return;
						}
					} else {
						header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
						return;
					}
				});

				$router->post('/servers/([0-9]+)/delete', function($serverid) use ($router, $displayEngine, $api) {
					$server = $api->getServer($serverid);
					if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

					if (isset($_POST['confirm']) && parseBool($_POST['confirm'])) {
						$errorReason = '';
						try {
							$result = $server->delete();
						} catch (Exception $e) {
							$result = FALSE;
							$errorReason = $e->getMessage();
						}

						if ($result) {
							$displayEngine->flash('success', '', 'Server ' . $server->getName() . ' has been deleted.');
							header('Location: ' . $displayEngine->getURL('/servers'));
							return;
						} else {
							$displayEngine->flash('error', '', trim('There was an error deleting the server. ' . $errorReason));
							header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
							return;
						}
					} else {
						header('Location: ' . $displayEngine->getURL('/servers/' . $serverid));
						return;
					}
				});

				$router->get('/servers/([0-9]+)/duplicate', function($serverid) use ($router, $displayEngine, $api) {
					$server = $api->getServer($serverid);
					if (!($server instanceof Server)) { return $this->showUnknown($displayEngine); }

					$displayEngine->setPageID('servers')->setTitle('Servers :: ' . $server->getName() . ' :: Duplicate');
					$displayEngine->setVar('server', $server);

					$displayEngine->display('servers/duplicate.tpl');
				});

				$router->post('/servers/([0-9]+)/duplicate.json', function($serverid) use ($router, $displayEngine, $api) {
					$server = $api->getServer($serverid);
					if (!($server instanceof Server)) {
						header('Content-Type: application/json');
						echo json_encode(['error' => 'Unknown Source Server.']);
						return;
					} else {
						$data = $server->toArray();
						$data['name'] = $_POST['newname'];
						$data['macaddr'] = $_POST['newmac'];
						$data['var'] = $data['variables'];

						[$result,$resultdata] = $api->createServer($data);
					}

					if ($result) {
						$displayEngine->flash('success', '', 'Server has been duplicated.');

						header('Content-Type: application/json');
						echo json_encode(['success' => 'Server has been duplicated.', 'location' => $displayEngine->getURL('/servers/' . $resultdata)]);
						return;
					} else {
						header('Content-Type: application/json');
						echo json_encode(['error' => 'There was an error duplicating this server: ' . $resultdata]);
						return;
					}
				});

				$router->post('/servers/create.json', function() use ($router, $displayEngine, $api) {
					$this->doCreateOrEdit($api, $displayEngine, NULL, $_POST);
				});

				$router->post('/servers/([0-9]+)/edit.json', function($serverid) use ($router, $displayEngine, $api) {
					$this->doCreateOrEdit($api, $displayEngine, $serverid, $_POST);
				});
			}
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
