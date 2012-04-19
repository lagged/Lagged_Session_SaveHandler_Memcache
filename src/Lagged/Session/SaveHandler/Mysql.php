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
 * @subpackage Lagged\Session\SaveHandler\Mysql
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    Release: @package_version@
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
class Mysql extends BaseAbstract implements \Zend_Session_SaveHandler_Interface
{
    /**
     * @param \Zend_Db_Adapter_Mysqli $db
     *
     * @return $this
     */
    public function __construct(\Zend_Db_Adapter_Mysqli $db)
    {
        $this->db = new MysqlWrapper($db->getConnection());
    }

    /**
     * Read the session data.
     *
     * @param string $id
     *
     * @return string
     */
    public function read($id)
    {
        $session_data = $this->db->find($id);
        if (false === $session_data) {
            $this->debug(sprintf("MySQL error: '%s', session: '%s'", $this->db->getError(), $id));
            return '';
        }
        if (empty($session_data)) {
            $this->debug(sprintf("No session '%s' in MySQL.", $id));
        } else {
            $this->debug(sprintf("Found session '%s' in MySQL", $id));
        }
        return $session_data;
    }

    /**
     * Write session data.
     *
     * @param string $id
     * @param string $data
     *
     * @return void
     */
    public function write ($id, $data)
    {
        $user   = $this->getUserId($data);
        $status = $this->db->save($id, $data, $user);
        if (false === $status) {
            $this->debug(sprintf("Failed writing session '%s' to MySQL: %s", $id, $this->db->getError()));
        }
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
        $status = $this->db->destroy($id);
        if (false === $status) {
            $this->debug(sprintf("Failed deleting session '%s' from MySQL.", $id));
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
