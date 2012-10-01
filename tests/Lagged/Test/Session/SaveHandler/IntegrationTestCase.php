<?php
namespace Lagged\Test\Session\SaveHandler;

use Lagged\Session\SaveHandler\Memcache as MemcacheSession;
use Lagged\Session\Helper;

/**
 * This is an integration test suite with $_SESSION.
 *
 * @category Testing
 * @package  Lagged\Session\SaveHandler\Memcache
 * @author   Till Klampaeckel <till@php.net>
 *
 */
class IntegrationTestCase extends AbstractTestCase
{
    /**
     * This is an integration test for our custom session handler.
     *
     * @return void
     * @runInSeparateProcess
     */
    public function testSessionHandler()
    {
        $this->memcache = $this->setUpMemcache();
        $db             = $this->setUpDb();

        $handler          = new MemcacheSession($this->memcache, $db, true);
        $handler->testing = true;

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

        $session_memcache_raw = $this->memcache->get($session_id, \MEMCACHE_COMPRESSED);
        $this->assertInternalType(
            'string',
            $session_memcache_raw,
            sprintf("Memcache did not give us a string (session: %s): %s",
                $session_id,
                gettype($session_memcache_raw)
            )
        );

        $session_memcache = Helper::decode($session_memcache_raw);

        $this->assertArrayHasKey('foo', $session_memcache, "'foo' was not found in stored session.");
        $this->assertEquals('bar', $session_memcache['foo'], "'foo' inside the session is not 'bar'");

        /**
         * @var $newDb \Mysqli A new DB connection because the other won't be in sync!
         */
        $newDb           = $this->setUpDb();
        //$dbWrapper
        $session_sql_raw = $newDb->fetchOne(
            sprintf("SELECT session_data FROM %s WHERE session_id = %s",
                'session2',
                $newDb->quote($session_id)
            )
        );

        $this->assertSame($session_memcache_raw, $session_sql_raw, "Memcache and SQL value are not equal.");

        $session_sql = Helper::decode($session_sql_raw);

        $this->assertArrayHasKey('foo', $session_sql, "DB result does not have key 'foo'");
        $this->assertEquals('bar', $session_sql['foo'], "'foo' (from SQL) is not 'bar'");

        $this->assertEquals($session_memcache, $session_sql);
    }
}