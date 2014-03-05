<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Drop all the tables and rerun all the migrations.
 * Will ask for confirmation before proceeding.
 *
 * options:
 *  - force: use this flag to skip confirmation
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Recreate extends Minion_Task {

	protected $_options = array(
		'force' => FALSE,
	);

	protected function _execute(array $options)
	{
		if ($options['force'] === NULL OR 'yes' === Minion_CLI::read('This will destroy all data in the current database. Are you sure? [yes/NO]'))
		{
			Minion_CLI::write('Dropping Tables', 'green');

			$migrations = new Migrations(array('log' => 'Minion_CLI::write'));
			$migrations->clear_all();
			$options['task'] = 'db:migrate';

			Minion_Task::factory($options)->execute();
		}
		else
		{
			Minion_CLI::write(Minion_CLI::color('Nothing done', 'brown'));
		}
	}
}
