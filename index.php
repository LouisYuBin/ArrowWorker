<?php
/**
 * User: Louis
 * Date: 2016/8/1
 * Time: 18:03
 */
use ArrowWorker\ArrowWorker as arrow;
define('APP_PATH',__DIR__.'/App');
//APP_TYPE要启动的应用数组，可选值： 1、worker（工作进程）  2、server（服务）
define('APP_TYPE',['worker','server']);
//define('APP_TYPE',['server']);
//define('APP_TYPE',['worker']);

//开发调试模式
define('DEBUG', false);

require __DIR__.'/ArrowWorker/ArrowWorker.php';
arrow::Start();


