<?php
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

/**
 * \file        class/abstractmigration.class.php
 * \ingroup     h2g2
 * \brief       This file is an abstract class for migration manager
 */

abstract class AbstractMigration
{

	/**
	 * @var DoliDB  Database handler
	 */
	protected $db;

	/**
	 * @var string  Version of the module which is associated to the migration
	 */
	public $version;

	/**
	 * @var string  Migration description
	 */
	public $description;

	/**
	 * @var string  Migration name
	 */
	public $name;

	/**
	 * @var array   List of queries for the migration
	 */
	protected $queries;

	/**
	 * AbtractMigration constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->queries = array();
	}

	/**
	 * Add an sql query to the migration content
	 *
	 * @param  string $sql Sql request to add for the migration
	 * @return void
	 */
	public function addQuery($sql)
	{
		array_push($this->queries, $sql);
	}

	/**
	 * Get the list of queries to execute for the migration
	 *
	 * @return array            List of queries
	 */
	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * Method executed when we up the migration
	 *
	 * @return void
	 */
	abstract public function up();

	/**
	 * Method executed when we rollback the migration
	 *
	 * @return void
	 */
	abstract public function down();
}
