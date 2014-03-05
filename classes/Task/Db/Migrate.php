<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Execute all unexecuted migrations. Behavior changes when supplied any of the parameters
 *
 * options:
 *  - version: set which version you want to go to. Will execute nessesary migrations to reach this version (up or down)
 *  - steps: how many migrations to execute before stopping. works for both up and down.
 *  - dry-run: if this flag is set, will run the migration without accually touching the database, only showing the result.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Migrate extends Minion_Migration {

	protected function _execute(array $options)
	{
		$executed = $this->executed_migrations();
		$unexecuted = $this->unexecuted_migrations();
		$all = $this->all_migrations();

		$up = array();
		$down = array();

		if ($options['version'])
		{
			foreach ($all as $migration)
			{
				if ( ! in_array($migration, $executed) AND $migration <= $options['version'])
				{
					$up[] = $migration;
				}
				if (in_array($migration, $executed) AND $migration > $options['version'])
				{
					$down[] = $migration;
				}
			}
		}
		elseif ($options['steps'])
		{
			$up = array_slice($unexecuted, 0, $options['steps']);
		}
		else
		{
			$up = $unexecuted;
		}

		$this->migrate($up, $down, $options['dry-run'] === NULL);
	}
}
