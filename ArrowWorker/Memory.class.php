<?php
/**
 * By yubin at 2019/3/4 3:49 PM.
 */

namespace ArrowWorker;

use ArrowWorker\Driver\Memory\SwTable;
use \Swoole\Table;

class Memory
{
    /**
     *
     */
    const CONFIG_NAME = 'Memory';

    const LOG_NAME = 'Memory';

    /**
     *
     */
    const DATA_TYPE = [
        'int',
        'string',
        'float',
    ];

    /**
     * @var array
     */
    private static $_pool = [];

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
        $config = Config::Get( static::CONFIG_NAME );
        foreach ( $config as $key => $value )
        {
            if ( !isset( $value[ 'size' ] ) || !isset( $value[ 'column' ] ) || count( $value[ 'column' ] ) == 0 )
            {
                Log::Error( "memory Table( key : {$key} ) config is incorrect.", self::LOG_NAME );
                continue;
            }

            $structure = self::_parseTableColumn( $value[ 'column' ] );
            if ( count( $structure ) == 0 )
            {
                continue;
            }
            $swTable = new SwTable( $structure,  $value[ 'size' ] );
            if( $swTable->Create() )
            {
                self::$_pool[ $key ] = $swTable;
            }
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    private static function _parseTableColumn( array $columns ) : array
    {
        $structure = [];
        foreach ( $columns as $name => $type )
        {
            if ( !in_array( $type, static::DATA_TYPE ) )
            {
                Log::Error( 'memory column type is incorrect.', self::LOG_NAME );
                continue;
            }
            switch ( $type )
            {
                case 'int':
                    $structure[ $name ] = [
                        'type' => Table::TYPE_INT,
                        'len'  => 8,
                    ];
                    break;
                case 'string':
                    $structure[ $name ] = [
                        'type' => Table::TYPE_STRING,
                        'len'  => 128,
                    ];
                    break;
                default:
                    $structure[ $name ] = [
                        'type' => Table::TYPE_FLOAT,
                        'len'  => 8,
                    ];
            }
        }
        return $structure;
    }

    /**
     * @param string $name
     * @return bool|SwTable
     */
    public static function Get( string $name )
    {
        if( isset(self::$_pool[ $name ]) )
        {
            return self::$_pool[ $name ];
        }
        return false;
    }

}