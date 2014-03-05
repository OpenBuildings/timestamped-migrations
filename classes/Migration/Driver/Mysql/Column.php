<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Migration_Driver_Mysql_Column extends Migration_Driver_Column
{
	/**
	 * Valid types
	 * @var array
	 */
	static protected $types = array
	(
		'primary_key' => array('type' => 'INT', 'null' => FALSE, 'auto' => TRUE, 'primary' => TRUE),
		'string' => array('type' => 'VARCHAR', 'limit' => 255),
		'text' => array('type' => 'TEXT'),
		'integer' => array('type' => 'INT'),
		'float' => array('type' => 'FLOAT', 'limit' => 10, 'precision' => 2),
		'long' => array('type' => 'BIGINT'),
		'decimal' => array('type' => 'DECIMAL', 'limit' => 10, 'precision' => 2),
		'datetime' => array('type' => 'DATETIME'),
		'timestamp' => array('type' => 'TIMESTAMP'),
		'time' => array('type' => 'TIME'),
		'date' => array('type' => 'DATE'),
		'binary' => array('type' => 'BLOB', 'limit' => 255),
		'boolean' => array('type' => 'TINYINT', 'limit' => 1, 'null' => FALSE, 'default' => 0),
		'enum' => array('type' => 'ENUM', 'values' => array()),
	);

	static protected $native_types = array
	(
		'char' => 'string',
		'varchar' => 'string',
		'text' => 'text',
		'int' => 'integer',
		'integer' => 'integer',
		'tinyint' => 'boolean',
		'bigint' => 'integer',
		'float' => 'float',
		'decimal' => 'decimal',
		'datetime' => 'datetime',
		'timestamp' => 'timestamp',
		'time' => 'time',
		'date' => 'date',
		'blob' => 'binary',
		'enum' => 'enum',
	);

	public function column_params_for($column)
	{
		return Arr::get(self::$types, $column, array());
	}

	public function load($table_name)
	{
		if (is_string($table_name))
		{
			try
			{
				$result = $this->driver->pdo->query("SHOW COLUMNS FROM `$table_name` LIKE '{$this->name}'");
			}
			catch (PDOException $e)
			{
				$result = NULL;
			}

			if ( ! $result OR $result->rowCount() !== 1)
			{
				throw new Migration_Exception("Column :column was not found in table :table", array(':column' => $this->name, ':table' => $this->name));
			}

			$result = $result->fetchObject();
		}
		else
		{
			$result = $table_name;
		}

		if (preg_match('/([^\(]+)(\((\d+)\))?( UNSIGNED)?/', $result->Type, $type))
		{
			$limit = Arr::get($type, 3);
			$unsigned = isset($type[4]) ? TRUE : NULL;
			$type = $type[1];
			$values = NULL;
		}

		if (preg_match('/enum\(([^\)]+)\)/', $result->Type, $enum_type))
		{
			$type = 'ENUM';
			$limit = NULL;
			$unsigned = NULL;
			$values = explode(',', $enum_type[1]);
			foreach ($values as & $value)
			{
				$value = trim($value, "'");
			}
		}

		$this->params(array(
			'type' => $type,
			'limit' => $limit,
			'unsigned' => $unsigned,
			'values' => $values,
			'null' => $result->Null == 'NO' ? TRUE : FALSE,
			'default' => $result->Default ? $result->Default : NULL,
			'auto' => $result->Extra == 'auto_increment',
			'primary' => $result->Key == 'PRI',
		));

		return $this;
	}

	public function type()
	{
		return Arr::get(self::native_types, strtolower($this->type));
	}

	public function sql()
	{
		extract(Arr::extract($this->params, Migration_Driver_Column::$available_params));

		return join(' ', array_filter(array(
			"`{$this->name}`",
			$type,
			$limit ? ($precision ? ( "({$limit}, {$precision})" ) : "({$limit})") : NULL,
			$values ? ('('.join(', ', array_map(array($this->driver->pdo, 'quote'), $values)).')') : NULL,
			$unsigned ? ("UNSIGNED") : NULL,
			($default OR $default === 0 OR $default === '0') ? ("DEFAULT ".$this->driver->pdo->quote($default)) : NULL,
			$null !== NULL ? ($null ? "NULL" : "NOT NULL") : NULL,
			$auto ? ("AUTO_INCREMENT") : NULL,
			$comment ? ("COMMENT '{$comment}'") : NULL,
			$after ? ("AFTER `{$after}`") : NULL,
			$first ? ("FIRST") : NULL,
		)));
	}
}
