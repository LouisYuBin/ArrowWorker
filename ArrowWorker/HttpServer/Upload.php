<?php
/**
 * User: louis
 * Time: 18-5-11 上午10:34
 */

namespace ArrowWorker\HttpServer;

use ArrowWorker\Config;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Log\Log;

/**
 * Class Upload
 * @package ArrowWorker
 */

/**
 * Class Upload
 * @package ArrowWorker
 */
class Upload
{
    /**
     * @var array $config
     */
    private static array $config = [
        'savePath'  => APP_PATH . '/Runtime/Upload/',
        'extension' => [
            'jpg',
            'png',
            'jpeg',
            'rar',
            'zip'
        ]
    ];

    /**
     * @var bool
     */
    private static bool $isConfigInitialized = false;

    /**
     * file : file information
     * @var array
     */
    private array $file;

    /**
     * _newFileName : file name for saving
     * @var string
     */
    private string $newFileName = '';

    /**
     * extension : upload file extension
     * @var string
     */
    private string $extension = '';

    /**
     * Upload constructor.
     * @param array $file
     */
    public function __construct(array $file)
    {
        $this->file = $file;
        $this->initConfig();
        $this->setExt();
    }

    /**
     *
     */
    private function initConfig(): void
    {
        if(self::$isConfigInitialized) {
            return ;
        }
        
        $config = Config::get('Upload');
        if (false === $config) {
            Log::warning("Config::get('Upload') failed", [], __METHOD__);
        }
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * @return bool
     */
    private function IsExtAllowed(): bool
    {
        return in_array(
            $this->GetExt(),
            self::$config['extension']);
    }

    /**
     * @return void
     */
    private function setExt(): void
    {
        $pathNode = explode('.', $this->file['name']);
        $nodeCount = count($pathNode);
        if ($nodeCount === 1) {
            $this->extension = '';
            return;
        }
        $this->extension = strtolower($pathNode[$nodeCount - 1]);
    }

    /**
     * @return string
     */
    public function GetExt(): string
    {
        return $this->extension;
    }

    /**
     * @return array
     */
    public function getAttribute(): array
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function GetTmpName(): string
    {
        return (string)$this->file['tmp_name'];
    }

    /**
     * @return string
     */
    public function GetOraName(): string
    {
        return (string)$this->file['name'];
    }

    /**
     * @return string
     */
    public function GetNewName(): string
    {
        return $this->newFileName;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function SetNewName(string $name = '')
    {
        if (!empty($name)) {
            $this->newFileName = $name;
        }
        return $this;
    }

    /**
     * @param string $savePath
     * @return bool
     */
    public function Save(string $savePath = ''): bool
    {
        if (empty($this->newFileName)) {
            $this->newFileName = dechex(Coroutine::id()) . dechex(time()) . dechex(random_int(100, 999));
        }
        $savePath = empty($savePath) ? self::$config['savePath'] : $savePath;

        return move_uploaded_file($this->file['tmp_name'], $savePath . $this->newFileName . '.' . $this->extension);
    }


}