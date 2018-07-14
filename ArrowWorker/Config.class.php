<?php
/**
 * User: Louis
 * Date: 2016/8/3 12:02
 * Update Records:
 *      2017-07-24 by Louis
 */

namespace ArrowWorker;
use function PHPSTORM_META\type;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{
    /**
     * app class map file
     * @var string
     */
    public static $AppFileMap  = 'alias';

    /**
     * 配置文件路径
     * @var string
     */
    private static $path          = '';

    /**
     * 配置文件记录
     * @var array
     */
    private static $configFileMap = [];

    /**
     * 配置文件后缀
     * @var array
     */
    private static $configExt     = '.php';

    /**
     * Init
     * @author Louis
     * @param string $configFilePath
     */
    public static function Init(string $configFilePath='')
    {
        if( empty(self::$path) && empty($configFilePath) )
        {
            self::$path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_DIR . DIRECTORY_SEPARATOR;
        }
        else if( !empty($configFilePath) )
        {
            self::$path = $configFilePath;
        }
    }

    /**
     * Get
     * @author Louis
     * @param string $configFileName
     * @return bool|mixed
     */
    public static function Get(string $configFileName=APP_CONFIG_FILE )
    {
        try
        {
            return self::Load( $configFileName );
        }
        catch (\Exception $e)
        {
            Log::Error($e->getMessage());
            return false;
        }
    }

    /**
     * Load
     * @author Louis
     * @param string $fileName
     * @param string $filePath
     * @return mixed
     * @throws \Exception
     */
    public static function Load(string $fileName, string $filePath='' )
    {
        if( isset( self::$configFileMap[$fileName] ) )
        {
            return self::$configFileMap[$fileName];
        }

        static::Init($filePath);

        $configFile = self::$path.$fileName.self::$configExt;
        if( !file_exists($configFile) )
        {
            throw new \Exception( "Config File : {$configFile} does not exists.");
        }
        self::$configFileMap[$fileName] = require( $configFile );
        return self::$configFileMap[$fileName];
    }

}
