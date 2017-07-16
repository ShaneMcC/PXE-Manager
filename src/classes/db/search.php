<?php

	class Search {
		/** PDO object to search in. */
		private $_pdo;
		/** Main table to search in. */
		private $_table;
		/** Fields to extract. */
		private $_fields;
		/** last error for this object. */
		private $lastError = NULL;

		/** Fields to search for. */
		private $whereFields = [];

		/** Extra keys to get */
		private $extraSelectKeys = [];

		/** Add Ordering clauses */
		private $orderBy = [];

		/** Joins. */
		private $joins = [];

		/** Limit */
		private $limit = FALSE;

		public function __construct($pdo, $table, $fields) {
			$this->_pdo = $pdo;
			$this->_table = $table;
			$this->_fields = $fields;
		}

		/**
		 * Get the last error we encountered with the database.
		 *
		 * @return last error.
		 */
		public function getLastError() {
			return $this->lastError;
		}

		/**
		 * Find some rows from the database.
		 *
		 * @param $fields Fields to look for (Array of field => value)
		 * @param $comparators (Optional Array) Comparators to use for fields.
		 * @return FALSE if we were unable to find rows, else an array of rows.
		 */
		public function searchRows($searchFields, $comparators = []) {
			$this->whereFields = [];
			foreach ($searchFields as $key => $value) {
				$comparator = isset($comparators[$key]) ? $comparators[$key] : null;

				$this->where($key, $value, $comparator);
			}

			$rows = $this->getRows();
			return (count($rows) == 0) ? FALSE : $rows;
		}

		/**
		 * Add a where item to the search.
		 *
		 * @param $key Key to search for.
		 * @param $value Value to search for.
		 * @param $comparator (Default: '=') Comparator to use.
		 * @return $this for chaining.
		 */
		public function where($key, $value, $comparator = null) {
			$this->whereFields[] = [$key, $comparator, $value];
			return $this;
		}

		/**
		 * Add an order clause.
		 *
		 * @param $key Key to order by
		 * @param $direction Direction to order (Default: 'ASC')
		 * @return $this for chaining.
		 */
		public function order($key, $direction = 'ASC') {
			$this->orderBy[] = [$key, $direction];
			return $this;
		}

		/**
		 * Add a limit
		 *
		 * @param $limit Limit returned results.
		 * @param $offset (Optional) Optional offset.
		 * @return $this for chaining.
		 */
		public function limit($limit, $offset = FALSE) {
			$this->limit = [$limit, $offset];
			return $this;
		}

		/**
		 * Add an extra colum to select (eg from a join).
		 *
		 * This will not allow you to select a column AS a name that exists in
		 * the original fields that were passed, as these are all selected AS
		 * their own name.
		 *
		 * @param $table Table key is in.
		 * @param $key Key is in.
		 * @param $as (Default: $key) name to select this column as
		 * @return $this for chaining.
		 */
		public function select($table, $key, $as = null) {
			if ($as === null) { $as = $key; }

			if (!in_array($as, $this->_fields)) {
				$this->extraSelectKeys[] = [$table, $key, $as];
			}
			return $this;
		}

		/**
		 * Add a join
		 *
		 * @param $table Table to join to
		 * @param $on (Default: none) Statement to join on
		 * @param $direction (Default: none) LEFT/RIGHT join.
		 * @return $this for chaining.
		 */
		public function join($table, $on = null, $direction = null) {
			$this->joins[] = [$table, $on, $direction];
			return $this;
		}

		/**
		 * Get rows based on this object.
		 *
		 * @param $index (Default: FALSE) If set to a key id, this will set the
		 *               array keys of the rows to be the value of this.
		 *               If multiple rows have the same key, then the latest one
		 *               takes priority.
		 *               If $index is not a valid returned key then it will be
		 *               treated as FALSE.
		 * @return Array of matching rows.
		 */
		public function getRows($index = FALSE) {
			list($query, $params) = $this->buildQuery();

			$statement = $this->_pdo->prepare($query);
			$result = $statement->execute($params);
			$rows = [];
			if ($result) {
				$fetch = $statement->fetchAll(PDO::FETCH_ASSOC);
				if ($index !== FALSE) {
					foreach ($fetch as $row) {
						if (!array_key_exists($index, $row)) {
							$rows = $fetch;
							continue;
						}

						$rows[$row[$index]] = $row;
					}
				} else {
					$rows = $fetch;
				}
			} else {
				$this->lastError = $statement->errorInfo();
			}

			return $rows;
		}

		/**
		 * Build the query to execute.
		 *
		 * @return Array [$query, $params] of built query.
		 */
		public function buildQuery() {
			// Keys we are requesting
			$keys = [];

			// Specific keys to get
			foreach ($this->_fields as $key) {
				$keys[] = sprintf('`%s`.`%s` AS `%s`', $this->_table, $key, $key);
			}
			// Extra keys to get.
			foreach ($this->extraSelectKeys as $tablekey) {
				$keys[] = sprintf('`%s`.`%s` AS `%s`', $tablekey[0], $tablekey[1], $tablekey[2]);
			}

			// WHERE Data.
			$where = [];
			// Params
			$params = [];
			foreach ($this->whereFields as $f) {
				list($key, $comparator, $value) = $f;

				// If value is an array, then we can do OR or use IN.
				if (is_array($value)) {
					$arrayWhere = [];
					// Use IN for '=' or != or null comparators
					$useIN = ($comparator === null || $comparator == '=' || $comparator == '!=');

					// PDO doesn't support arrays, so we need to break it out
					// into separate params and expand the query to include
					// these params.
					for ($i = 0; $i < count($value); $i++) {
						// PDO-Friendly param name.
						$params[sprintf(':%s_%d', $key, $i)] = $value[$i];

						// If we're using IN then we just generate an array of
						// parameters, else generate the usual <key> <comparator> <param>
						if ($useIN) {
							$arrayWhere[] = sprintf(':%s_%d', $key, $i);
						} else {
							$arrayWhere[] = sprintf('`%s` %s :%s_%d', $key, $comparator, $key, $i);
						}
					}

					// Either build:
					//    <key> [NOT] IN (<params>)
					//    (<key> <comparator> <value> OR <key> <comparator> <value> OR <key> <comparator> <value> ... )
					if ($useIN) {
						if ($comparator == '!=') {
							$where[] = sprintf('`%s` NOT IN (%s)', $key, implode(', ', $arrayWhere));
						} else {
							$where[] = sprintf('`%s` IN (%s)', $key, implode(', ', $arrayWhere));
						}
					} else {
						$where[] = '(' . implode(' OR ', $arrayWhere) . ')';
					}
				} else {
					// Not an array, a nice simple <key> <comparator> <value> bit!
					$where[] = sprintf('`%s` %s :%s', $key, ($comparator === null ? '=' : $comparator), $key);
					$params[':' . $key] = $value;
				}
			}

			// Start off the query!
			$query = sprintf('SELECT %s FROM %s', implode(', ', $keys), $this->_table);

			// Add in any joins.
			if (count($this->joins) > 0) {
				foreach ($this->joins as $join) {
					// Join Direction
					if (!empty($join[2])) { $query .= sprintf(' %s', $join[2]); }
					// Join table
					$query .= sprintf(' JOIN %s', $join[0]);
					// Join on
					if (!empty($join[1])) { $query .= sprintf(' ON %s', $join[1]); }
				}
			}

			// Add our WHERE clause.
			if (count($where) > 0) {
				$query .= sprintf(' WHERE %s', implode(' AND ', $where));
			}

			// Add Ordering
			if (count($this->orderBy) > 0) {
				$orders = [];
				foreach ($this->orderBy as $order) {
					$orders[] = sprintf('`%s` %s', $order[0], $order[1]);
				}
				$query .= ' ORDER BY ' . implode(', ', $orders);
			}

			// Add LIMIT
			if ($this->limit !== FALSE) {
				if ($this->limit[1] === FALSE) {
					$query .= sprintf(' LIMIT %d', $this->limit[0]);
				} else {
					$query .= sprintf(' LIMIT %d,%d', $this->limit[0], $this->limit[1]);
				}
			}

			// Return the query and it's params!
			return [$query, $params];
		}
	}
