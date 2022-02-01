<?php

use Twig\Loader\LoaderInterface as Twig_LoaderInterface;
use Twig\Source as Twig_Source;
use Twig\Error\LoaderError as Twig_Error_Loader;
use Twig\TwigFunction as Twig_Function;

class BootableImageTwigLoader implements Twig_LoaderInterface {
	private $db;
	private $cache = [];

	public function __construct($db) {
		$this->db = $db;
	}

	public function getSourceContext(string $name): Twig_Source {
		[$data, $time] = $this->getTemplate($name);

		return new Twig_Source($data, $name);
	}

	public function getCacheKey(string $name): string {
		[$data, $time] = $this->getTemplate($name);

		return $name;
	}

	public function isFresh(string $name, int $time): bool {
		[$data, $lasttime] = $this->getTemplate($name);

		return ($time >= $lasttime);
	}

	public function exists(string $name) {
		try {
			return $this->getTemplate($name) !== FALSE;
		} catch (Twig_Error_Loader $ex) {
			return FALSE;
		}
	}

	public function injectTemplate($name, $contents) {
		$this->cache[strtolower($name)] = [$contents, time()];
	}

	public function getTemplate($name) {
		$data = FALSE;
		if (isset($this->cache[strtolower($name)])) {
			return $this->cache[strtolower($name)];
		}

		$bits = explode('/', strtolower($name));

		$imageid = $bits[0];
		$template = isset($bits[1]) ? $bits[1] : '';

		$image = BootableImage::load($this->db, $imageid);
		if ($image instanceof BootableImage) {
			if ($template == 'pxedata') {
				$data = [$image->getPXEData(), $image->getLastModified()];
			} else if ($template == 'script' || $template == 'kickstart' || $template == 'preseed') {
				$data = [$image->getScript(), $image->getLastModified()];
			} else if ($template == 'postinstall') {
				$data = [$image->getPostInstall(), $image->getLastModified()];
			}
		} else {
			throw new Twig_Error_Loader('Unknown image ID: ' . $imageid . ' (Looking for: ' . $name . ')');
		}

		if ($data === FALSE) {
			throw new Twig_Error_Loader($name . ' was not found.');
		}

		$this->cache[strtolower($name)] = $data;
		return $data;
	}

}

