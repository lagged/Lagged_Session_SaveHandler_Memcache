<?php
/**
 * Till Klampaeckel, Copyright 2012
 *
 * This code is BSD licensed. I'll add a complete header when I find time.
 *
 * PHP Version 5.3
 *
 * @category   Session
 * @package    Lagged\Session
 * @subpackage Lagged\Session\SaveHandler\Memcache
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    GIT: $Id$
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
namespace Lagged\Session\SaveHandler;

use Lagged\Session\BaseAbstract;
use Lagged\Session\Helper;
use Lagged\Session\MysqlWrapper;

/**
 * @category   Session
 * @package    Lagged\Session
 * @subpackage Lagged\Session\SaveHandler\Memcache
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    Release: @package_version@
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
class Memcache extends BaseAbstract implements \Zend_Session_SaveHandler_Interface
{
    /**
     * Compression? Yes? If not: 0.
     * @var int
     */
    protected $compression = \MEMCACHE_COMPRESSED;

    /**
     * Read the session data.
     *
     * @param string $id
     *
     * @return string
     */
    public function read($id)
    {
        $session = $this->memcache->get($id, $this->compression);
        if (false !== $session) {
            $this->debug(sprintf("Found session '%s' in memcache.", $id));
            return $session;
        }

        $session_data = $this->db->find($id);
        if (false === $session_data) {
            // db error
            $this->debug(sprintf("MySQL error: '%s', session: '%s'", $this->db->error, $id));
            return '';
        }
        if (empty($session_data)) {
            $this->debug(sprintf("No session '%s' in MySQL.", $id));
            return '';
        }

        $this->debug(sprintf("Found session '%s' in MySQL", $id));

        $this->memcache->set($id, $session_data, $this->compression, $this->expire);
        $this->debug(sprintf("Saved session '%s' to memcache.", $id));

        return $session_data;
    }

    /**
     * Write session data.
     *
     * @param string $id
     * @param string $data
     *
     * @return bool
     */
    public function write ($id, $data)
    {
        $this->debug(sprintf("write(): ID: %s Data: %s", $id, $data));
        if (false === ($this->memcache->replace($id, $data, $this->compression, $this->expire))) {
            $status = $this->memcache->set($id, $data, $this->compression, $this->expire);
            if (false === $status) {
                $msg = sprintf("Memcache::set() failed: '%s'", $id);
                if (true === $this->testing) {
                    throw new \RuntimeException($msg);
                }
                $this->debug($msg);
            } else {
                $this->debug(sprintf("Memcache::set() success: '%s'", $id));
            }
        } else {
            $this->debug(sprintf("Memcache::replace() success: '%s'", $id));
        }

        $user   = $this->getUserId($data);
        $status = $this->db->save($id, $data, $user);
        if (false === $status) {
            $msg = sprintf("Failed writing session '%s' to MySQL: %s", $id, $this->db->getError());
            if (true === $this->testing) {
                throw new \RuntimeException($msg);
            }
            $this->debug($msg);
            return false;
        }
        return true;
    }

    /**
     * Not used.
     *
     * @param string $save_path
     * @param string $name
     *
     * @return bool
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Not used.
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Delete the session!
     *
     * @param string $id
     *
     * @return void
     */
    public function destroy($id)
    {
        $this->memcache->delete($id);
        $this->debug(sprintf("Deleted session '%s' from Memcache.", $id));

        $status = $this->db->destroy($id);
        if (false === $status) {
            $msg = sprintf("Failed deleting session '%s' from MySQL.", $id);
            if (true === $this->testing) {
                throw new \RuntimeException($msg);
            }
            $this->debug($msg);
            return;
        }
        $this->debug(sprintf("Deleted session '%s' from MySQL", $id));
    }

    /**
     * Not used. We do it asynchronously!
     *
     * @param $maxlifetime
     *
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}