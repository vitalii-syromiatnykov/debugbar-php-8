<?php

use DebugBar\Bridge\Twig\TimeableTwigExtensionProfiler;
use DebugBar\Bridge\TwigProfileCollector;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../src/DebugBar/Resources');

$loader = new Twig_Loader_Filesystem('.');
$twig = new Twig_Environment($loader);
$profile = new Twig_Profiler_Profile();
$twig->addExtension(new TimeableTwigExtensionProfiler($profile, $debugbar['time']));

$debugbar->addCollector(new TwigProfileCollector($profile));

render_demo_page(function() use ($twig): void {
    echo $twig->render('hello.html', ['name' => 'peter pan']);
});
