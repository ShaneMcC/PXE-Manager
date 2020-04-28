<?php

	class DisplayEngine {
		private $twig;
		private $directories = [];
		private $basepath;
		private $vars = [];
		private $pageID = '';
		private $customSidebar = FALSE;
		private $menu;
		private $overrideURL = '';

		public function __construct($siteconfig) {
			$config = $siteconfig['templates'];

			$loader = new Twig_Loader_Filesystem();
			$this->twig = $twig = new Twig_Environment($loader, array(
				'cache' => $config['cache'],
				'auto_reload' => true,
				'debug' => true,
				'autoescape' => 'html',
			));

			$themes = [];
			if (isset($config['theme'])) {
				$themes = is_array($config['theme']) ? $config['theme'] : [$config['theme']];
			}
			foreach (array_unique(array_merge($themes, ['default'])) as $theme) {
				$path = $config['dir'] . '/' . $theme;
				if (file_exists($path)) {
					$this->addTemplateDirectory($path, $theme);
				}
			}

			$twig->addExtension(new Twig_Extension_Debug());

			$this->basepath = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
			$this->basepath = preg_replace('#^' . preg_quote($_SERVER['DOCUMENT_ROOT']) . '#', '/', $this->basepath);
			$this->basepath = preg_replace('#^/+#', '/', $this->basepath);

			$twig->addFunction(new Twig_Function('url', function ($path) { return $this->getURL($path); }));
			$twig->addFunction(new Twig_Function('fullurl', function ($path) { return $this->getFullURL($path); }));
			$twig->addFunction(new Twig_Function('getVar', function ($var) { return $this->getVar($var); }));
			$twig->addFunction(new Twig_Function('hasPermission', function($permissions) { return $this->hasPermission($permissions); }));

			$twig->addFunction(new Twig_Function('flash', function() { $this->displayFlash(); }));
			$twig->addFunction(new Twig_Function('showSidebar', function() { $this->showSidebar(); }));
			$twig->addFunction(new Twig_Function('showHeaderMenu', function($menuName = 'left') { $this->showHeaderMenu($menuName); }));

			$twig->addFilter(new Twig_Filter('yesno', function($input) {
				return parseBool($input) ? "Yes" : "No";
			}));

			$twig->addFilter(new Twig_Filter('date', function($input) {
				return date('r', $input);
			}));

			$twig->addFilter(new Twig_Filter('vardisplay', function($input) {
				if (is_string($input)) {
					return '"' . $input . '"';
				} else {
					ob_start();
					var_dump($input);
					$dump = ob_get_contents();
					ob_end_clean();

					return $dump;
				}
			}));

			$this->vars = ['sitename' => '', 'pagetitle' => ''];
		}

		public function addTemplateDirectory($path, $namespace = '__main__') {
			if ($namespace !== '__main__') {
				$this->getTwig()->getLoader()->addPath($path, $namespace);
			}

			$this->getTwig()->getLoader()->addPath($path, '__main__');

			$this->directories[] = $path;
		}

		public function hasPermission($permissions) {
			return getAuthProvider()->checkPermissions($permissions);
		}

		public function getTwig() {
			return $this->twig;
		}

		public function getPageID() {
			return $this->pageID;
		}


		public function setPageID($pageID) {
			$this->pageID = $pageID;
			return $this;
		}

		public function setSiteName($sitename) {
			$this->vars['sitename'] = $sitename;
			return $this;
		}

		public function setOverrideURL($overrideURL) {
			$this->overrideURL = $overrideURL;

			return $this;
		}

		public function setTitle($title) {
			$this->vars['pagetitle'] = $title;
			return $this;
		}

		public function setSidebar($vars) {
			$this->customSidebar = $vars;
			return $this;
		}

		public function setVar($var, $value) {
			$this->vars[$var] = $value;
			return $this;
		}

		public function getVar($var) {
			return array_key_exists($var, $this->vars) ? $this->vars[$var] : '';
		}

		public function getBaseURL() {
			$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
			$domain = $_SERVER['SERVER_NAME'];

			$port = $_SERVER['SERVER_PORT'];
			$port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ':' . $port;

			return $protocol . '://' . $domain . $port;
		}

		public function getBasePath() {
			return rtrim($this->basepath, '/');
		}

		public function getFullURL($path) {
			if (!empty($this->overrideURL)) { return $this->overrideURL . '/' . ltrim($path, '/'); }

			return $this->getBaseURL() . $this->getURL($path);
		}

		public function getURL($path) {
			$path = sprintf('%s/%s', $this->getBasePath(), ltrim($path, '/'));

			return $path;
		}

		public function setExtraVars() {
			$this->setVar('csrftoken', session::get('csrftoken'));
		}

		public function display($template) {
			$this->setExtraVars();

			$this->twig->display('header.tpl', $this->vars);
			$this->twig->display($template, $this->vars);
			$this->twig->display('footer.tpl', $this->vars);
		}

		public function displayRaw($template) {
			$this->setExtraVars();

			$this->twig->display($template, $this->vars);
		}


		public function render($template) {
			$this->setExtraVars();

			return $this->twig->load($template)->render($this->vars);
		}

		public function renderString($string) {
			$this->setExtraVars();

			$oldLoader = $this->twig->getLoader();
			$oldCache = $this->twig->getCache();

			$this->twig->setCache(false);
			$this->twig->setLoader(new Twig_Loader_Array(['template' => $string]));
			$rendered = $this->twig->load('template')->render($this->vars);

			$this->twig->setCache($oldCache);
			$this->twig->setLoader($oldLoader);

			return $rendered;
		}

		public function getFile($file) {
			$file = str_replace('../', '', $file);

			foreach ($this->directories as $dir) {
				$path = $dir . '/' . $file;
				if (file_exists($path)) {
					return $path;
				}
			}

			return FALSE;
		}

		public function flash($type, $title, $message) {
			if ($type == 'error') { $type = 'danger'; }

			session::append('DisplayEngine::Flash', ['type' => $type, 'title' => $title, 'message' => $message]);
		}

		public function displayFlash() {
			if (session::exists('DisplayEngine::Flash')) {
				$messages = session::get('DisplayEngine::Flash');
				foreach ($messages as $flash) {
					$this->twig->display('flash_message.tpl', $flash);
				}
				session::remove('DisplayEngine::Flash');
			}
		}

		public function showSidebar() {
			$vars = [];

			if ($this->customSidebar !== FALSE) {
				$vars = $this->customSidebar;
			} else {
				$menu = [];
				$sections = [];

				$vars['menu'] = $menu;
			}

			$this->twig->display('sidebar_menu.tpl', $vars);
		}


		public function addMenuItem($item, $menuName = 'left') {
			if (!isset($this->menu[$menuName])) {
				$this->menu[$menuName] = [];
			}

			$this->menu[$menuName][] = $item;
		}

		public function showHeaderMenu($menuName = 'left') {
			$menu = isset($this->menu[$menuName]) ? $this->menu[$menuName] : [];

			if (is_array($menu)) {
				foreach ($menu as &$m) {
					if (isset($m['active']) && is_callable($m['active'])) {
						$m['active'] = call_user_func($m['active'], $this);
					}
				}
			}

			$this->twig->display('header_menu.tpl', ['menu' => $menu]);
		}
	}
