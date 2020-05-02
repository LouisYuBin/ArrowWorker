<?php

namespace ArrowWorker\Component\Memory;

use ArrowWorker\Container;
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
    private $table;

    /**
     * @var array
     */
    private $structure;

    /**
     * @var int
     */
    private $size = 10;

    private $container;

    /**
     * SwTable constructor.
     * @param Container $container
     * @param array $structure
     * @param int $size
     */
    public function __construct(Container $container, array $structure, int $size)
    {
        $this->container = $container;
        $this->structure = $structure;
        $this->size = $size;

        $this->table = $this->container->Make(Table::class, [$size]);
        foreach ($structure as $name => $property) {
            if ($property['type'] == Table::TYPE_FLOAT) {
                $this->table->column($name, $property['type']);
                continue;
            }
            $this->table->column($name, $property['type'], $property['len']);
        }
    }

    /**
     * @return bool
     */
    public function Create(): bool
    {
        if (!$this->table->create()) {
            Log::Error('create memory table failed, config is : {config}', [
                'config' => json_encode($this->structure)
            ], self::LOG_NAME);
            return false;
        }
        return true;
    }

    /**
     * @param string $key
     * @return array
     */
    public function Read(string $key)
    {
        return $this->table->get($key);
    }

    /**
     * @return array
     */
    public function ReadAll()
    {
        $list = [];
        $instance = $this->table;
        foreach ($instance as $key => $value) {
            $list[$key] = $value;
        }
        return $list;
    }

    /**
     * @param string $key
     * @param array $value
     * @return bool
     */
    public function Write(string $key, array $value): bool
    {
        return $this->table->set($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function IsKeyExists(string $key): bool
    {
        return $this->table->exist($key);
    }

    /**
     * @return mixed
     */
    public function Count(): int
    {
        return $this->table->count();
    }


    /**
     * @param string $key
     * @return bool
     */
    public function Delete(string $key): bool
    {
        return $this->table->del($key);
    }


    /**
     * @param string $key
     * @param string $column
     * @param int $incrby
     * @return mixed
     */
    public function Incr(string $key, string $column, int $incrby = 1)
    {
        return $this->table->incr($key, $column, $incrby);
    }


    /**
     * @param string $key
     * @param string $column
     * @param int $decrby
     * @return mixed
     */
    public function Decr(string $key, string $column, int $decrby = 1)
    {
        return $this->table->decr($key, $column, $decrby);
    }

    /**
     * @return int
     */
    public function GetMemorySize()
    {
        return $this->table->getMemorySize();
    }

}