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
        $this->checkConfig();
        $this->getFileExt();
    }

    /**
     * checkConfig
     * @throws \Exception
     */
    private function checkConfig()
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
     * @throws \Exception
     */
    private function IsExtAllowed() :bool
    {
        $ext = $this->GetFileExt();
        if( false===$ext )
        {
            return false;
        }

        return in_array( $ext, $this->config['extension'] );
    }

    /**
     * GetFileExt : get file extension
     */
    private function getFileExt()
    {
        $dotPos = strrpos($this->file['name'],'.');
        if( false===$dotPos )
        {
            throw new \Exception('file extension does not exits');
        }
        $this->extension = strtolower( substr($this->file['name'],$dotPos+1) );
    }

    /**
     * FileExt : return file extension
     * @return null
     */
    public function FileExt()
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
     * @param string|null $savePath
     * @return bool
     */
    public function Save(string $savePath = null )
    {
        if( is_null( $this->newFileName) )
        {
            $this->newFileName = microtime(true).mt_rand(1000,1000000);
        }

        var_dump($this->config['savePath'].$this->newFileName.'.'.$this->extension);
        var_dump( file_exists($this->file['tmp_name']) );
        return move_uploaded_file( $this->file['tmp_name'], $this->config['savePath'].$this->newFileName.'.'.$this->extension);
    }


}