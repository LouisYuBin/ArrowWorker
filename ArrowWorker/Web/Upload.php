<?php
/**
 * User: louis
 * Time: 18-5-11 上午10:34
 */

namespace ArrowWorker\Web;

use ArrowWorker\Config;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Log;

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
     * default configuraion
     */
    private static $config = [
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
     * file : file information
     * @var
     */
    private $_file;

    /**
     * _newFileName : file name for saving
     * @var string
     */
    private $_newFileName = '';

    /**
     * extension : upload file extension
     * @var string
     */
    private $_extension = '';

    /**
     * Upload constructor.
     * @param array $file
     */
    public function __construct(array $file)
    {
        $this->_file = $file;
        $this->_setExt();
    }

    public static function Init()
    {
        $config = Config::Get('Upload');
        if (false === $config) {
            Log::Warning("Config::Get('Upload') failed");
        }
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * IsExtAllowed : check if the upload file extension is allowed
     * @return bool
     */
    private function IsExtAllowed(): bool
    {
        return in_array(
            $this->GetExt(),
            self::$config['extension']);
    }

    /**
     * _setExt : get file extension
     */
    private function _setExt()
    {
        $pathNode = explode('.', $this->_file['name']);
        $nodeCount = count($pathNode);
        if ($nodeCount == 1) {
            $this->_extension = '';
            return;
        }
        $this->_extension = strtolower($pathNode[$nodeCount - 1]);
    }

    /**
     * GetExt : return file extension
     * @return string
     */
    public function GetExt(): string
    {
        return $this->_extension;
    }

    /**
     * Attr
     * @return array
     */
    public function Attr(): array
    {
        return $this->_file;
    }

    /**
     * GetTmpName
     * @return string
     */
    public function GetTmpName(): string
    {
        return (string)$this->_file['tmp_name'];
    }

    /**
     * GetOraName
     * @return string
     */
    public function GetOraName(): string
    {
        return (string)$this->_file['name'];
    }

    /**
     * GetNewName
     * @return string
     */
    public function GetNewName(): string
    {
        return $this->_newFileName;
    }

    /**
     * SetNewName
     * @param string $name
     * @return $this
     */
    public function SetNewName(string $name = '')
    {
        if (!empty($name)) {
            $this->_newFileName = $name;
        }
        return $this;
    }

    /**
     * Save :save upload file
     * @param string $savePath
     * @return bool
     */
    public function Save(string $savePath = '')
    {
        if (empty($this->_newFileName)) {
            $this->_newFileName = dechex(Coroutine::Id()) . dechex(time()) . dechex(mt_rand(100, 999));
        }
        $savePath = empty($savePath) ? self::$config['savePath'] : $savePath;

        return move_uploaded_file($this->_file['tmp_name'], $savePath . $this->_newFileName . '.' . $this->_extension);
    }


}