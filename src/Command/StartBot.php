<?php

namespace Druplicon\Command;

use Druplicon\Event\ScheduleTick;
use Druplicon\EventListener;
use Druplicon\Events;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs Druplicon bot.
 */
class StartBot extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('start-bot')
      ->setDescription('Start Druplicon bot');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $container = $this->getApplication()->getContainer();

    $container['state']->set('start_time', time());

    if (function_exists('cli_set_process_title')) {
      cli_set_process_title($container['application_name']);
    }

    $container['logger']->info('Process ID is ' .   getmypid());
    $schedule_ts = time();

    $skype_engine =  $container['skype_engine'];
    $container['logger']->info('Start waiting loop...');
    while (TRUE) {

      $skype_engine->waitLoop();

      if ((time() - $schedule_ts) > $container['schedule_timeout']) {
        $container['logger']->debug('Run schedule tasks...');

        $container['dispatcher']->dispatch(
          Events::SCHEDULE_TICK,
          new ScheduleTick()
        );

        $container['logger']->debug('Memory usage: ' . $this->formatSize(memory_get_usage()));

        $schedule_ts = time();

      }
    }

  }

  /**
   * Generates a string representation for the given byte count.
   *
   * @param integer $size
   *   A size in bytes.
   *
   * @return float
   *   A string representation of the size.
   */
  protected function formatSize($size) {
    if ($size < 1024) {
      return $size . ' B';
    }
    else {
      $size = $size / 1024;
      $units = ['KB', 'MB', 'GB', 'TB'];
      foreach ($units as $unit) {
        if (round($size, 2) >= 1024) {
          $size = $size / 1024;
        }
        else {
          break;
        }
      }
      return round($size, 2) . ' ' . $unit;
    }
  }

}
