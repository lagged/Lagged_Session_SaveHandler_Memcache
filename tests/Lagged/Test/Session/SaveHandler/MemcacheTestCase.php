<?php
namespace Lagged\Test\Session\SaveHandler;

use Lagged\Session\SaveHandler\Memcache as MemcacheSession;
use Lagged\Session\Helper;

/**
 * This is an integration test.
 *
 * @category Testing
 * @package  Lagged\Session\SaveHandler\Memcache
 * @author   Till Klampaeckel <till@php.net>
 *
 */
class MemcacheTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Memcache object!
     * @var \Memcache
     * @see self::setUpMemcache();
     */
    protected $memcache;

    /**
     * Confirm standard set/get.
     *
     * @return void
     */
    public function testMemcacheSet()
    {
        $this->memcache = $this->setUpMemcache();
        $key            = 'foo';
        $data           = 'foo|s:3:"bar"';
        $compression    = \MEMCACHE_COMPRESSED;

        $this->memcache->set($key, $data, $compression);
        $this->assertSame($data, $this->memcache->get($key, $compression));
    }

    /**
     * Test writing a session - directly.
     *
     * @return void
     */
    public function testWriteRead()
    {
        $this->memcache = $this->setUpMemcache();
        $db             = $this->setUpDb();
        $session_id     = 'session_id';
        $data           = 'foo|s:3:"bar"';

        $handler          = new MemcacheSession($this->memcache, $db, true);
        $handler->testing = true;

        $this->assertTrue($handler->write($session_id, $data));

        $sessionData  = $handler->read($session_id);
        $memcacheData = $this->memcache->get($session_id, \MEMCACHE_COMPRESSED);

        $this->assertSame($data, $memcacheData);
        $this->assertSame($data, $sessionData);
    }

    /**
     * This is an integration test for our custom session handler.
     *
     * @return void
     * @runInSeparateProcess
     */
    public function testSomething()
    {
        $memcache = $this->setUpMemcache();
        $db       = $this->setUpDb();

        $handler = new MemcacheSession($memcache, $db);
        $this->assertInstanceOf('\Lagged\Session\SaveHandler\Memcache', $handler);

        $status = session_set_save_handler(
            array($handler, 'open'),
            array($handler, 'close'),
            array($handler, 'read'),
            array($handler, 'write'),
            array($handler, 'destroy'),
            array($handler, 'gc')
        );
        $this->assertTrue($status);

        session_start();
        $session_id = session_id();

        $_SESSION['foo'] = 'bar';
        session_write_close();

        $db = $this->setUpDb();

        $session_memcache_raw = $memcache->get($session_id, \MEMCACHE_COMPRESSED);
        $this->assertInternalType('string', $session_memcache_raw);

        $session_memcache = Helper::decode($session_memcache_raw);

        $this->assertArrayHasKey('foo', $session_memcache);
        $this->assertEquals('bar', $session_memcache['foo']);

        $session_sql_raw = $db->fetchOne(
            sprintf("SELECT session_data FROM %s WHERE session_id = %s",
                'session2',
                $db->quote($session_id)
            )
        );

        $this->assertSame($session_memcache_raw, $session_sql_raw);

        $session_sql = Helper::decode($session_sql_raw);

        $this->assertArrayHasKey('foo', $session_sql);
        $this->assertEquals('bar', $session_sql['foo']);

        $this->assertEquals($session_memcache, $session_sql);
    }

    /**
     * Creates the database and the table.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function setUp()
    {
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
            'username' => 'root',
            'password' => '',
            'dbname'   => 'sessions',
            'host'     => '127.0.0.1',
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