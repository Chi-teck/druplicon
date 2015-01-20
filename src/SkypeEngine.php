<?php

namespace Druplicon;

use Druplicon\Event\ChatIncomingMessage;
use Psr\Log\LoggerInterface;

/**
 * Provides an access to Skype API.
 *
 * TODO: Write tests.
 */
class SkypeEngine {

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * The name of the main chat.
   *
   * @var String
   */
  protected $chatName;

  /**
   * Skype account.
   *
   * @var String
   */
  protected $account;

  /**
   * D-Bus wait loop timeout.
   *
   * @var integer
   */
  protected $waitLoopTimeout;

  /**
   * @var Dbus
   */
  protected $dbus;

  /**
   * @var DbusObject
   */
  protected $proxy;

  /**
   * Constructs SkypeEngine object.
   *
   * @param LoggerInterface $logger
   * @param string $chat_name
   * @param string $account
   * @param integer $wait_loop_timeout
   * @param string $application_name
   */
  public function __construct(LoggerInterface $logger, $chat_name, $account, $wait_loop_timeout, $application_name) {

    $this->logger = $logger;
    $this->chatName = $chat_name;
    $this->account = $account;
    $this->waitLoopTimeout = $wait_loop_timeout;

    $this->dbus = new Dbus(Dbus::BUS_SESSION, TRUE);
    $this->dbus->registerObject('/com/Skype/Client', 'com.Skype.API.Client', __CLASS__);

    $this->proxy = $this->dbus->createProxy('com.Skype.API', '/com/Skype', 'com.Skype.API');
    $this->proxy->Invoke("NAME  $application_name");
    $this->proxy->Invoke('PROTOCOL 8');

    // TODO: Handle connection error.
    $current_user = explode(' ', $this->proxy->Invoke('GET CURRENTUSERHANDLE'))[1];
    if ($current_user != $this->account) {
      throw new \RuntimeException("User $current_user is not allowed to handle this bot.");
    }

    $this->logger->info("Current user: $current_user");
    // TODO: Log user status.

  }

  /**
   * Returns message properties.
   *
   * @param $message_id
   * @return mixed
   */
  public function getMessageData($message_id) {

    $properties = [
      'CHATNAME',
      'BODY',
      'TIMESTAMP',
      'FROM_HANDLE',
      'FROM_DISPNAME',
      'TYPE',
      'STATUS',
    ];

    $data['ID'] = $message_id;
    foreach ($properties as $property_name) {
      $property_value = $this->proxy->Invoke("GET CHATMESSAGE $message_id $property_name");
      $data[$property_name] = explode("$property_name ", $property_value, 2)[1];
    }

    return $data;
  }

  /**
   * WaitLoop shortcut.
   */
  public function waitLoop() {
    $this->dbus->waitLoop($this->waitLoopTimeout);
  }

  /**
   * Sends message to a given chat.
   *
   * @param $chat_name
   * @param $message_body
   */
  public function send($chat_name, $message_body) {
    $this->proxy->Invoke("CHATMESSAGE $chat_name $message_body");
    $this->logger->info(' => ' . $message_body);
  }

  /**
   * D-Bus callback.
   *
   * @param $notify
   * @see Dbus::registerObject
   */
  public static function notify ($notify) {

    // TODO: FInd a better way to inject the container.
    global $container;

    $skype_engine = $container['skype_engine'];
    $skype_engine->logger->debug($notify);

    // Reply only on incoming messages.
    if (preg_match('/CHATMESSAGE (\d+) STATUS RECEIVED/', $notify, $matches)) {

      $container['state']->set('last_message_time', time());

      $message_id = $matches[1];
      $message_data = $skype_engine->getMessageData($message_id);

      $container['logger']->info(' <= ' . $message_data['BODY']);

      $event = new ChatIncomingMessage(
        $message_data
      );

      $container['dispatcher']->dispatch(
        Events::CHAT_INCOMING_MESSAGE,
        $event
      );

    }

    // Authorization request.
    if ($container['accept_auth_requests'] && preg_match('/USER (.*) RECEIVEDAUTHREQUEST/', $notify, $matches)) {
      $user_account = $matches[1];

      $container['logger']->info('Auth request form ' . $user_account);
      $skype_engine->proxy->Invoke("SET USER $user_account BUDDYSTATUS 2");

    }

  }

}
