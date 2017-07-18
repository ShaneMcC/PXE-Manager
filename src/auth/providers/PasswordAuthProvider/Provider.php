<?php

class PasswordAuthProvider extends AuthProvider implements RouteProvider {
	private $config;
	private $isAuthenticated = false;

	public function checkSession($sessionData) {
		if (isset($sessionData['type']) && isset($sessionData['checkPass']) && $sessionData['type'] == __CLASS__) {
			$this->isAuthenticated = $sessionData['checkPass'] == sha1($this->config['authProvider']['password']);
		}
	}

	public function isAuthenticated() {
		return $this->isAuthenticated;
	}

	public function getPermissions() {
		$permissions = [];
		foreach (array_keys(AuthProvider::$VALID_PERMISSIONS) as $p) {
			// Edit permissions require authentication, view do not.
			$permissions[$p] = startsWith($p, 'edit_') ? $this->isAuthenticated() : true;
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

				if ($pass == $this->config['authProvider']['password']) {
					$displayEngine->flash('success', 'Success!', 'You are now logged in.');

					session::set('logindata', ['type' => __CLASS__, 'checkPass' => sha1($pass)]);

					if (session::exists('wantedPage')) {
						header('Location: ' . $displayEngine->getURL(session::get('wantedPage')));
						session::remove('wantedPage');
					} else {
						header('Location: ' . $displayEngine->getURL('/'));
					}
				} else {
					$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided.');

					session::clear(['DisplayEngine::Flash', 'wantedPage']);
					header('Location: ' . $displayEngine->getURL('/login'));
					return;
				}
			});
		}
	}

	public function addAuthedRoutes($authProvider, $router, $displayEngine, $api) {

	}
}
