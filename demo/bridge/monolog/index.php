<?php

use Monolog\Logger;
use DebugBar\Bridge\MonologCollector;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../src/DebugBar/Resources');

$logger = new Logger('demo');

$debugbar->addCollector(new MonologCollector($logger));

$logger->info('hello world');

render_demo_page();
