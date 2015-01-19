<?php

// Application name.
$container['application_name'] = 'Druplicon';

// Path to log file.
$container['log_file'] = PROJECT_DIR . '/logs/debug.log';

// Path to sqlite database.
$container['database'] = PROJECT_DIR . '/databases/db.sqlite';

// CSV file with core functions to import.
$container['api_functions_file'] = PROJECT_DIR . '/data/d7-core-functions.csv';

// D-bus wait timeout, ms.
$container['wait_loop_timeout'] = 1000;

// Schedule timeout, s.
$container['schedule_timeout'] = 15 * 60;

// Skype account.
$container['user_id'] = 'druplicon';

// Chat name
$container['chat_name'] = '#druplicon/$chat_id';

// Default timezone.
$container['time_zone'] = 'Europe/Moscow';
