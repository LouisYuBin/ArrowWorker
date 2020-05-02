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
     * @var $container Container
     */
    private $container;

    /**
     * @var array
     */
    private $tables = [];

    private static $instance;

    public function __construct(Container $container)
    {
        self::$instance = $this;
        $this->container = $container;
        $table = Config::Get(self::CONFIG_NAME);
        foreach ($table as $name => $definition) {
            if (!is_array($definition) ||
                !isset($definition['size']) ||
                !isset($definition['column']) ||
                count($definition['column']) == 0
            ) {
                Log::Error("memory Table( {name} : {definition} ) config is incorrect.", [
                    'name'       => $name,
                    'definition' => json_encode($definition),

                ], self::LOG_NAME);
                continue;
            }

            $structure = $this->parseTableColumn($definition['column']);
            if (count($structure) == 0) {
                continue;
            }

            /**
             * @var $swTable SwTable
             */
            $swTable = $this->container->Make(SwTable::class, [$this->container, $structure, $definition['size']]);
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
                Log::Error('table column type is incorrect.', [], self::LOG_NAME);
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
    public static function Get(string $name)
    {
        $memory = self::$instance;
        if (isset($memory->tables[$name])) {
            return $memory->tables[$name];
        }
        return false;
    }

}