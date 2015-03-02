<?php

namespace Lovoo\Component\Tracedump;

/**
 * @author David Wolter <david@dampfer.net>
 */
class Tracedump
{
    /**
     * @return bool
     */
    public static function isCli()
    {
        return 'cli' === PHP_SAPI || isset($_SERVER["HTTP_BEHAT"]);
    }

    public static function tracedump()
    {
        if (self::isCli()) {
            $class = new Cli();
        } else {
            $class = new Html();
        }

        return call_user_func_array(array($class, 'tracedump'), func_get_args());
    }
}
