<?php

namespace Druplicon;

use Pimple\Container;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * {@inheritdoc}
 */
class Application extends BaseApplication {

  /**
   * @var Container
   */
  protected $container;

  /**
   * @param Container $container
   */
  public  function setContainer(Container $container) {
    $this->container = $container;
  }

  /**
   * @return Container
   */
  public  function getContainer() {
    return $this->container;
  }

}
