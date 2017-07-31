<?php
	require_once(__DIR__ . '/vendor/autoload.php');

	use shanemcc\phpdb\DB;

	function getEnvOrDefault($var, $default) {
		$result = getEnv($var);
		return $result === FALSE ? $default : $result;
	}

	require_once(dirname(__FILE__) . '/config.php');

	// Prep DB
	if ($database['type'] == 'sqlite') {
		$pdo = new PDO(sprintf('%s:%s', $database['type'], $database['file']));
	} else {
		$pdo = new PDO(sprintf('%s:host=%s;dbname=%s', $database['type'], $database['server'], $database['database']), $database['username'], $database['password']);
	}

 	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	DB::get()->setPDO($pdo);

	function getAuthProvider() {
		global $__AUTHPROVIDER, $config;

		if ($__AUTHPROVIDER === null) {
			if (isset($config['authProvider']['name'])) {
				if (class_exists($config['authProvider']['name'])) {
					try {
						$provider = new $config['authProvider']['name']();
						if ($provider instanceof AuthProvider) {
							$__AUTHPROVIDER = $provider;
						}
					} catch (Exception $ex) { }
				}
			} else {
				// Use full-auth provider if no specific provider has been
				// requested.
				$__AUTHPROVIDER = new FullAuthProvider();
			}

			if ($__AUTHPROVIDER === null) {
				// If we didn't get a valid provider, use the NullAuthProvider
				$__AUTHPROVIDER = new NullAuthProvider();
			}
		}

		return $__AUTHPROVIDER;
	}

	function recursiveFindFiles($dir) {
		if (!file_exists($dir)) { return; }

		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
		foreach($it as $file) {
			if (pathinfo($file, PATHINFO_EXTENSION) == "php") {
				yield $file;
			}
		}
	}

	function getDisplayEngine() {
		global $config;

		$displayEngine = new DisplayEngine($config);
		$displayEngine->setSiteName($config['sitename']);

		return $displayEngine;
	}

	function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	function get_mime_type($file) {
		$mime_types = [];
		$mime_types['css'] = 'text/css';
		$mime_types['js'] = 'application/javascript';

		$bits = explode('.', $file);
		$ext = strtolower(array_pop($bits));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		} else {
			return mime_content_type($file);
		}
	}

	function parseBool($input) {
		$in = strtolower($input);
		return ($in === true || $in == 'true' || $in == '1' || $in == 'on' || $in == 'yes');
	}

	function genUUID() {
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	function isValidMac($mac) {
		return preg_match('/^([A-F0-9]{2}[:|\-|\.]?){6}$/i', $mac);
	}


	class bcrypt {
		public static function hash($password, $work_factor = 0) {
			if ($work_factor > 0) { $options = ['cost' => $work_factor]; }
			return password_hash($password, PASSWORD_DEFAULT);
		}
		public static function check($password, $stored_hash, $legacy_handler = NULL) {
			return password_verify($password, $stored_hash);
		}
	}
