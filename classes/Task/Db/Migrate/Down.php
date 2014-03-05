<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migrate down the latest migration. Behavior changes when supplied any of the parameters
 *
 * options:
 *  - version: migrate all the way down to the specified migration.
 *  - steps: how many times to migrate down
 *  - dry-run if this flag is set, will run the migration without accually touching the database, only showing the result.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Migrate_Down extends Minion_Migration {

	protected $_options = array(
		'version' => NULL,
		'steps' => 1,
		'dry-run' => FALSE
	);

	protected function _execute(array $options)
	{
		$executed = $this->executed_migrations();

		$up = array();
		$down = array();

		if ($options["version"])
		{
			if (in_array($options["version"], $executed))
			{
				$down[] = $options["version"];
			}
		}
		else
		{
			$down = array_slice($executed, 0, $options['steps']);
		}

		$this->migrate($up, $down, $options['dry-run'] === NULL);
	}
}
