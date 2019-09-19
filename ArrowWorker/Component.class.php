<?php
/**
 * By yubin at 2019-09-02 17:35.
 */

namespace ArrowWorker;


/**
 * Class Component
 * @package ArrowWorker
 */
class Component
{
    /**
     *
     */
    const WEB_RELEASE_COMPONENTS = [
        '\ArrowWorker\Web\Cookie',
        '\ArrowWorker\Web\Request',
        '\ArrowWorker\Web\Response',
        '\ArrowWorker\Web\Session',
        '\ArrowWorker\Log',
        '\ArrowWorker\Db'
    ];

    /**
     *
     */
    const BASE_RELEASE_COMPONENTS = [
        '\ArrowWorker\Log'
    ];

    /**
     * @param array $components
     */
    public static function Init( array $components)
    {
        foreach ( $components as $key=>$config )
        {
            $component = strtoupper($key);
            switch ($component)
            {
                case 'DB':
                    Db::Init($config);
                    break;
            }
        }
    }

    /**
     * @param int $type
     */
    public static function Release( int $type=1)
    {
        $components = 1==$type ? self::WEB_RELEASE_COMPONENTS : self::BASE_RELEASE_COMPONENTS;
        foreach ($components as $componentName)
        {
            $componentName::Release();
        }
    }

}