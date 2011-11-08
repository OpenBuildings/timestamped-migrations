<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Migration.
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author     Ivan K
 * @copyright  (c) 2011 OpenBuildings
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Migration
{
	private $driver = null;
	private $config = null;

	abstract public function up();
	abstract public function down();

	public function __construct($config = null)
	{
		$this->config = arr::merge(Kohana::$config->load('migrations')->as_array(), (array) $config);

		$database = Kohana::$config->load('database.'.Arr::get(Kohana::$config->load('migrations'), 'database', 'default'));

		// Set the driver class name
		$driver = 'Migration_Driver_'.ucfirst($database['type']);

		// Create the database connection instance
		$this->driver = new $driver(Arr::get(Kohana::$config->load('migrations'), 'database', 'default'));		
	}

	public function log($message)
	{
		if($this->config['log'])
		{
			call_user_func($this->config['log'], $message);
		}
		else
		{
			echo $message."\n";
			ob_flush();
		}
	}

	protected function run_driver($title, $method, $args)
	{
		$this->log("-- $title");
		$start = microtime(TRUE);
		call_user_func_array(array($this->driver, $method), $args);
		$end = microtime(TRUE);
		$this->log('   --> '.number_format($end-$start, 4).'s');
		return $this;
	}


	/**
	 * Create Table
	 *
	 * Creates a new table
	 *
	 * $fields:
	 *
	 * 		Associative array containing the name of the field as a key and the
	 * 		value could be either a string indicating the type of the field, or an
	 * 		array containing the field type at the first position and any optional
	 * 		arguments the field might require in the remaining positions.
	 * 		Refer to the TYPES function for valid type arguments.
	 * 		Refer to the FIELD_ARGUMENTS function for valid optional arguments for a
	 * 		field.
	 *
	 * @code
	 *
	 *		create_table (
	 * 			'blog',
	 * 			array (
	 * 				'title' => array ( 'string[50]', default => "The blog's title." ),
	 * 				'date' => 'date',
	 * 				'content' => 'text'
	 * 			),
	 * 		)
	 * @endcode
	 * @param	string   Name of the table to be created
	 * @param	array
	 * @param	mixed    Primary key, false if not desired, not specified sets to 'id' column.
	 *                   Will be set to auto_increment, serial, etc.
	 * @param bool if_not_exists
	 * @return	boolean
	 */
	public function create_table($table_name, $fields, $primary_key = TRUE, $if_not_exists = FALSE)
	{
		return $this->run_driver("create_table( $table_name, array(".join(", ", array_keys($fields)).") )", 'create_table', func_get_args());
	}

	/**
	 * Drop a table
	 *
	 * @param string    Name of the table
	 * @return boolean
	 */
	public function drop_table($table_name)
	{
		return $this->run_driver("drop_table( $table_name )", __FUNCTION__, func_get_args());
	}

	/**
	 * Rename a table
	 *
	 * @param   string    Old table name
	 * @param   string    New name
	 * @return  boolean
	 */
	public function rename_table($old_name, $new_name)
	{
		return $this->run_driver("rename_table( $old_name, $new_name )", __FUNCTION__, func_get_args());
	}
	
	/**
	 * Add a column to a table
	 *
	 * @code
	 * add_column ( "the_table", "the_field", array('string', 'limit[25]', 'not_null') );
	 * add_coumnn ( "the_table", "int_field", "integer" );
	 * @endcode
	 *
	 * @param   string  Name of the table
	 * @param   string  Name of the column
	 * @param   array   Column arguments array
	 * @return  bool
	 */
	public function add_column($table_name, $column_name, $params)
	{
		return $this->run_driver("add_column( $table_name, $column_name )", __FUNCTION__, func_get_args());
	}
	
	/**
	 * Rename a column
	 *
	 * @param   string  Name of the table
	 * @param   string  Name of the column
	 * @param   string  New name
	 * @return  bool
	 */
	public function rename_column($table_name, $column_name, $new_column_name)
	{
		return $this->run_driver("rename_column( $table_name, $column_name, $new_column_name )", __FUNCTION__, func_get_args());
	}
	
	/**
	 * Alter a column
	 *
	 * @param   string  Table name
	 * @param   string  Columnn ame
	 * @param   array   Column arguments
	 * @return  bool
	 */
	public function change_column($table_name, $column_name, $params)
	{
		return $this->run_driver("change_column( $table_name, $column_name )", __FUNCTION__, func_get_args());
	}
	
	/**
	 * Remove a column from a table
	 *
	 * @param   string  Name of the table
	 * @param   string  Name of the column
	 * @return  bool
	 */
	public function remove_column($table_name, $column_name)
	{
		return $this->run_driver("remove_column( $table_name, $column_name )", __FUNCTION__, func_get_args());
	}

	/**
	 * Add an index
	 *
	 * @param   string  Name of the table
	 * @param   string  Name of the index
	 * @param   string|array  Name(s) of the column(s)
	 * @param   string  Type of the index (unique/normal/primary)
	 * @return  bool
	 */
	public function add_index($table_name, $index_name, $columns, $index_type = 'normal')
	{
		return $this->run_driver("add_index( $table_name, $index_name, array(".join(', ',(array) $columns)."), $index_type )", __FUNCTION__, func_get_args());
	}

	/**
	 * Remove an index
	 *
	 * @param   string  Name of the table
	 * @param   string  Name of the index
	 * @return  bool
	 */
	public function remove_index($table_name, $index_name)
	{
		return $this->run_driver("remove_index( $table_name, $index_name )", __FUNCTION__, func_get_args());
	}
	
}
