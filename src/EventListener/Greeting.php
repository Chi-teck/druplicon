<?php

namespace Druplicon\EventListener;

use Druplicon\Event\ScheduleTick;

/**
 *
 */
class Greeting {

  public function __construct($skype_engine, $logger, $chat_name, $state, $http_client) {
    $this->skype_engine = $skype_engine;
    $this->logger = $logger;
    $this->chat_name = $chat_name;
    $this->state = $state;
    $this->httpClient = $http_client;
  }

  public function onScheduleTick(ScheduleTick $event)   {

    // 5 is Friday
    if (date('N') <= 5 && date('H') > 9 && date('H') < 15) {

      $time_elapsed = time() - (int) $this->state->get('last_greeting_time');
      if ($time_elapsed > 23 * 3600) {
        $this->state->set('last_greeting_time', time());

        if ($greeting = $this->getGreetingMessage()) {
          $this->skype_engine->send($this->chat_name, $greeting);
        }

      }

    }

  }

  protected function getGreetingMessage() {
    $date = $this->translateDate(date('l, j-е F'));

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

    $message = "Всем привет!\n";
    $message .= "Сегодня $date\n";
    $message .= "Стабильная версия Друпал - $d7_version\n";
    $message .= "Версия в разработке - $d8_version  ($d8_issues_total)\n";
    $message .= "Хороший день, чтобы помочь нам в разработке Друпал 8!\n";
    $message .= "https://www.drupal.org/drupal-8.0/get-involved\n";
    return $message;
  }

  protected function translateDate($date) {
    $dw = [
      'Monday' => 'понедельник',
      'Tuesday' => 'вторник',
      'Wednesday' => 'среда',
      'Thursday' => 'четверг',
      'Friday' => 'пятница',
      'Saturday' => 'суббота',
      'Sunday' => 'воскресенье',
    ];
    $mn = [
      'January' => 'января',
      'February' => 'февраля',
      'March' => 'марта',
      'April' => 'апреля',
      'May' => 'мая',
      'June' => 'июня',
      'July' => 'июля',
      'August' => 'августа',
      'September' => 'сентября',
      'October' => 'октября',
      'November' => 'ноября',
      'December' => 'декабря',
    ];

    $dw_ru = array_keys($dw);
    $mn_ru = array_keys($mn);
    $date = str_replace($dw_ru, $dw, $date);
    $date = str_replace($mn_ru, $mn, $date);
    return $date;

  }

}
