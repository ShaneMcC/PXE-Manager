<?php

class BootableImageTwigLoader implements Twig_LoaderInterface {
	private $db;
	private $cache = [];

	public function __construct($db) {
		$this->db = $db;
	}

	public function getSourceContext($name) {
		[$data, $time] = $this->getTemplate($name);

		return new Twig_Source($data, $name);
	}

	public function getCacheKey($name) {
		[$data, $time] = $this->getTemplate($name);

		return $name;
	}

	public function isFresh($name, $time) {
		[$data, $lasttime] = $this->getTemplate($name);

		return ($time >= $lasttime);
	}

	public function exists($name) {
		try {
			return $this->getTemplate($name) !== FALSE;
		} catch (Twig_Error_Loader $ex) {
			return FALSE;
		}
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
			throw new Twig_Error_Loader('Unknown image ID: ' . $imageid);
		}

		if ($data === FALSE) {
			throw new Twig_Error_Loader($name . ' was not found.');
		}

		$this->cache[strtolower($name)] = $data;
		return $data;
	}

}

