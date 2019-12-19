<?php

namespace ArrowWorker;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{
	const MODULE_NAME = 'Config';

    const ENV_DEV = 'Dev';

    const ENV_TEST = 'Test';

    const ENV_PRODUCTION = 'Production';

    const EXT = '.php';

    /**
     * @var Config
     */
    private static $_instance;

    private $_env = 'Dev';

    /**
     * 配置文件路径
     * @var string
     */
    private $_path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_DIR . DIRECTORY_SEPARATOR;

    /**
     * 配置文件记录
     * @var array
     */
    private $_configMap = [];


    private function __construct(string $env)
    {
        $this->_env  = in_array( $env, [
            self::ENV_DEV,
            self::ENV_TEST,
            self::ENV_PRODUCTION,
        ] ) ? $env : self::ENV_DEV;
        $this->_path = $this->_path . $this->_env . DIRECTORY_SEPARATOR;
    }


    /**
     * @param string $env
     */
    public static function Init( string $env)
    {
        self::$_instance = new self($env);
    }

    /**
     * @param string $name
     * @return bool|mixed
     * @author Louis
     */
    public static function Get( string $name = APP_CONFIG_FILE )
    {
        return self::$_instance->_get($name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function _get( string $name)
    {
        if ( isset( $this->_configMap[ $name ] ) )
        {
            return $this->_configMap[ $name ];
        }
        return $this->_load( $name );
    }

    /**
     * Load
     * @param string $name
     * @return mixed
     * @author Louis
     */
    private function _load( string $name )
    {
        $path = $this->_path . $name . self::EXT;
        if ( !file_exists( $path ) )
        {
            Log::Dump( "file : {$path} not found.", Log::TYPE_WARNING, self::MODULE_NAME );
            return false;
        }
        $this->_configMap[ $name ] = require( $path );
        return $this->_configMap[ $name ];
    }

}
