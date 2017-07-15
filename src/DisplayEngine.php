<?php

	class DisplayEngine {
		private $twig;
		private $directories = [];
		private $basepath;
		private $vars = [];
		private $pageID = '';
		private $customSidebar = FALSE;

		public function __construct($siteconfig) {
			$config = $siteconfig['templates'];

			$loader = new Twig_Loader_Filesystem();
			$themes = [];
			if (isset($config['theme'])) {
				$themes = is_array($config['theme']) ? $config['theme'] : [$config['theme']];
			}
			foreach (array_unique(array_merge($themes, ['default'])) as $theme) {
				$path = $config['dir'] . '/' . $theme;
				if (file_exists($path)) {
					$loader->addPath($path, $theme);
					$loader->addPath($path, '__main__');
					$this->directories[] = $path;
				}
			}

			$twig = new Twig_Environment($loader, array(
				'cache' => $config['cache'],
				'auto_reload' => true,
				'debug' => true,
				'autoescape' => 'html',
			));

			$twig->addExtension(new Twig_Extension_Debug());

			$this->basepath = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
			$this->basepath = preg_replace('#^' . preg_quote($_SERVER['DOCUMENT_ROOT']) . '#', '/', $this->basepath);
			$this->basepath = preg_replace('#^/+#', '/', $this->basepath);

			$twig->addFunction(new Twig_Function('url', function ($path) { return $this->getURL($path); }));
			$twig->addFunction(new Twig_Function('getVar', function ($var) { return $this->getVar($var); }));

			$twig->addFunction(new Twig_Function('flash', function() { $this->displayFlash(); }));
			$twig->addFunction(new Twig_Function('showSidebar', function() { $this->showSidebar(); }));
			$twig->addFunction(new Twig_Function('showHeaderMenu', function() { $this->showHeaderMenu(); }));

			$twig->addFilter(new Twig_Filter('yesno', function($input) {
				return parseBool($input) ? "Yes" : "No";
			}));

			$twig->addFilter(new Twig_Filter('date', function($input) {
				return date('r', $input);
			}));

			$this->vars = ['sitename' => '', 'pagetitle' => ''];

			$this->twig = $twig;
		}

		public function getTwig() {
			return $this->twig;
		}

		public function setPageID($pageID) {
			$this->pageID = $pageID;
			return $this;
		}

		public function setSiteName($sitename) {
			$this->vars['sitename'] = $sitename;
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

		public function getBasePath() {
			return rtrim($this->basepath, '/');
		}

		public function getURL($path) {
			$path = sprintf('%s/%s', $this->getBasePath(), ltrim($path, '/'));

			return $path;
		}

		public function display($template) {
			$this->twig->display('header.tpl', $this->vars);
			$this->twig->display($template, $this->vars);
			$this->twig->display('footer.tpl', $this->vars);
		}

		public function displayRaw($template) {
			$this->twig->display($template, $this->vars);
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

		public function showHeaderMenu() {
			$menu = [];

			$public = ['link' => $this->getURL('/'), 'title' => 'Home', 'active' => true];
			array_unshift($menu, $public);

			$this->twig->display('header_menu.tpl', ['menu' => $menu]);
		}
	}
