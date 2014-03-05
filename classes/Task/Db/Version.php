<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Get the current migration version
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Version extends Minion_Task {

	protected function _execute(array $options)
	{
		$migrations = new Migrations(array('log' => 'Minion_CLI::write'));

		$executed_migrations = $migrations->get_executed_migrations();

		Minion_CLI::write('Current Version: '.end($executed_migrations));
	}
}
