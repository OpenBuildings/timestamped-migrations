<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Load the structure in migrations/schema.sql file to the database, clearing the database in the process.
 * Will ask for confirmation before proceeding.
 *
 * options:
 *  - database: the id of the database to load to from the config/database.php file, 'default' by default, can be overwritten from config
 *  - force: use this flag to skip confirmation
 *  - file: override the schema.sql file to load another sql file
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Structure_Load extends Minion_Database {

	protected $_options = array(
		'database' => 'default',
		'force' => FALSE,
		'file' => FALSE
	);

    protected function __construct()
    {
        $this->_options['database'] = Kohana::$config->load("migrations.database");
        parent::__construct();
    }

	protected function _execute(array $options)
	{
		$db = $this->db_params($options['database']);
		$file = $options['file'] ? $options['file'] : Kohana::$config->load("migrations.path").DIRECTORY_SEPARATOR.'schema.sql';

		if ($options['force'] === NULL OR 'yes' === Minion_CLI::read("This will destroy database ".$db['database']." Are you sure? [yes/NO]"))
		{
			$command = strtr("mysql -u:username :password -h :hostname :database < :file ", array(
				':username' => $db['username'],
				':password' => $db['password'] ? '-p'.$db['password'] : '',
				':database' => $db['database'],
                ':hostname' => $db['hostname'],
				':file'     => $file
			));

			Minion_CLI::write(Minion_CLI::color('Loading data from '.Debug::path($file).' to '.$db['database'], 'green'));
			system($command);
		}
		else
		{
			Minion_CLI::write(Minion_CLI::color('Nothing done', 'brown'));
		}
	}
}
