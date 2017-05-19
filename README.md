Timestamped Migrations
======================

[![Build Status](https://travis-ci.org/OpenBuildings/timestamped-migrations.png?branch=master)](https://travis-ci.org/OpenBuildings/timestamped-migrations)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/OpenBuildings/timestamped-migrations/badges/quality-score.png?s=44bba08bc99dd687c7e0780c5b3f76bba5ae70f1)](https://scrutinizer-ci.com/g/OpenBuildings/timestamped-migrations/)
[![Code Coverage](https://scrutinizer-ci.com/g/OpenBuildings/timestamped-migrations/badges/coverage.png?s=a4f5002841b99c8fd4bbdbb078235d87fd4b3124)](https://scrutinizer-ci.com/g/OpenBuildings/timestamped-migrations/)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/timestamped-migrations/v/stable.png)](https://packagist.org/packages/openbuildings/timestamped-migrations)

Migrations are a convenient way for you to alter your database in a structured and organized manner. You could edit fragments of SQL by hand but you would then be responsible for telling other developers that they need to go and run them. You'd also have to keep track of which changes need to be run against the production machines next time you deploy.

Migrations module tracks which migrations have already been run so all you have to do is update your source and run ./minion db:migrate. Migrations module will work out which migrations should be run.

Migrations also allow you to describe these transformations using PHP. The great thing about this is that it is database independent: you don't need to worry about the precise syntax of CREATE TABLE any more than you worry about variations on SELECT * (you can drop down to raw SQL for database specific features). For example you could use SQLite3 in development, but MySQL in production.

Dependencies
------------

This module utilizes the built-in  [Kohana-minion](https://github.com/kohana/minion) for it's command line interface. The system is fairly decoupled from it though so you can easily implement this with other cli tools if you use something different.

Options
-------

* log - this is the logging function to be used, to integrate into whatever controller/backend you are using
* path - the path to where the migrations will be stored, defaults to APPPATH/migrations
* type - the driver for the backend for which migrations have been already executed as well as the migrations themselves, defaults to mysql

Migration command line tools
------------------

This module provides a set of kohana-minion tasks to work with migrations giving you the ability to easily create, run and rollback them. All of them have extensive documentation which you can easily read with kohana-minion's built in commands, e. g.

	./minion db:migrate --help

Migrate all new migrations
------------------

The most common migration related task you use will probably be `db:migrate`. In its most basic form it just runs the up method for all new migration versions, that have not been executed yet. If there are no such migrations it exits.

    ./minion db:migrate
    
If you specify a target version, Active Record will run the required migrations (up or down) until it has reached the specified version. The version is the numerical prefix on the migration’s filename. For example to migrate to version 1322837510 run

	./minion db:migrate --version=1322837510

If this is greater than the current version (i.e. it is migrating upwards) this will run the up method on all migrations up to and including 1322837510, if migrating downwards this will run the down method on all the migrations down to, but not including, 1322837510.

Migrate a migration
------------
The `db:migrate:up` will migrate the first migration version that has not been migrated yet.

    ./minion db:migrate:up


Rolling back a migration
------------

The `db:migration:down` is used to rollback the last migrated migration, for example if you made a mistake in it and wish to correct it. Rather than tracking down the version number associated with the previous migration you can run the step below without specifying any version number. 

	./minion db:migrate:down

Redo a migration
------------
The `db:migrate:redo` task is a shortcut for doing a rollback and then migrating back up again. It will execute *db:migrate:down* and after that *db:migrate:up* again. 

	./minion db:migrate:redo

Migrate specific version
------------
If you need to run a specific migration up or down the `db:migrate:up` and `db:migrate:down` commands will do that. Just specify the appropriate version and the corresponding migration will have its up or down method invoked, for example

	./minion db:migrate:up --version=1321025460

will run the up method from the 1321025460 migration. These commands check whether the migration has already run, so for example db:migrate:up --version=1321025460 will do nothing if Migrations module believes that --version=1321025460 has already been run.

Migrate last few steps
------------
The commands `db:migrate:up`, `db:migrate:down` and `db:migrate:redo` by default are executed for just one migration. However they support `--step` option. It allows you migrate several migration steps at once. For example:

    	./minion db:migrate:down --step=3

 will roll back last 3 migrations that has been migrated.

Neither of these commands do anything you could not do with *db:migrate*, they are simply more convenient since you do not need to explicitly specify the version to migrate to.

Dry Run
-------

You can add a ``--dry-run`` option and it will only show you the migrations that will be executed, without accually executing anything.

	./minion db:migrate:up --step=3 --dry-run


Generating a migration
----------------------

You can generate a migration with the `db:generate` command which will create a file inside the path you've specified in the config of the module. It will prefix the filename with the timestamp and return the created filename

	./minion db:generate --name=my_migration

 will display `1495194020_my_migration_user.php Migration File Generated`. It creates scaffold with `up()` and `down()` methods. The migration file will looklike this:

```php
class My_Migration extends Migration
{
    public function up()
    {

    }

    public function down()
    {

    }
}
```

---------
There are several patterns in the filename that will be recognized and converted to actual helper methods in the up/down methods of the migration.

* create\_table\_{table}
* drop\_table\_{table}
* add\_{columns}\_to\_{table} where {columns} is a list of column names, delimited by ``_and_`` so you can write ``add_name_and_title_to_users`` - which will add both columns.
* remove\_{columns}\_from\_{table}
* change\_{columns}\_in\_{table}
* rename\_table\_{old\_name}\_to\_{new\_name}
* rename\_{old\_column\_name}\_to\_{new\_column\_name}\_in\_{table\_name}


To create a users table you could use following command

	    ./minion db:generate --name=create_table_users

```php
<?php defined('SYSPATH') OR die('No direct script access.');
class Create_Users extends Migration
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

You can use more than one pattern if you separate them with ``_also_``. For example
to create a users table, columns login and name at once use command

    ./minion db:generate --name=create_table_users_also_add_login_and_name

which will create a migration:

```php
class Create_Table_Users_Also_Add_Login_And_Name_To_Users extends Migration
{
    public function up()
    {
        $this->create_table('users', array( ));
        $this->add_column('users', 'login', 'string');
        $this->add_column('users', 'name', 'string');
    }

    public function down()
    {
        $this->drop_table('users');
        $this->remove_column('users', 'login');
        $this->remove_column('users', 'name');
    }
}
```

Column types are guessed according to suffix - \_id and \_count columns will be integers, \_at -> datetime, \_on -> date, is\_ -> boolean and "description" and "text" will be assumed to be text. The default type of a column is string.

If none of the patterns match, it will just create a migration with empty up and down methods.


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

``` php
// Create a table with innoDB, UTF-8 as default charset, and guid for primary key.
$this->create_table( "users", array(
	'title' => 'string',
	'guid' => 'primary_key',
	'is_admin' => array('boolean', 'null' => FALSE, 'default' => 0)
), array (
	'id' => FALSE,
	'options' => 'ENGINE=innoDB CHARSET=utf8'
));
```

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

Helper Tasks
------------

There are some more built in tasks to help you manage your database

```
minion db:structure:dump
```

Copy the structure of one database to another.
Will ask for confirmation before proceeding.

options:

* __from__ database id from config/database.php file to load structure from, 'default' by default
* __to__ database id from config/database.php file to dump structure to
* __force__ se this flag to skip confirmation

```
minion db:structure:copy
```

Dump the current database structure to a file (migrations/schema.sql by default)
options:

- __database__ the id of the database to dump from the config/database.php file, 'default' by default, configurable from config
- __file__ file override the schema.sql file location to dump to another file

```
minion db:structure:load
```

Load the structure in migrations/schema.sql file to the database, clearing the database in the process.
Will ask for confirmation before proceeding.
options:

- __database__ the id of the database to load to from the config/database.php file, 'default' by default, can be overwritten from config
- __force__ use this flag to skip confirmation
- __file__ override the schema.sql file to load another sql file

```
minion db:test:load
```

Load the latest structure to the test database.
Will also load all the sql files from test/test_data/structure/test-schema-{type}.sql where {type} is based on the test database type.

```
minion db:recreate
```

Drop all the tables and rerun all the migrations.
Will ask for confirmation before proceeding.

options:

- force: use this flag to skip confirmation

```
minion db:version
```

Get the current migration version

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

	./minion db:generate add_facebook_id --template=<path to template file>

And you're done. If you use the --template option, all the name patters are of course ignored.

License
-------

timestamped migrations are Copyright © 2012-2017 OpenBuildings Inc. developed by Ivan Kerin. It is free software, and may be redistributed under the terms specified in the LICENSE file.
