<?php
namespace Lagged\Test\Session\SaveHandler;

use Lagged\Session\SaveHandler\Memcache as MemcacheSession;
use Lagged\Session\Helper;

/**
 * This is an integration test suite.
 *
 * @category Testing
 * @package  Lagged\Session\SaveHandler\Memcache
 * @author   Till Klampaeckel <till@php.net>
 *
 */
class MemcacheNoPersistenceTestCase extends AbstractTestCase
{
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
        $db             = null;
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
}
