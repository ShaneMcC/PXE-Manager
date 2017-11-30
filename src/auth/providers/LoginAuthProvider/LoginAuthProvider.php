<?php

abstract class LoginAuthProvider extends AuthProvider implements RouteProvider {
	private $isAuthenticated = false;
	private $config;
	private $router;
	private $displayEngine;
	private $permissions = [];
	private $fields = [];

	protected abstract function providerInit();

	public final function init($config, $router, $displayEngine) {
		$this->config = $config;
		$this->router = $router;
		$this->displayEngine = $displayEngine;

		$displayEngine->addTemplateDirectory(__DIR__ . '/templates/', 'LoginAuthProvider');

		$possiblePermissions = [];

		$this->setPermissions($this->calculatePermissions($possiblePermissions));
		$this->setAuthenticated(false);

		$this->providerInit();
	}

	protected abstract function providerCheckSession($sessionData);

	public final function checkSession($sessionData) {
		if (isset($sessionData['type']) && isset($sessionData['checkAuth']) && $sessionData['type'] == get_class($this)) {
			$this->providerCheckSession($sessionData['checkAuth']);
		}
	}

	protected function setFields($fields) {
		$this->fields = $fields;
	}

	protected function getConfig($var = null, $default = null) {
		$class = get_class($this);
		$config = array_key_exists($class, $this->config) ? $this->config[$class] : [];

		if ($var == null) {
			return $config;
		} else {
			return array_key_exists($var, $config) ? $config[$var] : $default;
		}
	}

	protected function getRouter() {
		return $this->router;
	}

	protected function getDisplayEngine() {
		return $this->displayEngine;
	}

	protected function setPermissions($permissions) {
		$this->permissions = $permissions;
	}

	public function getPermissions() {
		return $this->permissions;
	}

	protected function setAuthenticated($authenticated) {
		$this->isAuthenticated = $authenticated;
	}

	public function isAuthenticated() {
		return $this->isAuthenticated;
	}

	protected abstract function checkAuth($vars);

	public function addRoutes($authProvider, $router, $displayEngine, $api) {
		if ($authProvider->isAuthenticated()) {
			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/logout'), 'title' => 'Logout'], 'right');

			$router->get('/logout(.json)?', function($json = false) use ($displayEngine) {
				session::clear();
				if ($json) {
					header('Content-Type: application/json');
					echo json_encode(['success' => 'Logged out.']);
				} else {
					$displayEngine->flash('success', 'Success!', 'You have been logged out.');
					header('Location: ' . $displayEngine->getURL('/'));
				}
			});


			$router->match('GET|POST', '/login(.json)?', function($json = false) use ($displayEngine) {
				if ($json) {
					header('Content-Type: application/json');
					echo json_encode(['error' => 'You are already logged in.']);
				} else {
					$displayEngine->flash('warning', 'Login failed', 'You are already logged in.');
					header('Location: ' . $displayEngine->getURL('/'));
				}
				return;
			});
		} else {
			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/login'), 'title' => 'Login', 'active' => function($de) { return $de->getPageID() == 'login'; }], 'right');

			$router->get('/login(.json)?', function($json = false) use ($displayEngine) {
				if ($json) {
					header('Content-Type: application/json');
					echo json_encode(['fields' => $this->fields, 'csrftoken' => session::get('csrftoken')]);
				} else {
					$displayEngine->setTitle('login');
					$displayEngine->setPageID('login');
					$displayEngine->setVar('fields', $this->fields);
					$displayEngine->display('loginauthprovider_login.tpl');
				}
			});

			$router->post('/login(.json)?', function($json = false) use ($displayEngine, $api) {
				$checkAuth = $this->checkAuth($_POST);

				if ($json) { header('Content-Type: application/json'); }

				if ($checkAuth === FALSE) {
					if ($json) {
						echo json_encode(['error' => 'There was an error with the details provided.']);
					} else {
						$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided.');

						session::clear(['DisplayEngine::Flash', 'wantedPage']);
						header('Location: ' . $displayEngine->getURL('/login'));
					}
					return;
				} else {
					session::set('logindata', ['type' =>  get_class($this), 'checkAuth' => $checkAuth]);

					if ($json) {
						echo json_encode(['success' => 'You are now logged in.', 'token' => session_id()]);
					} else {
						$displayEngine->flash('success', 'Success!', 'You are now logged in.');
						if (session::exists('wantedPage')) {
							header('Location: ' . $displayEngine->getURL(session::get('wantedPage')));
							session::remove('wantedPage');
						} else {
							header('Location: ' . $displayEngine->getURL('/'));
						}
					}
					return;
				}
			});
		}
	}

	public function handle404($router, $displayEngine, $wanted) {
		if (!$this->isAuthenticated()) {
			header('Location: ' . $displayEngine->getURL('/login'));
			return true;
		}

		return false;
	}
}
