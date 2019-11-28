<?php

namespace ArrowWorker\Component\Memory;

use ArrowWorker\Log;
use Swoole\Table;

class SwTable
{

    const DATA_TYPE_INT = Table::TYPE_INT;

    const DATA_TYPE_STRING = Table::TYPE_STRING;

    const DATA_TYPE_FLOAT = Table::TYPE_FLOAT;

    const LOG_NAME = 'Memory';

    /**
     * @var Table
     */
    private $_table;

    /**
     * @var array
     */
    private $_structure;

    /**
     * @var int
     */
    private $_size = 10;

    /**
     * SwTable constructor.
     * @param array $structure
     * @param int   $size
     */
    public function __construct( array $structure, int $size )
    {
        $this->_structure = $structure;
        $this->_size      = $size;

        $this->_table     = new Table( $size );
        foreach ( $structure as $name => $property )
        {
            if ( $property[ 'type' ] == Table::TYPE_FLOAT )
            {
                $this->_table->column( $name, $property[ 'type' ] );
                continue;
            }
            $this->_table->column( $name, $property[ 'type' ], $property[ 'len' ] );
        }
    }

    /**
     * @return bool
     */
    public function Create() : bool
    {
        if ( !$this->_table->create() )
        {
            Log::Error( 'create memory table failed, config is : ' . json_encode( $this->_structure ), self::LOG_NAME );
            return false;
        }
        return true;
    }

    /**
     * @param string $key
     * @return array
     */
    public function Read( string $key )
    {
        return $this->_table->get( $key );
    }

    /**
     * @return array
     */
    public function ReadAll()
    {
        $list     = [];
        $instance = $this->_table;
        foreach ( $instance as $key => $value )
        {
            $list[ $key ] = $value;
        }
        return $list;
    }

    /**
     * @param string $key
     * @param array  $value
     * @return bool
     */
    public function Write( string $key, array $value ) : bool
    {
        return $this->_table->set( $key, $value );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function IsKeyExists( string $key ) : bool
    {
        return $this->_table->exist( $key );
    }

    /**
     * @return mixed
     */
    public function Count() : int
    {
        return $this->_table->count();
    }


    /**
     * @param string $key
     * @return bool
     */
    public function Delete( string $key ) : bool
    {
        return $this->_table->del( $key );
    }


    /**
     * @param string $key
     * @param string $column
     * @param int    $incrby
     * @return mixed
     */
    public function Incr( string $key, string $column, int $incrby = 1)
    {
        return $this->_table->incr( $key, $column, $incrby );
    }


    /**
     * @param string $key
     * @param string $column
     * @param int    $decrby
     * @return mixed
     */
    public function Decr( string $key, string $column, int $decrby = 1)
    {
        return $this->_table->decr( $key, $column, $decrby );
    }

    /**
     * @return int
     */
    public function GetMemorySize()
    {
        return $this->_table->getMemorySize();
    }

}