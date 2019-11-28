<?php

namespace ArrowWorker;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{

    const LOG_NAME = 'Config';

    const ENV_DEV = 'Dev';

    const ENV_TEST = 'Test';

    const ENV_PRODUCTION = 'Production';

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

    /**
     * 配置文件后缀
     * @var array
     */
    private $_configExt = '.php';


    private function __construct()
    {
        $env         = ucfirst(Console::Init()->GetEnv());
        $this->_env  = in_array( $env, [
            self::ENV_DEV,
            self::ENV_TEST,
            self::ENV_PRODUCTION,
        ] ) ? $env : self::ENV_DEV;
        $this->_path = $this->_path . $this->_env . DIRECTORY_SEPARATOR;
    }

    /**
     * Init
     * @param string $subPath
     * @return string
     * @author Louis
     */
    private function _getPath( string $subPath = '' )
    {
        if ( empty( $subPath ) )
        {
            return $this->_path;
        }

        return $this->_path . $subPath . DIRECTORY_SEPARATOR;
    }

    /**
     * Get
     * @param string $configName
     * @return bool|mixed
     * @author Louis
     */
    public static function Get( string $configName = APP_CONFIG_FILE )
    {
        if ( self::$_instance instanceof Config )
        {
            goto _RETURN;
        }

        self::$_instance = new self();

        _RETURN:
        return self::$_instance->_getConfig( $configName );
    }

    public static function SetEnv( string $env = self::ENV_DEV )
    {
        $env        = ucfirst( $env );
        self::$_env = !in_array( $env, [
            self::ENV_DEV,
            self::ENV_TEST,
            self::ENV_PRODUCTION,
        ] ) ? $env : self::ENV_DEV;
    }

    private function _getConfig( string $configName )
    {
        if ( isset( $this->_configMap[ $configName ] ) )
        {
            return $this->_configMap[ $configName ];
        }
        return $this->_load( $configName );
    }

    /**
     * Load
     * @param string $configName
     * @param string $subPath
     * @return mixed
     * @author Louis
     */
    private function _load( string $configName, string $subPath = '' )
    {
        $configPath = $this->_getPath( $subPath ) . $configName . $this->_configExt;
        if ( !file_exists( $configPath ) )
        {
            Log::Error( "Config File : {$configPath} does not exists.", self::LOG_NAME );
            return false;
        }
        $this->_configMap[ $configName ] = require( $configPath );
        return $this->_configMap[ $configName ];
    }

}
