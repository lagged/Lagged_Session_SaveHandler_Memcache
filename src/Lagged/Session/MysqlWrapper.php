<?php
/**
 * Till Klampaeckel, Copyright 2012
 *
 * This code is BSD licensed. I'll add a complete header when I find time.
 *
 * PHP Version 5.3
 *
 * @category Database
 * @package  Lagged\Session
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version  GIT: $Id$
 * @link     http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
namespace Lagged\Session;


/**
 * A wrapper around all necessary MySQL calls.
 *
 * @category Database
 * @package  Lagged\Session
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version  GIT: $Id$
 * @link     http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
class MysqlWrapper
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The table name!
     * @var string
     */
    protected $table;

    /**
     * @param \Zend_Db_Adapter_Abstract $db
     * @param string                    $table
     *
     * @return \Lagged\Session\MysqlWrapper
     */
    public function __construct(\Zend_Db_Adapter_Abstract $db, $table = 'session2')
    {
        $this->db    = $db;
        $this->table = $table;
    }

    /**
     * Initializes the connection.
     *
     * @return \mysqli
     * @throws \RuntimeException
     */
    protected function connect()
    {
        try {
            return $this->db->getConnection();
        } catch (Zend_Db_Exception $e) {
            throw new \RuntimeException("Could not connect to database.", null, $e);
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id)
    {
        $db = $this->connect();

        $sql = sprintf(
            "DELETE FROM %s WHERE session_id = %s",
            $this->table,
            $db->real_escape_string($id)
        );
        $status = $this->query($sql);
        return $status;
    }

    /**
     * @param string $id
     *
     * @return bool|string
     */
    public function find($id)
    {
        $db = $this->connect();

        $sql = sprintf(
            "SELECT session_data FROM `%s` WHERE session_id = '%s'",
            $this->table,
            $db->real_escape_string($id)
        );
        $res = $this->query($sql);
        if (false === $res) {
            return false;
        }
        /* @var $res \Mysqli_Result */
        if ($res->num_rows == 0) {
            return '';
        }
        while ($row = $res->fetch_object()) {
            $session_data = $row->session_data;
            $res->close();
            return $session_data;
        }
    }

    /**
     * @return string
     */
    public function getError()
    {
        $db = $this->connect();
        return $db->error;
    }

    /**
     * @param string $id
     * @param string $data
     * @param mixed  $user
     *
     * @return mixed
     */
    public function save($id, $data, $user)
    {
        $db = $this->connect();

        $session_id   = $db->real_escape_string($id);
        $session_data = $db->real_escape_string($data);
        $user_id      = $db->real_escape_string($user);

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
        return $status;
    }

    /**
     * Run the MySQL query, by default asynchronously.
     *
     * @param string $sql
     *
     * @return mixed
     */
    protected function query($sql)
    {
        $db = $this->connect();

        if (substr($sql, 0, 6) == 'SELECT') {
            $mode = \MYSQLI_STORE_RESULT;
        } else {
            $mode = \MYSQLI_ASYNC;
        }
        $result = $db->query($sql, $mode);
        return $result;
    }

    /**
     * @param string $table
     *
     * @return MysqlWrapper
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
}
