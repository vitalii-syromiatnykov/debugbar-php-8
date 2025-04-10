<?php
use Symfony\Component\Console\Helper\HelperSet;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

// cli-config.php
require_once __DIR__ . "/bootstrap.php";

$em = $entityManager;
$helperSet = new HelperSet([
    'db' => new ConnectionHelper($em->getConnection()),
    'em' => new EntityManagerHelper($em)
]);
