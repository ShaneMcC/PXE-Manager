#!/usr/bin/env php
<?php
	require_once(dirname(__FILE__) . '/../functions.php');
	require_once(dirname(__FILE__) . '/dataChanges.php');

	use shanemcc\phpdb\DB;
	use shanemcc\phpdb\DBChanger;

	DB::get()->runChanges(new DataChanges());

	$authProvider = getAuthProvider();
	if ($authProvider instanceof DBChanger) {
		DB::get()->runChanges($authProvider);
	}

	exit(0);
