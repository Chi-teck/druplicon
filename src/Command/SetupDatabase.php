<?php

namespace Druplicon\Command;

use Druplicon\EventListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs database schemas.
 */
class SetupDatabase extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('setup-database')
      ->setDescription('Setup Druplicon database');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $container = $this->getApplication()->getContainer();

    // TODO: Add confirmation here.

    $container['db_connection']->exec("DROP TABLE factoids_vendor");
    $container['db_connection']->exec("CREATE TABLE factoids_vendor (subject varchar collate nocase, is_are varchar, statement TEXT)");

    $container['db_connection']->exec("DROP TABLE core_functions");
    $container['db_connection']->exec("CREATE TABLE core_functions (name varchar collate nocase, summary varchar, signature varchar)");

    $container['db_connection']->exec("DROP TABLE state");
    $container['db_connection']->exec("CREATE TABLE state (name varchar, value varchar)");
    $container['db_connection']->exec("CREATE UNIQUE INDEX idx_name ON state(name)");

    // TODO : Check for errors.
    $container['logger']->info('Done');

  }

}
