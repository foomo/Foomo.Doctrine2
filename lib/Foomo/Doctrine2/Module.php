<?php
namespace Foomo\Doctrine2;

use Foomo\Modules\ModuleBase;

/**
 * Module Foomo.Doctrine2 for foomo
 * Created 2011-06-21 21:35:11
 */
class Module extends ModuleBase {
	/**
	 * the name of this module
	 *
	 */
	const NAME = 'Foomo.Doctrine2';
	/**
	 * Your module needs to be set up, before being used - this is the place to do it
	 */
	public static function initializeModule()
	{
	}
	public static function getIncludePaths()
	{
		return array(
			\Foomo\CORE_CONFIG_DIR_MODULES . DIRECTORY_SEPARATOR . self::NAME . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'doctrine-orm'
		);
	}
	/**
	 * Get a plain text description of what this module does
	 *
	 * @return string
	 */
	public static function getDescription()
	{
		return 'Doctrine 2 integration';
	}
	/**
	 * get all the module resources
	 *
	 * @return Foomo\Modules\Resource[]
	 */
	public static function getResources()
	{
		return array(
			\Foomo\Modules\Resource\Module::getResource('Foomo', self::VERSION)
		);
	}
}