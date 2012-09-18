<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Load the structure in migrations/schema.sql file to the database, clearing the database in the process.
 * Will ask for confirmation before proceeding.
 *
 * @param string database the id of the database to load to from the config/database.php file, 'default' by default
 * @param boolean force use this flag to skip confirmation
 * @param string file override the schema.sql file to load another sql file
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_DB_Structure_Load extends Minion_Database {

	protected $_config = array(
		'database' => 'default',
		'force' => FALSE,
		'file' => FALSE
	);

	public function execute(array $options)
	{
		$db = $this->db_params($options['database']);
		$file = $options['file'] ? $options['file'] : Kohana::$config->load("migrations.path").DIRECTORY_SEPARATOR.'schema.sql';

		if ($options['force'] === NULL OR 'yes' === Minion_CLI::read("This will destroy database ".$db['database']." Are you sure? [yes/NO]"))
		{
			$command = strtr("mysql -u:username -p:password :database < :file ", array(
				':username' => $db['username'],
				':password' => $db['password'],
				':database' => $db['database'],
				':file'     => $file
			));

			Minion_CLI::write('Loading data from '.Debug::path($file).' to '.$db['database'], 'green');
			system($command);
		}
		else
		{
			Minion_CLI::write('Nothing done', 'brown');
		}
	}

}
