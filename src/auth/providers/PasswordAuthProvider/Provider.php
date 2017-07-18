<?php

class PasswordAuthProvider extends AuthProvider implements RouteProvider {
	private $config;
	private $isAuthenticated = false;
	private $passtype = 'none';

	public function checkSession($sessionData) {
		if (isset($sessionData['type']) && isset($sessionData['checkPass']) && isset($sessionData['passtype']) && $sessionData['type'] == __CLASS__) {
			$this->isAuthenticated = $sessionData['checkPass'] == sha1($this->config['authProvider'][$sessionData['passtype']]);
			$this->passtype = $sessionData['passtype'];
		}
	}

	public function isAuthenticated() {
		return $this->isAuthenticated;
	}

	public function getPermissions() {
		$permissions = [];
		foreach (array_keys(AuthProvider::$VALID_PERMISSIONS) as $p) {
			// Edit permissions require authentication
			// Otherwise, allow read-only if $config['authProvider']['allowread'] is not false
			if ($this->isAuthenticated() && $this->passtype == 'password') {
				$permissions[$p] = true;
			} else if ($this->isAuthenticated() && $this->passtype == 'readonlypassword') {
				$permissions[$p] = startsWith($p, 'view_');
			} else if (startsWith($p, 'edit_')) {
				$permissions[$p] = false;
			} else {
				$permissions[$p] = isset($this->config['authProvider']['allowread']) ? $this->config['authProvider']['allowread'] : true;
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

				foreach (['password', 'readonlypassword'] as $passtype) {
					if (isset($this->config['authProvider'][$passtype]) && $pass == $this->config['authProvider'][$passtype]) {
						$displayEngine->flash('success', 'Success!', 'You are now logged in.');

						session::set('logindata', ['type' => __CLASS__, 'checkPass' => sha1($pass), 'passtype' => $passtype]);

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

	public function addAuthedRoutes($authProvider, $router, $displayEngine, $api) {

	}
}
