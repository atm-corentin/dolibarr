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

dol_include_once('h2g2/class/querybuilder.class.php');

/**
 * \file        class/migrationmanager.class.php
 * \ingroup     h2g2
 * \brief       This file is the engine to manage module migration
 */
class MigrationManager
{

	/**
	 * @var DoliDB                  Database handler
	 */
	private $_db;

	/**
	 * @var AbstractMigration       Migration to execute
	 */
	private $_migration;

	/**
	 * @var string                  Classname of the module
	 */
	private $_moduleClassName;

	/**
	 * @var string                  Name of the module
	 */
	private $_moduleName;

	/**
	 * @var string                  Version of the module
	 */
	private $_moduleVersion;

	/**
	 * @var string                  List of all module versions ordered asc
	 */
	private $_moduleVersionList;

	/**
	 * @var string                  Path of the migration folder of the module
	 */
	private $_moduleMigrationPath;

	/**
	 * @var string                  Migration mode. Either 'update' or 'rollback'
	 */
	private $_mode;

	/**
	 * @var array                   List of migrations to execute
	 */
	private $_migrationsToExecute = array();

	/**
	 * @var array                   Array of migration files
	 */
	private $_migrationFiles = array();

	/**
	 * Mode constant
	 */
	const MODE_UPDATE = 'update';
	const MODE_ROLLBACK = 'rollback';
	const MODE_CREATE = 'create';
	const MODE_RESET = 'reset';

	/**
	 * Migration manager constructor.
	 *
	 * @param DoliDB   $db                  Database handler
	 * @param string   $moduleClassName     Classname of the module
	 * @param string   $moduleName          Name of the module
	 * @param string   $moduleVersion       Version of the module
	 * @param string[] $moduleVersionList   List of all module version ordered asc
	 * @param string   $moduleMigrationPath Path of the migration folder of the module
	 */
	public function __construct($db, $moduleClassName, $moduleName, $moduleVersion, $moduleVersionList, $moduleMigrationPath)
	{
		$this->_db = $db;
		$this->_moduleClassName = $moduleClassName;
		$this->_moduleName = $moduleName;
		$this->_moduleVersion = $moduleVersion;
		$this->_moduleVersionList = $moduleVersionList;
		$this->_moduleMigrationPath = $moduleMigrationPath;

		// Load the migration to execute
		$this->_loadMigration();
	}

	/**
	 * Get the classname of a file
	 *
	 * @param  string $filepath Filepath
	 * @return string                      Name of the class in the file
	 */
	private function _getFileClassName($filepath)
	{
		$fp = fopen($filepath, 'r');
		$class = '';
		$buffer = '';
		$i = 0;

		if ($fp) {
			while (!$class) {
				if (feof($fp)) { break; // Check the end of file
				}

				$buffer .= fread($fp, filesize($filepath));
				$tokens = token_get_all($buffer);

				if (strpos($buffer, '{') === false) { continue;
				}

				for (;$i<count($tokens);$i++) {
					if ($tokens[$i][0] === T_CLASS) {
						for ($j=$i+1;$j<count($tokens);$j++) {
							if ($tokens[$j] === '{') {
								$class = $tokens[$i+2][1];
							}
						}
					}
				}
			}
		}

		return $class;
	}

	/**
	 * Load the correct migration for the module in order to execute it.
	 *
	 * @return void
	 */
	private function _loadMigration()
	{
		$migrationFiles = array();

		// Scan module migration directory
		$files = scandir(dol_buildpath($this->_moduleMigrationPath));
		if ($files) {
			foreach ($files as $file) {
				if (!in_array($file, ['.', '..'])) { // We skip . and ..
					$filePath = $this->_moduleMigrationPath.'/'.$file;
					$fileFullPath = dol_buildpath($filePath);
					dol_include_once($filePath);
					$classname = $this->_getFileClassName($fileFullPath);
					$classStatic = new $classname($this->_db);
					if ($classStatic) {
						$migrationFiles[$classStatic->version] = array('name' => $file, 'includepath' => $filePath,'fullpath' => $fileFullPath, 'classname' => $classname);
					}
				}
			}

			$this->_migrationFiles = $migrationFiles;
		}

		// Search which version to install
		$lastInstalledVersion = self::getLastVersionInstalled($this->_moduleName);
		if ($lastInstalledVersion) {
			$lastVersionIdx = array_search($lastInstalledVersion, $this->_moduleVersionList);

			if ($lastVersionIdx !== false && $lastVersionIdx >= 0) { // array_search return false
				// Get all versions to install
				$this->_loadMigrationFromIdx($lastVersionIdx, self::MODE_UPDATE);
			} else {
				// TODO : Error : last version intalled is not in $this->_moduleVersionList
			}
		} else {
			// No installation, we must install all versions
			$this->_loadMigrationForInstall();
		}
	}

	/**
	 * Load migrations for a full install
	 *
	 * @return void
	 * @throws Exception
	 */
	private function _loadMigrationForInstall()
	{
		if (count($this->_moduleVersionList) > 0) {
			// Init first version
			$versionToInstall = $this->_moduleVersionList[0];
			$versionInfo = $this->_migrationFiles[$versionToInstall];
			if ($versionInfo) {
				// Load the migration for execution
				$versionInfo['mode'] = self::MODE_CREATE;
				$this->_migrationsToExecute[$versionToInstall] = $versionInfo;
			}

			// Get all versions to install after the first one
			$lastVersionIdx = 0;
			$this->_loadMigrationFromIdx($lastVersionIdx, self::MODE_CREATE);
		}
	}

	/**
	 * Load migrations starting from one index
	 *
	 * @param  int    $lastVersionIdx Index of last version installed in $this->_moduleVersionList
	 * @param  string $mode           Mode ('create', 'delete', 'rollback')
	 * @return void
	 * @throws Exception
	 */
	private function _loadMigrationFromIdx($lastVersionIdx, $mode)
	{
		$listSize = count($this->_moduleVersionList);
		$i = $lastVersionIdx;
		while (++$i < $listSize) {
			// Get the version migration info for execution
			$versionToInstall = $this->_moduleVersionList[$i];
			$versionInfo = $this->_migrationFiles[$versionToInstall];
			if ($versionInfo) {
				// Load the migration for execution
				$versionInfo['mode'] = $mode;
				$this->_migrationsToExecute[$versionToInstall] = $versionInfo;
			} else {
				// Version is not implemented in migration folder
				dol_syslog("MigrationManager::_load : Can't load migration for version $versionToInstall (not implemented)", LOG_ERR);
			}
		}
	}

	/**
	 * Execute the migration loaded in $this->migration.
	 *
	 * @return int                  < 0 on error, > 0 on success
	 * @throws Exception
	 */
	private function _executeMigration()
	{
		global $conf;

		dol_syslog("--- Start executing '".$this->_migration->name."' migration ---", LOG_DEBUG);

		$versionAfterMigration = $this->_migration->version;

		switch ($this->_mode) {
			case self::MODE_CREATE :
			case self::MODE_UPDATE :
				$this->_migration->up();
			break;
			case self::MODE_ROLLBACK :
				$this->_migration->down();
				// We need to store previous version in case of rollback
				$prevVersion = $this->getVersionBefore($versionAfterMigration);
				$versionAfterMigration = $prevVersion ?: $versionAfterMigration;

				// Unactivate the module if it's activated to avoid functional problems
				if (!empty($conf->{$this->_moduleName}->enabled)) {
					include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
					unActivateModule($this->_moduleClassName, 0);
				}
			break;
			case self::MODE_RESET:
				$this->_migration->down();
				// We need to store previous version in case of rollback
				$prevVersion = $this->getVersionBefore($versionAfterMigration);
				$versionAfterMigration = $prevVersion ?: $versionAfterMigration;

				$this->_migration->up();
			break;
			default :
				dol_syslog('Migration mode "'.$this->_mode.'" not implemented.', LOG_ERR);
			throw new Exception('Migration mode "'.$this->_mode.'" not implemented.');
		}

		$queries = $this->_migration->getQueries();

		if ($queries) {
			foreach ($queries as $query) {
				$resql = $this->_db->query($query);
				if (!$resql) {
					dol_syslog("Got an error executing '".$this->_migration->name."' migration's following query : ".$query, LOG_ERR);
				}
			}
		}

		dol_syslog("--- End executing '".$this->_migration->name."' migration ---", LOG_DEBUG);

		// Insert information about migration in table
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c42migration (date_creation, module_name, module_version, action, entity) VALUES (";
		$sql.= "'".$this->_db->idate(dol_now())."', '".$this->_moduleName."', '".$versionAfterMigration."', '".$this->_mode."', ".$conf->entity.")";

		$resql = $this->_db->query($sql);

		return $resql ? 1 : -1;
	}

	/**
	 * Get the last version of the module which is installed
	 *
	 * @param  string $moduleName Name of the module
	 * @return string                      Last version of the module installed
	 * @throws Exception
	 */
	public static function getLastVersionInstalled($moduleName)
	{
		global $conf;

		$ret = '';

		try {
			$results = QueryBuilder::table('c42migration')
				->select('module_version')
				->where(
					[
					['entity', '=', $conf->entity],
					['module_name', '=', $moduleName]
					]
				)
				->orderBy('date_creation', 'DESC')
				->orderBy('module_version', 'DESC')
				->limit(1)
				->get();
		} catch (QueryBuilderException $e) {
			dol_syslog($e->getMessage(), LOG_ERR);
		}

		if (!empty($results) && $results[0]) {
			$ret = $results[0]->module_version;
		}

		return $ret;
	}

	/**
	 * Get the initial installation version of the module
	 *
	 * @param  string $moduleName Name of the module
	 * @return string                      Initial installation version of the module
	 * @throws Exception
	 */
	public static function getInitialInstallationVersion($moduleName)
	{
		global $conf;

		$ret = '';

		try {
			$results = QueryBuilder::table('c42migration')
				->select('module_version')
				->where(
					[
					['entity', '=', $conf->entity],
					['module_name', '=', $moduleName],
					['action', '=', 'create']
					]
				)
				->orderBy('module_version', 'DESC')
				->limit(1)
				->get();
		} catch (QueryBuilderException $e) {
			dol_syslog($e->getMessage(), LOG_ERR);
		}

		if (!empty($results) && $results[0]) {
			$ret = $results[0]->module_version;
		}

		return $ret;
	}

	/**
	 * Get the version history of the module
	 *
	 * @param  string $moduleName Name of the module
	 * @return array                       History of the module
	 * @throws Exception
	 */
	public static function getVersionHistory($moduleName)
	{
		global $conf;

		return QueryBuilder::table('c42migration')
			->select('date_creation', 'module_version', 'action')
			->where(
				[
				['entity', '=', $conf->entity],
				['module_name', '=', $moduleName],
				]
			)
			->orderBy('date_creation')
			->orderBy('module_version')
			->get();;
	}

	/**
	 * Compare versions in database and version list from module descriptor to delete all versions which don't corresponds
	 *
	 * @param  DolibarrModules    $moduleInstance    Name of the module
	 * @return array
	 */
	public function correctInstalledVersions($moduleInstance)
	{
		global $conf, $db;

		// [#63] If bad version number is insert in DB, we delete it
		$versionsDeleted = array();

		// Get all versions inserted in database
		$versions = QueryBuilder::table('c42migration')
			->select('module_version')
			->where(
				[
					['entity', '=', $conf->entity],
					['module_name', '=', $moduleInstance->rights_class],
				]
			)
			->get();

		// For each version, we check if it is contained in module descriptor's version list
		if (!empty($versions)) {
			foreach ($versions as $version) {
				// If it isn't, we delete version in database to avoid errors
				if (!empty($version->module_version) && !in_array($version->module_version, $moduleInstance->versionList)) {
					$res = QueryBuilder::table('c42migration')
						->where(
							[
								['entity', '=', $conf->entity],
								['module_name', '=', $moduleInstance->rights_class],
								['module_version', '=', $version->module_version]
							]
						)
						->delete();
					// We return version deleted to inform the user
					if ($res > 0) $versionsDeleted[] = $version->module_version;
				}
			}
		}

		return $versionsDeleted;
	}

	/**
	 * Method used to launch all migrations for the module
	 *
	 * @return void
	 * @throws Exception
	 */
	public function launch()
	{
		if ($this->_migrationsToExecute) {
			dol_syslog("\n\n------- Start executing all migrations for module '".$this->_moduleName."' and for following versions : ".implode(', ', array_keys($this->_migrationsToExecute)).' -------', LOG_DEBUG);
			foreach ($this->_migrationsToExecute as $migration) {
				// Execute version migration
				dol_include_once($migration['includepath']);
				$migrationObj = new $migration['classname']($this->_db);
				$this->_migration = $migrationObj;
				$this->_mode = $migration['mode'];

				$this->_executeMigration();
			}
			dol_syslog("\n------- End executing all migrations -------\n", LOG_DEBUG);
		}
	}

	/**
	 * Get the list of module migration files
	 *
	 * @return array            List of migration files
	 */
	public function getMigrationFiles()
	{
		return $this->_migrationFiles;
	}

	/**
	 * Execute a migration manually. This will execute one given version only
	 *
	 * @param  array  $migrationToExecute Migration file : array('name', 'includepath', 'fullpath', 'classname')
	 * @param  string $mode               Migration mode 'update', 'rollback' or 'create'
	 * @return void
	 */
	public function executeManualMigration($migrationToExecute, $mode)
	{
		// Execute version migration
		dol_include_once($migrationToExecute['includepath']);
		$migrationObj = new $migrationToExecute['classname']($this->_db);
		$this->_migration = $migrationObj;
		$this->_mode = $mode;

		$this->_executeMigration();
	}

	/**
	 * Execute a migration rollback manually. This will execute one given version only
	 *
	 * @param  array $migrationToExecute Migration file : array('name', 'includepath', 'fullpath', 'classname')
	 * @return void
	 */
	public function executeManualRollback($migrationToExecute)
	{
		$this->executeManualMigration($migrationToExecute, self::MODE_ROLLBACK);
	}

	/**
	 * Execute a migration reset manually. This will execute one given version only
	 *
	 * @param  array $migrationToExecute Migration file : array('name', 'includepath', 'fullpath', 'classname')
	 * @return void
	 */
	public function executeManualReset($migrationToExecute)
	{
		$this->executeManualMigration($migrationToExecute, self::MODE_RESET);
	}

	/**
	 * Execute a migration update manually. This will execute one given version only
	 *
	 * @param  array $migrationToExecute Migration file : array('name', 'includepath', 'fullpath', 'classname')
	 * @return void
	 */
	public function executeManualUpdate($migrationToExecute)
	{
		$this->executeManualMigration($migrationToExecute, self::MODE_UPDATE);
	}

	/**
	 * Get the previous version number
	 *
	 * @return string|null          Previous version number
	 */
	public function getPrevVersion()
	{
		$prevVersion = null;

		// Get the index of the version
		$versionIndex = array_search($this->_moduleVersion, $this->_moduleVersionList);
		if ($versionIndex !== false && $versionIndex > 0) {
			$prevVersion = $this->_moduleVersionList[$versionIndex - 1];
		} else {
			$prevVersion = $this->_moduleVersionList[0];
		}

		return $prevVersion;
	}

	/**
	 * Get the previous version number before the target version
	 *
	 * @param  string $targetVersion Targeting version
	 * @return string|null                     Previous version number
	 */
	public function getVersionBefore($targetVersion)
	{
		$prevVersion = null;

		// Get the index of the version
		$versionIndex = array_search($targetVersion, $this->_moduleVersionList);
		if ($versionIndex !== false && $versionIndex > 0) {
			$prevVersion = $this->_moduleVersionList[$versionIndex - 1];
		}

		return $prevVersion;
	}

	/**
	 * Get the next version number
	 *
	 * @return string|null          Next version number
	 */
	public function getNextVersion()
	{
		$nextVersion = null;

		// Get the index of the version
		$versionIndex = array_search($this->_moduleVersion, $this->_moduleVersionList);

		if ($versionIndex !== false && $versionIndex >= 0 && $versionIndex < count($this->_moduleVersionList) - 1) {
			$nextVersion = $this->_moduleVersionList[$versionIndex + 1];
		}

		return $nextVersion;
	}
}
