<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Copy the structure of one database to another.
 * Will ask for confirmation before proceeding.
 *
 * options:
 * 	- from: database id from config/database.php file to load structure from, 'default' by default
 * 	- to: database id from config/database.php file to dump structure to
 * 	- force: se this flag to skip confirmation
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Structure_Copy extends Minion_Database {

	protected $_options = array(
		'from' => 'default',
		'to' => NULL,
		'force' => FALSE,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('to', 'not_empty');
	}

	protected function _execute(array $options)
	{
		Minion_Task::factory(array(
				'task' => 'db:structure:dump',
				'database' => $options['from'],
				'force' => $options['force']
			))
			->execute();

		Minion_Task::factory(array(
				'task' => 'db:structure:load',
				'database' => $options['to'],
				'force' => $options['force']
			))
			->execute();
	}
}
