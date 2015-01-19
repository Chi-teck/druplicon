<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ChatIncomingMessage;
use Druplicon\SkypeEngine;
use Goutte\Client;
use Psr\Log\LoggerInterface;

/**
 * Retrieves information about project issue.
 */
class DoProjectIssue {

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
   * @var string
   */
  protected $lastMentionedIssue;

  /**
   * @param SkypeEngine $skype_engine
   * @param LoggerInterface $logger
   * @param Client $http_client
   */
  function __construct(SkypeEngine $skype_engine, LoggerInterface $logger, Client $http_client) {

    $this->skypeEngine =  $skype_engine;
    $this->logger = $logger;
    $this->httpClient = $http_client;

  }

  /**
   * @param ChatIncomingMessage $event
   */
  public function onChatIncomingMessage(ChatIncomingMessage $event)   {

    $message_data = $event->getMessageData();

    if (preg_match('!https?://[\w\d\-]*?\.?drupal\.org/node/\d+!i', $message_data['BODY'], $matches)) {
      $url = $matches[0];

      // Do not mention the node twice.
      if ($this->lastMentionedIssue == $url) {
        return;
      }

      $this->logger->debug("Node url: $url");
      $crawler = $this->httpClient->request('GET', $url);
      if ($crawler && $this->httpClient->getResponse()->getStatus() == 200) {

        $status_crawler = $crawler->filter('.field-name-field-issue-status');

        // Status available only for project issues.
        if (count($status_crawler)) {

          $title = explode(' [#', $crawler->filter('title')->text())[0];
          $reply = $url . ' => ' . $title;

          $status = $status_crawler->text();
          $reply .= " [$status]";

          $comments = $crawler->filter('.comment');

          $total_comments = count($comments);
          $suffix = $total_comments == 1 ? '' : 's';
          $reply .= " - $total_comments comment$suffix";
        }

        $this->skypeEngine->send($message_data['CHATNAME'], $reply);
        $this->lastMentionedIssue = $url;

      }

    }

  }

}
