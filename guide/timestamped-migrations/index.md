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

It creates scaffold with `up()` and `down()` methods. 

``` php
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

# Footnotes
A lot of this text has been taken from http://guides.rubyonrails.org/migrations.html as I've tried to mimic their functionality and interface as much as I could.

