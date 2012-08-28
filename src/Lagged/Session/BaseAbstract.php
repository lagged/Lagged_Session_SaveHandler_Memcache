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

use Lagged\Session\SaveHandler\Memcache;
use Lagged\Session\MysqlWrapper;

/**
 * A base class for the session handlers.
 *
 * @property-write int     $compression Memcache compression, only applicable for the Memcache session handler.
 * @property-write boolean $debug       Noisy logs are noisy.
 * @property-write int     $expire      The expiration of the session.
 * @property-write string  $sessionName The name of the session (see {@link self::getUserId()}).
 * @property-write string  $table       The name of the session table in MySQL.
 * @property-write boolean $testing     Throw exceptions.
 *
 * @category Session
 * @package  Lagged\Session
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version  GIT: $Id$
 * @link     http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
abstract class BaseAbstract
{
    /**
     * Database connection.
     * @var MysqlWrapper
     */
    protected $db;

    /**
     * Debug, yes or no?
     * @var boolean
     */
    protected $debug;

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
     * This is the name: new Zend_Auth_Storage_Session('ezSession')
     * @var string
     */
    protected $sessionName = 'ezSession';

    /**
     * Allows us to be super-verbose in the testsuite!
     * @var bool
     */
    protected $testing = false;

    /**
     * Bootstrap this.
     *
     * @param \memcache                 $memcache
     * @param \Zend_Db_Adapter_Abstract $db
     * @param boolean                   $debug
     *
     * @return \Lagged\Session\BaseAbstract
     * @throws \InvalidArgumentException
     */
    public function __construct(\memcache $memcache, \Zend_Db_Adapter_Abstract $db, $debug = false)
    {
        $this->memcache = $memcache;
        $this->db       = new MysqlWrapper($db);
        if (!is_bool($debug)) {
            throw new \InvalidArgumentException("'debug' must be boolean.");
        }
        $this->debug = $debug;
    }

    /**
     * Overwrite class variables!
     *
     * @param string $var
     * @param string $value
     *
     * @return $this
     * @throws \LogicException           When a value cannot be set.
     * @throws \InvalidArgumentException When a value is invalid (duh!).
     * @throws \RuntimeException         On attempt to overwrite a value we don't allow.
     */
    public function __set($var, $value)
    {
        switch ($var) {
        case 'compression':
            if (!($this instanceof Memcache)) {
                throw new \LogicException("This value is only required with Memcache.");
            }
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
            $this->db->setTable($value);
            break;
        case 'testing':
            if (!is_bool($value)) {
                throw new \InvalidArgumentException("Testing can be either 'true' or 'false'");
            }
            $this->testing = $value;
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
     * Only log when {@link self::$debug} is 'true'.
     *
     * @param string $message
     *
     * @return void
     */
    protected function debug($message)
    {
        if (true === $this->debug) {
            $this->log($message);
        }
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
                $userId = $session[$this->sessionName]['storage']['id'];
            }
        }
        return $userId;
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
        $logger->log($message, \Zend_Log::DEBUG);
    }
}
