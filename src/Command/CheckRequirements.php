<?php

namespace Druplicon\Command;

use Druplicon\EventListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check system requirements for Druplicon application.
 */
class CheckRequirements extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('check-requirements')
      ->setDescription('Check system requirements');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $errors = [];

    if (version_compare(PHP_VERSION, '5.4.5') < 0) {
      $errors[] = 'Your PHP installation is too old. Druplicon requires at least PHP 5.4.5.';
    }

    $required_extensions = [
      'date',
      'dom',
      'filter',
      'json',
      'pcre',
      'pdo',
      'SimpleXML',
      'SPL',
      'xml',
      'dbus',
    ];
    foreach ($required_extensions as $extension) {
      if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
      }
    }

    if (!empty($missing_extensions)) {
      $errors[] = 'Drupal requires you to enable the PHP extensions in the following list: ' . implode(', ', $missing_extensions);
    }

    $db_drivers = \PDO::getAvailableDrivers();
    if (!in_array('sqlite', $db_drivers)) {
      $errors[] = 'SQLite driver is not available';
    }

    if ($errors) {

      foreach ($errors as $error) {
        $output->writeln("<error>$error</error>");
      }

      return 1;
    }

    $output->writeln('System requirements: [<info>OK</info>]');

  }

}
