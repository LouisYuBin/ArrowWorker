<?php
/**
 * By yubin at 2020-06-25 16:52.
 */

namespace ArrowWorker;


/**
 * Class Environment
 * @package ArrowWorker
 */
class Environment
{
    /**
     *
     */
    public const TYPE_DEV = 'Dev';

    /**
     *
     */
    public const TYPE_TEST = 'Test';

    /**
     *
     */
    public const TYPE_PRODUCTION = 'Production';

    /**
     * @var array
     */
    private static $validateEnv = [
        self::TYPE_DEV,
        self::TYPE_TEST,
        self::TYPE_PRODUCTION
    ];

    /**
     * @var string
     */
    private static $currentType = self::TYPE_DEV;

    /**
     * @return string
     */
    public static function getType() :string
    {
        return self::$currentType;
    }

    /**
     * @param string $env
     */
    public static function setType(string $env): void
    {
        self::$currentType = in_array($env, self::$validateEnv) ? $env : self::TYPE_DEV;

    }

}