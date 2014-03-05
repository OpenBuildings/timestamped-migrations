<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
abstract class Migration_Driver
{

	/**
	 * Get the driver of a srtain type
	 * @param type $type
	 * @return type
	 */
	static public function factory($database = 'default')
	{
		$config = Kohana::$config->load("database.$database");

		if ( ! $config)
			throw new Migration_Exception("Configuration :database for database does not exist", array(':database' => $database));

		// Set the driver class name
		$driver_name = in_array($config['type'], array('PDO', 'MySQL')) ? 'Mysql' : ucfirst($config['type']);
		$driver = 'Migration_Driver_'.$driver_name;

		if ( ! class_exists($driver))
			throw new Migration_Exception("Driver :type does not exist (class :driver)", array(':type' => $config['type'], ':driver' => $driver));

		// Create the database driver instance
		return new $driver($database);
	}

	protected $versions = null;
	protected $_affected_rows = NULL;

	public function __construct($config)
	{
		$class = get_class($this).'_Versions';
		$this->versions = $class($this);
	}

	public function affected_rows()
	{
		return $this->_affected_rows;
	}
	/**
	 * Get or set the current versions
	 */
	public function versions(Migration_Driver_Versions $versions = NULL)
	{
		if ($versions == NULL)
		{
			return $this->versions;
		}
		else
		{
			$this->versions = $versions;
		}
		return $this;
	}

	abstract public function clear_all();

	abstract public function table($name);
	abstract public function column($name);
	abstract public function quote($string);

	abstract public function create_table($table_name, $fields, $primary_key = TRUE);
	abstract public function drop_table($table_name);
	abstract public function change_table($table_name, $options);
	abstract public function rename_table($old_name, $new_name);

	abstract public function add_column($table_name, $column_name, $params);
	abstract public function remove_column($table_name, $column_name);
	abstract public function change_column($table_name, $column_name, $params);
	abstract public function rename_column($table_name, $column_name, $new_column_name);

	abstract public function add_index($table_name, $index_name, $columns, $index_type = 'normal');
	abstract public function remove_index($table_name, $index_name);
}
