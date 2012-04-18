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
     * This is the name: new Zend_Auth_Storage_Session('ezSession')
     * @var string
     */
    protected $sessionName = 'ezSession';

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
        $sql = sprintf(
            "SELECT session_data FROM `%s` WHERE session_id = '%s'",
            $this->table,
            $this->db->real_escape_string($id)
        );
        $res = $this->query($sql);
        if (false === $res) {
            // db error
            $this->debug(sprintf("MySQL error: '%s', session: '%s'", $this->db->error, $id));
            return '';
        }
        if ($res->num_rows == 0) {
            $this->debug(sprintf("No session '%s' in MySQL.", $id));
            return '';
        }
        while ($row = $res->fetch_object()) {
            $session_data = $row->session_data;
            break;
        }
        $this->debug(sprintf("Found session '%s' in MySQL", $id));

        $res->close();
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
     * @return void
     */
    public function write ($id, $data)
    {
        if (false === ($this->memcache->replace($id, $data, $this->compression, $this->expire))) {
            $this->memcache->set($id, $data, $this->compression, $this->expire);
            $this->debug(sprintf("Replaced session '%s' in Memcache", $id));
        }

        $session_id   = $this->db->real_escape_string($id);
        $session_data = $this->db->real_escape_string($data);

        $user_id = $this->getUserId($data);

        $sql  = sprintf("INSERT INTO `%s` (", $this->table);
        $sql .= " session_id, session_data, user_id, rec_dateadd, rec_datemod";
        $sql .= " )";
        $sql .= " VALUES(";
        $sql .= sprintf(" '%s', '%s', '%s', NOW(), NOW()",
            $session_id,
            $session_data,
            $user_id
        );
        $sql .= " )";
        $sql .= " ON DUPLICATE KEY UPDATE";
        $sql .= sprintf(" session_data = '%s',", $session_data);
        $sql .= sprintf(" user_id = %s,", $user_id);
        $sql .= " rec_datemod = NOW()";

        $status = $this->query($sql);
        if (false === $status) {
            $this->debug(sprintf("Failed writing session '%s' to MySQL: %s", $id, $this->db->error));
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
        $this->memcache->delete($id);
        $this->debug(sprintf("Deleted session '%s' from Memcache.", $id));

        $sql = sprintf(
            "DELETE FROM %s WHERE session_id = %s",
            $this->table,
            $this->db->real_escape_string($id)
        );
        $status = $this->query($sql);
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

    /**
     * This extracts the user's ID from our session.
     *
     * You can obviously overwrite this by extending in case you need something else!
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function getUserId($data)
    {
        $session = Helper::decode($data);
        $userId  = 'NULL';
        if (isset($session[$this->sessionName])) {
            if (isset($session[$this->sessionName]['storage'])) {
                $userId = $this->db->real_escape_string($session[$this->sessionName]['storage']['id']);
            }
        }
        return $userId;
    }
}
