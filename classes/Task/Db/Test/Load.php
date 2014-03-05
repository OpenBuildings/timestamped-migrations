<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Load the latest structure to the test database.
 * Will also load all the sql files from test/test_data/structure/test-schema-<type>.sql
 * where <type> is based on the test database type.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Test_Load extends Minion_Database {

	protected function _execute(array $options)
	{
		$db = $this->db_params(Kohana::TESTING);

		Minion_Task::factory(array(
				'task' => 'db:structure:load',
				'database' => Kohana::TESTING,
				'force' => NULL,
				'file' => NULL,
			))
			->execute();

		$structure_files = array_filter(array_map(function($dir) use ($db) {
			$file = $dir.'tests'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'structure'.DIRECTORY_SEPARATOR.strtolower($db['type']).'.sql';
			return is_file($file) ? $file : NULL;
		}, Kohana::modules()));

		foreach ($structure_files as $schema)
		{
			Minion_Task::factory(array(
				'task' => 'db:structure:load',
				'database' => Kohana::TESTING,
				'force' => NULL,
				'file' => $schema,
			))
			->execute();
		}
	}
}
