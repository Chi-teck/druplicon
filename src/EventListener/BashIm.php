<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ChatIncomingMessage;
use Druplicon\SkypeEngine;
use Goutte\Client;
use Psr\Log\LoggerInterface;

/**
 * Retrieves random stories from bash.im site.
 */
class BashIm {

  /**
   * @var SkypeEngine
   */
  protected $skypeEngine;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var Client
   */
  protected $httpClient;

  /**
   * Constructs BashIm event listener.
   *
   * @param SkypeEngine $skype_engine
   * @param LoggerInterface $logger
   * @param Client $http_client
   */
  function __construct(SkypeEngine $skype_engine, LoggerInterface $logger, Client $http_client) {

    $this->skypeEngine = $skype_engine;
    $this->logger = $logger;
    $this->httpClient = $http_client;

  }

  /**
   * @param ChatIncomingMessage $event
   */
  public function onChatIncomingMessage(ChatIncomingMessage $event)   {

    if ($event->getCommand() == 'bashim') {

      try {

        $this->logger->debug('Send query to bash.im');
        $crawler = $this->httpClient->request('GET', 'http://bash.im/random/');

        if ($crawler && $this->httpClient->getResponse()->getStatus() == 200) {

          $quote = $crawler->filter('.quote');
          $link = 'http://bash.im' . $quote->filter('.id')->attr('href');
          $text = $quote->filter('.text')->first()->html();

          $reply = html_entity_decode($link . "\n" . str_replace('<br>', "\n", $text));
          $this->skypeEngine->send($event->getMessageData()['CHATNAME'], $reply);

        }
        else {
          $this->logger->warning('bash.im is not available');
        }

      }
      catch (\Exception $e) {
        $this->logger->error("Request error: " . $e->getMessage());
      }

    }

  }

}
