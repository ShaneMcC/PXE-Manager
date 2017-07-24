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

		$possiblePermissions = isset($this->config['authProvider']['default']) ? $this->config['authProvider']['default'] : [];

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

	protected function calculatePermissions($allowedPermissions) {
		$permissions = [];

		if (is_array($allowedPermissions)) {
			foreach (array_keys(AuthProvider::$VALID_PERMISSIONS) as $p) {
				// Check if permission matches a permission in the array.
				foreach ($allowedPermissions as $permission) {
					if (preg_match('#' . str_replace('#', '\\#', $permission) . '#', $p)) {
						$permissions[$p] = true;
					}
				}
			}
		}

		return $permissions;
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

			$router->get('/logout', function() use ($displayEngine) {
				$displayEngine->flash('success', 'Success!', 'You have been logged out.');

				session::clear();

				header('Location: ' . $displayEngine->getURL('/'));
				return;
			});


			$router->match('GET|POST', '/login', function() use ($displayEngine) {
				$displayEngine->flash('warning', 'Login failed', 'You are already logged in.');
				header('Location: ' . $displayEngine->getURL('/'));
				return;
			});
		} else {
			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/login'), 'title' => 'Login', 'active' => function($de) { return $de->getPageID() == 'login'; }], 'right');

			$router->get('/login', function() use ($displayEngine) {
				$displayEngine->setTitle('login');
				$displayEngine->setPageID('login');
				$displayEngine->setVar('fields', $this->fields);
				$displayEngine->display('loginauthprovider_login.tpl');
			});

			$router->post('/login', function() use ($displayEngine, $api) {
				$checkAuth = $this->checkAuth($_POST);

				if ($checkAuth === FALSE) {
					$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided.');

					session::clear(['DisplayEngine::Flash', 'wantedPage']);
					header('Location: ' . $displayEngine->getURL('/login'));
					return;
				} else {
					$displayEngine->flash('success', 'Success!', 'You are now logged in.');

					session::set('logindata', ['type' =>  get_class($this), 'checkAuth' => $checkAuth]);

					if (session::exists('wantedPage')) {
						header('Location: ' . $displayEngine->getURL(session::get('wantedPage')));
						session::remove('wantedPage');
					} else {
						header('Location: ' . $displayEngine->getURL('/'));
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
