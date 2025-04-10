<?php

use CacheCache\Cache;
use CacheCache\Backends\Memory;
use DebugBar\Bridge\CacheCacheCollector;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../src/DebugBar/Resources');

$cache = new Cache(new Memory());

$debugbar->addCollector(new CacheCacheCollector($cache));

$cache->set('foo', 'bar');
$cache->get('foo');
$cache->get('bar');

render_demo_page();
