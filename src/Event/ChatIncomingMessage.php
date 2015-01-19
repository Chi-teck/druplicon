<?php

namespace Druplicon\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Triggered whenever a new chat incoming message is received.
 */
class ChatIncomingMessage extends Event {

  /**
   * @var
   */
  protected $command;

  /**
   * @var array
   *
   * TODO: move it to config.
   */
  protected $botNames = [
    'druplicon',
    'друпликон.'
  ];

  /**
   * @param array $message_data
   */
  public function __construct(array $message_data) {
    $this->messageData = $message_data;
  }

  /**
   * @return mixed
   */
  public function getMessageData() {
    return $this->messageData;
  }

  /**
   * Parses command name from the message.
   *
   * @return null | string.
   */
  public function getCommand() {

    if (!$this->command) {

      $message_body = $this->messageData['BODY'];

      if (preg_match('#^:\s?([^\s]+)\s?#', $message_body, $matches)) {
        $this->command = $matches[1];
      }
      else {
        foreach ($this->botNames as $name) {
          $regexp = "#^$name\s([^\s]+)#iu";
          if (preg_match($regexp, $message_body, $matches)) {
            $this->command = $matches[1];
            break;
          }
        }
      }

    }

    return $this->command;
  }
}
