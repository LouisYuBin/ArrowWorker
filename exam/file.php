<?php
/**
 * User: louis
 * Time: 18-6-11 上午11:48
 */
$file = dirname(__FILE__).'/a.log';
var_dump($file);
$result = fopen($file,'w');
var_dump($result);

var_dump(rename($file,'b.log'));