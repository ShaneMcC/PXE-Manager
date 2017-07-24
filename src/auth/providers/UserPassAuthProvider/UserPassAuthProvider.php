<?php

class UserPassAuthProvider extends LoginAuthProvider implements RouteProvider, DBChanger {
	protected $currentUser = NULL;

	protected function providerInit() {
		$this->setFields(['user' => ['type' => 'text', 'label' => 'Username', 'placeholder' => 'Username'],
		                  'pass' => ['type' => 'password', 'label' => 'Password', 'placeholder' => 'Password'],
		                 ]);

		AuthProvider::$VALID_PERMISSIONS['view_users'] = 'View Users';
		AuthProvider::$VALID_PERMISSIONS['edit_users'] = 'Edit Users';

		$this->getDisplayEngine()->addTemplateDirectory(__DIR__ . '/templates/', 'UserPassAuthProvider');
	}

	protected function providerCheckSession($sessionData) {
		// If we get this far, assume we are authenticated.
		$user = UserPassAuthProvider_User::load(DB::get(), $sessionData['userid']);

		if ($user instanceof UserPassAuthProvider_User && $user->isEnabled()) {
			$this->currentUser = $user;
			$this->setAuthenticated(true);
			$this->setPermissions($user->getPermissions());
		}
	}

	protected function checkAuth($vars) {
		$wantuser = $vars['user'];
		$wantpass = $vars['pass'];

		$user = UserPassAuthProvider_User::loadFromUsername(DB::get(), $wantuser);
		if ($user instanceof UserPassAuthProvider_User) {
			if ($user->isEnabled() && $user->checkPassword($wantpass)) {
				return ['userid' => $user->getID()];
			}
		}

		// Check for no user accounts.
		if (UserPassAuthProvider_User::getUserCount(DB::get()) == 0) {
			$user = new UserPassAuthProvider_User(DB::get());
			$user->setUsername($wantuser);
			$user->setPassword($wantpass);
			$user->setRealname($wantuser);
			$user->setEnabled(true);
			$user->setPermission('all', true);
			$user->save();

			$this->getDisplayEngine()->flash('info', '', 'No user accounts exist, created default admin user.');
			return ['userid' => $user->getID(), 'first' => true];
		}

		return FALSE;
	}

	public function addRoutes($authProvider, $router, $displayEngine, $api) {
		parent::addRoutes($authProvider, $router, $displayEngine, $api);

		if ($authProvider->isAuthenticated()) {

			if (!$authProvider->checkPermissions(['view_users'])) { return; }

			$displayEngine->addMenuItem(['link' => $displayEngine->getURL('/users'), 'title' => 'Users', 'active' => function($de) { return $de->getPageID() == 'users'; }]);

			$router->get('/users', function() use ($displayEngine) {
				$displayEngine->setTitle('Users');
				$displayEngine->setPageID('users');

				$users = UserPassAuthProvider_User::find(DB::get(), []);

				$displayEngine->setVar('myuser', $this->currentUser);
				$displayEngine->setVar('users', $users);
				$displayEngine->setVar('validPermissions', array_keys(AuthProvider::$VALID_PERMISSIONS));
				$displayEngine->display('userpassauthprovider_list.tpl');
			});

			if (!$authProvider->checkPermissions(['edit_users'])) { return; }

			$router->post('/users/action/setPermission/([0-9]+)', function($userid) use ($displayEngine) {
				$result = NULL;
				$user = UserPassAuthProvider_User::load(DB::get(), $userid);

				if ($user instanceof UserPassAuthProvider_User) {
					foreach ($_POST['permissions'] as $perm => $value) {
						$user->setPermission($perm, $value);
					}
					$user->save();
					$result = ['response' => $user->toArray()];
				}

				header('Content-Type: application/json');
				echo json_encode($result);
			});

			$router->post('/users/action/(unsuspend|suspend)/([0-9]+)', function($type, $userid) use ($displayEngine) {
				$result = NULL;
				$user = UserPassAuthProvider_User::load(DB::get(), $userid);

				if ($user instanceof UserPassAuthProvider_User) {
					$user->setEnabled($type == "unsuspend");
					$user->save();
					$result = ['response' => $user->toArray()];
				}

				header('Content-Type: application/json');
				echo json_encode($result);
			});


			$router->post('/users/delete/(.*)', function($userid) use ($displayEngine, $api) {
				$result = NULL;
				$user = UserPassAuthProvider_User::load(DB::get(), $userid);

				if ($user instanceof UserPassAuthProvider_User) {
					$user->delete();
					$result = ['response' => $user->toArray()];
				}

				header('Content-Type: application/json');
				echo json_encode($result);
			});

			$router->post('/users/create', function() use ($displayEngine, $api) {
				$canUpdate = true;

				$fields = ['username' => 'You must specify an email address for the user',
				           'realname' => 'You must specify a name for the user',
				           'password' => 'You must specify a password for the user',
				           'confirmpassword' => 'You must confirm the password for the user',
				          ];


				foreach ($fields as $field => $error) {
					if (!array_key_exists($field, $_POST) || empty($_POST[$field])) {
						$canUpdate = false;
						$displayEngine->flash('error', '', 'There was an error creating the user: ' . $error);
						break;
					}
				}

				$pass = isset($_POST['password']) ? $_POST['password'] : NULL;
				$confirmpass = isset($_POST['confirmpassword']) ? $_POST['confirmpassword'] : NULL;

				if ($canUpdate && $pass != $confirmpass) {
					$canUpdate = false;
					$displayEngine->flash('error', '', 'There was an error creating the user: Passwords do not match.');
					return;
				}

				if ($canUpdate) {
					$user = new UserPassAuthProvider_User(DB::get());
					$user->setUsername($_POST['username']);
					$user->setPassword($_POST['password']);
					$user->setRealname($_POST['realname']);
					$user->setEnabled(true);

					try {
						$user->validate();
						$user->save();

						$displayEngine->flash('success', '', 'New user has been created');
					} catch (Exception $ex) {
						$displayEngine->flash('error', '', 'There was an error creating the user: ' . $ex->getMessage());
					}
				}

				header('Location: ' . $displayEngine->getURL('/users'));
				return;
			});


		}

	}

	public function getVersionField() {
		return (new UserPassAuthProvider_DBChanges())->getVersionField();
	}

	public function getChanges() {
		return (new UserPassAuthProvider_DBChanges())->getChanges();
	}
}
