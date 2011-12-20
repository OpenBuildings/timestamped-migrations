<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Mysql Driver 
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author		 Matías Montes
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Migration_Driver_Mysql extends Migration_Driver
{
	protected $pdo;
	
	public function __construct($database)
	{
		if($database instanceof PDO)
		{
			$this->pdo = $database;
		}
		else
		{
			$database = Kohana::$config->load('database.'.$database);

			if($database['type'] !== 'pdo')
			{
				$database['connection']['dsn'] = $database['type'].':'.
				'host='.$database['connection']['hostname'].';'.
				'dbname='.$database['connection']['database'];
			}

			$this->pdo = new PDO(
				$database['connection']['dsn'], 
				$database['connection']['username'], 
				$database['connection']['password']
			);
		}

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function generate_schema()
	{
		$this->execute("CREATE TABLE IF NOT EXISTS schema_version (version int)");

		return $this;
	}

	public function get_tables($value='')
	{
		return Arr::pluck($this->pdo->query('SHOW TABLES'), '0');
	}

	public function get_executed_migrations()
	{
		$migrations = array();
		foreach($this->pdo->query('SELECT version FROM schema_version ORDER BY version ASC') as $result)
		{
			$migrations[] = intval($result['version']);
		}

		return $migrations;
	}

	public function set_executed($version)
	{
		$this->execute("INSERT INTO schema_version SET version = ?", array($version));
		return $this;
	}

	public function set_unexecuted($version)
	{
		$this->execute('DELETE FROM schema_version WHERE version = ?', array($version));
		return $this;
	}

	public function execute($sql, $params = null)
	{
		if( $params )
		{
			$statement = $this->pdo->prepare($sql);

			$statement->execute((array) $params);
		}
		else
		{
			$result = $this->pdo->exec($sql);

		}

		return $this;
	}

	public function create_table($table_name, $fields, $options = TRUE)
	{
		if( is_array( $options ) )
		{
			$if_not_exists = Arr::get($options, 'if_not_exists');
			$primary_key = Arr::get($options, 'primary_key', TRUE);
			unset($options['if_not_exists'], $options['primary_key']);
		}
		else
		{
			$primary_key = $options;
			$if_not_exists = false;
		}

		$sql = "CREATE TABLE ".($if_not_exists?'IF NOT EXISTS ':'')." `$table_name` (";

		// add a default id column if we don't say not to
		if ($primary_key === TRUE)
		{
			$primary_key = 'id';
			$fields = array_merge(array('id' => array('integer', 'null' => FALSE)), $fields);
		}
		
		foreach ($fields as $field_name => $params)
		{
			$params = (array) $params;
			
			if ($primary_key === $field_name AND $params[0] == 'integer')
			{
				$params['auto'] = TRUE;
			}
			else
			{
				$params['auto'] = FALSE;
			}
			
			$sql .= $this->compile_column($field_name, $params);
			$sql .= ",";
		}

		$sql = rtrim($sql, ',');

		if ($primary_key)
		{
			$sql .= ' , PRIMARY KEY (';
			
			foreach ( (array) $primary_key as $pk ) {
				$sql .= " `$pk`,";
			}
			$sql = rtrim($sql, ',');
			$sql .= ')';
		}

		$sql .= ")";

		if ( is_array($options) )
		{
			foreach ($options as $name => $option) {
				$sql .= ' '.strtoupper($name).'='.$option;	
			}
		}

		$this->execute($sql);

		return $this;
	}

	public function drop_table($table_name, $if_exists = FALSE)
	{
		$this->execute("DROP TABLE ".($if_exists? 'IF EXISTS ' : '')."`$table_name`");
		return $this;
	}

	public function rename_table($old_name, $new_name)
	{
		$this->execute("RENAME TABLE `$old_name`  TO `$new_name` ;");
		return $this;
	}
	
	public function add_column($table_name, $column_name, $params)
	{
		$sql = "ALTER TABLE `$table_name` ADD COLUMN " . $this->compile_column($column_name, $params, TRUE);
		$this->execute($sql);
		return $this;
	}

	public function rename_column($table_name, $column_name, $new_column_name)
	{
		$params = $this->get_column($table_name, $column_name);
		$sql    = "ALTER TABLE `$table_name` CHANGE `$column_name` " . $this->compile_column($new_column_name, $params);
		$this->execute($sql);
		return $this;
	}
	
	public function change_column($table_name, $column_name, $params)
	{
		$sql = "ALTER TABLE `$table_name` MODIFY " . $this->compile_column($column_name, $params);
		$this->execute($sql);
		return $this;
	}

	public function change_table($table_name, $options)
	{
		$sql = "ALTER TABLE `$table_name` ";
		foreach( $options as $name => $option)
		{
			$sql .= ' '.strtoupper($name).'='.$option;
		}

		$this->execute($sql);
		return $this;
	}
	
	public function remove_column($table_name, $column_name)
	{
		$this->execute("ALTER TABLE `$table_name` DROP COLUMN `$column_name` ;");

		return $this;
	}
	
	public function add_index($table_name, $index_name, $columns, $index_type = 'normal')
	{
		switch ($index_type)
		{
			case 'normal':   $type = 'INDEX'; break;
			case 'unique':   $type = 'UNIQUE INDEX'; break;
			case 'primary':  $type = 'PRIMARY KEY'; break;
			case 'fulltext':  $type = 'FULLTEXT'; break;
			case 'spatial':  $type = 'SPATIAL'; break;
			
			default: throw new Migration_Exception("Incorrect index type :index_type, normal, unique primary, fulltext and spacial allowed", array(':index_type' => $index_type));
		}
		
		$sql = "ALTER TABLE `$table_name` ADD $type `$index_name` (";
		
		foreach ((array) $columns as $column)
		{
			$sql .= " `$column`,";
		}
		
		$sql  = rtrim($sql, ',');
		$sql .= ')';
		$this->execute($sql);
		return $this;
	}

	public function remove_index($table_name, $index_name)
	{
		$this->execute("ALTER TABLE `$table_name` DROP INDEX `$index_name`");
		return $this;
	}
	
	protected function compile_column($field_name, $params, $allow_order = FALSE)
	{
		if (empty($params))
			throw new Migration_Exception("Parameters must not be empty");
		
		$params = (array) $params;
		$null   = TRUE;
		$auto   = FALSE;
		$primary = FALSE;
		$unsigned = FALSE;
		
		foreach ($params as $key => $param)
		{
			$args = NULL;

			if (is_string($key))
			{
				switch ($key)
				{
					case 'after':   if ($allow_order) $order = "AFTER `$param`"; break;
					case 'null':    $null = (bool) $param; break;
					case 'default': $default = 'DEFAULT ' . ( $param == 'CURRENT_TIMESTAMP' ? $param : $this->pdo->quote($param)); break;
					case 'auto':    $auto = (bool) $param; break;
					case 'unsigned':$unsigned = (bool) $param; break;
					case 'primary': $primary = (bool) $param; break;
					default: throw new Migration_Exception("Invalid column parameter :param", array(':param' => $key));
				}
				continue; // next iteration
			}
			
			// Split into param and args
			if (is_string($param) AND preg_match('/^([^\[]++)\[(.+)\]$/', $param, $matches))
			{
				$param = $matches[1];
				$args  = $matches[2];

				// Replace escaped comma with comma
				$args = str_replace('\,', ',', $args);
			}
			
			if ($this->is_type($param))
			{
				$type = $this->native_type($param, $args);
				continue;
			}
			
			switch ($param)
			{
				case 'first':   if ($allow_order) $order = 'FIRST'; continue 2;
				default: break;
			}

			throw new Migration_Exception("Invalid column parameter :param", array(':param' => $param));
		}

		if (empty($type))
		{
			throw new Migration_Exception('Missing a required argument');
		}

		$sql  = " `$field_name` $type ";

		$sql .= $unsigned? ' UNSIGNED ' : '';
		isset($default)  and $sql .= " $default ";
		$sql .= $null    ? ' NULL ' : ' NOT NULL ';
		$sql .= $auto    ? ' AUTO_INCREMENT ' : '';
		$sql .= $primary ? ' PRIMARY KEY ' : '';
		isset($order)    and $sql .= " $order ";
		
		return $sql;
	}
	
	public function get_column($table_name, $column_name)
	{
		$result = $this->pdo->query("SHOW COLUMNS FROM `$table_name` LIKE '$column_name'");

		if ($result->rowCount() !== 1)
		{
			throw new Migration_Exception("Column :column was not found in table :table", array(':column' => $column_name, ':table' => $column_name));
		}

		$result = $result->fetchObject();
		$params = array($this->migration_type($result->Type));
		
		if ($result->Null == 'NO')
			$params['null'] = FALSE;

		if ($result->Default)
			$params['default'] = $result->Default;
			
		if ($result->Extra == 'auto_increment')
			$params['auto'] = TRUE;
		
		return $params;
	}

	public function get_table($table_name)
	{
		$table = new stdClass;
		$table->name =  $table_name;
		$table->fields = array();
		$table->options = array();

		$result = $this->pdo->query("SHOW TABLE STATUS LIKE '$table_name'");

		if ($result->rowCount() !== 1)
		{
			throw new Migration_Exception(":table does not exist", array(':table' => $column_name));
		}

		$table->options['engine'] = $result->fetchObject()->Engine;

		$fields_reuslt = $this->pdo->query("SHOW COLUMNS FROM `$table_name`");

		while($result = $fields_reuslt->fetchObject())
		{
			$params = array($this->migration_type($result->Type));
			
			if ($result->Null == 'NO')
				$params['null'] = FALSE;

			if ($result->Default)
				$params['default'] = $result->Default;
				
			if ($result->Extra == 'auto_increment')
				$params['auto'] = TRUE;

			if ($result->Key == 'PRI' AND $result->Field != 'id')
				$table->options['primary_key'] = $result->Field;

			if ($result->Key != 'PRI' OR $result->Field != 'id')
				$table->fields[$result->Field] = $params;
		}

		return $table;
	}	

	protected function default_limit($type)
	{
		switch ($type)
		{
			case 'decimal': return "10,0";
			case 'integer': return "normal";
			case 'string':  return "255";
			case 'binary':  return "1";
			case 'boolean': return "1";
			default: return "";
		}
	}
	
	protected function native_type($type, $limit)
	{
		if ( ! $this->is_type($type))
		{
			throw new Migration_Exception('Invalid database or migration type :type', array(':type' => $type));
		}
		
 		if (empty($limit))
 		{
 			$limit = $this->default_limit($type);
 		}
 		
 		switch ($type)
		{
			case 'integer':
				switch ($limit)
				{
					case 'big':    return 'bigint';
					case 'normal': return 'int';
					case 'small':  return 'smallint';
					default: break;
				}
				throw new Migration_Exception('Invalid database or migration type :type', array(':type' => $type));
				
			case 'string': return "varchar ($limit)";
			case 'boolean': return 'tinyint (1)';
			default: $limit and $limit = "($limit)"; return "$type $limit";
		}
	}
	
	public function migration_type($native)
	{
		if (preg_match('/^([^\(]++)\((.+)\)( unsigned)?$/', $native, $matches))
		{
			$native = $matches[1];
			$limit  = $matches[2];
		}
		switch ($native)
		{
			case 'bigint':   return 'integer[big]';
			case 'smallint': return 'integer[small]';
			case 'int':      return 'integer';
			case 'varchar':  return "string[$limit]";
			case 'char':  return "string[$limit]";
			case 'tinyint':  return 'boolean';
			case 'float':    return 'float';
			case 'double':    return 'double';
			case 'point':    return 'point';
			default: break;
		}
		
		if ( ! $this->is_type($native))
		{
			throw new  Migration_Exception('Invalid database or migration type :type', array(':type' => $native));
		}
		
		return $native . (isset($limit) ? "[$limit]" : '');
	}
}