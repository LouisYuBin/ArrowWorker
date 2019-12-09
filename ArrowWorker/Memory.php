<?php

namespace ArrowWorker;

use ArrowWorker\Component\Memory\SwTable;

class Memory
{
    const LOG_NAME = 'Memory';

    const CONFIG_NAME = 'Memory';

    const DATA_TYPE_STRUCTURE = [
        'int'    => [
            'type' => SwTable::DATA_TYPE_INT,
            'len'  => 8,
        ],
        'string' => [
            'type' => SwTable::DATA_TYPE_STRING,
            'len'  => 128,
        ],
        'float'  => [
            'type' => SwTable::DATA_TYPE_FLOAT,
            'len'  => 8,
        ],
    ];

    /**
     * @var array
     */
    private static $_table = [];

    public static function Init()
    {
        $table = Config::Get( self::CONFIG_NAME );
        foreach ( $table as $name => $definition )
        {
            if ( !is_array( $definition ) ||
                 !isset( $definition[ 'size' ] ) ||
                 !isset( $definition[ 'column' ] ) ||
                 count( $definition[ 'column' ] ) == 0
            )
            {
                Log::Error( "memory Table( {$name} : " .
                            json_encode( $definition ) .
                            " ) config is incorrect.", self::LOG_NAME );
                continue;
            }

            $structure = self::_parseTableColumn( $definition[ 'column' ] );
            if ( count( $structure ) == 0 )
            {
                continue;
            }
            $swTable = new SwTable( $structure, $definition[ 'size' ] );
            if ( $swTable->Create() )
            {
                self::$_table[ $name ] = $swTable;
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
            if ( !isset( self::DATA_TYPE_STRUCTURE[ $type ] ) )
            {
                Log::Error( 'table column type is incorrect.', self::LOG_NAME );
                continue;
            }
            $structure[ $name ] = self::DATA_TYPE_STRUCTURE[ $type ];
        }
        return $structure;
    }

    /**
     * @param string $name
     * @return bool|SwTable
     */
    public static function Get( string $name )
    {
        if ( isset( self::$_table[ $name ] ) )
        {
            return self::$_table[ $name ];
        }
        return false;
    }

}