<?php

namespace ArrowWorker;

use ArrowWorker\Component\Memory\SwTable;
use ArrowWorker\Log\Log;

class Memory
{

    private const CONFIG_NAME = 'Memory';

    private const DATA_TYPE_STRUCTURE = [
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
     * @var $container Container
     */
    private Container $container;

    /**
     * @var array
     */
    private array $tables = [];

    private static $instance;

    public function __construct(Container $container)
    {
        self::$instance = $this;
        $table = Config::get(self::CONFIG_NAME);
        foreach ($table as $name => $definition) {
            if (!is_array($definition) ||
                !isset($definition['size'], $definition['column']) ||
                count($definition['column']) === 0
            ) {
                Log::error("memory Table( {name} : {definition} ) config is incorrect.", [
                    'name'       => $name,
                    'definition' => json_encode($definition),

                ], __METHOD__);
                continue;
            }

            $structure = $this->parseTableColumn($definition['column']);
            if (count($structure) === 0) {
                continue;
            }

            /**
             * @var $swTable SwTable
             */
            $swTable = $container->make(SwTable::class, [$container, $structure, $definition['size']]);
            if ($swTable->Create()) {
                $this->tables[$name] = $swTable;
            }
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    private function parseTableColumn(array $columns): array
    {
        $structure = [];
        foreach ($columns as $name => $type) {
            if (!isset(self::DATA_TYPE_STRUCTURE[$type])) {
                Log::error('table column type is incorrect.', [], __METHOD__);
                continue;
            }
            $structure[$name] = self::DATA_TYPE_STRUCTURE[$type];
        }
        return $structure;
    }

    /**
     * @param string $name
     * @return false|SwTable
     */
    public static function get(string $name)
    {
        $memory = self::$instance;
        if (isset($memory->tables[$name])) {
            return $memory->tables[$name];
        }
        return false;
    }

}