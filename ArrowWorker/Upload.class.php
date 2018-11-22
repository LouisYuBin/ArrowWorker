<?php
/**
 * User: louis
 * Time: 18-5-11 上午10:34
 */

namespace ArrowWorker;

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
        $this->file= $_FILES[$name];
        $this->_initConfig();
        $this->_setExt();
    }

    /**
     * checkConfig
     * @throws \Exception
     */
    private function _initConfig()
    {
        $config = Config::Get('Upload');
        if( false===$config )
        {
            throw new \Exception('upload config is not found');
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
     * GetFileExt : get file extension
     */
    private function _setExt() : string
    {
        $pathNode  = explode('.', $this->file['name']);
        $nodeCount = count($pathNode);
        if( $nodeCount==1 )
        {
            $this->extension = '';
            goto _RETURN;
        }
        $this->extension = strtolower( $pathNode[$nodeCount-1] );

        _RETURN:
        return $this->extension;
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
     * SetNewName
     * @param string|null $name
     * @return $this
     */
    public function SetNewName(string $name=null)
    {
        if( !is_null($name) )
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
        if( empty( $this->newFileName) )
        {
            $this->newFileName = dechex(getmypid()).microtime(true).mt_rand(1000,1000000);
        }
        $savePath = empty($savePath) ? $this->config['savePath'] : $savePath;

        return move_uploaded_file( $this->file['tmp_name'], $savePath.$this->newFileName.'.'.$this->extension);
    }


}