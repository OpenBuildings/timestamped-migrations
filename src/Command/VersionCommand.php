<?php

namespace Clippings\Migrations\Command;

use Migrations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:version')
            ->setDescription('Get the current migration version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrations = new Migrations();

        $executed_migrations = $migrations->get_executed_migrations();

        $output->writeln(end($executed_migrations));
    }
}
