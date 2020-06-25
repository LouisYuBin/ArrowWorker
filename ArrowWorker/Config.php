<?php /** @noinspection AccessModifierPresentedInspection */

namespace ArrowWorker;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{

    /**
     *
     */
    protected const EXT = '.php';

    /**
     * @var array
     */
    protected $validateEnvironment = [
        Environment::TYPE_DEV,
        Environment::TYPE_TEST,
        Environment::TYPE_PRODUCTION,
    ];

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

    /**
     * @var Config
     */
    private static $instance;


    /**
     * Config constructor.
     * @param string $env
     */
    public function __construct()
    {
        $this->path     .= Environment::getType() . DIRECTORY_SEPARATOR;
        self::$instance = $this;
        $this->load($this->path);
    }


    /**
     * @param string $name
     * @return bool|mixed
     */
    public static function Get(string $name = APP_CONFIG_FILE)
    {
        return self::$instance->getConfig($name);
    }

    /**
     * @param string $name
     * @param        $value
     * @return mixed
     */
    public static function Set(string $name, $value)
    {
        self::$instance->setConfig($name, $value);
        return $value;
    }

    /**
     * @return Config
     */
    public static function GetInstance(): Config
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getConfig(string $name)
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
    private function setConfig(string $name, $value): void
    {
        $this->config[$name] = $value;
    }

    /**
     * @param string $path
     */
    private function load(string $path): void
    {
        $files = scandir($path);

        if (false === $files) {
            return;
        }

        foreach ($files as $fileName) {
            if(in_array($fileName, ['.','..'])) {
                continue;
            }
            $filePath = $path . $fileName;

            if (is_dir($filePath)) {
                $this->load($filePath . DIRECTORY_SEPARATOR);
            }

            if (is_file($filePath)) {
                $configName                = substr($fileName, 0, strrpos($fileName, '.'));
                $this->config[$configName] = require($filePath);
            }

        }
    }

}
