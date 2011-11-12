<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Migrations class.
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Migrations
{
	protected $config;
	protected $driver;
	protected $migrations;
	public $output = null;

	/**
	 * Intialize migration library
	 *
	 * @param   bool   Do we want output of migration steps?
	 * @param   string Database group
	 */
	public function __construct($config = null)
	{
		$this->config = arr::merge(Kohana::$config->load('migrations')->as_array(), (array) $config);

		$database = Kohana::$config->load('database.'.Arr::get(Kohana::$config->load('migrations'), 'database', 'default'));

		// Set the driver class name
		$driver = 'Migration_Driver_'.ucfirst($database['type']);

		// Create the database connection instance
		$this->driver = new $driver(Arr::get(Kohana::$config->load('migrations'), 'database', 'default'));    

		$this->driver->generate_schema();

		if( ! is_dir($this->config['path']))
		{
			mkdir($this->config['path'], 0777, true);
		}
	}
	
	public function set_executed($version)
	{
		$this->driver->set_executed($version);
	}
	
	public function set_unexecuted($version)
	{
		$this->driver->set_unexecuted($version);
	}

	
	public function generate_new_migration_file($name)
	{
		$actions = $this->actions_from_string($name);

		$up = join("\n", array_map('Migrations::indent', $actions['up']));
		$down = join("\n", array_map('Migrations::indent', $actions['down']));

		$template = file_get_contents(Kohana::find_file('templates', 'migration', 'tpl'));
		$class_name = str_replace(' ','_',ucwords(str_replace('_',' ',$name)));
		$filename = sprintf("%d_$name.php", time());

		file_put_contents($this->config['path'].DIRECTORY_SEPARATOR.$filename, strtr($template, array('{up}' => $up, '{down}' => $down, '{class_name}' => $class_name)));   
		return $filename;
	}

	static function indent($action)
	{
		return "\t\t$action";
	}

	public function actions_from_string($string)
	{
		$actions = array('up' => array(), 'down' => array());
		$parts = explode('_also_', $string);
		$matches;
		foreach($parts as $part)
		{
			if(preg_match('/^add_(.*)_to_(.*)$/', $part, $matches))
			{
				foreach (explode('_and_', $matches[1]) as $column) {
					$actions['up'][] = "\$this->add_column('{$matches[2]}', '$column', 'string[255]');";
					$actions['down'][] = "\$this->remove_column('{$matches[2]}', '$column');";
				}
			}
			elseif(preg_match('/^remove_(.*)_from_(.*)$/', $part, $matches))
			{
				foreach (explode('_and_', $matches[1]) as $column) {
					$actions['up'][] = "\$this->remove_column('{$matches[2]}', '$column');";

					try{
						$field_params = $this->driver->get_column($matches[2], $column);

						$actions['down'][] = "\$this->add_column('{$matches[2]}', '$column', ".self::field_params_to_string($field_params).");";	
					}
					catch(Migration_Exception $e)
					{
						$actions['down'][] = "\$this->add_column('{$matches[2]}', '$column', 'string[255]');";	
					}
				}
			}
			elseif(preg_match('/^create_(.*)$/', $part, $matches))
			{
				$actions['up'][] = "\$this->create_table('{$matches[1]}', array( ) );";
				$actions['down'][] = "\$this->drop_table('{$matches[1]}');";
			}
			elseif(preg_match('/^drop_(.*)$/', $part, $matches))
			{
				$actions['up'][] = "\$this->drop_table('{$matches[1]}');";

				try{
					$table = $this->driver->get_table($matches[1]);
					$fields = array();
					$options = array();
					foreach($table->fields as $name => $field_params)
					{
						$fields[] = "\n\t\t\t'$name' => ".self::field_params_to_string($field_params);
					}

					foreach($table->options as $name => $option)
					{
						$options[] = "'$name' => '$option'";
					}

					$actions['down'][] = "\$this->create_table('{$table->name}', array( ".join(',', $fields)." \n\t\t), array( ".join(',', $options)." )); ";	
				}
				catch(Migration_Exception $e)
				{
					$actions['down'][] = "\$this->create_table('{$matches[1]}', array( ) );";
				}
			}
			elseif(preg_match('/^rename_table_(.*)_to_(.*)$/', $part, $matches))
			{
				$actions['up'][] = "\$this->rename_table('{$matches[1]}', '{$matches[2]}' );";
				$actions['down'][] = "\$this->rename_table('{$matches[2]}', '{$matches[1]}' );";
			}
			elseif(preg_match('/^rename_column_(.*)_to_(.*)_in_(.*)$/', $part, $matches))
			{
				$actions['up'][] = "\$this->rename_column('{$matches[3]}', '{$matches[1]}', '{$matches[2]}');";
				$actions['down'][] = "\$this->rename_column('{$matches[3]}', '{$matches[2]}', '{$matches[1]}');";
			}
			elseif(preg_match('/^change_(.*)_in_(.*)$/', $part, $matches))
			{
				foreach (explode('_and_', $matches[1]) as $column) {
					$actions['up'][] = "\$this->change_column('{$matches[2]}', '$column', 'string[255]');";

					try{
						$field_params = $this->driver->get_column($matches[2], $column);

						$actions['down'][] = "\$this->change_column('{$matches[2]}', '$column', ".self::field_params_to_string($field_params).");";	
					}
					catch(Migration_Exception $e)
					{
						$actions['down'][] = "\$this->change_column('{$matches[2]}', '$column', 'string[255]');";	
					}
					
				}
			}

		}
		$actions['down'] = array_reverse($actions['down']);
		return $actions;
	}


	static public function field_params_to_string($params)
	{
		$type = $params[0];
		$options = '';
		foreach( array_slice($params, 1) as $option_name => $option)
		{
			if($option === null)
			{
				$option = 'null';
			}
			elseif($option === false)
			{
				$option = 'false';
			}
			elseif(is_string($option))
			{
				$option = "'$option'";
			}
			$options[] = '"'.$option_name.'" => '.$option;
		}		
		if($options)
		{
			return "array( '$type', ".join(', ', $options).')';
		}
		else
		{
			return "'$type'";
		}
	}
	
	/**
	 * Loads a migration
	 *
	 * @param   integer   Migration version number
	 * @return  Migration_Core  Class object
	 */
	public function load_migration($version)
	{
		$f = glob(sprintf($this->config['path'] . DIRECTORY_SEPARATOR . '%d_*.php', $version));

		if ( count($f) > 1 ) // 
			throw new Migration_Exception('Only one migration per step is permitted, there are :count of version :version', array(':count' => count($f), ':version' => $version));

		if ( count($f) == 0 ) // 
			throw new Migration_Exception('Migration step not found with version :version', array(":version" => $version));

		$file = basename($f[0]);
		$name = basename($f[0], EXT);

		// Filename validations
		if ( ! preg_match('/^\d+_(\w+)$/', $name, $match) )
			throw new Migration_Exception('Invalid filename :file', array(':file' => $file));

		$match[1] = strtolower($match[1]);

		include_once $f[0];
		$class = ucfirst($match[1]);

		if ( ! class_exists($class) )
			throw new Migration_Exception('Migration class :class does not exist', array( ':class' => $class));

		return new $class($this->config);
	}
	
	/**
	 * Retrieves all the timestamps of the migration files 
	 *
	 * @return   array
	 */
	public function get_migrations()
	{
		if( ! $this->migrations)
		{
			$migrations = glob($this->config['path'] . DIRECTORY_SEPARATOR . '*' . EXT);
			$ids = array();
			foreach ((array)$migrations as $file)
			{
				$name = basename($file, EXT);
				$matches = array();
				if ( preg_match('/^(\d+)_(\w+)$/', $name, $matches))
				{
					$ids[] = intval($matches[1]);
				}
			}
			$this->migrations = $ids;
		}
		return $this->migrations;
	}

	public function get_executed_migrations()
	{
		return $this->driver->get_executed_migrations();
	}

	public function get_unexecuted_migrations()
	{
		return array_diff($this->get_migrations(), $this->get_executed_migrations());
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
}