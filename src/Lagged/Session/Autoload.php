<?php
namespace Lagged\Session;

/**
 * An Autoloader when this is installed via PEAR.
 *
 * @category   Autoload
 * @package    Lagged\Session
 * @subpackage Lagged\Session\Autoload
 * @author     Till Klampaeckel <till@lagged.biz>
 * @version    Release: @package_version@
 * @license
 * @link
 */
class Autoload
{
    /**
     * The base dir to include from.
     * @var mixed
     */
    static $base;

    /**
     * Registered? Should be 'true' eventually.
     * @var mixed
     */
    static $registered;

    /**
     * Load a class. Works only for Lagged\Session\*
     *
     * @param string $className
     *
     * @return boolean
     */
    public function autoload($className)
    {
        if (0 !== strpos($className, 'Lagged\Session')) {
            return false;
        }
        if (null === self::$base) {
            self::$base = dirname(dirname(__DIR__));
        }
        $fileName = str_replace('\\', '/', $className) . '.php';
        return include self::$base . '/' . $fileName;
    }

    /**
     * Register the autoloader.
     *
     * @param boolean $prepend Prepend the autoloader to the stack.
     *
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public static function register($prepend = false)
    {
        if (!is_bool($prepend)) {
            throw new \InvalidArgumentException("Parameter must be boolean.");
        }
        if (null === self::$registered) {
            $loader = new self;
            self::$registered = true;
            return spl_autoload_register(array($loader, 'autoload'), false, $prepend);
        }
        return false;
    }
}
