<?php

use Slim\Slim;
use DebugBar\Bridge\SlimCollector;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../src/DebugBar/Resources');

$app = new Slim();
$app->get('/', function () use ($app): void {
    $app->getLog()->info('hello world');
    render_demo_page();
});

$debugbar->addCollector(new SlimCollector($app));

$app->run();
