<?php

use Doctrine\DBAL\Logging\DebugStack;
use DebugBar\Bridge\DoctrineCollector;
use Demo\Product;

include __DIR__ . '/bootstrap.php';
include __DIR__ . '/../../bootstrap.php';

$debugbarRenderer->setBaseUrl('../../../src/DebugBar/Resources');

$debugStack = new DebugStack();
$entityManager->getConnection()->getConfiguration()->setSQLLogger($debugStack);
$debugbar->addCollector(new DoctrineCollector($debugStack));

$product = new Product();
$product->setName("foobar");

$entityManager->persist($product);
$entityManager->flush();

render_demo_page();
