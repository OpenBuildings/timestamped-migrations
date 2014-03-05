<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Migration.
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Migration
{
	private $driver = null;
	private $config = null;
	private $dry_run = false;

	abstract public function up();
	abstract public function down();


	public function __construct($config = null)
	{
		$this->config = arr::merge(Kohana::$config->load('migrations')->as_array(), (array) $config);
		$this->driver(Migration_Driver::factory(Arr::get($this->config, 'database', 'default')));
	}

	/**
	 * Get or set the current driver
	 */
	public function driver(Migration_Driver $driver = NULL)
	{
		if ($driver !== NULL)
		{
			$this->driver = $driver;
			return $this;
		}
		return $this->driver;
	}

	public function log($message)
	{
		if ($this->config['log'])
		{
			call_user_func($this->config['log'], $message);
		}
		else
		{
			echo $message."\n";
			ob_flush();
		}
	}

	public function dry_run($dry_run = NULL)
	{
		if ($dry_run !== NULL)
		{
			$this->dry_run = $dry_run;
			return $this;
		}

		return $this->dry_run;
	}

	protected function run_driver($title, $method, $args, $will_return = FALSE)
	{
		if ($title)
		{
			$this->log("-- ".($this->dry_run ? "[dry-run]" : '')."$title");
		}
		$start = microtime(TRUE);
		$return = NULL;

		if ( ! $this->dry_run)
		{
			$return = call_user_func_array(array($this->driver, $method), $args);
		}
		$end = microtime(TRUE);

		if ($title)
		{
			$this->log('   --> '.number_format($end-$start, 4).'s'.($method == 'execute' ? ', affected rows: '.$this->driver->affected_rows() : ''));
		}
		return $will_return ? $return : $this;
	}

	public function execute($sql, $params = NULL, $display = NULL)
	{
		$args = func_get_args();
		if ($display === NULL)
		{
			$display = str_replace("\n", '↵', $sql);
			$display = preg_replace("/[\s\t]+/", " ", $display);
			$display = "execute( ".Text::limit_chars($display, 80)." )";
		}
		return $this->run_driver($display, __FUNCTION__, $args);
	}

	public function query($sql, $params = NULL)
	{
		$args = func_get_args();
		$display = str_replace("\n", '↵', $sql);
		$display = preg_replace("/[\s\t]+/", " ", $display);
		$display = Text::limit_chars($display, 60);
		return $this->run_driver("query( $display )", __FUNCTION__, $args, TRUE);
	}


	public function quote($value)
	{
		if (is_array($value))
		{
			return '('.join(', ', array_map(array($this->driver(), 'quote'), $value)).')';
		}
		else
		{
			return $this->driver()->quote($value);
		}
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
	 * @param	array    array of options - 'primary_key', false if not desired, not specified sets to 'id' column. Will be set to auto_increment, serial, etc. , 'if_not_exists' - bool, and all the others will be added as options to the end of the create table clause.
	 * @param bool if_not_exists
	 * @return	boolean
	 */
	public function create_table($table_name, $fields, $options = NULL)
	{
		$args = func_get_args();
		return $this->run_driver("create_table( $table_name, array(".join(", ", array_keys($fields)).") )", __FUNCTION__, $args);
	}

	/**
	 * Drop a table
	 *
	 * @param string    Name of the table
	 * @return boolean
	 */
	public function drop_table($table_name)
	{
		$args = func_get_args();
		return $this->run_driver("drop_table( $table_name )", __FUNCTION__, $args);
	}

	/**
	 * Change table options (passed directly to alter table)
	 *
	 * @param string $table_name
	 * @param array $options an array of options
	 * @return boolean
	 */
	public function change_table($table_name, $options)
	{
		$args = func_get_args();
		return $this->run_driver("change_table( $table_name , array(".join(", ", array_keys((array) $options))."))", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("rename_table( $old_name, $new_name )", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("add_column( $table_name, $column_name )", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("rename_column( $table_name, $column_name, $new_column_name )", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("change_column( $table_name, $column_name )", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("remove_column( $table_name, $column_name )", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("add_index( $table_name, $index_name, array(".join(', ',(array) $columns)."), $index_type )", __FUNCTION__, $args);
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
		$args = func_get_args();
		return $this->run_driver("remove_index( $table_name, $index_name )", __FUNCTION__, $args);
	}

}
