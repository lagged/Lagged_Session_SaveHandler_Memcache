<?php
$autoload = dirname(__DIR__) . '/vendor/.composer/autoload.php';
if (!file_exists($autoload)) {
    die("Please run 'php composer.phar install'.");
}
require_once $autoload;
require_once 'PHPUnit/Autoload.php';