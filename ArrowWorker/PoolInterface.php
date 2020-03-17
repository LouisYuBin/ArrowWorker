<?php

namespace ArrowWorker;


interface PoolInterface
{

    /**
     * @param int
     */
    const DEFAULT_POOL_SIZE = 10;
    
    /**
     * @param string $alias
     * @return mixed
     */
    public static function Get( string $alias = 'default' );
    
    public function __construct( Container $container, array $presetConfig, array $userConfig=[]);
	
	/**
     * @return void
     */
    public function Release() : void ;
}