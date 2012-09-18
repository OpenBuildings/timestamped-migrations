<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Load the latest structure to the test database. 
 * Will also load all the sql files from test/test_data/structure/test-schema-<type>.sql
 * where <type> is based on the test database type.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_DB_Test_Load extends Minion_Database {

	public function execute(array $options)
	{
		$db = $this->db_params(Kohana::TESTING);

		Minion_Task::factory('db:structure:load')->execute(array(
			'database' => Kohana::TESTING,
			'force' => NULL,
			'file' => NULL,
		));

		$module_test_schemas = Kohana::find_file('tests/test_data/structure', 'test-schema-'.$db['type'], 'sql', TRUE);

		foreach ($module_test_schemas as $schema) 
		{
			Minion_Task::factory('db:structure:load')->execute(array(
				'database' => Kohana::TESTING,
				'force' => NULL,
				'file' => $schema,
			));
		}
	}

}
