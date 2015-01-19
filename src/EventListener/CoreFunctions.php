<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ChatIncomingMessage;
use Druplicon\SkypeEngine;
use Psr\Log\LoggerInterface;

/**
 * Provides information about Drupal core functions.
 */
class CoreFunctions {

  /**
   * @var SkypeEngine
   */
  protected $skypeEngine;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var \PDO
   */
  protected $db_connection;

  /**
   * @param $skype_engine
   * @param LoggerInterface $logger
   * @param $db_connection
   */
  function __construct(SkypeEngine $skype_engine, LoggerInterface $logger, \PDO $db_connection) {

    $this->skypeEngine = $skype_engine;
    $this->logger = $logger;
    $this->dbConnection = $db_connection;

  }

  /**
   * @param ChatIncomingMessage $event
   */
  public function onChatIncomingMessage(ChatIncomingMessage $event)   {

    $message_data = $event->getMessageData();

    if (preg_match("/([^\s]*)[!\?]+$/i", $message_data['BODY'], $matches)) {

      $function_name = $matches[1];

      $this->logger->debug('Searching for a function with name "' . $matches[1] . '"');
      $sth = $this->dbConnection->prepare("SELECT summary, signature FROM core_functions WHERE name = ?");
      $sth->execute([$function_name]);
      $result = $sth->fetch(\PDO::FETCH_NUM);

      if ($result) {
        $result[] = "http://api.drupal.org/api/function/$function_name/7";
        $reply = $function_name . ': ' . implode(' => ', $result);
        $this->skypeEngine->send($message_data['CHATNAME'], $reply);
      }

    }

  }

}
