<?php

	/**
	 * Basic global storage, per-page-load.
	 */
	class storage {
		private static $storedVars = array();

		/**
		 * Clear this storage.
		 *
		 * @param $keep (Default: []) Array of saved vars not to clear.
		 */
		static function clear($keep = []) {
			$keepData = array();
			foreach ($keep as $k) {
				if (isset(self::$storedVars[$k])) {
					$keepData[$k] = self::$storedVars[$k];
				}
			}
			self::$storedVars = array();

			foreach ($keepData as $k => $v) {
				self::$storedVars[$k] = $v;
			}
		}

		/**
		 * Store a variable in this storage. This will overwrite any existing
		 * variable with the same name.
		 *
		 * @param $variable Variable name
		 * @param $value Value to store
		 */
		static function set($variable, $value) {
			self::$storedVars[$variable] = $value;
		}

		/**
		 * Store a variable in this storage. This will turn the variable into
		 * an array if it is not already one, and then append a new element.
		 *
		 * @param $variable Variable name
		 * @param $value Value to store
		 */
		static function append($variable, $value) {
			if (!isset(self::$storedVars[$variable])) {
				self::$storedVars[$variable] = array();
			} else if (!is_array(self::$storedVars[$variable])) {
				self::$storedVars[$variable] = array(self::$storedVars[$variable]);
			}
			self::$storedVars[$variable][] = $value;
		}

		/**
		 * Check to see if a variable is stored in this storage under a given name.
		 *
		 * @param $variable Variable name
		 */
		static function exists($variable) {
			return isset(self::$storedVars[$variable]);
		}

		/**
		 * Get a variable stored in this storage.
		 *
		 * @param $variable Variable name
		 * @param $fallback Fallback value if value is not found.
		 */
		static function get($variable, $fallback = null) {
			return isset(self::$storedVars[$variable]) ? self::$storedVars[$variable] : $fallback;
		}

		/**
		 * Remove a stored variable from this storage.
		 *
		 * @param $variable Variable name
		 */
		static function remove($variable) {
			unset(self::$storedVars[$variable]);
		}
	}
