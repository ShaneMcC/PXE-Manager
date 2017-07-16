<?php

	class SearchToObject extends Search {
		/** Object to create. */
		private $_object;

		/** Our DB Object. */
		private $_db;

		public function __construct($db, $table, $fields, $object) {
			parent::__construct($db->getPDO(), $table, array_keys($fields));
			$this->_object = $object;
			$this->_db = $db;
		}

		/**
		 * Find some rows from the database.
		 *
		 * @param $fields Fields to look for (Array of field => value)
		 * @param $comparators (Optional Array) Comparators to use for fields.
		 * @return FALSE if we were unable to find rows, else an array of rows.
		 */
		public function search($searchFields, $comparators = []) {
			return $this->rowsToObject(parent::searchRows($searchFields, $comparators));
		}

		/**
		 * Find objects.
		 *
		 * @return Array of matching objects.
		 */
		public function find($index = FALSE) {
			return $this->rowsToObject(parent::getRows($index));
		}

		private function rowsToObject($rows) {
			if (!is_array($rows)) { return FALSE; }

			$return = array();
			foreach ($rows as $key => $row) {
				$class = $this->_object;
				$obj = new $class($this->_db);

				$method = new ReflectionMethod($class, 'setFromArray');
				$method->setAccessible(true);
				$method->invoke($obj, $row, true);

				$obj->postLoad();
				$obj->setChanged(false);

				$return[$key] = $obj;
			}

			return $return;
		}
	}
