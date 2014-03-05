<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Get the current migration version
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Minion_Database extends Minion_Task {

	protected function db_params($database)
	{
		$db = Kohana::$config->load("database.$database.connection");

		if ( ! isset($db['database']) )
		{
			if ( ! preg_match('/dbname=([^;]+)/', $db['dsn'], $matches))
				throw new Kohana_Exception("Error connecting to database, database missing");

			$db['database'] = $matches[1];
		}

		$db['type'] = Kohana::$config->load("database.$database.type");

		return $db;
	}
}
