<?php
/**
 * User: louis
 * Time: 18-5-11 上午10:34
 */

namespace ArrowWorker\Web;

use ArrowWorker\Coroutine;
use ArrowWorker\Config;
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
    private $config =[
        'savePath'  => APP_PATH.'/Runtime/Upload/',
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
    private $file;

    /**
     * newFileName : file name for saving
     * @var null
     */
    private $newFileName = null;

    /**
     * extension : upload file extension
     * @var null
     */
    private $extension = null;

    /**
     * Upload constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->file= $_FILES[Coroutine::Id()][$name];
        $this->_initConfig();
        $this->_setExt();
    }

    /**
     * checkConfig
     */
    private function _initConfig()
    {
        $config = Config::Get('Upload');
        if( false===$config )
        {
            Log::Warning("Config::Get('Upload') failed");
        }
        $this->config = array_merge($this->config, $config);
    }

    /**
     * IsExtAllowed : check if the upload file extension is allowed
     * @return bool
     */
    private function IsExtAllowed() : bool
    {
        return in_array(
            $this->GetExt(),
            $this->config['extension'] );
    }

    /**
     * _setExt : get file extension
     */
    private function _setExt()
    {
        $pathNode  = explode('.', $this->file['name']);
        $nodeCount = count($pathNode);
        if( $nodeCount==1 )
        {
            $this->extension = '';
            return ;
        }
        $this->extension = strtolower( $pathNode[$nodeCount-1] );
    }

    /**
     * GetExt : return file extension
     * @return string
     */
    public function GetExt() : string
    {
        return $this->extension;
    }

    /**
     * Attr
     * @return array
     */
    public function Attr() : array
    {
        return $this->file;
    }

    /**
     * GetTmpName
     * @return string
     */
    public function GetTmpName() : string
    {
        return (string)$this->file['tmp_name'];
    }

    /**
     * GetOraName
     * @return string
     */
    public function GetOraName() : string
    {
        return (string)$this->file['name'];
    }

    /**
     * GetNewName
     * @return string
     */
    public function GetNewName() : string
    {
        return $this->newFileName;
    }

    /**
     * SetNewName
     * @param string $name
     * @return $this
     */
    public function SetNewName(string $name='')
    {
        if( !empty($name) )
        {
            $this->newFileName = $name;
        }
        return $this;
    }

    /**
     * Save :save upload file
     * @param string $savePath
     * @return bool
     */
    public function Save(string $savePath = '' )
    {
        if( empty($this->newFileName) )
        {
            $this->newFileName = dechex(Coroutine::Id()).dechex(time()).dechex(mt_rand(100,999));
        }
        $savePath = empty($savePath) ? $this->config['savePath'] : $savePath;

        return move_uploaded_file( $this->file['tmp_name'], $savePath.$this->newFileName.'.'.$this->extension);
    }


}