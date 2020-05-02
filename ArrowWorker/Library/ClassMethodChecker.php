<?php
/**
 * By yubin at 2020-03-22 11:30.
 */

namespace ArrowWorker\Library;


class ClassMethodChecker
{

    public static function IsClassMethodExists(string $class, string $method): bool
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