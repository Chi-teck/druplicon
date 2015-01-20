<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ChatIncomingMessage;
use Druplicon\SkypeEngine;
use Goutte\Client;
use Psr\Log\LoggerInterface;

/**
 * Retrieves random stories from bash.im site.
 */
class DrupalStatus {

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

    if ($event->getCommand() != 'drupal-status') {
      return;
    }

    try {
      $this->logger->debug('Send queries to updates.drupal.org');

      $d7_crawler = $this->httpClient->request('GET', 'http://updates.drupal.org/release-history/drupal/7.x');
      if ($d7_crawler && $this->httpClient->getResponse()->getStatus() == 200) {
        $d7_version = $d7_crawler->filterXPath('//project/releases/release[1]/version')->text();
      }

      $d8_crawler = $this->httpClient->request('GET', 'http://updates.drupal.org/release-history/drupal/8.x');
      if ($d8_crawler && $this->httpClient->getResponse()->getStatus() == 200) {
        $d8_version = $d8_crawler->filterXPath('//project/releases/release[1]/version')->text();
      }

      $d8_issues_counter_crawler = $this->httpClient->request('GET', 'https://www.drupal.org/drupal-8.0');
      if ($d8_issues_counter_crawler && $this->httpClient->getResponse()->getStatus() == 200) {
        $d8_issues_total = $d8_issues_counter_crawler->filter('#block-drupalorg-project-critical-count h3 a')->text();
      }

      if (empty($d7_version) || empty($d8_version) || empty($d8_issues_total)) {
        $this->logger->warn('Could not get Drupal versions from updates.drupal.org');
        return FALSE;
      }

    }
    catch (\Exception $e) {
      $this->logger->error("HTTP Client error: " . $e->getMessage());
      return FALSE;
    }

    $reply = "Стабильная версия - $d7_version\n";
    $reply .= "Версия в разработке - $d8_version  ($d8_issues_total)\n";

    $this->skypeEngine->send($event->getMessageData()['CHATNAME'], $reply);

  }

}
