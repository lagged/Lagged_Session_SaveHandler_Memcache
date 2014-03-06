# Lagged\Session

This code already evolved into two session handlers!

[![Build Status](https://secure.travis-ci.org/lagged/Lagged_Session_SaveHandler_Memcache.png?branch=master)](http://travis-ci.org/lagged/Lagged_Session_SaveHandler_Memcache)

## Lagged\Session\SaveHandler\Memcache

This is a Memcache session handler for Zend Framework with MySQL persistence.

Each time session information is requested, it'll ask Memcached first and read from MySQL as a failover.

Data is written to Memcached and to MySQL. The write to MySQL is asynchronous (yay).

## Lagged\Session\SaveHandler\Mysql

This is a Mysql session handler for Zend Framework (but without the bottlenecks Zend_Db_Table and prepared statements).

## Requirements

 * PHP 5.3.0+
 * `ext/memcache`
 * `ext/mysqli` (with mysqlnd)
 * Zend Framework (tested with 1.11.11)

## Setup

The setup is (hopefully) simple and straight forward. A PEAR/PECL-based solution is preferred.

### PEAR & PECL

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

### Manual

 * compile the memcache extension and setup the memcache extension
 * git clone this repository
 * add `path/to/repository/src' to your PHP's `include_path`

### Schemas

 1. Memcached: But it doesn't require a schema, so we're done!
 2. MySQL: The schema for MySQL can be found in [var/session.sql](https://github.com/lagged/Lagged_Session_SaveHandler_Memcache/blob/master/var/session.sql).

### Verify!

Always verify that your setup was completed correctly.

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

The obvious:

  1. Verify Memcache is running and your application servers can access it.
  2. Verify MySQL is running and all credentials are correct.

## Usage

This is likely to be in one of your bootstrap files:

    <?php
    use Lagged\Session\Autoload as SessionAutoload;
    use Lagged\Session\SaveHandler\Memcache as SessionHandler;

    // yay, include_path
    require_once 'Lagged/Session/Autoload.php';
    SessionAutoload::register();

    $memcache = new Memcache();
    $memcache->addServer($config->memcache->host); // assumes default port 11211

    $db = Zend_Db::factory('Mysqli', $config->database);

    $saveHandler = new SessionHandler($memcache, $db);
    Zend_Session::setSaveHandler($saveHandler);
    register_shutdown_function('session_write_close');


To use MySQL without Memcache:

    <?php
    use Lagged\Session\Autoload as SessionAutoload;
    use Lagged\Session\SaveHandler\Mysql as SessionHandler;

    // yay, include_path
    require_once 'Lagged/Session/Autoload.php';
    SessionAutoload::register();

    $db = Zend_Db::factory('Mysqli', $config->database);

    $saveHandler = new SessionHandler($db);
    Zend_Session::setSaveHandler($saveHandler);
    register_shutdown_function('session_write_close');

Using Memcache without MySQL:

    <?php
    use Lagged\Session\Autoload as SessionAutoload;
    use Lagged\Session\SaveHandler\Memcache as SessionHandler;

    // yay, include_path
    require_once 'Lagged/Session/Autoload.php';
    SessionAutoload::register();

    $memcache = new Memcache();
    $memcache->addServer($config->memcache->host); // assumes default port 11211

    $saveHandler = new SessionHandler($memcache);
    Zend_Session::setSaveHandler($saveHandler);
    register_shutdown_function('session_write_close');

### Performance

It's critical to set appropriate timeouts to MySQL:

    database.params.driver_options.MYSQLI_OPT_CONNECT_TIMEOUT = 5

In pure PHP:

    $db->options(\MYSQLI_OPT_CONNECT_TIMEOUT, 5);

Or to `\Zend_Db`:

    $config->database->params->driver_options = array(
        \MYSQLI_OPT_CONNECT_TIMEOUT => 5,
    );

### Redundancy with Memcache

To write to multiple servers, just do the following in your `php.ini` or `memcache.ini`:

    memcache.redundancy=X

X being the number of nodes in your Memcache setup.

### Error Handling

The code is designed to not throw Exceptions (from within the session handler) and to generally be quiet.

In case you need to debug anything in production pass `$debug` into `__construct()` and watch Syslog (it's the last parameter for either session handler). `\Zend_Log` with a syslog writer is used underneath.

For development and testing, you may use the following to get `\RuntimeExceptions`:

    $saveHandler->testing = true;

### Stability

So even though this code is a WIP, it's already used in production.

The versioning is conservative - whatever is publish on our [PEAR channel](http://easybib.github.com/pear) is running in production!

