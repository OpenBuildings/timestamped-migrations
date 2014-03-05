<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Get the current migration version
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Minion_Migration extends Minion_Task {

	protected $_options = array(
		'version' => NULL,
		'steps' => NULL,
		'dry-run' => FALSE
	);

	protected $_migrations;

	public function migrations()
	{
		if ( ! $this->_migrations)
		{
			$this->_migrations = new Migrations(array('log' => 'Minion_CLI::write'));
		}

		return $this->_migrations;
	}

	public function executed_migrations()
	{
		return array_reverse($this->migrations()->get_executed_migrations());
	}

	public function unexecuted_migrations()
	{
		return $this->migrations()->get_unexecuted_migrations();
	}

	public function all_migrations()
	{
		return $this->migrations()->get_migrations();
	}

	public function migrate(array $up, array $down, $dry_run = FALSE)
	{
		$this->migrations()->execute_all($up, $down, $dry_run);

		if ($up OR $down)
		{
			Minion_Task::factory(array('task' => 'db:structure:dump', 'file' => NULL))->execute();
		}
	}

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('version', 'exact_length', array(':value', 10))
			->rule('version', 'digit')
			->rule('steps', 'digit');
	}
}
