
<?php

use mon\log\Logger;

require __DIR__ . '/../vendor/autoload.php';

Logger::instance()->createChannel();

Logger::info('test');

Logger::instance()->channel()->emergency('xxxx');

$d = Logger::instance()->channel()->getLog();
var_dump($d);

$a = Logger::instance()->channel()->saveLog();
var_dump($a);
