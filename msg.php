<?php
set_time_limit(0);
ini_set('max_execution_time',0);
define( 'DS' , DIRECTORY_SEPARATOR );
//定义核心框架根路径
define( 'ROOT' , dirname( __FILE__ ) . DS . '_core_' .DS );
//定义应用框架根路径,可以实现核心框架和应用框架的分离
define( 'APPROOT' , dirname( __FILE__ ) . DS  );
//定义是否调试模式
define( 'DEBUG' , true);
//?m=tools&a=imHotel
$_GET['m'] = $_REQUEST['m'] = $_POST['m'] = 'sys.msg';
$_GET['a'] = $_REQUEST['a'] = $_POST['a'] = 'index';
//$_GET['a'] = $_REQUEST['a'] = $_POST['a'] = 'imHotel';
//定义核心启动文件路径，框架的核心文件
$coreStartFile = ROOT .'core.class.php';
include($coreStartFile);
//调用静态方法启动应用程序
Core::run();