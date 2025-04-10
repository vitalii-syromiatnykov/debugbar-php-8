<?php

include __DIR__ . '/bootstrap.php';
$debugbar['messages']->addMessage('hello from redirect');
$debugbar->stackData();
header('Location: index.php');
