<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Mysql Driver
 *
 * @package    Despark/timestamped-migrations
 * @author		 Matías Montes
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Migration_Driver_Mysql extends Migration_Driver
{
	public $pdo;

	public function __construct($database)
	{
		if ($database instanceof PDO)
		{
			$this->pdo = $database;
		}
		else
		{
			$database = Kohana::$config->load('database.'.$database);

			if ($database['type'] !== 'PDO')
			{
				$database['connection']['dsn'] = strtolower($database['type'].':'.
				'host='.$database['connection']['hostname'].';'.
				'dbname='.$database['connection']['database']);
			}

			$this->pdo = new PDO(
				$database['connection']['dsn'],
				$database['connection']['username'],
				$database['connection']['password'],
				array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
			);
		}

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->versions = new Migration_Driver_Mysql_Versions($this->pdo);
	}

	public function clear_all()
	{
		$tables = array_diff(Arr::pluck($this->pdo->query('SHOW TABLES'), '0'), array(Migration_Driver_Mysql_Versions::SCHEMA_TABLE));

		if (count($tables))
		{
			$this->execute("DROP TABLE ".join(', ', $tables));
		}

		return $this;
	}

	public function column($name)
	{
		return new Migration_Driver_Mysql_Column($name, $this);
	}

	public function table($name)
	{
		return new Migration_Driver_Mysql_Table($name, $this);
	}

	public function execute($sql, $params = NULL)
	{
		try
		{
			$statement = $this->pdo->prepare($sql);
			if ($params)
			{
				$statement->execute((array) $params);
			}
			else
			{
				$statement->execute();
			}
			$this->_affected_rows = $statement->rowCount();
		}
		catch (PDOException $e)
		{
			throw new Migration_Exception(":sql\n Exception: :message", array(':sql' => $sql, ":message" => $e->getMessage()));
		}

		return $this;
	}

	public function query($sql, $params = NULL)
	{
		try
		{
			$query = $this->pdo->prepare($sql);
			$query->execute($params);
			return $query;
		}
		catch (PDOException $e)
		{
			throw new Migration_Exception(":sql\n Exception: :message", array(':sql' => $sql, ":message" => $e->getMessage()));
		}
	}

	public function quote($string)
	{
		return $this->pdo->quote($string);
	}

	public function create_table($table_name, $fields, $options = NULL)
	{
		$this->execute($this->table($table_name)->params($fields, $options)->sql());
		return $this;
	}

	public function drop_table($table_name)
	{
		$this->execute("DROP TABLE `$table_name`");
		return $this;
	}

	public function rename_table($old_name, $new_name)
	{
		$this->execute("RENAME TABLE `$old_name` TO `$new_name`");
		return $this;
	}

	public function add_column($table_name, $column_name, $params)
	{
		$column = $this->column($column_name)->params($params);

		if ($column->param('primary'))
		{
			$sql = "ALTER TABLE `$table_name` DROP PRIMARY KEY, ADD COLUMN ".$column->sql().', ADD PRIMARY KEY (`'.$column_name.'`)';
		}
		else
		{
			$sql = "ALTER TABLE `$table_name` ADD COLUMN ".$column->sql();
		}

		$this->execute($sql);
		return $this;
	}

	public function rename_column($table_name, $column_name, $new_column_name)
	{
		$column = $this->column($column_name)->load($table_name)->name($new_column_name);
		$this->execute("ALTER TABLE `$table_name` CHANGE `$column_name` ".$column->sql());
		return $this;
	}

	public function change_column($table_name, $column_name, $params)
	{
		$this->execute("ALTER TABLE `$table_name` MODIFY ".$this->column($column_name)->params($params)->sql());
		return $this;
	}

	public function change_table($table_name, $options)
	{
		$this->execute("ALTER TABLE `$table_name` ".join(' ', (array) $options));
		return $this;
	}

	public function remove_column($table_name, $column_name)
	{
		$this->execute("ALTER TABLE `$table_name` DROP COLUMN `$column_name`");

		return $this;
	}

	public function add_index($table_name, $index_name, $columns, $index_type = 'normal')
	{
		switch ($index_type)
		{
			case 'normal':   $type = 'INDEX'; break;
			case 'unique':   $type = 'UNIQUE INDEX'; break;
			case 'primary':  $type = 'PRIMARY KEY'; break;
			case 'fulltext': $type = 'FULLTEXT'; break;
			case 'spatial':  $type = 'SPATIAL'; break;

			default: throw new Migration_Exception("Incorrect index type :index_type, normal, unique primary, fulltext and spacial allowed", array(':index_type' => $index_type));
		}
		$columns = (array) $columns;

		foreach ($columns as $i => &$column)
		{
			$column = "`$column`";
		}

		$sql = join(' ', array(
			"ALTER TABLE `$table_name` ADD $type `$index_name`",
			'('.join(', ', $columns).')'
		));

		$this->execute($sql);
		return $this;
	}

	public function remove_index($table_name, $index_name)
	{
		$this->execute("ALTER TABLE `$table_name` DROP INDEX `$index_name`");
		return $this;
	}
}
