<?php
/**
 * By yubin at 2019/3/4 3:49 PM.
 */

namespace ArrowWorker;


class Memory
{
    /**
     * @var
     */
    private static $_instance;

    /**
     * @var array
     */
    private static $_pool = [];

    /**
     * @var string
     */
    private static $_path = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Memory/';

    /**
     * @var string
     */
    private static $_current = '';

    private $_defaultSize = 1024*1024*15;

    /**
     * Memory constructor.
     * @param string $name
     */
    private function __construct(string $name)
    {
        if( !$this->_isExtensionLoaded() )
        {
            return ;
        }

        $key = $this->_getKey($name);
        if( $key==-1 )
        {
            return ;
        }

        $size = $this->_getSize();
        $resource = shm_attach($key,'10M');
        var_dump('shm_attach',$size, $resource);

        static::$_pool[$name] = $resource;
    }

    /**
     * @return bool
     */
    private function _isExtensionLoaded()
    {
        if( !extension_loaded('sysvshm') )
        {
            Log::Error("sysvshm not loaded while using in shm_attach");
            return false;
        }
        return true;
    }

    /**
     * @param string $name
     * @return int
     */
    private function _getKey(string $name) : int
    {
        $file = static::$_path.$name.'.memory';
        if( !file_exists($file) )
        {
            if( false===touch($file) )
            {
                Log::Error("touch memory key-file failed : {$file}.");
                return -1;
            }
        }

        $key = ftok($file,'a');
        if( $key==-1 )
        {
            Log::Error("ftok({$file},'a') failed.");
            return -1;
        }
        return $key;
    }

    private function _getSize() : int
    {
        $config = Config::Get('Memory');
        if( false===$config || !isset($config['size']) )
        {
            return $this->_defaultSize;
        }
        return (int)$config['size'];
    }

    /**
     * @param string $name
     * @return self
     */
    public static function Get(string $name)
    {
        if( isset(static::$_pool[$name]) )
        {
            static::$_current = $name;
            return static::$_instance;
        }

        if( !static::$_instance )
        {
            static::$_instance = new self($name);
        }

        return static::$_instance;
    }

    /**
     * @param int $key
     * @return bool|mixed
     */
    public function Read(int $key)
    {
        $shmId = $this->_getCurrent();
        if( false===$shmId )
        {
            return false;
        }
        return shm_get_var($this->_getCurrent(), $key);
    }

    /**
     * @param int $key
     * @param     $value
     * @return bool
     */
    public function Write(int $key, $value) : bool
    {
        $shmId = $this->_getCurrent();
        if( false===$shmId )
        {
            return false;
        }
        return shm_put_var($shmId, $key, $value);
    }

    public function IsKeyExists(int $key) : bool
    {
        $shmId = $this->_getCurrent();
        if( false===$shmId )
        {
            return false;
        }
        return shm_has_var( $shmId , $key);
    }

    /**
     * @return mixed
     */
    private function _getCurrent()
    {
        if( !is_resource(static::$_pool[static::$_current]) )
        {
            Log::Error("memory resource does not exists.");
            return false;
        }
        return static::$_pool[static::$_current];
    }

    /**
     *
     */
    public static function Remove()
    {
        foreach (static::$_pool as $key=>$value)
        {
            shm_remove($value);
            shm_detach($value);
        }
    }


}