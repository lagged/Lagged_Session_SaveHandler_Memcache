<?php
namespace Lagged\Zend\Session\SaveHandler;

class Memcache implements \Zend_Session_SaveHandler_Interface
{

    private $cache = null;

    public function __construct()
    {

    }

    public function read ($id)
    {

    }

    public function write ($id, $data)
    {

    }

    public function open ($save_path, $name)
    {
        return true;
    }

    public function close ()
    {
        // free memcache, mysql
        return true;
    }

    public function destroy ($id)
    {
    }

    public function gc ($maxlifetime)
    {
        return true;
    }
}

