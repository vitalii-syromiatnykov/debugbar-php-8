<?php

include __DIR__ . '/bootstrap.php';

try {
    throw new Exception('Something failed!');
} catch (Exception $exception) {
    $debugbar['exceptions']->addException($exception);
}

?>
error from AJAX
<?php
    echo $debugbarRenderer->render(false);
?>
