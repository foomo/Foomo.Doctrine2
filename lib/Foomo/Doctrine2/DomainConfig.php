<?php

/*
 * This file is part of the foomo Opensource Framework.
 *
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\Doctrine2;

/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author bostjan <bostjan.marusic@bestbytes.de>
 */
class DomainConfig extends \Foomo\Config\AbstractConfig {
	//---------------------------------------------------------------------------------------------
	// ~ Constants
	//---------------------------------------------------------------------------------------------

	/**
	 * The name of the doctrine 2 domain configuration
	 */
	const NAME = 'Foomo.doctrine2';

	//---------------------------------------------------------------------------------------------
	// ~ Variables
	//---------------------------------------------------------------------------------------------

	/**
	 * Entity manager using configuration
	 *
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $entityManager;
	/**
	 * namespace for entity classes
	 *
	 * note: this is also needed if autoExport is set to true
	 *
	 * @var string
	 */
	public $entityNamespace = '\MyNamespace\Model';
	/**
	 * the folder where proxy classes need to be generated
	 *
	 * @var string
	 */
	public $proxyDir = 'moduleName/lib/MyNamespace/Model/Proxy';
	/**
	 * proxy namespace
	 *
	 * @var string
	 */
	public $proxyNamespace = '\MyNamespace\Proxy';
	/**
	 * Sets whether proxy classes should be generated automatically at runtime
	 * by Doctrine. If set to FALSE, proxy classes must be generated manually
	 * through the doctrine command line task generate-proxies. The
	 * recommended value for a production environment is FALSE.
	 *
	 * @var boolean
	 */
	public $generateProxyClasses = false;
	/**
	 * Implementation of the metadata driver. Select from:
	 *
	 * - \Doctrine\ORM\Mapping\Driver\AnnotationDriver
	 * - \Doctrine\ORM\Mapping\Driver\XmlDriver
	 * - \Doctrine\ORM\Mapping\Driver\YamlDriver
	 * - \Doctrine\ORM\Mapping\Driver\DriverChain
	 *
	 * @var string
	 */
	public $metadataDriverImplementation = '\Doctrine\ORM\Mapping\Driver\AnnotationDriver';
	/**
	 * Model/entity directory. Note that this folder contains files of type that
	 * depends on the setting of $metadataDriverImplementation
	 *
	 * @var string
	 */
	public $entityDir = 'moduleName/lib/MyNamespace/Model';
	/**
	 * sets the cache implementation to use for caching metadata information,
	 * that is, all the information you supply via annotations, xml or yaml,
	 * so that they do not need to be parsed and loaded from scratch on every
	 * single request which is a waste of resources. The cache implementation
	 * must implement the Doctrine\Common\Cache\Cache  interface. Select from:
	 *
	 * \Doctrine\Common\Cache\ApcCache
	 * \Doctrine\Common\Cache\MemcacheCache
	 * \Doctrine\Common\Cache\XcacheCache
	 *
	 * If not set, caching is disabled
	 *
	 * @var string
	 */
	public $cacheImplementation = '\Doctrine\Common\Cache\ApcCache';
	/**
	 * query cache implementation - optional
	 * Select from:
	 * \Doctrine\Common\Cache\ApcCache
	 * \Doctrine\Common\Cache\MemcacheCache
	 * \Doctrine\Common\Cache\XcacheCache
	 *
	 * @var string
	 */
	public $queryCacheImplementation = '\Doctrine\Common\Cache\ApcCache';
	/**
	 * sqlLogger implementation - optional.
	 * Is a class that implements the Doctrine\DBAL\Logging\SqlLogger interface
	 * For example:
	 *    \Doctrine\DBAL\Logging\EchoSqlLogger
	 *
	 * @var string
	 */
	public $sqlLogger = '\Doctrine\DBAL\Logging\EchoSqlLogger';
	/**
	 * the dsn to connect to
	 *
	 * @var string
	 */
	public $dsn;
	/**
	 * if true, the db scema will be exported if database not exists
	 *
	 * @var boolean
	 */
	public $autoExportSchema = false;
	/**
	 * flag for auto db creation if not exists
	 *
	 * @var boolean
	 */
	public $createDbIfNotExists = true;
	/**
	 * cache for tablenames
	 * @var array 
	 */
	private $tableNames;
	/**
	 * cache of model metadata
	 * @var array 
	 */
	private $classes;

	//---------------------------------------------------------------------------------------------
	// ~ Constructor
	//---------------------------------------------------------------------------------------------

	/**
	 * constructor. does nothing.
	 */
	public function __construct() {
		$this->getEntityManager();
	}

	//---------------------------------------------------------------------------------------------
	// ~ Public methods
	//---------------------------------------------------------------------------------------------

	/**
	 * get a db connection
	 *
	 * @throws Foomo\Doctrine2\UnsupportedDatabaseTypeException
	 *
	 * @return Doctrine\DBAL\Connection
	 */
	public function getConnection() {

		$type = null;
		if (isset($this->dsn)) {
			$this->parseConfig($this->dsn, $serverName, $dbName, $userName, $password);
			$type = preg_replace('/^(.+):\/\/.+/i', '$1', $this->dsn);
			//check if database type is supported
			if ($type != 'mysql') {
				throw new UnsupportedDatabaseTypeException('Specified database type ' . $type . ' in the doctrine 2 domain config is not supported.');
			}
			//TODO: handle other db types here: now mysql only
			//create DBAL connection
			if ($type == 'mysql') {
				$connectionParams = array(
					'dbname' => $dbName,
					'user' => $userName,
					'password' => $password,
					'host' => $serverName,
					'driver' => 'pdo_mysql'
				);
				//handle db creation
				if ($this->createDbIfNotExists) {
					if (!$this->databaseExists($dbName, $serverName, $userName, $password)) {
						$this->createMySQLDatabaseIfNotExists($dbName, $serverName, $userName, $password);
					}
				}
				$connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
				return $connection;
			}
			return null;
		}
	}

	/**
	 * return entity manager for this domain configuration
	 *
	 * @param $forceCreateNew boolean forces the creation of a new entity manager using a new db connection
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager($forceCreateNew = false) {

		//if forcing new entity manager, release the old connection first
		if ($forceCreateNew == true && isset($this->entityManager)) {
			try {
				$this->entityManager->getConnection()->close();
				$this->entityManager->close();
			} catch (\Exception $e) {
				trigger_error(__METHOD__ . $e->getMessage(), \E_USER_WARNING);
			}
		}


		if (!isset($this->entityManager) || $forceCreateNew) {
			$config = new \Doctrine\ORM\Configuration();
			//handle different implementations of the metadata driver
			//TODO: probably works just for the Annotation driver. Check others!
			//may need to pass additional parameters for others?
			switch ($this->metadataDriverImplementation) {
				case '\Doctrine\ORM\Mapping\Driver\AnnotationDriver':
					$driverImpl = $config->newDefaultAnnotationDriver(array(\Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . $this->entityDir));
					$config->setMetadataDriverImpl($driverImpl);
					break;

				case '\Doctrine\ORM\Mapping\Driver\XmlDriver':
					$driverImpl = new \Doctrine\ORM\Mapping\Driver\XmlDriver(array(\Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . $this->entityDir));
					$config->setMetadataDriverImpl($driverImpl);
					break;

				case '\Doctrine\ORM\Mapping\Driver\YamlDriver':
					$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(array(\Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . $this->entityDir));
					$config->setMetadataDriverImpl($driverImpl);
					break;

				case '\Doctrine\ORM\Mapping\Driver\DriverChain':
					$driverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain(array(\Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . $this->entityDir));
					$config->setMetadataDriverImpl($driverImpl);
					break;
				case true:
					throw new \Exception('Unsupported doctrine 2 metadata driver implementation.');
					break;
			}

			//set the metadata cache implementation based on config
			switch (true) {
				case $this->cacheImplementation == '\Doctrine\Common\Cache\ArrayCache':
					$cache = new \Doctrine\Common\Cache\ArrayCache();
					$config->setMetadataCacheImpl($cache);
					break;
				case $this->cacheImplementation == '\Doctrine\Common\Cache\ApcCache':
					$cache = new \Doctrine\Common\Cache\ApcCache();
					$config->setMetadataCacheImpl($cache);
					break;
				case $this->cacheImplementation == '\Doctrine\Common\Cache\MemcacheCache':
					$cache = new \Doctrine\Common\Cache\MemcacheCache();
					$config->setMetadataCacheImpl($cache);
					break;
				case $this->cacheImplementation == '\Doctrine\Common\Cache\XcacheCache':
					$cache = new \Doctrine\Common\Cache\XcacheCache();
					$config->setMetadataCacheImpl($cache);
					break;
				case $this->cacheImplementation == null:
					//if not set do nothing
					break;
				case true:
					throw new \Exception('Unsupported doctrine 2 cache implementation.');
					break;
			}

			//set the query cache based on config
			switch (true) {
				case $this->cacheImplementation == '\Doctrine\Common\Cache\ArrayCache':
					$queryCache = new \Doctrine\Common\Cache\ArrayCache();
					$config->setQueryCacheImpl($queryCache);
					break;
				case $this->queryCacheImplementation == '\Doctrine\Common\Cache\ApcCache':
					$queryCache = new \Doctrine\Common\Cache\ApcCache();
					$config->setQueryCacheImpl($queryCache);
					break;
				case $this->queryCacheImplementation == '\Doctrine\Common\Cache\MemcacheCache':
					$queryCache = new \Doctrine\Common\Cache\MemcacheCache();
					$config->setQueryCacheImpl($queryCache);
					break;
				case $this->queryCacheImplementation == '\Doctrine\Common\Cache\XcacheCache':
					$queryCache = new \Doctrine\Common\Cache\XcacheCache();
					$config->setQueryCacheImpl($queryCache);
					break;
				case $this->queryCacheImplementation == null:
					//if not set do nothing
					break;
				case true:
					throw new \Exception('Unsupported doctrine 2 query cache implementation.');
					break;
			}
			//set the proxy directory
			$config->setProxyDir($this->getDoctrineProxyDir());
			//set the proxy namespace
			$config->setProxyNamespace($this->proxyNamespace);
			$config->setAutoGenerateProxyClasses($this->generateProxyClasses);

			//set the SQL logger
			if ($this->sqlLogger == '\Doctrine\DBAL\Logging\EchoSqlLogger') {
				$logger = new \Doctrine\DBAL\Logging\EchoSQLLogger();
				$config->setSQLLogger($logger);
			}

			$connection = $this->getConnection();

			if ($connection) {
				$this->entityManager = \Doctrine\ORM\EntityManager::create($connection->getParams(), $config);

				$this->entityManager->getConnection()->setCharset('utf8');

				if ($this->autoExportSchema) {
					$this->exportSchema();
				}

				return $this->entityManager;
			}
		} else {
			return $this->entityManager;
		}
	}

	/**
	 * resets the entity manager.
	 *
	 * note: this creates a new entity manager internaly. should be used when entity manager is closed
	 * due to exception etc
	 *
	 */
	public function resetEntityManager() {
		$this->getEntityManager(true);
	}

	/**
	 * @return string
	 */
	public function getDoctrineProxyDir() {
		//var_dump(\Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . $this->proxyDir);
		return \Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . $this->proxyDir;
	}

	/**
	 * exports schemas if db not exists
	 *
	 * note: the db must exist at the time of the call
	 */
	public function exportSchema() {
		//if ($this->databaseExists($databaseName, $serverName, $userName, $password);
		$classes = $this->getOwnClasses();
		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$tool->updateSchema($classes, true);

		//$tool->createSchema($classes);
	}

	/**
	 * drop the scema if it exists
	 *
	 */
	public function dropSchema() {
		$this->entityManager = $this->getEntityManager();
		$classes = $this->getOwnClasses();
		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$tool->dropSchema($classes);
		//em might be broken now - create new one
		$em = $this->getEntityManager(true);
	}

	/**
	 * get the configuration array
	 *
	 * @internal
	 * @return array
	 */
	public function getValue() {
		$ret = array();
		foreach ($this as $prop => $value) {
			switch ($prop) {
				case 'entityManager':
					continue;
				default:
					$ret[$prop] = $value;
			}
		}
		return $ret;
	}

	/**
	 * Validate the schema definitions in our model classes.
	 * @return boolean true if the schema definitions are valid
	 */
	public function validateSchema() {
		$validator = new \Doctrine\ORM\Tools\SchemaValidator($this->getEntityManager());
		$errors = $validator->validateMapping();
		if (count($errors) > 0) {
			// Lots of errors!
			\trigger_error(implode("\n\n", $errors), \E_USER_WARNING);
			return false;
		}
		return true;
	}

	/**
	 * Returns an array of Metadata of all model classes.
	 * @return \Doctrine\ORM\Mapping\ClassMetadata[] array of metadata information for each model class
	 */
	public function getModelClasses() {
		if (!isset($this->classes)) {
			$this->classes = array();
			$factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
			$factory->setEntityManager($this->getEntityManager());
			$this->classes = $factory->getAllMetadata();
		}
		return $this->classes;
	}

	/**
	 * Deletes the contents of all tables. Use it for tests only!
	 * @note the entity manager is closed finally, so any references got by a former call of self::getEM() are obsolet now
	 */
	public function truncateTables() {
		if (empty($this->tableNames)) {
			$this->tableNames = array();
			foreach ($this->getModelClasses() as $classMetadata) {
				/* @var $classMetadata \Doctrine\ORM\Mapping\ClassMetadata */
				$tableName = $classMetadata->table['name'];
				foreach ($classMetadata->associationMappings as $am) {
					if (isset($am['joinTable'])) {
						$jt = $am['joinTable'];
						if (isset($jt['name']) && !in_array($jt['name'], $this->tableNames)) {
							$this->tableNames[] = $jt['name'];
						}
					}
				}
				if (!in_array($tableName, $this->tableNames)) {
					$this->tableNames[] = $tableName;
				}
			}
		}


		$conn = $this->entityManager->getConnection();
		$conn->executeQuery("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0");
		foreach ($this->tableNames as $tableName) {
			$query = "TRUNCATE TABLE $tableName";
			//file_put_contents('/tmp/db.log', "about to execute '$query'", FILE_APPEND);
			try {
				$conn->executeQuery($query);
				//file_put_contents('/tmp/db.log', "... executed" . PHP_EOL, FILE_APPEND);
			} catch (\Exception $e) {
				//file_put_contents('/tmp/db.log', "... failed" . PHP_EOL, FILE_APPEND);
			}
		}
		$conn->executeQuery("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS");
		$this->entityManager->close();
		$this->getEntityManager(true);
	}

	/**
	 * Creates proxy classes for all entities (model classes)
	 */
	public static function createProxies() {
		$factory = $this->entityManager->getProxyFactory();
		$factory->generateProxyClasses($this->getModelClasses());
	}

	//---------------------------------------------------------------------------------------------
	// ~ Private methods
	//---------------------------------------------------------------------------------------------

	/**
	 * @return array
	 */
	private function getOwnClasses() {
		$classes = array();
		$classMap = \Foomo\AutoLoader::getClassMap();
		foreach ($classMap as $className => $file) {
			// do not export the proxy classes!
			if (\strpos($file, $this->proxyDir) != false) {
				continue;
			}
			$needle = $this->entityDir;
			// remove leading backslash if any
			if (\substr($needle, 0, 1) == "\\") {
				$needle = \substr($needle, 1);
			}
			if (\strpos(\strtolower($file), \strtolower($needle)) !== false) {
				$classes[] = $this->entityManager->getClassMetadata($className);
			}
		}
		return $classes;
	}

	/**
	 * get a db connection
	 *
	 * @throws Foomo\Doctrine2\UnsupportedDatabaseTypeException
	 *
	 * @return Doctrine\DBAL\Connection
	 */
	private function parseConfig($dsn, &$serverName, &$dbName, &$username, &$password) {
		$parsed = \parse_url($dsn);
		$type = $parsed['scheme'];
		if ($parsed['scheme'] != 'mysql')
			throw new \Exception('Specified database type ' . $type . ' not supported.');
		$username = isset($parsed['user']) ? $parsed['user'] : '';
		$password = isset($parsed['pass']) ? $parsed['pass'] : '';
		$dbName = \substr($parsed['path'], 1);
		$type = $parsed['scheme'];
		$serverName = $parsed['host'];
	}

	/**
	 * create db if not exists
	 *
	 * uses mysql api as PDO does not allow to create db before connection is established
	 *
	 * @param string $databaseName
	 */
	private function createMySQLDatabaseIfNotExists($databaseName, $serverName, $userName, $password) {
		try {
			mysql_connect($serverName, $userName, $password);
			$query = "CREATE DATABASE IF NOT EXISTS " . $databaseName . ";";
			if (mysql_query($query)) {
				mysql_select_db($databaseName);
			} else {
				\trigger_error(__CLASS__ . __METHOD__ . ' : ' . mysql_error());
			}
			\mysql_close();
		} catch (\Exception $e) {
			\trigger_error(__CLASS__ . __METHOD__ . $e->getMessage());
		}
	}

	/**
	 * check if db exists
	 *
	 * @param string $databaseName
	 *
	 * @param string $serverName
	 *
	 * @param string $userName
	 *
	 * @param string $password
	 *
	 * @return boolean
	 */
	private function databaseExists($databaseName, $serverName, $userName, $password) {
		try {
			$conn = \mysql_connect($serverName, $userName, $password);
			// make foo the current db
			$db_selected = mysql_select_db($databaseName, $conn);
			if (!$db_selected) {
				\mysql_close($conn);
				return false;
			} else {
				\mysql_close($conn);
				return true;
			}
		} catch (\Exception $e) {
			return false;
		}
	}

	//---------------------------------------------------------------------------------------------
	// ~ Magic methods
	//---------------------------------------------------------------------------------------------

	/**
	 * @return string[]
	 */
	public function __sleep() {
		return array(
			'entityNamespace',
			'proxyDir',
			'proxyNamespace',
			'generateProxyClasses',
			'metadataDriverImplementation',
			'entityDir',
			'cacheImplementation',
			'queryCacheImplementation',
			'sqlLogger',
			'dsn',
			'autoExportSchema',
			'createDbIfNotExists'
		);
	}

}
