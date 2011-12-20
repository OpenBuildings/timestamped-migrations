<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
abstract class Migration_Driver
{
	/**
	 * Valid types
	 * @var array
	 */
	protected $types = array
	(
		'decimal',
		'float',
		'double',
		'integer',
		'datetime',
		'date',
		'timestamp',
		'time',
		'text',
		'string',
		'binary',
		'boolean',
		'enum',
		'tinytext',
		'longtext',
		'POINT',
		'GEOMETRY',
		'point',
	);

	
	/**
	 * Is this a valid type?
	 *
	 * @return bool
	 */
	protected function is_type($type)
	{
		return in_array($type, $this->types);
	}	

	abstract public function generate_schema();
	abstract public function get_executed_migrations();
	abstract public function set_executed($version);
	abstract public function set_unexecuted($version);
	abstract public function create_table($table_name, $fields, $primary_key = TRUE);
	abstract public function drop_table($table_name);
	abstract public function rename_table($old_name, $new_name);
	abstract public function add_column($table_name, $column_name, $params);
	abstract public function rename_column($table_name, $column_name, $new_column_name);
	abstract public function change_column($table_name, $column_name, $params);
	abstract public function remove_column($table_name, $column_name);
	abstract public function add_index($table_name, $index_name, $columns, $index_type = 'normal');
	abstract public function remove_index($table_name, $index_name);
}
