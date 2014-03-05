<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migrate up the first unexecuted migration. Behavior changes when supplied any of the parameters
 *
 * options:
 *  - version: migrate all the way up to the specified migration.
 *  - steps: how many times to migrate up
 *  - dry-run: if this flag is set, will run the migration without accually touching the database, only showing the result.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Migrate_Up extends Minion_Migration {

	protected $_options = array(
		'version' => NULL,
		'steps' => 1,
		'dry-run' => FALSE
	);

	protected function _execute(array $options)
	{
		$unexecuted = $this->unexecuted_migrations();

		$up = array();
		$down = array();

		if ($options["version"])
		{
			if (in_array($options["version"], $unexecuted))
			{
				$up[] = $options["version"];
			}
		}
		else
		{
			$up = array_slice($unexecuted, 0, $options['steps']);
		}

		$this->migrate($up, $down, $options['dry-run'] === NULL);
	}
}
