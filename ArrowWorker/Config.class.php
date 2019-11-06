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
    private static $_path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_DIR . DIRECTORY_SEPARATOR . ENV . DIRECTORY_SEPARATOR;

    /**
     * 配置文件记录
     * @var array
     */
    private static $_configMap = [];

    /**
     * 配置文件后缀
     * @var array
     */
    private static $_configExt = '.php';

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
            return self::$_path;
        }

        return self::$_path . $subPath . DIRECTORY_SEPARATOR;
    }

    /**
     * Get
     * @author Louis
     * @param string $configName
     * @return bool|mixed
     */
    public static function Get( string $configName = APP_CONFIG_FILE )
    {
        if ( isset( self::$_configMap[$configName] ) )
        {
            return self::$_configMap[$configName];
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
        $_pathName = self::_getPath( $subPath ) . $configName . self::$_configExt;
        if ( !file_exists( $_pathName ) )
        {
            Log::Error( "Config File : {$_pathName} does not exists.", self::LOG_NAME );
            return false;
        }
        self::$_configMap[$configName] = require($_pathName);
        return self::$_configMap[$configName];
    }

}
