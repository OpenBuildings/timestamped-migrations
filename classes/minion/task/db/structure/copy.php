<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Copy the structure of one database to another.
 * Will ask for confirmation before proceeding.
 *
 * @param string from database id from config/database.php file to load structure from, 'default' by default
 * @param string to database id from config/database.php file to dump structure to
 * @param boolean force use this flag to skip confirmation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_DB_Structure_Copy extends Minion_Database {

	protected $_config = array(
		'from' => 'default',
		'to' => NULL,
		'force' => FALSE,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('to', 'not_empty');
	}

	public function execute(array $options)
	{
		Minion_Task::factory('db:structure:dump')->execute(array(
			'database' => $options['from'], 
			'force' => $options['force']
		));

		Minion_Task::factory('db:structure:load')->execute(array(
			'database' => $options['to'], 
			'force' => $options['force']
		));
	}

}
