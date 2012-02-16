# Blog Post

[http://ivank.github.com/blog/2011/11/timestamped-migrations/](http://ivank.github.com/blog/2011/11/timestamped-migrations/)

Migrations are a convenient way for you to alter your database in a structured and organized manner. You could edit fragments of SQL by hand but you would then be responsible for telling other developers that they need to go and run them. You'd also have to keep track of which changes need to be run against the production machines next time you deploy.

Migrations module tracks which migrations have already been run so all you have to do is update your source and run ./kohana db:migrate. Migrations module will work out which migrations should be run. 

Migrations also allow you to describe these transformations using PHP. The great thing about this is that it is database independent: you don't need to worry about the precise syntax of CREATE TABLE any more than you worry about variations on SELECT * (you can drop down to raw SQL for database specific features). For example you could use SQLite3 in development, but MySQL in production.

Dependencies
------------

This module utilizes [kohana-cli](https://github.com/ivank/kohana-cli) for it's command line interface. You use your own, you can implement it with this module.

Options
-------

* log - this is the logging function to be used, to integrate into whatever controller/backend you are using
* path - the path to where the migrations will be stored, defaults to APPPATH/migrations
* type - the driver for the backend for which migrations have been already executed as well as the migrations themselves, defaults to mysql

Command line tools
==================

Kohana cli provides a set of kohana-cli commands to work with migrations which boils down to running certain sets of migrations. The very first migration related command you use will probably be db:migrate. In its most basic form it just runs the up method for all the migrations that have not yet been run. If there are no such migrations it exits.

If you specify a target version, Active Record will run the required migrations (up or down) until it has reached the specified version. The version is the numerical prefix on the migration’s filename. For example to migrate to version 1322837510 run

	./kohana db:migrate --version=1322837510

If this is greater than the current version (i.e. it is migrating upwards) this will run the up method on all migrations up to and including 2008090612, if migrating downwards this will run the down method on all the migrations down to, but not including, 2008090612.

Rolling Back
------------

A common task is to rollback the last migration, for example if you made a mistake in it and wish to correct it. Rather than tracking down the version number associated with the previous migration you can run

	./kohana db:rollback

This will run the down method from the latest migration. If you need to undo several migrations you can provide a --step option:

	./kohana db:rollback --step=3
will run the down method from the last 3 migrations.

The db:migrate:redo task is a shortcut for doing a rollback and then migrating back up again. As with the db:rollback task you can use the --step option if you need to go more than one version back, for example

	./kohana db:migrate:redo --step=3

Neither of these commands do anything you could not do with db:migrate, they are simply more convenient since you do not need to explicitly specify the version to migrate to.

Being Specific
--------------

If you need to run a specific migration up or down the db:migrate:up and db:migrate:down commands will do that. Just specify the appropriate version and the corresponding migration will have its up or down method invoked, for example

	./kohana db:migrate:up --version=1321025460

will run the up method from the 2008090612 migration. These commands check whether the migration has already run, so for example db:migrate:up --version=2008090612 will do nothing if Migrations module believes that --version=1321025460 has already been run.

Dry Run
-------

You can add a ``--dry-run`` option and it will only show you the migrations that will be executed, without accually executing anything.

	./kohana db:migrate:up --step=3 --dry-run


Generating a migration
----------------------

You can generate a migration with the db:generate command which will create a file inside the path you've specified in the config of the module. It will prefix the filename with the timestamp and return the created filename

	./kohana db:generate create_users

will create a migration that looks like this

``` php
<?php defined('SYSPATH') OR die('No direct script access.');
class Create_User extends Migration
{
	public function up()
	{
		$this->create_table('users', array( ));
	}
	
	public function down()
	{
		$this->drop_table('users');
	}
}
?>
```

There are several patterns in the filename that will be recognized and converted to actual helper methods in the up/down methods of the migration.

* create\_table\_{table}
* drop\_table\_{table}
* add\_{columns}\_to\_{table} where {columns} is a list of column names, delimited by __\_and\___ so you can write ``add\_name\_and\_title\_to\_users`` - which will add both columns.
* remove\_{columns}\_from\_{table}
* change\_{columns}\_in\_{table}
* rename\_table\_{old\_name}\_to\_{new\_name}
* rename\_{old\_column\_name}\_to\_{new\_column\_name}\_in\_{table\_name}

You can use more than one pattern if you separate them with __\_also\___

	php kohana db:generate add_name_and_title_to_users_also_create_profiles

If none of the patterns match, it will just create a migration with empty up and down methods.

Additionally - column types are guessed according to suffix - _id columns will be integers, _at -> datetime, _on -> date, and "description" and "text" will be assumed to be text.

Helper Methods
--------------

You have a bunch of helper methods that will simplify your life writing migrations.

* create_table
* rename_table
* drop_table
* add_column
* remove_column
* change_column
* rename_column
* add_index
* remove_index	
* change_table

If you need to perform tasks specific to your database (for example create a foreign key constraint) then the execute method allows you to execute arbitrary SQL. A migration is just a regular PHP class so you're not limited to these functions. For example after adding a column you could write code to set the value of that column for existing records (if necessary using your models).

Here's a quick example of all of this at work

``` php
<?php defined('SYSPATH') OR die('No direct script access.');
class Create_User extends Migration
{
	public function up()
	{          
		$this->create_table( "users", array(
			'title' => 'string',
			'is_admin' => array('boolean', 'null' => FALSE, 'default' => 0)
		));

		$this->add_column("users", "latlon", array("type" => "POINT"));
		$this->add_column("users", "email", array("string", "null" => FALSE));

		$this->add_index("users", "latlon", "latlon", "spatial");
		$this->add_index("users", "search", array("title", "email"), "fulltext");

		$this->execute(
			"INSERT INTO `users` (`id`, `title`, `is_admin`) VALUES(1, 'user1', 1);
			INSERT INTO `users` (`id`, `title`, `is_admin`) VALUES(2, 'user2', 0);"
		);		
	}
	
	public function down()
	{
		$this->remove_index("users", "latlon");
		$this->remove_index("users", "search");
		$this->remove_column("users", "email");
		$this->remove_column("users", "latlon");
		$this->drop_table("users");
	}
}
?>
```
Available types for columns are:

* primary_key
* string
* text
* integer
* float
* decimal
* datetime
* timestamp
* time
* date
* binary
* boolean

and each column can have options like these

* after - after which column to place this one
* null - TRUE or FALSE
* default
* auto - TRUE or FALSE - adds autoincrement
* unsigned - TRUE or FALSE
* limit - limit
* precision - for ``decimal`` and ``float``
* primary - TRUE or FALSE

You can use custom database types, to do this skip the column type and define it directly:
	
	$this->add_column('table', 'column', array('type' => 'BIGINT', 'null' => FALSE, 'unsigned' => TRUE));
	$this->add_column('table', 'column', array('type' => 'GEOMETRY', 'null' => FALSE));

``function create_table($table, $fields, $options)``

Options are:

	* __id__ - bool - Set this to FALSE to prevent automatic adding of the id primary_key. Default is TRUE.
	* __options__ added AS IS to the end of the table definition.

	//Create a table with innoDB, UTF-8 as default charset, and guid for primary key.
	$this->create_table( "users", array(
		'title' => 'string',
		'guid' => 'primary_key',
		'is_admin' => array('boolean', 'null' => FALSE, 'default' => 0)
	), array (
		'id' => FALSE,
		'options' => array('ENGINE=innoDB', 'CHARSET=utf8')
	));

``function add_index($table, $index_name, $columns, $type = 'normal')``

You can pass multiple columns for the indexs (as an array of column names), and available types are

* normal
* unique
* primary
* fulltext
* spatial

PRIMARY KEYS
------------

Primary keys have special handlings. When you create a table, it will create a composite primary key with all your fields with 'primary' => TRUE, and when you add a column with primary_key, it will drop the current primary key and assign the new column as primary key

## Footnotes 
A lot of this text has been taken from http://guides.rubyonrails.org/migrations.html as I've tried to mimic their functionality and interface as much as I could.

Templates
---------

You can create a template to be used as a basis for generating a migration. This is useful if you want to bundle migrations with other modules. A migration template is just a text file with a "--- DOWN ---" line in it. everything above it is placed in the "up" method, everything below - in the "down"

Example:

	$this->add_column('users', 'facebook_uid', 'string');
	$this->add_index('users', 'facebook_uid_index', 'facebook_uid');

	--- DOWN ---

	$this->remove_column('users', 'facebook_uid');
	$this->remove_index('users', 'facebook_uid_index');

And then:

	php kohana db:generate add_facebook_id --template=<path to template file>

And you're done. If you use the --template option, all the name patters are of course ignored.


	