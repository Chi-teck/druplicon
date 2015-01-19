<?php

namespace Druplicon;

/**
 * Contains all events thrown in the Druplicon.
 */
final class Events {

  /**
   * Chat incoming message has been received.
   */
  const CHAT_INCOMING_MESSAGE = 'chat.incoming_message';

  /**
   * The regular tick occurs.
   */
  const SCHEDULE_TICK = 'schedule.tick';

}
