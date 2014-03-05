<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Generate a migration file
 *
 * Based on the name of the migration it will be populated with appropraite commands:
 *
 *  - add_<column>_and_<column>_to_<table>
 *  - remove_<column>_and_<column>_from_<table>
 *  - drop_table_<table>
 *  - rename_table_<table>_to_<new table>
 *  - rename_<column>_to_<new column>_in_<table>
 *  - change_<column>_in_<table>
 *
 * You can also chain those together with also:
 *
 *  add_<column>_to_<table>_also_drop_table_<table>
 *
 * Additionally based on column names it will try to guess the type, using 'string' by default:
 *
 *  - ..._id, ..._count, ..._width, ..._height, ..._x, ..._y, id or position - integer
 *  - ..._at - datetime
 *  - ..._on - date
 *  - is_... - boolean
 *  - description or text - text
 *
 * options:
 *  - name: required paramter - the name of the migration
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Generate extends Minion_Task {

	protected $_options = array(
		'name' => NULL,
		'template' => NULL,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('name', 'not_empty');
	}

	protected function _execute(array $options)
	{
		$migrations = new Migrations(array('log' => 'Minion_CLI::write'));

		$migration = $migrations->generate_new_migration_file($options['name'], $options['template']);

		Minion_CLI::write(Minion_CLI::color($migration, 'green').Minion_CLI::color(' Migration File Generated', 'brown'));
	}
}
