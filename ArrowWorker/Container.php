<?php

namespace ArrowWorker;

/**
 * Class Container
 * @package ArrowWorker
 */
class Container
{
    /**
     * @var Container
     */
    private static $instance;

    /**
     * @var array
     */
    private $alias = [];

    /**
     * Container constructor.
     */
    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function Has(string $name):bool
    {
        return isset($this->alias[$name]);
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return mixed|null
     */
    public function Get(string $name, array $parameters = [])
    {
        if (isset($this->alias[$name])) {
            return $this->alias[$name];
        }
        $this->alias[$name] = $instance = $this->Make($name, $parameters);
        return $instance;
    }

    /**
     * @param string $name
     * @param $value
     * @return mixed
     */
    public function Set(string $name, $value)
    {
        $this->alias[$name] = $value;
        return $value;
    }

    /**
     * @param string $class
     * @param array $parameters
     * @return mixed
     */
    public function Make(string $class, array $parameters = [])
    {
        return class_exists($class) ? new $class(...$parameters) : null;
    }

    /**
     * @return Container
     */
    public static function GetInstance():self
    {
        return self::$instance;
    }

}
