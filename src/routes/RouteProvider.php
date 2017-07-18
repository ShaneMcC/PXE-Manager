<?php

	interface RouteProvider {
		public function init($config, $router, $displayEngine);
		public function addRoutes($authProvider, $router, $displayEngine, $api);
	}
