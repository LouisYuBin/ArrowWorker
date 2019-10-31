<?php
/**
 * User: Louis
 * Date: 2016/8/3 12:02
 * Update Records:
 *      2017-07-24 by Louis
 */

namespace ArrowWorker;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{

    const LOG_NAME = 'Config';

    /**
     * 配置文件路径
     * @var string
     */
    private static $path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_DIR . DIRECTORY_SEPARATOR . ENV . DIRECTORY_SEPARATOR;

    /**
     * 配置文件记录
     * @var array
     */
    private static $configMap = [];

    /**
     * 配置文件后缀
     * @var array
     */
    private static $configExt = '.php';

    /**
     * Init
     * @author Louis
     * @param string $subPath
     * @return string
     */
    private static function _getPath( string $subPath = '' )
    {
        if ( empty( $configFilePath ) )
        {
            return self::$path;
        }

        return self::$path . $subPath . DIRECTORY_SEPARATOR;
    }

    /**
     * Get
     * @author Louis
     * @param string $configName
     * @return bool|mixed
     */
    public static function Get( string $configName = APP_CONFIG_FILE )
    {
        if ( isset( self::$configMap[$configName] ) )
        {
            return self::$configMap[$configName];
        }
        return self::Load( $configName );
    }

    /**
     * Load
     * @author Louis
     * @param string $configName
     * @param string $subPath
     * @return mixed
     */
    private static function Load( string $configName, string $subPath = '' )
    {
        $pathName = self::_getPath( $subPath ) . $configName . self::$configExt;
        if ( !file_exists( $pathName ) )
        {
            Log::Error( "Config File : {$pathName} does not exists.", self::LOG_NAME );
            return false;
        }
        self::$configMap[$configName] = require($pathName);
        return self::$configMap[$configName];
    }

}
