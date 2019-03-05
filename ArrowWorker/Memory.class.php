<?php
/**
 * By yubin at 2019/3/4 3:49 PM.
 */

namespace ArrowWorker;

use \Swoole\Table;

class Memory
{
    /**
     *
     */
    const CONFIG_NAME = 'Memory';

    /**
     *
     */
    const DATA_TYPE   = ['int', 'string', 'float'];

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
    private static $_current = [];


    /**
     * Memory constructor.
     */
    private function __construct()
    {
        //todo
    }

    /**
     *
     */
    public static function Init()
    {
        $config = Config::Get(static::CONFIG_NAME);
        foreach ($config as $key=>$value)
        {
            if( !isset($value['size']) || !isset($value['column']) || count($value['column'])==0 )
            {
                Log::Error("memory Table(key:{$key}) config is incorrect.");
                continue ;
            }

            $structure = static::_parseTableColumn($value['column']);
            if( count($structure)==0)
            {
                continue ;
            }
            static::$_pool[$key] = static::_initOneTable($value['size'], $structure);
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    private static function _parseTableColumn(array $columns) : array
    {
        $structure = [];
        foreach ($columns as $name=>$type)
        {
            if( !in_array($type,static::DATA_TYPE) )
            {
                Log::Error('memory column type is incorrect.');
                continue;
            }
            switch($type)
            {
                case 'int':
                    $structure[$name] = [
                        'type' => Table::TYPE_INT,
                        'len'  => 8,
                    ];
                    break;
                case 'string':
                    $structure[$name] = [
                        'type' => Table::TYPE_STRING,
                        'len'  => 128,
                    ];
                    break;
                default:
                    $structure[$name] = [
                        'type' => Table::TYPE_FLOAT,
                        'len'  => 8,
                    ];
            }
        }
        return $structure;
    }

    /**
     * @param int   $size
     * @param array $structure
     * @return Table
     */
    private static function _initOneTable(int $size, array $structure) : Table
    {
        $table = new Table($size);
        foreach ($structure as $name=>$property)
        {
            if( $property['type']==Table::TYPE_FLOAT)
            {
                $table->column($name, $property['type']);
                continue ;
            }
            $table->column($name, $property['type'], $property['len']);
        }

        if( !$table->create() )
        {
            Log::Error('create memory table failed, config is : '.json_encode($structure));
        }
        return $table;
    }

    /**
     * @param string $name
     * @return self
     */
    public static function Get(string $name)
    {
        static::$_current[Swoole::GetCid()] = $name;

        if( !is_object(static::$_instance) )
        {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    /**
     * @param string $key
     * @return array
     */
    public function Read(string $key)
    {
        return $this->_getCurrent()->get($key);
    }

    /**
     * @param string $key
     * @param array  $value
     * @return bool
     */
    public function Write(string $key, array $value) : bool
    {
        return $this->_getCurrent()->set($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function IsKeyExists(string $key) : bool
    {
        return $this->_getCurrent()->exist($key);
    }

    /**
     * @param int $key
     * @return int
     */
    public function Count(int $key) : int
    {
        return $this->_getCurrent()->count($key);
    }

    /**
     * @return mixed
     */
    private function _getCurrent()
    {
        return static::$_pool[static::$_current[Swoole::GetCid()]];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function Delete(string $key) : bool
    {
        return $this->_getCurrent()->del($key);
    }

    public static function Release()
    {
        unset( static::$_current[Swoole::GetCid()] );
    }

}