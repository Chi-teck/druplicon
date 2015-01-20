# Druplicon
Skype chat bot for Drupal communities insipred by the [Drupal IRC Bot](https://www.drupal.org/project/bot).

## Installation

This project requires PHP D-Bus library which can be installed from PECL.

### Installing D-Bus
    apt-get install php5-dev
    apt-get install php-pear
    pear install PEAR
    apt-get install libdbus-1-dev libxml2-dev
    pecl install dbus-beta

On some systems the installation proccess may be different.

### Installing the Druplicon
    cd path/to/druplicon
    composer install
    cp config/example.config.php config/config.php
    vim config/config.php
    bin/druplicon.php check-requirements
    bin/druplicon.php setup-database
    bin/druplicon.php import-core-functions
    bin/druplicon.php import-factoids

## Usage
    bin/druplicon.php start-bot

## License
This project is licensed under GPL Version 2 license.
