<?php
/**
 * 框架核心文件
 * 核心文件声明了几个用于整个系统赖以运行的静态方法
 * import，checkDir，controller,model
 * 基础的控制器类，模型类也在本文件中进行了声明，并在根模型中实现了大部门的数据库操作方法
 * controller -> assign,template,motel
 * model -> table[表属性]，db[数据库对象],setDns,query,insert,update,del,get,getOne,支持链式写法的jion,limit,where,order,group
 * update的where；del的where，get，getOne的jion,limit,where,order,group均支持链式写法
 * $m1->where(array())->field(array('id','username','roleid'))->limit(0,10)->order(array('id'=>'desc','username'=>'asc'))->group('id','username')->getOne();
 * 作者:Hailin<hailingr@foxmail.com>
 * 创建:2014.03.05 chengdu.china
 */
class Core
{
    //系统全局信息存储,在本属性中会保存大部分系统全局配置，资源信息
    public static $G         = array();
    /**
     * 1：初始化相关信息
     * 2：载入配置文件，调试开关
     * 3：初始化数据库连接，并默认切换到连接到第一个数据库服务器上
     * */
    public static function ini($conf='core')
    {
        //是否启用调试模式
        if(DEBUG==true)
        {
            ini_set('display_errors', true);
            error_reporting(E_ALL);
        }else
        {
            ini_set('display_errors', false);
            error_reporting(0);
        }
        //发送http字符定义头
        header('Content-type:text/html;chartset=utf-8');
        //时区声明
        date_default_timezone_set('PRC');
        //错误处理
        include_once(ROOT.'lib/error.class.php');
        //载入全局函数库
        include_once(ROOT.'lib/lib.function.php');
        //输入安全检查
        checkSafeInput();

        //静态化支持
        //goChtml();

        //获取配置文件
        $config = self::import($conf,'config');
        self::$G['config'] = $config;
        //数据库连接初始化
        $db     = null;
        $mongo  = array();
        $redis  = array();
        //数据库连接
        foreach($config['dbns'] as $dnsid=>$dns)
        {
            //mysql数据库支持
            if($dns['dbdrive']=='mysql' && $dns['dbhost'])
            {
                $db = self::import('db.mysql','lib',true);
                $db->pcconnect = true;
                $db->connect($dns['dbhost'],$dns['dbport'],$dns['dbname'],$dns['dbuser'],$dns['dbpwd'],'utf8mb4',DEBUG,$dnsid);
                $db->setDns($dnsid);
            }
            //mysqli数据库支持
            if($dns['dbdrive']=='mysqli'  && $dns['dbhost'])
            {
                $db = self::import('db.mysqli','lib',true);
                $connectHost = $dns['dbhost'];
                if(isset($dns['dbpccon']) && $dns['dbpccon']){
                    $connectHost = 'p:'.$dns['dbhost'];
                }
                $db->connect($connectHost,$dns['dbport'],$dns['dbname'],$dns['dbuser'],$dns['dbpwd'],'utf8mb4',DEBUG,$dnsid);
                $db->setDns($dnsid);
            }
            //mongodb数据库支持
            if($dns['dbdrive']=='mongodb'  && $dns['dbhost'])
            {
                $conn    = new MongoClient("mongodb://".$dns['dbuser'].":".$dns['dbpwd']."@".$dns['dbhost'].":".$dns['dbport']."/".$dns['dbname']."");
                $mongo[$dnsid] = $conn->$dns['dbname'];
            }
            //redis支持
            //dump($dns);
            if($dns['dbdrive']=='redis'  && $dns['dbhost'])
            {
                $_redis = new Redis();
                $dbpccon = $dns['dbpccon'];
                if($dbpccon){
                    $_redis->pconnect($dns['dbhost'],$dns['dbport']);
                }else{
                    $_redis->connect($dns['dbhost'],$dns['dbport']);
                }

                if($dns['dbuser']) $_redis->auth($dns['dbuser']);
                $redis[$dnsid] = $_redis;
            }
        }
        //dump($redis);
        self::$G['db']      = $db;
        self::$G['mongo']   = $mongo;
        self::$G['redis']   = $redis;
    }

    /**
     * 1：启动应用程序，
     * 2：调用相应的控制器，
     * 3：控制器方法
     */
    public static function run($conf='core')
    {
        self::ini($conf);
        $route          = self::router();
        $controller     = $route['con'];
        $action         = $route['act'];
        self::controller($controller,$action);
        //Gzip支持
        if(self::$G['config']['gzip'])
        {
            $gzips = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
            if( strpos($gzips, 'gzip') !== FALSE && @ini_get("zlib.output_compression") )
            {
                ob_start("ob_gzhandler");
            }
        }
    }

    /**
     * 通过url地址获取到当前的控制性及控制器方法
     * cli命令行状态下调用方式如下：
     * ..../php.exe ./index.php "m=def&a=index&a=user"
     * @return array
     */
    public static function router()
    {
        global $argv;
        $return         = array();
        $conf           = self::$G['config'];
        $controllerSign = $conf['defaultCon'];
        $actionSign     = $conf['defaultAct'];

        //兼容cli模式下的参数传入
        $params = isset($argv) ? $argv :null;
        $params = $params ? $params : null;
        if(is_array($params) && !empty($params))
        {
            $paramStrs = isset($params[1]) ? $params[1] : '';
            if($paramStrs){
                $paramAlls = explode('&',$paramStrs);
                foreach($paramAlls as $k=>$v)
                {
                    $item = $v;
                    $items= explode('=',$item);
                    if(isset($items[0])){
                        $itemIndex = isset($items[0]) ? $items[0] : '';
                        $itemValue = isset($items[1]) ? $items[1] : '';
                        $_GET[$itemIndex]       = $itemValue;
                        $_REQUEST[$itemIndex]   = $itemValue;
                        $_POST[$itemIndex]      = $itemValue;
                    }
                }
            }
        }
        //+end

        //+------------------
        //兼容用路径的方式来省略模块和方法的方式,将“?m=xxx&a=nnn”变为“/xxx/nnn”
        $pathInfo = isset($_REQUEST['_PATHINFO_']) ? $_REQUEST['_PATHINFO_'] : '';
        if($pathInfo){
            $pathInfo  = str_replace('//','/',$pathInfo);
            $pathInfo  = str_replace('///','/',$pathInfo);
            $pathInfo  = str_replace('////','/',$pathInfo);
            $pathInfo  = str_replace('/////','/',$pathInfo);
            $pathInfos = explode('/',$pathInfo);
			if($pathInfos[0]=='') array_shift($pathInfos);
            $pathMode  = isset($pathInfos[0]) ? trim($pathInfos[0]) : '';
            $pathAct   = isset($pathInfos[1]) ? trim($pathInfos[1]) : '';
            if($pathMode){
                $_GET[$controllerSign] = $pathMode;
            }
            if($pathAct){
                $_GET[$actionSign] = $pathAct;
            }
            //self::dump($pathInfos);
        }
        //+-----------------------------

        $controller     = isset($_GET[$controllerSign]) ? @$_GET[$controllerSign] : 'def';
        $action         = isset($_GET[$actionSign]) ? @$_GET[$actionSign] : 'index';
        //兼容入口用“-”的方式来组合控制器的方案
        $controller     = str_replace('-','.',$controller);
        $action         = str_replace('-','.',$action);
        $return['con']  = trim($controller);
        $return['act']  = trim($action);
        self::$G['mod'] = $return['con'];
        self::$G['act'] = $return['act'];
        //dump($return);
        return $return;
    }

    /**
     * 启动控制器及控制器方法
     * @param $controller
     * @param $action
     */
    public static function controller($controller,$action)
    {
        $controllerObj  = self::import($controller,'controller',true);
        if(method_exists($controllerObj,$action))
        {
            call_user_func(array( $controllerObj , $action));
        }else
        {
            if(DEBUG==true)
            {
                echo 'Error:Method ['.$action.'] is not exist!';
                exit;
            }else{
                $error404file = APPROOT.'/404.php';
                $errorStrs    = file_get_contents($error404file);
                echo $errorStrs;
            }
        }
    }
    /**
     * 核心方法 文件，模块载入
     * 本方法是本系统的基础，核心，支持“.”到目录的映射
     * 比如：“hailin.persion”将自动被映射为“hailin/persion”
     * 载入的起始路径依赖于APPROOT，ROOT，并首先在APPROOT的相应$type目录下搜寻，如果没有找到则到ROOT的$type目录下载入，否则报错并终止运行
     * @param $fileName    文件名称，不含后缀名,支持路径 “hailin.persion”
     * @param $type        文件类型，lib,controller,model,view 对应于APPROOT，ROOT下的相应目录
     * @param $isnew       是否自动创建对象，载入完毕后自动创建对象，必须保持载入的类名和目录一致 及：hailin.persion -> 类名 HailinPersion
     * @param $args        传入参数，用于视图文件载入出的视图变量处理
     * @param $isdir       是否强制为指定的路径
     * @param $dnsType     该参数只用在载入模型的时候用以指定该模型的数据库支持类型，当前支持mysql/mongodb
     * @return obj
     */
    public static function import($fileName,$type='lib',$isnew=false,$args=null,$isdir=false,$dnsType='mysql')
    {
        $fileArray  = self::checkDir($fileName);
        $fileNameOld = $fileName;
        $fileName   = $fileArray['filedir'];
        $fileDir    = $type;
        switch($type)
        {
            case 'lib':
                $fileEndExt = '.class.php';
                break;
            case 'view':
                $fileEndExt = '.html';
                break;
            default:
                $fileEndExt = '.'.$type.'.php';;
                break;
        }
        //强制为路径
        if($isdir){
            $fileNameOne= $fileNameOld.$fileEndExt;
            $fileNameTwo= $fileNameOld.$fileEndExt;
        }else
        {
            $fileNameOne= APPROOT.$fileDir.DS.$fileName.$fileEndExt;
            $fileNameTwo= ROOT.$fileDir.DS.$fileName.$fileEndExt;
        }
        if(isset($fileNameOne) && file_exists($fileNameOne))
        {
            //模板变量插入
            ($args && $type=='view') ? extract($args) : '';
            //模板文件是可以重复载入的
            if($type=='view')
            {
                $return = include($fileNameOne);
            }
            else
            {
                $return = include_once($fileNameOne);
            }
        }elseif(isset($fileNameTwo) && file_exists($fileNameTwo))
        {
            //模板变量插入
            ($args && $type=='view') ? extract($args) : '';
            if($type=='view')
            {
                $return = include($fileNameTwo);
            }else
            {
                $return = include_once($fileNameTwo);
            }

        }else
        {
            if(DEBUG==true)
            {
                echo 'Error:file['.$fileNameOne.'|'.$fileNameTwo.'] is not exist!';
                exit;
            }else{
                $error404file = APPROOT.'/404.php';
                $errorStrs    = file_get_contents($error404file);
                echo $errorStrs;
            }
        }
        //创建对象
        if($isnew)
        {
            $className = $fileArray['fileclass'];
            //对控制器，模型增加类名后缀，采用兼容写法
            $ext = '';
            if($type=='controller') $ext = 'Controller';
            if($type=='model') $ext = 'Model';
            $classNewName = $className.$ext;
            if(class_exists($classNewName))
            {
                //exit;
                if($type=='model')
                {
                   $return = new $classNewName($dnsType);
                }else
                {
                    $return = new $classNewName;
                }
            }else
            {
                if(class_exists($className))
                {
                    //exit;
                    if($type=='model')
                    {
                        $return = new $className($dnsType);
                    }else
                    {
                        $return = new $className;
                    }
                }else{
                    if(DEBUG==true)
                    {
                        echo 'Error:Class Name ['.$classNewName.'] is not exist!';
                        exit;
                    }else{
                        $error404file = APPROOT.'/404.php';
                        $errorStrs    = file_get_contents($error404file);
                        echo $errorStrs;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * 路径处理，根据名称生成相应的类名，文件路径等
     * @param $fileName
     * @return mixed
     */
    public  static function checkDir($fileName)
    {
        //echo $fileName;
        if($fileName)
        {
            $return['filedir']      = str_replace('.',DS,$fileName);
            $temp                   = explode('.',$fileName);
            $tempIndexMax           = count($temp)-1;
            $return['filename']     = $temp[$tempIndexMax];
            $className              = '';
            foreach($temp as $_temp)
            {
                $className         .= ucfirst($_temp);
            }
            $return['fileclass']    = $className;
        }else
        {
            $return['filedir']      = null;
            $return['filename']     = null;
            $return['fileclass']    = null;
        }
        //print_r($return);
        return $return;
    }

    /**
     * 创建一个数据模型
     * @param $model
     * @return mixed
     */
    public static function model($model,$dnsType='mysql')
    {
        //echo $dnsType;
        return self::import($model,'model',true,null,false,$dnsType);
    }

    /**
     * 格式化打印调试数据，用于php端的调试
     * @param $var
     */
    public static function dump($var)
    {
        $str = '<pre>';
        $str.= print_r($var,true);
        $str.= '</pre>';
        echo $str;
    }

    /**
     * 前端调试用方法，需要在chrome浏览器端安装相应的调试工具
     * @param $var
     */
    public static function jsonDump($var)
    {
        self::import('chrome.php','lib');
        ChromePhp::log($var);
    }

}
/**
 * 默认控制器实现
 * 本方法只实现了最简单的三个常规方法
 * assign 视图变量呼入
 * template 视图文件载入
 */
class Controller
{
    public $tplVars = array();

    /**
     * 视图变量呼入
     * 支持单个变量的呼入，多个变量的呼入请用带有意义的数组进行呼入
     * assign('a','a.value')
     * assign(array('a'=>'1','b'=>'2','c'=>array(1,2,3,4,5)))
     * @param $key assign('a','a.value');assign(array('a'=>'1','b'=>'2','c'=>array(1,2,3,4,5)))
     * @param string $values
     */
    public function assign($key,$values='')
    {
        if(is_array($key))
        {
            foreach($key as $_key=>$_value)
            {
                $this->tplVars[$_key] = $_value;
            }
        }else
        {
            $this->tplVars[$key] = $values;
        }
    }

    /**
     * 批量获取外部变量，一般用来处理外部提交的数据（$_GET/$_POST/$_REQUEST）
     * 只支持一维数组
     * @param $paras    需要处理的外部变量数组
     * @param $keys     需要返回的变量数组 arr
     */
    public function getRequests($keys,$paras=array())
    {
        if(empty($paras)) $paras = $_REQUEST;
        if(is_array($paras) && is_array($keys)){
            $return = array();
            foreach($keys as $key=>$val)
            {
                if(isset($paras[$key])){
                    $return[$key] = trim($paras[$key]);
                }else{
                    $tempVal = isset($val) ? $val : '';
                    $return[$key] = $tempVal;
                }
            }
            return $return;
        }else{
            return false;
        }
    }

    /**
     * 获取单个外部变量
     * @param $key        变量名
     * @param $defaultVal 初始值
     * @param $paras      需要处理的外部变量数组
     */
    public function getRequest($key,$defaultVal='',$paras=array())
    {
        if(empty($paras)) $paras = $_REQUEST;
        $val  = isset($paras[$key]) ? $paras[$key] : $defaultVal;
        if(is_string($val)) $val  = trim($val);
        return $val;
    }

    /**
     * 视图文件载入
     * @param $tplName 视图名称，不带后缀
     * @return mixed
     */
    public function template($tplName,$isdir=false)
    {
        $args = $this->tplVars;
        return core::import($tplName,'view',false,$args,$isdir);
    }

    /**
     * 模型文件的载入
     * @param $model 模型名称，不带后缀
     * @return mixed
     */
    public function model($model)
    {
        return core::model($model);
    }


}

/**
 * 模型基类
 * 本类实现大部分的数据库操作方法
 * model -> table[表属性]，db[数据库对象],setDns,query,insert,update,del,get,getOne,
 * update的where；del的where，get，getOne的jion,limit,where,order,group均支持链式写法
 * $m1->where(array())->field(array('id','username','roleid'))->limit(0,10)->order(array('id'=>'desc','username'=>'asc'))->group('id','username')->getOne();
 * 在模型中随时可以根据实际的需要切换到不同的数据库服务器上去（必须先在配置文件上进行多个数据库服务器的连接配置）
 * setDns($dbdnsid)
 */
class Model
{
    public $db          = '';
    public $mongo       = '';
    public $redis       = '';
    public $table       = '';
    public $execTable   = '';

    /**
     * 支持非关系类数据库mongodb
     * @param $dns     默认 mysql
     * @return mixed
     */
    public function __construct($dns='mysql')
    {
        //dump($this);
        if($dns=='mysql')
        {
            $this->db = core::$G['db'];
            $this->db->table($this->table);
        }

        $this->mongoSetDns();
        $this->redisSetDns();
    }

    /**
     * 设置mongodb的多服务器切换
     * @param string $dnsId
     */
    public function mongoSetDns($dnsId='')
    {
        $dbs = core::$G['mongo'];
        if($dnsId==''){
            foreach($dbs as $db){
                $this->mongo = $db;
                break;
            }
        }else{
            $this->mongo = $dbs[$dnsId];
        }
    }
    /**
     * 设置redis的多服务器切换
     * @param string $dnsId
     */
    public function redisSetDns($dnsId='')
    {
        $dbs = core::$G['redis'];
        if($dnsId==''){
            foreach($dbs as $db){
                $this->redis = $db;
                break;
            }
        }else{
            $this->redis = $dbs[$dnsId];
        }
    }

    /**
     * 表定义
     */
    protected function setTable()
    {
        if($this->execTable)
        {
            $this->db->table($this->execTable);
        }else
        {
            $this->db->table($this->table);
        }
    }
    /**
     * 切换数据库连接标示
     * @param $dnsId
     */
    public function setDns($dnsId)
    {
        $rs = $this->db->setDns($dnsId);
        return $rs;
    }

    /**
     * 获取一条数据
     * @return mixed
     */
    public function getOne($sql=null)
    {
        $this->setTable();
        $rs = $this->db->getOne($sql);
        $this->execTable = '';
        return $rs;
    }

    /**
     * 获取多条数据
     * @return mixed
     */
    public function get($sql=null)
    {
        $this->setTable();
        $rs = $this->db->get($sql);
        $this->execTable = '';
        return $rs;
    }
    public function count($sql=null)
    {
        $this->setTable();
        $rs = $this->db->count($sql);
        $this->execTable = '';
        return $rs;
    }

    /**
     * 返回数据集[二维]
     * @param $sql
     * @return mixed
     */
    public function query($sql)
    {
        $this->setTable();
        $rs = $this->db->query($sql);
        $this->execTable = '';
        return $rs;
    }

    /**
     * 删除指定的数据
     * @param $where
     * @return mixed
     */
    public function del($where=null)
    {
        $this->setTable();
        $rs = $this->db->delete($where);
        $this->execTable = '';
        return $rs;
    }
    public function insert($params)
    {
        $this->setTable();
        $rs = $this->db->insert($params);
        $this->execTable = '';
        return $rs;
    }
    public function table($table)
    {
        $this->execTable = $table;
        return $this;
    }
    public function update($params,$where=null)
    {
        $this->setTable();
        $rs = $this->db->update($params,$where);
        $this->execTable = '';
        return $rs;
    }
    public function jion($jion)
    {
        $this->setTable();
        $this->db->jion($jion);
        return $this;
    }
    public function where($where)
    {
        $this->setTable();
        $this->db->where($where);
        return $this;
    }
    public function field($params)
    {
        $this->setTable();
        $this->db->field($params);
        return $this;
    }
    public function group($params)
    {
        $this->setTable();
        $this->db->group($params);
        return $this;
    }
    public function limit($start,$limit)
    {
        $this->setTable();
        $this->db->limit($start,$limit);
        return $this;
    }
    public function order($params)
    {
        $this->setTable();
        $this->db->order($params);
        return $this;
    }
    public function lastSql()
    {
        return $this->db->sql;
    }
    //+------------
    //+事务相关
    /**
     * 启动事务
     * @return mixed
     */
    public function start()
    {
        return $this->query("START TRANSACTION");
    }

    /**
     * 事务回滚
     * @return mixed
     */
    public function back()
    {
        return $this->query("ROLLBACK");
    }

    /**
     * 事务结束，自动提交事务
     * @return mixed
     */
    public function end()
    {
        return $this->query("COMMIT");
    }
}
//定义空对象
class NullObj {}
