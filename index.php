<?php
define( 'STARTTIME',microtime());
/**
 * 作者：hailin<hailingr@foxmail.com>
 * 时间：2013.03.06 chengdu.china
 * 项目入口文件
 */
//定义文件夹路径符号
define( 'DS' , DIRECTORY_SEPARATOR );
//定义核心框架根路径
define( 'ROOT' , dirname( __FILE__ ) . DS . '_core_' .DS );
//定义应用框架根路径,可以实现核心框架和应用框架的分离
define( 'APPROOT' , dirname( __FILE__ ) . DS  );
//定义是否调试模式
define( 'DEBUG' , true);
//定义核心启动文件路径，框架的核心文件
$coreStartFile = ROOT .'core.class.php';
include($coreStartFile);
//调用静态方法启动应用程序
//print_r($_REQUEST);
//run支持参数为配置文件，redis.config.php，缺省为默认的配置文件
Core::run();

