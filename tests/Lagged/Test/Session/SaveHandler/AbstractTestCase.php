<?php
namespace Lagged\Test\Session\SaveHandler;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Memcache object!
     * @var \Memcache
     * @see self::setUpMemcache();
     */
    protected $memcache;

    /**
     * Creates the database and the table.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function setUp()
    {
        if (!extension_loaded('memcache')) {
            $this->markTestSkipped("Test requires ext/memcache");
            return;
        }
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped("Test requires ext/mysql");
            return;
        }

        $config = $this->getDbConfiguration();

        $db = new \mysqli($config['host'], $config['username'], $config['password']);

        $sql = sprintf("CREATE DATABASE IF NOT EXISTS %s", $config['dbname']);
        $db->query($sql);

        $db->query(sprintf("USE %s", $config['dbname']));

        $table = realpath(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/var/session.sql');
        $sql   = file_get_contents($table);
        if (false === $sql) {
            throw new \RuntimeException(sprintf("Could not open file '%s'", $table));
        }

        $res = $db->query($sql);
        if (false === $res) {
            throw new \RuntimeException(sprintf("Could not create table from '%s': %s", $table, $db->error));
        }
        $db->close();
    }

    /**
     * Deletes the test database. Empties Memcached.
     *
     * @return void
     */
    public function tearDown()
    {
        $config = $this->getDbConfiguration();

        $db = new \mysqli($config['host'], $config['username'], $config['password']);
        $db->query(sprintf("DROP DATABASE %s", $config['dbname']));
        $db->close();
        $this->setUpMemcache()->flush();
    }

    /**
     * Configuration for testing!
     *
     * @return array
     */
    protected function getDbConfiguration()
    {
        return array(
            'username'       => 'root',
            'password'       => '',
            'dbname'         => 'sessions',
            'host'           => '127.0.0.1',
            'driver_options' => array(
                \MYSQLI_OPT_CONNECT_TIMEOUT => 5,
            ),
        );
    }
    /**
     * @return \Zend_Db_Adapter_Mysqli
     */
    protected function setUpDb()
    {
        $config = $this->getDbConfiguration();
        return new \Zend_Db_Adapter_Mysqli(
            $config
        );
    }

    /**
     * @return \Memcache
     */
    protected function setUpMemcache()
    {
        $memcache = new \Memcache();
        $memcache->addserver("127.0.0.1", 11211);
        return $memcache;
    }
}
