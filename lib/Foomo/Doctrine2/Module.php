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
 * @author jan <jan@bestbytes.de>
 */
class Module extends \Foomo\Modules\ModuleBase
{
	//---------------------------------------------------------------------------------------------
	// ~ Constants
	//---------------------------------------------------------------------------------------------

	/**
	 * The name of this module
	 */
	const NAME = 'Foomo.Doctrine2';

	//---------------------------------------------------------------------------------------------
	// ~ Public static methods
	//---------------------------------------------------------------------------------------------

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
	 * Get all the module resources
	 *
	 * @return Foomo\Modules\Resource[]
	 */
	public static function getResources()
	{
		return array(
			\Foomo\Modules\Resource\Module::getResource('Foomo', self::VERSION),
			\Foomo\Modules\Resource\ClassName::getResource('Doctrine\Common\Version'), // Doctrine Common
			\Foomo\Modules\Resource\ClassName::getResource('Doctrine\DBAL\Version'), // Doctrine DBAL
			\Foomo\Modules\Resource\ClassName::getResource('Doctrine\ORM\Version'), // Doctrine ORM
		);
	}
}