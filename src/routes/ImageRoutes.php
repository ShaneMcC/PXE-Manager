<?php
	class ImageRoutes implements RouteProvider {

		public function showUnknown($displayEngine) {
			$displayEngine->setPageID('images')->setTitle('Bootable Images :: Unknown');
			$displayEngine->display('images/unknown.tpl');
		}

		public function init($config, $router, $displayEngine) {
		}

		public function addRoutes($authProvider, $router, $displayEngine, $api) {
			if (!$authProvider->checkPermissions(['view_images'])) { return; }
			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/images'), 'title' => 'Images', 'active' => function($de) { return $de->getPageID() == 'images'; }]);

			$router->get('/images(.json)?', function($json = true) use ($displayEngine, $api) {
				$displayEngine->setPageID('images')->setTitle('Bootable Images');

				$images = $api->getBootableImages();
				if ($json) {
					header('Content-Type: application/json');
					echo json_encode(['images' => $images]);
					return;
				}

				$displayEngine->setVar('images', $images);

				$displayEngine->display('images/index.tpl');
			});

			$router->get('/images/([0-9]+)', function($imageid) use ($router, $displayEngine, $api) {
				$image = $api->getBootableImage($imageid);
				if (!($image instanceof BootableImage)) { return $this->showUnknown($displayEngine); }

				$displayEngine->setVar('image', $image->toArray());

				$displayEngine->setPageID('images')->setTitle('Bootable Images :: ' . $image->getName());

				$displayEngine->display('images/view.tpl');
			});

			if ($authProvider->checkPermissions(['edit_images'])) {
				$router->get('/images/create', function() use ($displayEngine, $api) {
					$displayEngine->setPageID('images')->setTitle('Bootable Images :: Create');

					$displayEngine->display('images/create.tpl');
				});

				$router->get('/images/([0-9]+)/duplicate', function($imageid) use ($router, $displayEngine, $api) {
					$image = $api->getBootableImage($imageid);
					if (!($image instanceof BootableImage)) { return $this->showUnknown($displayEngine); }

					$displayEngine->setPageID('images')->setTitle('Bootable Images :: ' . $image->getName() . ' :: Duplicate');
					$displayEngine->setVar('image', $image);

					$displayEngine->display('images/duplicate.tpl');
				});

				$router->post('/images/([0-9]+)/duplicate.json', function($imageid) use ($router, $displayEngine, $api) {
					$image = $api->getBootableImage($imageid);
					if (!($image instanceof BootableImage)) {
						header('Content-Type: application/json');
						echo json_encode(['error' => 'Unknown Source Image.']);
						return;
					} else {
						$data = $image->toArray();
						$data['name'] = $_POST['newname'];
						$data['var'] = $data['variables'];

						[$result,$resultdata] = $api->createBootableImage($data);
					}

					if ($result) {
						$displayEngine->flash('success', '', 'Image has been duplicated.');


						header('Content-Type: application/json');
						echo json_encode(['success' => 'Image has been duplicated.', 'location' => $displayEngine->getURL('/images/' . $resultdata)]);
						return;
					} else {
						header('Content-Type: application/json');
						echo json_encode(['error' => 'There was an error duplicating this image: ' . $resultdata]);
						return;
					}
				});

				$router->post('/images/create.json', function() use ($router, $displayEngine, $api) {
					$this->doCreateOrEdit($api, $displayEngine, NULL, $_POST);
				});

				$router->post('/images/([0-9]+)/edit.json', function($imageid) use ($router, $displayEngine, $api) {
					$this->doCreateOrEdit($api, $displayEngine, $imageid, $_POST);
				});

				$router->post('/images/([0-9]+)/delete', function($imageid) use ($router, $displayEngine, $api) {
					$image = $api->getBootableImage($imageid);
					if (!($image instanceof BootableImage)) { return $this->showUnknown($displayEngine); }

					if (isset($_POST['confirm']) && parseBool($_POST['confirm'])) {
						$errorReason = '';
						try {
							$result = $image->delete();
						} catch (Exception $e) {
							$result = FALSE;
							$errorReason = $e->getMessage();
						}

						if ($result) {
							$displayEngine->flash('success', '', 'Image ' . $image->getName() . ' has been deleted.');
							header('Location: ' . $displayEngine->getURL('/images'));
							return;
						} else {
							$displayEngine->flash('error', '', trim('There was an error deleting the image. ' . $errorReason));
							header('Location: ' . $displayEngine->getURL('/images/' . $imageid));
							return;
						}
					} else {
						header('Location: ' . $displayEngine->getURL('/images/' . $imageid));
						return;
					}
				});
			}
		}

		function doCreateOrEdit($api, $displayEngine, $imageid, $data) {
			if ($imageid !== NULL) {
				[$result,$resultdata] = $api->editBootableImage($imageid, $_POST);
			} else {
				[$result,$resultdata] = $api->createBootableImage($_POST);
			}

			if ($result) {
				$displayEngine->flash('success', '', 'Your changes have been saved.');

				header('Content-Type: application/json');
				echo json_encode(['success' => 'Your changes have been saved.', 'location' => $displayEngine->getURL('/images/' . $resultdata)]);
				return;
			} else {
				header('Content-Type: application/json');
				echo json_encode(['error' => 'There was an error with the data provided: ' . $resultdata]);
				return;
			}
		}
	}
