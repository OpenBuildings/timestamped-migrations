<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migrate down then up one migration. Behavior changes when supplied any of the parameters
 *
 * options:
 *  - version: migrate all the way down to the specified migration, and then all the way back up.
 *  - steps: how many times to migrate down before going back up.
 *  - dry-run: if this flag is set, will run the migration without accually touching the database, only showing the result.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Migrate_Redo extends Minion_Migration {

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

		if (isset($options['version']))
		{
			if (in_array($options['version'], $executed))
			{
				$down[] = $options['version'];
			}
		}
		else
		{
			$down = array_slice($executed, 0, $options['steps']);
		}

		if (isset($options['version']))
		{
			$up[] = $options['version'];
		}
		else
		{
			$up = array_reverse($down);
		}

		$this->migrate($up, $down, $options['dry-run'] === NULL);
	}
}
