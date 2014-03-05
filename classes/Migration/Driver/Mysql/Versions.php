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
class Migration_Driver_Mysql_Versions extends Migration_Driver_Versions
{
	public $pdo;
	const SCHEMA_TABLE = 'schema_version';

	public function __construct($pdo)
	{
		$this->pdo = $pdo;
	}

	public function init()
	{
		$this->pdo->exec('CREATE TABLE IF NOT EXISTS `'.self::SCHEMA_TABLE.'` (version INT)');

		return $this;
	}

	public function get()
	{
		$migrations = array();
		foreach($this->pdo->query('SELECT version FROM `'.self::SCHEMA_TABLE.'` ORDER BY version ASC') as $result)
		{
			$migrations[] = intval($result['version']);
		}

		return $migrations;
	}

	public function set($version)
	{
		$this->pdo->prepare('INSERT INTO `'.self::SCHEMA_TABLE.'` SET version = ?')->execute(array($version));
		return $this;
	}

	public function clear($version)
	{
		$this->pdo->prepare('DELETE FROM `'.self::SCHEMA_TABLE.'` WHERE version = ?')->execute(array($version));
		return $this;
	}

	public function clear_all()
	{
		$this->pdo->exec('DELETE FROM `'.self::SCHEMA_TABLE.'`');
		return $this;
	}
}
