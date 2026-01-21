<?php namespace h2g2;
/* Copyright (C) 2021  Fabien FERNANDES ALVES <fabien@code42.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

dol_include_once('/h2g2/class/querybuilderexception.class.php');

/**
 * \file        class/querybuilder.class.php
 * \ingroup     h2g2
 * \brief       This file is a db request manager / helper
 */
class QueryBuilder
{
	/**
	 * @var object Query Builder Instance
	 */
	protected static $instance = null;

	/**
	 * @var Database    $db     Database handler
	 */
	private $db;

	/**
	 * The table which the query is targeting.
	 * We can select on multiple table if needed.
	 *
	 * @var array
	 */
	private $from = array();

	/**
	 * The columns that should be returned.
	 *
	 * @var array|string
	 */
	private $columns = '*';

	/**
	 * Array of column and value pairs to update
	 *
	 * @var array
	 */
	private $update = array();

	/**
	 * The where constraints for the query.
	 *
	 * @var array
	 */
	private $wheres = array();

	/**
	 * The table joins for the query.
	 *
	 * @var array
	 */
	private $joins = array();

	/**
	 * The order by constraints for the query
	 *
	 * @var array
	 */
	private $order = array();

	/**
	 * The limit option for the query
	 *
	 * @var int
	 */
	private $limit = null;

	/**
	 * The offset option for the query
	 *
	 * @var int
	 */
	private $offset = null;

	/**
	 * Indicate if the query returns distinct results.
	 *
	 * @var bool
	 */
	private $distinct = false;

	/**
	 * String of free sql if user want to type it himself
	 *
	 * @var string
	 */
	private $freesql = null;

	/**
	 * Indicate if we throw a warning if there is no entity check in the query
	 *
	 * @var bool
	 */
	private $entityCheck = true;

	/**
	 * QueryBuilder constructor.
	 *
	 * @param string $from    The table which the query is targeting.
	 * @param bool   $freesql True if we want to use $from param as handmade sql query
	 */
	public function __construct($from, $freesql = false)
	{
		global $db, $conf;

		$this->db = $db;

		// Check if global QB_DISABLE_ENTITY_CHECK to disable entity check warning is set into dolibarr
		if (property_exists($conf->global, 'QB_DISABLE_ENTITY_CHECK') && $conf->global->QB_DISABLE_ENTITY_CHECK == 1) {
			$this->entityCheck = false;
		}

		if ($freesql) {
			$this->freesql = $from;
		} else {
			$this->addFrom($from);
		}
	}

	/**
	 * Return Query Builder instance defining the table
	 *
	 * @param  string $from The table which the query is targeting.
	 * @return QueryBuilder                A new Query Builder instance
	 */
	public static function table($from)
	{
		self::$instance = new self($from);
		return self::$instance;
	}

	/**
	 * Give a full handmade query to the builder
	 *
	 * @param  string $sql Sql query string
	 * @return QueryBuilder                A new Query Builder instance
	 */
	public static function sql($sql)
	{
		self::$instance = new self($sql, true);
		return self::$instance;
	}

	/**
	 * Set the columns that should be selected.
	 *
	 * Columns argument can either be :
	 *   - An array => select(array('name', 'age'))
	 *   - A list of argument => select('name', 'age')
	 *
	 * @param  array|mixed ...$columns Columns that should be selected
	 * @return QueryBuilder
	 */
	public function select(...$columns)
	{
		if ($columns) {
			if (is_array($columns[0])) { // First parameter is an array
				$columns = $columns[0];
			}

			foreach ($columns as $column) {
				if (!is_array($this->columns)) {
					$this->columns = array();
				}
				array_push($this->columns, $column);
			}
		}

		return $this;
	}

	/**
	 * Set the column and value to be updated.
	 * Columns argument can either be :
	 *  - An array => array('column name' => 'new value', 'column name' => 'new value', ...)
	 *  - 2 args => update(column, value)
	 *
	 * @param  array|mixed ...$columns Column and value pairs indicating the columns to be updated
	 * @return QueryBuilder
	 * @throws QueryBuilderException
	 */
	public function update(...$columns)
	{
		if ($columns) {
			if (is_array($columns[0])) { // First parameter is an array
				$this->update = $columns[0];
			} elseif (count($columns) > 1) { // We have to parameter
				$this->update  = array($columns[0] => $columns[1]);
			} else { // Wrong usage
				throw new QueryBuilderException('Wrong usage of QueryBuilder update function.');
			}
		}

		return $this;
	}

	/**
	 * Add a where clause to the query
	 *
	 * @param  string $column       Column to filter on
	 * @param  string $operator     Comparison operator
	 * @param  string $value        Comparison value
	 * @param  string $filter       Filter AND / OR
	 * @param  bool   $escapestring True if the $value it's a string to escape
	 * @return void
	 */
	private function addWhere($column, $operator, $value = null, $filter = 'AND', $escapestring = true)
	{
		array_push($this->wheres, array('column' => $column, 'operator' => $operator, 'value' => $value, 'filter' => $filter, 'escapestring' => $escapestring));
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * Columns argument can either be :
	 *   - A column => where('name', 'like', 'ext%')
	 *   - A list of argument => where(array(array('name', 'like', 'ext%')))
	 *
	 * @param  string|array $column   Column to filter or list a where clauses
	 * @param  mixed        $operator Operator or comparison value
	 * @param  mixed        $value    Comparison value
	 * @param  string       $filter   Filter AND / OR
	 * @return QueryBuilder
	 */
	public function where($column, $operator = null, $value = null, $filter = 'AND')
	{
		if (is_array($column)) { // First parameter is a list of where clauses
			foreach ($column as $whereClause) {
				if (count($whereClause) == 3) {
					$this->addWhere($whereClause[0], $whereClause[1], $whereClause[2], $filter);
				}
			}
		} else {
			if (func_num_args() === 2) { // We assume that the operator is an equals sign
				$value = $operator;
				$operator = '=';
			}

			$this->addWhere($column, $operator, $value, $filter);
		}

		return $this;
	}

	/**
	 * Add a where is null clause for a column
	 *
	 * @param  string $column Column to filter
	 * @return QueryBuilder
	 */
	public function whereNull($column)
	{
		$this->addWhere($column, "IS NULL");

		return $this;
	}

	/**
	 * Add a basic or where clause to the query.
	 *
	 * Columns argument can either be :
	 *   - A column => orWhere('name', 'like', 'ext%')
	 *   - A list of argument => orWhere(array(array('name', 'like', 'ext%')))
	 *
	 * @param  string|array $column   Column to filter or list a where clauses
	 * @param  mixed        $operator Operator or comparison value
	 * @param  mixed        $value    Comparison value
	 * @return QueryBuilder
	 */
	public function orWhere($column, $operator = null, $value = null)
	{
		$this->where($column, $operator, $value, $filter = 'OR');

		return $this;
	}

	/**
	 * Add a where is not null clause for a column
	 *
	 * @param  string $column Column to filter
	 * @return QueryBuilder
	 */
	public function whereNotNull($column)
	{
		$this->addWhere($column, "IS NOT NULL");

		return $this;
	}

	/**
	 * Add a where in clause for a column
	 *
	 * @param  string $column Column to filter
	 * @param  array  $values Array of values that column's value is contained
	 * @return QueryBuilder
	 */
	public function whereIn($column, $values)
	{
		$val = '('.implode(", ", $values).')';
		$this->addWhere($column, "IN", $val, 'AND', false);

		return $this;
	}

	/**
	 * Dump the current SQL
	 *
	 * @return QueryBuilder
	 */
	public function dump()
	{
		// If user use free sql display his request however build the query and display the result
		if ($this->freesql) {
			var_dump($this->freesql);
		} else { var_dump($this->buildQuery());
		}

		return $this;
	}

	/**
	 * Set the query to return distinct results.
	 *
	 * @return $this
	 */
	public function distinct()
	{
		$this->distinct = true;

		return $this;
	}

	/**
	 * Add a join clause to the query
	 *
	 * @param  string $table  Table to join
	 * @param  string $first  First column to join on
	 * @param  string $second Second column to join on
	 * @param  string $type   Join type (INNER, LEFT, RIGHT, OUTER, CROSS, FULL, SELF, NATURAL)
	 * @return QueryBuilder
	 */
	private function addJoinClause($table, $first, $second, $type = "INNER")
	{
		array_push(
			$this->joins, [
			'table' => MAIN_DB_PREFIX.$table,
			'first' => $first,
			'second' => $second,
			'type' => $type
			]
		);

		return $this;
	}

	/**
	 * Add an order by clause to the query
	 *
	 * @param  string $column Column name to order by
	 * @param  string $sort   Sort order, by default asc
	 * @return QueryBuilder
	 */
	public function orderBy($column, $sort = 'ASC')
	{
		array_push(
			$this->order, [
			'column' => $column,
			'sort' => strtoupper($sort)
			]
		);

		return $this;
	}

	/**
	 * Add a limit option to the query
	 *
	 * @param  int $limit Number of results returned by the query
	 * @return QueryBuilder
	 */
	public function limit($limit)
	{
		$this->limit = (int) $limit;

		return $this;
	}

	/**
	 * Add an offset option to the query
	 *
	 * @param  int $offset Number if results to skip in the query
	 * @return QueryBuilder
	 */
	public function offset($offset)
	{
		$this->offset = (int) $offset;

		return $this;
	}

	/**
	 * Add an inner join clause to the query
	 *
	 * @param  string $table  Table to join
	 * @param  string $first  First column to join on
	 * @param  string $second Second column to join on
	 * @return QueryBuilder
	 */
	public function join($table, $first, $second)
	{
		return $this->addJoinClause($table, $first, $second);
	}

	/**
	 * Add a left join clause to the query
	 *
	 * @param  string $table  Table to join
	 * @param  string $first  First column to join on
	 * @param  string $second Second column to join on
	 * @return QueryBuilder
	 */
	public function leftJoin($table, $first, $second)
	{
		return $this->addJoinClause($table, $first, $second, "LEFT");
	}

	/**
	 * Add a right join clause to the query
	 *
	 * @param  string $table  Table to join
	 * @param  string $first  First column to join on
	 * @param  string $second Second column to join on
	 * @return QueryBuilder
	 */
	public function rightJoin($table, $first, $second)
	{
		return $this->addJoinClause($table, $first, $second, "RIGHT");
	}

	/**
	 * Disable the warning in case there is no entity check
	 *
	 * @return QueryBuilder
	 */
	public function disableEntityCheck()
	{
		$this->entityCheck = false;

		return $this;
	}

	/**
	 * Add an outer join clause to the query
	 *
	 * @param  string $table  Table to join
	 * @param  string $first  First column to join on
	 * @param  string $second Second column to join on
	 * @return QueryBuilder
	 */
	public function outerJoin($table, $first, $second)
	{
		return $this->addJoinClause($table, $first, $second, "OUTER");
	}

	/**
	 * Set the table which the query is targeting and add dolibarr table prefix.
	 *
	 * @param  string $from The table which the query is targeting.
	 * @return void
	 */
	private function addFromWithPrefix($from)
	{
		array_push($this->from, MAIN_DB_PREFIX.$from);
	}

	/**
	 * Add another table to select from.
	 * Usage : QueryBuilder::table('user')->addFrom('device')
	 * QueryBuilder::table('user')->addFrom('device', 'something')
	 *
	 * @param  string|array ...$table Table name or a list of table.
	 * @return QueryBuilder
	 */
	public function addFrom(...$table)
	{
		if (is_array($table)) { // We want to add multiple table
			foreach ($table as $elem) {
				$this->addFromWithPrefix($elem);
			}
		} else { // We assume it's only one table
			$this->addFromWithPrefix($table);
		}

		return $this;
	}

	/**
	 * Manage where filters
	 *
	 * @param  int   $idx         Index of where clause in the array
	 * @param  array $whereClause Where clause definition
	 * @return string
	 */
	private function generateWhereFilter($idx, $whereClause)
	{
		$ret = "";
		if ($idx > 0) {
			$ret.= " ".$whereClause['filter']." ";
		}

		return $ret;
	}

	/**
	 * Prepare the where clause part of the query
	 *
	 * @return string               The where clause part to add to the query
	 */
	private function prepareWhereClause()
	{
		global $langs;

		$langs->load("h2g2@h2g2");

		$ret = "";
		$whereClausesNb = count($this->wheres);
		$entity = false;

		if ($whereClausesNb > 0) {
			$ret.= " WHERE ";
			foreach ($this->wheres as $idx => $whereClause) {
				// Add the filter AND / OR
				$ret.= $this->generateWhereFilter($idx, $whereClause);

				// Check entity verif
				if (strstr($whereClause['column'], 'entity')) {
					$entity = true;
				}

				// Add values to compare
				if (!is_null($whereClause['value'])) { // Classic where clause contains a value
					$column = $whereClause['column'];
					$operator = $whereClause['operator'];
					$value = $whereClause['value'];
					$ret.= $column." ".$operator." ";

					// Escape string and surround them with quotes
					if ($whereClause['escapestring']) {
						$ret.= "'".$this->db->escape($value)."'";
					} else {
						$ret.= $value;
					}
				} else { // We suppose it's a IS NULL / IS NOT NULL where clause
					$column = $whereClause['column'];
					$operator = $whereClause['operator'];
					$ret.= $column." ".$operator;
				}
			}
		}

		// Show a warning if there is no entity check
		if (!$entity && $this->entityCheck) {
			trigger_error($langs->transnoentities("H2G2WarningNoEntityCheck"), E_USER_WARNING);
		}

		return $ret;
	}

	/**
	 * Prepare the join clause part of the query
	 *
	 * @return string               The joins part to add to the query
	 */
	private function prepareJoins()
	{
		$ret = "";
		foreach ($this->joins as $join) {
			$ret.= " ".$join['type']." JOIN ".$join['table']." ON ".$join['first']." = ".$join['second'];
		}

		return $ret;
	}

	/**
	 * Prepare the from clause part of the query
	 *
	 * @param  bool $removeForm True to remove "FROM" statements for update or insert query
	 * @return string                      The from part to add to the query
	 */
	private function prepareFrom($removeForm = false)
	{
		$ret = "";
		$fromClausesNb = count($this->from);

		if ($fromClausesNb > 0) {
			if (!$removeForm) {
				$ret = " FROM ";
			}
			foreach ($this->from as $idx => $from) {
				$ret.= $from;

				if ($idx + 1 < $fromClausesNb) {
					$ret.= ", ";
				}
			}
		}
		return $ret;
	}

	/**
	 * Prepare the order by part of the query
	 *
	 * @return string           The order by part to add to the query
	 */
	private function prepareOrderBy()
	{
		$ret = "";
		$orderClausesNb = count($this->order);

		if ($orderClausesNb > 0) {
			$ret = " ORDER BY";
			foreach ($this->order as $order) {
				$ret.= " ".$order['column']." ".$order['sort'].",";
			}
			// delete the last ,
			$ret = substr($ret, 0, -1);
		}

		return $ret;
	}

	/**
	 * Prepare the limit/offset part of the query
	 *
	 * @return string           The limit/offset part to add to the query
	 */
	private function prepareLimitOffset()
	{
		$ret = "";

		if ($this->limit) {
			$ret .= " LIMIT $this->limit";
		}
		if ($this->offset) {
			$ret .= " OFFSET $this->offset";
		}

		return $ret;
	}

	/**
	 * Prepare the update column => value part of the query
	 *
	 * @return string           The update part to add to the query
	 */
	private function prepareUpdate()
	{
		$ret = "";

		if ($this->update) {
			$ret.= " SET";
			foreach ($this->update as $col => $val) {
				if (is_null($val)) {
					$ret .= " $col = NULL,";
				} else if (is_int($val)) {
					$ret .= " $col = $val,";
				} else {
					$ret .= " $col = '".$this->db->escape($val)."',";
				}
			}
			// delete the last ,
			$ret = substr($ret, 0, -1);
		}

		return $ret;
	}

	/**
	 * Build the entire SELECT query based on QueryBuilder parameters
	 *
	 * @return string                   SQL request
	 */
	private function buildSelectQuery()
	{
		$sql = "SELECT ";

		if ($this->distinct) {
			$sql.= "DISTINCT ";
		}

		// Manage columns selected
		$sql.= is_array($this->columns) ? implode(', ', $this->columns) : "*";

		// Manage from clause
		$sql.= $this->prepareFrom();

		// Manage joins
		$sql.= $this->prepareJoins();

		// Manage where options
		$sql.= $this->prepareWhereClause();

		// Manage order by options
		$sql.= $this->prepareOrderBy();

		// Manage limit and offset options
		$sql.= $this->prepareLimitOffset();

		return $sql;
	}

	/**
	 * Build the entire Update query based on QueryBuilder parameters
	 *
	 * @return string                   SQL request
	 */
	private function buildUpdateQuery()
	{
		$sql = "UPDATE ";

		// Manage from clause
		$sql.= $this->prepareFrom(true);

		// Manage update clause
		$sql.= $this->prepareUpdate();

		// Manage where options
		$sql.= $this->prepareWhereClause();

		return $sql;
	}

	/**
	 * Build the entire query based on QueryBuilder parameters
	 *
	 * @return string                   SQL request
	 */
	private function buildQuery()
	{
		if ($this->update) {
			return $this->buildUpdateQuery();
		} else {
			return $this->buildSelectQuery();
		}
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @return array                   The result of selected rows
	 * @throws QueryBuilderException
	 */
	public function get()
	{
		// If user use free sql use his request however build the query
		if ($this->freesql) {
			$sql = $this->freesql;
		} else { $sql = $this->buildQuery();
		}

		$resql = $this->db->query($sql);

		$result = array();

		if ($resql) {
			if ($this->update) { // If we are in update mode, we return true
				array_push($result, true);
			} else {
				while ($obj = $this->db->fetch_object($resql)) {
					array_push($result, $obj);
				}
			}
		} else {
			throw new QueryBuilderException('SQL Error : '.$this->db->lasterror, $sql);
		}

		return $result;
	}

	/**
	 * Execute the query as a "delete" statement.
	 *
	 * @return int                    > 0 on success
	 * @throws QueryBuilderException
	 */
	public function delete()
	{
		$sql = "DELETE";

		// Manage from clause
		$sql.= $this->prepareFrom();

		// Manage where options
		$sql.= $this->prepareWhereClause();

		$resql = $this->db->query($sql);

		if ($resql) {
			return 1;
		} else {
			throw new QueryBuilderException('SQL Error : '.$this->db->lasterror, $sql);
		}
	}
}
