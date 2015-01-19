<?php

namespace Druplicon\Command;

use Druplicon\EventListener;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Imports Drupal core functions.
 */
class ImportCoreFunctions extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('import-core-functions')
      ->setDescription('Import Drupal core functions from csv file');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $container = $this->getApplication()->getContainer();

    $csv = Reader::createFromPath($container['api_functions_file']);

    $container['db_connection']->beginTransaction();
    $container['db_connection']->exec("DELETE FROM core_functions");

    $sth = $container['db_connection']
      ->prepare('INSERT INTO core_functions(name, summary, signature) VALUES(?, ?, ?)');

    if (!$sth) {
      $output->writeLn('<error>' . $container['db_connection']->errorInfo()[2] . '</error>');
      return 1;
    }
    $nb_innsert = $csv->each(function ($row) use (&$sth) {
      return $sth->execute($row);
    });

    if ($container['db_connection']->commit()) {
      $output->writeln('<info>Imported functions: </info>' . $nb_innsert);
    }
    else {
      $output->writeLn("<error>Transaction failed</error>");
      return 1;
    }

  }

}
