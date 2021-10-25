<?php
/**
 * By yubin at 2020-03-22 11:30.
 */

namespace ArrowWorker\Library;


/**
 * Class ClassMethodChecker
 * @package ArrowWorker\Library
 */
class ClassMethodChecker
{

    /**
     * @param string $class
     * @param string $method
     * @return bool
     */
    public static function isClassMethodExists(string $class, string $method): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        if (!method_exists($class, $method)) {
            return false;
        }
        return true;
    }
}