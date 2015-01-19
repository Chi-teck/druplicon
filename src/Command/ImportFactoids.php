<?php

namespace Druplicon\Command;

use Druplicon\EventListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Imports factoids from druplicon.info site.
 */
class ImportFactoids extends Command {

  /**
   * Base url.
   */
  const BASE_URL = 'http://druplicon.info/bot/factoid';

  /**
   * {@inheritdoc}
   */
  protected function configure() {

    $this
      ->setName('import-factoids')
      ->setDescription('Import factoids from druplicon.info to local database');

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    // Error logger.
    $error = function ($error_message) use ($output) {
      $output->writeLn("<error>$error_message</error>");
      return 1;
    };

    $container = $this->getApplication()->getContainer();

    $container['db_connection']->beginTransaction();
    $container['db_connection']->exec('DELETE FROM factoids_vendor');

    $sth = $container['db_connection']
      ->prepare('INSERT INTO factoids_vendor(subject, is_are, statement) VALUES(?, ?, ?)');

    if (!$sth) {
      return $error('PDO Error: ' . $container['db_connection']->errorInfo()[2]);
    }

    $progress = $this->getHelper('progress');
    $progress->start($output);
    $total = 0;

    for ($page = 0; TRUE; $page++) {

      $url = self::BASE_URL . '?page=' . $page;

      try {

        $progress->advance();
        $output->write(' Page: ' . $url);

        $crawler = $container['http_client']->request('GET', $url);

        if ($container['http_client']->getResponse()->getStatus() == 200) {

          $trs = $crawler->filter('#bot-factoid-list tbody tr');
          foreach ($trs as $tr) {

            $sth->execute([
              $tr->childNodes->item(0)->nodeValue,
              $tr->childNodes->item(1)->nodeValue,
              $tr->childNodes->item(2)->nodeValue,
            ]);

            $total++;

          }

          // Check whether it is the last page.
          if (count($crawler->selectLink('next ›')) != 1) {
            break;
          }

        }
        else {
          $output->writeln('');
          return $error("druplicon.info is not available");
        }
      }
      catch (\Exception $e) {
        $output->writeln('');
        return $error("HTTP Client error: " . $e->getMessage());
      }

    }

    $progress->finish();

    if ($container['db_connection']->commit()) {
      $output->writeln('<info>Imported factoids: </info>' . $total);
    }
    else {
      $error('Transaction failed');
      return 1;
    }

  }

}
