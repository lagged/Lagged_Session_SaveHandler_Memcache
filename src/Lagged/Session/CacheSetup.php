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
 * @subpackage Lagged\Session\CacheSetup
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    GIT: $Id$
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
namespace Lagged\Session;

use Lagged\Session\BaseAbstract;

/**
 * Pre-warm our cache.
 *
 * @category   Session
 * @package    Lagged\Session
 * @subpackage Lagged\Session\CacheSetup
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    Release: @package_version@
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
class CacheSetup extends BaseAbstract
{
    /**
     * Populate our memcache with some data!
     *
     * @throws \RuntimeException
     */
    public function warm()
    {
        $sql    = sprintf("SELECT count(*) AS total FROM %s", $this->table);
        $result = $this->db->query($sql);
        if (false === $result) {
            throw new \RuntimeException(sprintf(
                "Could not gather the total in '%s': %s",
                $this->table,
                $this->db->error
            ));
        }

        /* @var $result \mysqli_result */
        $row = $result->fetch_object();
        if ($result->num_rows == 0) {
            return;
        }

        $total = (int) $row->total;

        $from  = 0;
        $limit = 100;

        while ($from <= $total) {

            $sql = sprintf(
                "SELECT session_id, session_date, rec_datemod FROM `%s` LIMIT %d, %d",
                $this->table,
                $from,
                $limit
            );

            $result = $this->db->query($sql);
            if (false === $result) {
                throw new \RuntimeException(sprintf("SQL error: %s", $this->db->error));
            }
            if ($result->num_rows == 0) {
                break;
            }
            while ($row = $result->fetch_object()) {
                $status = $this->memcache->set($row->session_id, $row->session_data);
                if (false === $status) {
                    throw new \RuntimeException("Could not save to memcache.");
                }
            }
            $from += $limit;
        }
    }
}