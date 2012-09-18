<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Drop all the tables and rerun all the migrations.
 * Will ask for confirmation before proceeding.
 *
 * @param boolean force use this flag to skip confirmation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_DB_Recreate extends Minion_Task {

	protected $_config = array(
		'force' => FALSE,
	);

	public function execute(array $options)
	{
		if ($options['force'] === NULL OR 'yes' === Minion_CLI::read('This will destroy all data in the current database. Are you sure? [yes/NO]'))
		{
			Minion_CLI::write('Dropping Tables', 'green');

			$migrations = new Migrations(array('log' => 'Minion_CLI::write'));
			$migrations->clear_all();

			Minion_Task::factory('db:migrate')->execute($options);
		}
		else
		{
			Minion_CLI::write('Nothing done', 'brown');
		}
	}
}
