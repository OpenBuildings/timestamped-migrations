<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Migration_Actions
{
	public $up = array();
	public $down = array();
	private $driver = NULL;

	static private $patterns = array(
		'add_columns'    => '/^add_(.+)_to_(.+)$/',
		'remove_columns' => '/^remove_(.+)_from_(.+)$/',
		'create_table'   => '/^create_table_(.+)$/',
		'drop_table'     => '/^drop_table_(.+)$/',
		'rename_table'   => '/^rename_table_(.+)_to_(.+)$/',
		'rename_column'  => '/^rename_(.+)_to_(.+)_in_(.+)$/',
		'change_column'  => '/^change_(.+)_in_(.+)$/',
	);

	public function __construct(Migration_Driver $driver) 
	{
		$this->driver = $driver;
	}

	public function parse($string)
	{
		foreach (explode('_also_', $string) as $part)
		{
			foreach (self::$patterns as $method => $pattern) 
			{
				if (preg_match($pattern, $part, $matches))
				{
					call_user_func_array(array($this, $method), array_slice($matches, 1));
					break;
				}
			}
		}

		return $this;
	}

	public function template($template)
	{
		if ( ! is_file($template))
			throw new Migration_Exception("Template file :template does not exist", array($template));

		list($this->up, $this->down) = explode('--- DOWN ---', file_get_contents($template));

		return $this;
	}

	public function add_columns($columns, $table)
	{
		foreach (explode('_and_', $columns) as $column) 
		{
			$this->up[] = "\$this->add_column('$table', '$column', 'string');";
			$this->down[] = "\$this->remove_column('$table', '$column');";
		}
	}

	public function remove_columns($columns, $table)
	{
		foreach (explode('_and_', $columns) as $column) 
		{
			$this->up[] = "\$this->remove_column('$table', '$column');";

			try
			{
				$field_params = $this->driver->column($table, $column);

				$this->down[] = "\$this->add_column('$table', '$column', ".self::field_params_to_string($field_params).");";	
			}
			catch (Migration_Exception $e)
			{
				$this->down[] = "\$this->add_column('$table', '$column', 'string');";	
			}
		}
	}

	public function create_table($tables)
	{
		foreach (explode('_and_', $tables) as $table_name) 
		{
			$this->up[] = "\$this->create_table('{$table_name}', array( ));";
			$this->down[] = "\$this->drop_table('{$table_name}');";
		}		
	}

	public function drop_table($tables)
	{
		foreach (explode('_and_', $tables) as $table_name) 
		{
			$this->up[] = "\$this->drop_table('{$table_name}');";

			try 
			{
				$table = $this->driver->table($table_name)->load();
				$fields = array();
				$options = array();

				foreach ($table->fields as $name => $field_params)
				{
					$fields[] = "\n\t\t\t'$name' => ".self::field_params_to_string($field_params);
				}

				foreach($table->options as $name => $option)
				{
					$options[] = "'$name' => '$option'";
				}

				$this->down[] = "\$this->create_table('{$table->name}', array( ".join(',', $fields)." \n\t\t), array( ".join(',', $options)." )); ";	
			}
			catch (Migration_Exception $e)
			{
				$this->down[] = "\$this->create_table('{$table_name}', array( ));";
			}
		}
	}

	public function rename_table($old_name, $new_name)
	{
		$this->up[] = "\$this->rename_table('$old_name', '$new_name');";
		$this->down[] = "\$this->rename_table('$new_name', '$old_name');";		
	}

	public function rename_column($old_name, $new_name, $table)
	{
		$this->up[] = "\$this->rename_column('$table', '$old_name', '$new_name');";
		$this->down[] = "\$this->rename_column('$table', '$new_name', '$old_name');";		
	}

	public function change_column($columns, $table)
	{
		foreach (explode('_and_', $columns) as $column) 
		{
			$this->up[] = "\$this->change_column('$table', '$column', 'string');";

			try 
			{
				$field_params = $this->driver->column($table, $column);

				$this->down[] = "\$this->change_column('$table', '$column', ".self::field_params_to_string($field_params).");";	
			}
			catch (Migration_Exception $e)
			{
				$this->down[] = "\$this->change_column('$table', '$column', 'string');";	
			}
		}
	}

	static public function field_params_to_string($column)
	{
		$options = '';

		foreach ($column->params() as $option_name => $option)
		{
			if (is_bool($option))
			{
				$option = $option ? 'TRUE' : 'FALSE';
			}
			elseif (is_string($option))
			{
				$option = "'$option'";
			}
			$options[] = (is_numeric($option_name) ? '' : '"'.$option_name.'" => ').$option;
		}

		return "array( ".join(', ', $options).')';
	}
	

}
