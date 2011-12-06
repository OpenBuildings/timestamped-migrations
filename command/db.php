<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Command line interface for Migrations.
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Command_DB extends Command
{
	protected $migrations = null;

	public function __construct()
	{
		$this->migrations = new Migrations(array('log' => 'Command::log'));
	}

	const VERSION_BRIEF = "Display last executed timestamp";
	public function version()
	{
		$migrations = $this->migrations->get_executed_migrations();
		Command::log("Current Version: ".Command::colored(end($migrations), Command::OK));
	}

	const MIGRATE_BRIEF = "Execute migrations";
	public function migrate(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate');
	}

	protected function _migrate($executed, $unexecuted, $all, $arguments, $steps, &$up, &$down)
	{
		if(isset($arguments["version"]))
		{
			foreach($all as $migration)
			{
			  if( ! in_array($migration, $executed) AND $migration <= $arguments["version"])
			  {
			    $up[] = $migration;
			  }
			  if( in_array( $migration, $executed) AND $migration > $arguments["version"]) 
			  {
			    $down[] = $migration;
			  }
			}
		}
		else
		{
			$up = $steps ? array_slice($unexecuted, 0, $steps) : $unexecuted;
		}
	}

	const MIGRATE_UP_BRIEF = "Execute next up migration";
	public function migrate_up(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate_up');
	}

	protected function _migrate_up($executed, $unexecuted, $all, $arguments, $steps, &$up, &$down)
	{
		if(isset($arguments["version"]))
		{
			if(in_array($arguments["version"], $unexecuted))
			{
				$up[] = $arguments["version"];	
			}
		}
		else
		{
			$up = array_slice($unexecuted, 0, $steps ? $steps : 1);
		}
	}	

	const MIGRATE_DOWN_BRIEF = "Execute next down migration";
	const MIGRATE_DOWN_DESC = "This will run the down method from the latest migration. If you need to undo several migrations you can provide a --step option
You can also give a --version and it will roll back all the migrations down to the specified";

	public function migrate_down(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate_down');
	}

	protected function _migrate_down($executed, $unexecuted, $all, $options, $steps, &$up, &$down)
	{
		if(isset($options["version"]))
		{
			if(in_array($options["version"], $executed))
			{
				$down[] = $options["version"];	
			}
		}
		else
		{
			$down = array_slice($executed, 0, $steps ? $steps : 1);
		}
	}	

	const MIGRATE_REDO_BRIEF = "Execute next down and up migration";
	const MIGRATE_REDO_DESC = "The db:migrate:redo command is a shortcut for doing a rollback and then migrating back up again. 
As with the db:rollback task you can use the --step option if you need to go more than one version back
You can also give a --version and it will roll back and up all the migrations to the specified";
	public function migrate_redo(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate_redo');
	}

	protected function _migrate_redo($executed, $unexecuted, $all, $options, $steps, &$up, &$down)
	{
		if(isset($options["version"]))
		{
			if(in_array($options["version"], $executed))
			{
				$down[] = $options["version"];	
			}
		}
		else
		{
			$down = array_slice($executed, 0, $steps ? $steps : 1);
		}

		if(isset($arguments["version"]))
		{
			$up[] = $arguments["version"];
		}
		else
		{
			$up = array_reverse($down);
		}
	}	

	const ROLLBACK_BRIEF = "Execute next down migration";
	const ROLLBACK_DESC = "This will run the down method from the latest migration. If you need to undo several migrations you can provide a --step option
You can also give a --version and it will roll back all the migrations down to the specified";	
	public function rollback(Command_Options $options)
	{
		$this->migrate_down($options);
	}

	protected function _execute_migration(Command_Options $options, $func)
	{
		$dry_run = $options->has('dry-run');

		$executed = array_reverse($this->migrations->get_executed_migrations());
		$unexecuted = $this->migrations->get_unexecuted_migrations();
		$all = $this->migrations->get_migrations();

		$steps = isset($options['step']) ? (int) $options['step'] : null;

		$up = array();
		$down = array();

		$this->$func($executed, $unexecuted, $all, $options, $steps, $up, $down);

		if( ! count($down) AND ! count($up))
		{
			$this->log("Nothing to do", Command::OK);
		}

		foreach ($down as $version) 
		{
      $migration = $this->migrations->load_migration($version);
	
			$this->log(Command::colored($version.' '.get_class($migration).' : migrating down', Command::WARNING). ($dry_run ? Command::colored(" -- Dry Run", 'purple') : ''));
			$start = microtime(TRUE);

			$migration->dry_run($dry_run)->down();

			if( ! $dry_run)
			{
				$this->migrations->set_unexecuted($version);
			}

			$end = microtime(TRUE);
			$this->log($version.' '.get_class($migration).' : migrated ('.number_format($end-$start, 4).'s)', Command::WARNING);
		}
		
		foreach ($up as $version) 
		{
			$migration = $this->migrations->load_migration($version);

			$this->log(Command::colored($version.' '.get_class($migration).' : migrating up', Command::OK). ($dry_run ? Command::colored(" -- Dry Run", 'purple') : ''));
			$start = microtime(TRUE);

			$migration->dry_run($dry_run)->up();
			
			if( ! $dry_run)
			{
				$this->migrations->set_executed($version);
			}			

			$end = microtime(TRUE);
			$this->log($version.' '.get_class($migration).' : migrated ('.number_format($end-$start, 4).'s)', Command::OK);
		}
	}

	const RECREATE_BRIEF = "Drop all tables and re-run all migrations";
	const RECREATE_DESC = "This will drop all existing tables, removing all the data, and then recreate the database by runing up all the migrations. Will prompt before preceding, or if you pass --force will preceed without prompting.";	

	public function recreate(Command_Options $options)
	{
		if( ! $options->has('force') )
		{
			$this->log("This will destroy all data in the current database. Are you sure? [yes/NO]", Command::WARNING);
			$input = strtolower(trim(fgets(STDIN)));
		}
		else
		{
			$input = 'yes';
		}

		if($input == 'yes')
		{
			$dry_run = $options->has('dry-run');

			$this->log(Command::colored('dropping tables', Command::OK). ($dry_run ? Command::colored(" -- Dry Run", 'purple') : ''));

			if( ! $dry_run)
			{
				$this->migrations->delete_tables();
			}

			$this->migrate($options);
		}
		else
		{
			$this->log("Nothing done", Command::WARNING);
		}		
	}

	const COPY_STRUCTURE_BRIEF = "Copy structure from default DB to another";
	const COPY_STRUCTURE_DESC = "Dump the current database structure to a temporary file and them import it to the given databse. 
The first argument is the name of the database connection in you database config file. 
Removes all the current structure of the target database. 
It will prompt before preceeding.";

	public function copy_structure(Command_Options $options, $database)
	{
		$dbs = array();
		$dbs['from'] = Kohana::$config->load('database.default.connection');
		$dbs['to'] = Kohana::$config->load("database.$database.connection");

		if ( ! $dbs['to'])
			throw new Kohana_Exception("Database :database does not exist, available databases are :databases", array(":database" => $database, ":databases" => join(', ', array_keys((array) Kohana::$config->load("database")))));

		foreach($dbs as &$db)
		if( ! isset($db['database']) )
		{
			$matches = array();
			if( ! preg_match('/dbname=([^;]+);', $db['dsn'], $matches));
				throw new Kohana_Exception("Error connecting to database, database missing");
			$db['database'] = $matches[1];
		}
		$file = tempnam(sys_get_temp_dir(), "Database_");
		
		$this->log("This will destroy database ".$dbs['to']['database']."Are you sure? [yes/NO]", Command::WARNING);

		$input = strtolower(trim(fgets(STDIN))); 

		if($input == 'yes')
		{
			$this->log("Dumping current structure to $file", Command::OK);
			system(strtr("mysqldump -u:username -p:password --add-drop-database --add-drop-table --no-data :database > :tmp ", array(
				':username' => $dbs['from']['username'],
				':password' => $dbs['from']['password'],
				':database' => $dbs['from']['database'],
				':tmp'      => $file
			)));

			$this->log("Importing structure from $file to ".$dbs['to']['database'], Command::OK);
			system(strtr("mysql -u:username -p:password :database < :tmp ", array(
				':username' => $dbs['to']['username'],
				':password' => $dbs['to']['password'],
				':database' => $dbs['to']['database'],
				':tmp'      => $file
			)));

			$this->log("removing $file", Command::OK);
			unlink($file);
		}
		else
		{
			$this->log("Nothing done", Command::WARNING);
		}
	}

	const GENERATE_BRIEF = "Generate a migration file";
	public function generate(Command_Options $options, $name = null)
	{
		if( ! $name)
			throw new Kohana_Exception("Please set a name for the migration ( db:generate {name} )");

		$template = $options->has('template') ? $options['template'] : null;

		$migration = $this->migrations->generate_new_migration_file($name, $template);

		self::log(self::colored($migration, Command::OK).self::colored(' Migration File Generated', Command::WARNING));
	}

}
