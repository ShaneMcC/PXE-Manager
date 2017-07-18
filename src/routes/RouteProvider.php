<?php

	interface RouteProvider {
		public function addUnauthedRoutes($router, $displayEngine, $api);
		public function addAuthedRoutes($authProvider, $router, $displayEngine, $api);
	}
