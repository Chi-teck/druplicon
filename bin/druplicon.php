#!/usr/bin/env php
<?php

/**
 * This file is part of the Druplicon package.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Druplicon\Application;
use Druplicon\Command\ImportCoreFunctions;
use Druplicon\Command\ImportFactoids;
use Druplicon\Command\CheckRequirements;
use Druplicon\Command\SetupDatabase;
use Druplicon\Command\StartBot;
use Druplicon\EventListener\BashIm;
use Druplicon\EventListener\CoreFunctions;
use Druplicon\EventListener\DoProjectIssue;
use Druplicon\EventListener\Factoid;
use Druplicon\EventListener\DrupalStatus;
use Druplicon\EventListener\System;
use Druplicon\Events;
use Druplicon\SkypeEngine;
use Druplicon\State;
use Goutte\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

define('PROJECT_DIR', dirname(__DIR__));
require PROJECT_DIR . '/vendor/autoload.php';

$container = new Container();
require_once PROJECT_DIR . '/config/config.php';

date_default_timezone_set($container['time_zone']);

// TODO: Cleanup this.
$input = new ArgvInput();
$verbosity_level_map[LogLevel::DEBUG] = OutputInterface::VERBOSITY_NORMAL;

// Set logger.
$output = new ConsoleOutput();

$container['logger'] = function($container) use ($output) {

  $verbosity_level_map = [
    LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
  ];

  $logger = new Logger($container['application_name']);
  $logger->pushHandler(new StreamHandler($container['log_file']));
  $logger->pushHandler(new ConsoleHandler($output, TRUE, $verbosity_level_map));

  return $logger;
};

// Set dispatcher.
$container['dispatcher'] = function ($container) {

  $dispatcher = new EventDispatcher();

  $dispatcher->addListener(
    Events::CHAT_INCOMING_MESSAGE,
    [
      new Factoid($container['skype_engine'], $container['logger'], $container['db_connection']),
      'onChatIncomingMessage'
    ]
  );
  $dispatcher->addListener(
    Events::CHAT_INCOMING_MESSAGE,
    [
      new System($container['skype_engine']),
      'onChatIncomingMessage'
    ]
  );
  $dispatcher->addListener(
    Events::CHAT_INCOMING_MESSAGE,
    [
      new BashIm($container['skype_engine'], $container['logger'], $container['http_client']),
      'onChatIncomingMessage'
    ]
  );
  $dispatcher->addListener(
    Events::CHAT_INCOMING_MESSAGE,
    [
      new DoProjectIssue($container['skype_engine'], $container['logger'], $container['http_client']),
      'onChatIncomingMessage'
    ]
  );
  $dispatcher->addListener(
    Events::CHAT_INCOMING_MESSAGE,
    [
      new CoreFunctions($container['skype_engine'], $container['logger'], $container['db_connection']),
      'onChatIncomingMessage'
    ]
  );
  $dispatcher->addListener(
    Events::CHAT_INCOMING_MESSAGE,
    [
      new DrupalStatus($container['skype_engine'], $container['logger'], $container['http_client']),
      'onChatIncomingMessage'
    ]
  );

  return $dispatcher;
};

// Set http client.
$container['http_client'] = function($container) {
  return new Client();
};

// Set database connection.
$container['db_connection'] = function($container) {
  return new PDO('sqlite://' . $container['database']);
};

// Set state storage.
$container['state'] = function($container) {
  return new State($container['db_connection']);
};

// Set Skype engine.
$container['skype_engine'] = function($container) {
  return new SkypeEngine(
    $container['logger'],
    $container['chat_name'],
    $container['user_id'],
    $container['wait_loop_timeout'],
    $container['application_name']
  );
};

$application = new Application('Druplicon');
$application->setContainer($container);
$application->addCommands([
  new StartBot(),
  new SetupDatabase(),
  new ImportFactoids(),
  new ImportCoreFunctions(),
  new CheckRequirements(),
]);
$application->run($input, $output);
