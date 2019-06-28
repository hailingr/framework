<?php
define( 'DS' , DIRECTORY_SEPARATOR );
//定义核心框架根路径
define( 'ROOT' , dirname( __FILE__ ) . DS . '_core_' .DS );
//定义应用框架根路径,可以实现核心框架和应用框架的分离
define( 'APPROOT' , dirname( __FILE__ ) . DS  );
//定义是否调试模式
define( 'DEBUG' , true);
//?m=tools&a=imHotel
$_GET['m'] = $_REQUEST['m'] = $_POST['m'] = 'tools';
$_GET['a'] = $_REQUEST['a'] = $_POST['a'] = 'setHotel';
//$_GET['a'] = $_REQUEST['a'] = $_POST['a'] = 'imHotel';
//定义核心启动文件路径，框架的核心文件
$coreStartFile = ROOT .'core.class.php';
include($coreStartFile);
//调用静态方法启动应用程序
Core::run();
exit;

ini_set("display_errors", "On");
error_reporting(E_ALL);
//phpinfo();
$conn = new MongoClient("mongodb://u6u:520123@118.192.9.44:27017/u6uhotel");
echo '<pre>';
//$conn = new MongoClient();
$db   = $conn->u6uhotel;

//var_dump($db);
$table = $db->test;
$list = $table->find(array());


/*
$document = array( 
      "title" => "MongoDB", 
      "description" => "database", 
      "likes" => 100,
      "url" => "http://www.w3cschool.cc/mongodb/",
      "by"=>"w3cschool.cc"
   );
   */
//$rst = $table->insert($document);
$list1 = $db->test->find(array('title'=>"MongoDB"));
foreach($list1 as $l)
{
	print_r($l);
}
echo '</pre>';