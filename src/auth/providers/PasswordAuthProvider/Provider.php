<?php

class PasswordAuthProvider extends AuthProvider implements RouteProvider {
	private $config;
	private $isAuthenticated = false;
	private $permissions = [];

	public function checkSession($sessionData) {
		if (isset($sessionData['type']) && isset($sessionData['checkPass']) && $sessionData['type'] == __CLASS__) {
			foreach ($this->config['PasswordAuthProvider']['passwords'] as $password => $permissions) {
				if ($sessionData['checkPass'] == sha1($password)) {
					$this->isAuthenticated = true;
					$this->permissions = $permissions;
					break;
				}
			}
		}
	}

	public function isAuthenticated() {
		return $this->isAuthenticated;
	}

	public function getPermissions() {
		$permissions = [];

		// If not authenticated, use default permissions, else use
		// permissions from authentication.
		if ($this->isAuthenticated()) {
			$allowedPermissions = $this->permissions;
		} else {
			$allowedPermissions = isset($this->config['PasswordAuthProvider']['default']) ? $this->config['PasswordAuthProvider']['default'] : [];
		}

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

	public function init($config, $router, $displayEngine) {
		$this->config = $config;
		$displayEngine->addTemplateDirectory(__DIR__ . '/templates/', 'PasswordAuthProvider');
	}

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
				$displayEngine->display('passwordauthprovider_login.tpl');
			});

			$router->post('/login', function() use ($displayEngine, $api) {
				$pass = $_POST['pass'];

				foreach ($this->config['PasswordAuthProvider']['passwords'] as $password => $permissions) {
					if ($pass == $password) {
						$displayEngine->flash('success', 'Success!', 'You are now logged in.');

						session::set('logindata', ['type' => __CLASS__, 'checkPass' => sha1($pass)]);

						if (session::exists('wantedPage')) {
							header('Location: ' . $displayEngine->getURL(session::get('wantedPage')));
							session::remove('wantedPage');
						} else {
							header('Location: ' . $displayEngine->getURL('/'));
						}
						return;
					}
				}

				$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided.');

				session::clear(['DisplayEngine::Flash', 'wantedPage']);
				header('Location: ' . $displayEngine->getURL('/login'));
				return;
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
