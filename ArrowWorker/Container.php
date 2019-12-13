<?php
/**
 * User: Louis
 * Date: 2016/8/3 15:51
 * Update Record:
 *      2017-07-24 By Louis
 */

namespace ArrowWorker;

/**
 * 用户类加载器
 * Class Container
 * @package ArrowWorker
 */
class Container
{
    /**
     * 用户定义类加载map
     * @var array
     */
    private static $appClass    = [];


    /**
     * Model 加载用户model
     * @author Louis
     * @param string $name
     * @return \App\Model\*
     */
    public static function Model( string $name )
    {
    
    }

	/**
     * Service 加载用户 service
	 * @param string $name
	 */
    public static function Service( string $name )
    {
    }
    

}
