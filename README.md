## 项目介绍
ArrowWorker 是一个简单高效的php框架。框架兼容php7，可在web模式和cli模式下运行。框架封装了常驻服务相关操作，主要擅长在cli下做为常驻服务来使用。
## 运行介绍
ArrowWorker 已在公司的项目中使用一年多，使用模型为一个监控进程+多个工作进程配合使用，使用期间运行非常稳定。
## 项目程序版本介绍
ArrowWorker V1.0版本未做开源,从V1.2版本开始,在github开源。V1.3版本做了功能升级和优化,包括:<br />
1、简化常驻服务初始化方法.<br />
2、单个任务可使用worker进程组执行，可添加多个任务实现多组进程并行.<br />
3、进程生命周期设置。<br />
4、进程名设置。<br />
5、常驻服务pid设置。<br />
6、常驻服务日志文件设置。<br />
7、常驻服务运行日志等级设置。<br />
8、优化监控进程监控工作进程方式。<br />
9、全面支持php7及更高版本。<br />
10、pthread分支增加了对多线程的支持（使用pthread扩展）, 用户可自行设置线程数。<br />
## 当前版本
V1.3
## 开发计划
从V1.4版本开始计划加入socket通信相关操作，是ArrowWorker成为一个可进行分布式部署的框架。
## 功能使用
###入口文件包含框架
```php
//设置命名空间
use ArrowWorker\ArrowWorker as arrow;
//应用目录
define('APP_PATH',__DIR__.'/App/');
//运行模式
define('APP_TYPE','cli');
//包含框架入口文件
require __DIR__.'/ArrowWorker/ArrowWorker.php';
//加载框架
arrow::start();
```
### 应用目录说明
>Config:应用配置文件目录(目录可自定义)<br />
>>alias.php:自动加载映射文件(文件名不可变更)<br />
>>common.php:默认程序配置文件(配置驱动类型、IP地址、端口等),文件名可自定义<br />
>Controller:控制器类文件目录<br />
>Model:模型类文件目录<br />
>Classes:类文件目录<br />
>Lang:多语言文件目录<br />
>Tpl:模板文件目录<br />

### 控制器中初始化Model方法
```php
$model  = self::load("ModelName");
$method = $model -> ModelMethod();
```
### 控制器初始化Classes
```php
$method  = self::load("MethodName",'c');
$method = $method -> MethodName();
```
### 控制器初始化缓存
```php
$cache = self::getObj('cache');
```
### 相关使用文档正在更新中。。。
