<?php
/**
 * User: Louis
 * Date: 2016/8/1
 * Time: 18:03
 */
use ArrowWorker\ArrowWorker as arrow;
define('APP_PATH',__DIR__.'/App');
//define('APP_TYPE',['worker','swHttp']);
define('APP_TYPE',['swHttp']);

require __DIR__.'/ArrowWorker/ArrowWorker.php';
arrow::Start();


