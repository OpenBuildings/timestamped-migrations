<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Dump the current database structure to a file (migrations/schema.sql by default)
 *
 * options:
 *
 *  - database: the id of the database to dump from the config/database.php file, 'default' by default, configurable from config
 *  - file: file override the schema.sql file location to dump to another file
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_DB_Structure_Dump extends Minion_Database {

	protected $_options = array(
		'database' => 'default',
		'file' => FALSE,
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
		
		$command = strtr("mysqldump -u:username :password -h :hostname --skip-comments --add-drop-database --add-drop-table --no-data :database | sed 's/AUTO_INCREMENT=[0-9]*\b//' > :file ", array(
			':username' => $db['username'],
			':password' => $db['password'] ? '-p'.$db['password'] : '',
			':database' => $db['database'],
			':hostname' => $db['hostname'],
			':file'     => $file
		));

		Minion_CLI::write(Minion_CLI::color('Saving structure of database "'.$db['database'].'" to '.Debug::path($file), 'green'));

		system($command);
	}

}
