<?php

namespace Druplicon;

/**
 * State storage service.
 */
class State {

  /**
   * @var \PDO
   */
  protected $dbConnection;

  /**
   * @param \PDO $db_connection
   */
  public function __construct(\PDO $db_connection) {
    $this->dbConnection = $db_connection;
  }

  /**
   * Returns the stored value for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function get($key) {
    $sth = $this->dbConnection->prepare('SELECT value FROM state WHERE name = ?');
    $sth->execute([$key]);
    return $sth->fetchColumn();
  }

  /**
   * Saves a value for a given key.
   *
   * @param string $key
   *   The key of the data to store.
   * @param string $value
   *   The data to store.
   * @throws \Exception
   */
  public function set($key, $value) {
    $sth = $this->dbConnection->prepare('REPLACE INTO state (name, value) VALUES (?, ?)');
    if (!$sth || !$sth->execute([$key, $value])) {
      throw new \Exception('PDO Error: ' . $this->dbConnection->errorInfo()[2]);
    }

  }

}
