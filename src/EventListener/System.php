<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ChatIncomingMessage;
use Druplicon\SkypeEngine;

/**
 * A set of system commands.
 */
class System {

  /**
   * @var SkypeEngine
   */
  protected $skypeEngine;

  /**
   * @param SkypeEngine $skype_engine
   */
  function __construct(SkypeEngine $skype_engine) {
    $this->skypeEngine = $skype_engine;
  }

  /**
   * @param ChatIncomingMessage $event
   */
  public function onChatIncomingMessage(ChatIncomingMessage $event)   {

    if (!$command = $event->getCommand()) {
      return;
    }

    $chat_name = $event->getMessageData()['CHATNAME'];

    switch ($command) {
      case 'time':
        $reply = date('H:i - l j F Y, T P');
        break;

      case 'ping':
        $reply = 'pong';
        break;

      case 'chatname':
        $reply = $chat_name;
        break;
    }

    if (isset($reply)) {
      $this->skypeEngine->send($chat_name, $reply);
    }

  }

}
