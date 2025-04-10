<?php

use DebugBar\OpenHandler;

include __DIR__ . '/bootstrap.php';

$openHandler = new OpenHandler($debugbar);
$openHandler->handle();
