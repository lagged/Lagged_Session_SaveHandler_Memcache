<?php
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (false === file_exists($autoload)) {
    die("Please run 'php composer.phar install'." . PHP_EOL);
}
if (false === extension_loaded("memcache")) {
    echo "Trying to load: memcache." . PHP_SHLIB_SUFFIX . PHP_EOL;
    if (false === dl("memcache." . PHP_SHLIB_SUFFIX)) {
        die("These tests require ext/memcache to run!" . PHP_EOL);
    }
}
require_once $autoload;
require_once 'PHPUnit/Autoload.php';
