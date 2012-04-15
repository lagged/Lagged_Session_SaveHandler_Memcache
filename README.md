## Lagged_Session_SaveHandler_Memcache

This is a Memcache session handler for Zend Framework with MySQL persistence.

Each time session information is requested, it'll ask Memcached first and read from MySQL as a failover.

Data is written to Memcached and to MySQL. The write to MySQL is asynchronous (yay).

### Requirements

 * PHP 5.3.0+
 * `ext/memcache`
 * `ext/mysqli` (with mysqlnd)
 * Zend Framework (tested with 1.11.11)

### Setup

#### PEAR & PECL

    $ pecl install memcache
    ...
    $ echo "extension=memcache.so" > /usr/local/etc/php/memcache.ini
    $ pear channel-discover easybib.github.com/pear
    ...
    $ pear install easybib/Lagged_Session_SaveHandler_Memcache-alpha
    ...

Note: Regarding the `memcache.ini`, your paths might be different, use your distributions package manager if you're not sure.

To find the location of all ini-files parsed:

    $ php --ini
    Configuration File (php.ini) Path: /usr/local/etc
    Loaded Configuration File:         /usr/local/etc/php-cli.ini
    Scan for additional .ini files in: /usr/local/etc/php
    Additional .ini files parsed:      /usr/local/etc/php/apc.ini,
    /usr/local/etc/php/gearman.ini,
    /usr/local/etc/php/memcache.ini,
    ...

#### Manual

 * compile the memcache extension and setup the memcache extension
 * git clone this repository
 * add `path/to/repository/src' to your PHP's `include_path`

#### Verify!

Always verify if your setup was completed correctly.

Verify the memcache extension is installed:

    $ php -m|grep memcache
    memcache

Verify the mysqli extension was build against mysqlnd (Client API library version):

    $ php --ri mysqli

    mysqli
    
    MysqlI Support => enabled
    Client API library version => mysqlnd 5.0.8-dev - 20102224 - $Revision: 310735 $
    Active Persistent Links => 0
    Inactive Persistent Links => 0
    Active Links => 0
    ...

### Usage

This is likely to be in one of your bootstrap files:

    <?php

    use Lagged\Session\Autoload as SessionAutoload;
    use Lagged\Session\SaveHandler\Memcache as SessionHandler;

    // yay, include_path
    require_once 'Lagged/Session/Autoload.php';
    SessionAutoload::register();

    $memcache = new Memcache();
    $memcache->addServer('127.0.0.1');

    $db = new Zend_Db::factory('Mysqli', $config->database);

    $memcacheSaveHandler = new SessionHandler($memcache, $db);
    Zend_Session::setSaveHandler($memcacheSaveHandler);

