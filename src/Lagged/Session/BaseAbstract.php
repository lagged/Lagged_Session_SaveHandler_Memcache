<?php
/**
 * Till Klampaeckel, Copyright 2012
 *
 * This code is BSD licensed. I'll add a complete header when I find time.
 *
 * PHP Version 5.3
 *
 * @category Session
 * @package  Lagged\Zend\Session
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version  GIT: $Id$
 * @link     http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
namespace Lagged\Session;

/**
 *
 */
abstract class BaseAbstract
{
    /**
     * Compression? Yes? If not: 0.
     * @var int
     */
    protected $compression = \MEMCACHE_COMPRESSED;

    /**
     * Database connection.
     * @var \mysqli
     */
    protected $db;

    /**
     * The expiration time: 7 days.
     * @var int
     */
    protected $expire = 604800;

    /**
     * A memcache object/resource.
     * @var \memcache
     */
    protected $memcache;

    /**
     * @var string
     */
    protected $table = 'session2';

    /**
     * Bootstrap this.
     *
     * @param \memcache                 $memcache
     * @param \Zend_Db_Adapter_Abstract $db
     *
     * @return $this
     */
    public function __construct(\memcache $memcache, \Zend_Db_Adapter_Abstract $db)
    {
        $this->memcache = $memcache;
        $this->db       = $db->getConnection();
    }

    /**
     * Overwrite class variables!
     *
     * @param string $var
     * @param string $value
     *
     * @return $this
     * @throws \RuntimeException On attempt to overwrite a value we don't allow.
     */
    public function __set($var, $value)
    {
        switch ($var) {
            case 'compression':
                if ($value !== 0 && $value !== \MEMCACHE_COMPRESSED) {
                    throw new \InvalidArgumentException("Illegal compression value.");
                }
                $this->compression = $value;
                break;
            case 'expire':
                if (!is_int($value)) {
                    throw new \InvalidArgumentException("The expiration value has to be an integer.");
                }
                if ($value > 2592000) {
                    throw new \InvalidArgumentException("The expiration value cannot exceed 30 days.");
                }
                $this->expire = $value;
                break;
            case 'table':
                $this->table = $value;
                break;
            case 'sessionName':
                $this->sessionName = $value;
                break;
            default:
                throw new \RuntimeException(sprintf("You cannot set '%s'.", $var));
        }
        return $this;
    }

    /**
     * Build a logger if required.
     *
     * @param string $message
     *
     * @return void
     */
    protected function log($message)
    {
        static $logger;
        if (null === $logger) {
            $writer = new \Zend_Log_Writer_Syslog(array('application' => __CLASS__));
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
        }
        $logger->debug($message);
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
        if (substr($sql, 0, 6) == 'SELECT') {
            $mode = \MYSQLI_STORE_RESULT;
        } else {
            $mode = \MYSQLI_ASYNC;
        }
        $result = $this->db->query($sql, $mode);
        if (false === $result) {
            $message = "Query: %s, Error: %s";
            $this->log(sprintf($message, $sql, $this->db->error));
        }
        return $result;
    }
}