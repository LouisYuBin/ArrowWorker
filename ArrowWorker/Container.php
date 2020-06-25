<?php

namespace ArrowWorker;

/**
 * Class Container
 * @package ArrowWorker
 */
class Container
{
    private static $instance;

    private $alias = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public function Has(string $name)
    {
        return isset($this->alias[$name]);
    }

    public function Get(string $name, array $parameters = [])
    {
        if (isset($this->alias[$name])) {
            return $this->alias[$name];
        }
        $this->alias[$name] = $instance = $this->Make($name, $parameters);
        return $instance;
    }

    public function Set(string $name, $value)
    {
        $this->alias[$name] = $value;
        return $value;
    }

    public function Make(string $class, array $parameters = [])
    {
        return class_exists($class) ? new $class(...$parameters) : null;
    }

    public static function GetInstance()
    {
        return self::$instance;
    }

}
