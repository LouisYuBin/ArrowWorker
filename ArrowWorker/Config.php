<?php

namespace ArrowWorker;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{

    const ENV_DEV = 'Dev';

    const ENV_TEST = 'Test';

    const ENV_PRODUCTION = 'Production';

    const MODULE_NAME = 'Config';

    const EXT = '.php';

    /**
     * @var string
     */
    private $env = 'Dev';

    /**
     * @var string
     */
    private $path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_DIR . DIRECTORY_SEPARATOR;

    /**
     * 配置文件记录
     * @var array
     */
    private $config = [];

    private static $instance;


    /**
     * Config constructor.
     * @param string $env
     */
    public function __construct(string $env)
    {
        $this->env = in_array($env, [
            self::ENV_DEV,
            self::ENV_TEST,
            self::ENV_PRODUCTION,
        ]) ? $env : self::ENV_DEV;
        $this->path = $this->path . $this->env . DIRECTORY_SEPARATOR;
        self::$instance = $this;
        $this->load();
    }


    /**
     * @param string $name
     * @return bool|mixed
     */
    public static function Get(string $name = APP_CONFIG_FILE)
    {
        return self::$instance->_get($name);
    }

    /**
     * @param string $name
     * @param        $value
     * @return mixed
     */
    public static function Set(string $name, $value)
    {
        self::$instance->_set($name, $value);
        return $value;
    }

    /**
     * @return Config
     */
    public static function GetInstance()
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function _get(string $name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return false;
    }

    /**
     * @param string $name
     * @param        $value
     */
    private function _set(string $name, $value)
    {
        $this->config[$name] = $value;
    }

    private function load(string $path)
    {
        $files = scandir($path);
        if (false === $files) {
            return;
        }

        foreach ($files as $fileName) {
            $filePath = $path . $fileName;
            if (is_file($filePath)) {
                $configName = substr($fileName, 0, strrpos($fileName, '.'));
                $this->config[$configName] = require($filePath);
            }

            if (is_dir($filePath)) {
                $this->load($filePath . DIRECTORY_SEPARATOR);
            }
        }
    }

}
