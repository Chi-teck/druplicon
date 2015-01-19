<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ChatIncomingMessage;
use Druplicon\SkypeEngine;
use Psr\Log\LoggerInterface;

/**
 * Class Factoid
 * @package Druplicon\EventListener
 */
class Factoid {

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var \PDO
   */
  protected $dbConnection;

  /**
   * @var SkypeEngine
   */
  protected $skypeEngine;

  /**
   * @param SkypeEngine $skype_engine
   * @param LoggerInterface $logger
   * @param \PDO $db_connection
   */
  public function __construct(SkypeEngine $skype_engine, LoggerInterface $logger, \PDO $db_connection) {

    $this->logger = $logger;
    $this->dbConnection = $db_connection;
    $this->skypeEngine = $skype_engine;

  }

  /**
   * @param ChatIncomingMessage $event
   */
  public function onChatIncomingMessage(ChatIncomingMessage $event)   {

    $message_data = $event->getMessageData();

    if (preg_match("/([^\s]*)[!\?]+$/i", $message_data['BODY'], $matches)) {

      $subject = $matches[1];

      $this->logger->debug('Searching factoid for ' . $matches[1]);
      $sth = $this->dbConnection->prepare("SELECT * FROM factoids_vendor WHERE subject = ?");
      $sth->execute([$subject]);
      $result = $sth->fetch(\PDO::FETCH_NUM);

      if ($result) {

        if (strpos($result[2], '<reply>') !== FALSE)  {
          $reply = str_replace('<reply>', '', $result[2]);
        }
        else if (strpos($result[2], '<action>') !== FALSE)  {
          // Do nothing.
        }
        else {
          $reply = implode(' ', $result);
        }

        $this->skypeEngine->send($message_data['CHATNAME'], $reply);
      }

    }

  }

}
