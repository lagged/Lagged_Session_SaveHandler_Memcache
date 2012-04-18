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
 * @subpackage Lagged\Session\Helper
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    GIT: $Id$
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
namespace Lagged\Session;

/**
 * Helper
 *
 * @category   Session
 * @package    Lagged\Session
 * @subpackage Lagged\Session\Helper              
 * @author     Till Klampaeckel <till@php.net>
 * @license    New BSD License http://www.opensource.org/licenses/bsd-license.php
 * @version    Release: @package_version@
 * @link       http://github.com/lagged/Lagged_Zend_Session_SaveHandler_Memcache
 */
class Helper
{
    /**
     * Decode the raw session data from PHP to extract the user's ID.
     *
     * Inspiration from http://php.net/session_decode
     *
     * @param string $data
     *
     * @return array
     */
    public static function decode($data)
    {
        $vars = preg_split(
            '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/',
            $data,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        $result = array();
        for ($i=0; $i<=(count($vars)/2); $i=$i+2) {
            if (!isset($vars[$i])) {
                continue;
            }
            $key = $vars[$i];
            $val = $vars[$i+1];

            $result[$key] = unserialize($val);
        }
        return $result;
    }
}
