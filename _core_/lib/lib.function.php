<?php
/**
 * 定义的一些全局的方法
 * 本文件中定义的均为一些全局范围的公用函数，可以直接进行调用的函数
 * 作者:Hailin<hailingr@foxmail.com>
 * 创建:2014.04.24 chengdu.china
 */

//+------------------------------------------------------
//+一些全局函数的定义
/**
 * 防止注入等攻击输入
 */
function checkSafeInput()
{
    include_once (ROOT.'/lib/lib.safe.php');
}

/**
 * 格式化打印
 * @param $var
 */
function dump($var)
{
    //core::dump($var);
    $str = '<pre>';
    $str .= print_r($var, true);
    $str .= '</pre>';
    echo $str;
}

/**
 * 格式化调试，需要chome浏览器支持
 * @param $var
 */
function jsonDump($var)
{
    core::jsonDump($var);
}

/**
 * 核心快捷方法 文件载入
 * @param $fileName    包含文件名称，不含‘.php’后缀名,如果在fileName中含有'.'则会自动将‘.’替换成路径
 * @param $type        文件类型，lib,controller,model,
 * @param $isnew       是否自动创建对象
 */
function import($fileName, $type = 'lib', $isnew = false, $args = null)
{
    return core::import($fileName, $type, $isnew, $args);
}

/**
 * 核心方法，启动指定的控制器
 * @param $controller
 * @param $action
 */
function controller($controller, $action)
{
    return core::controller($controller, $action);
}

/**
 * 核心方法
 * 模型载入
 * @param $model
 * @return obj
 *
 */
function model($model, $dnsType = 'mysql')
{
    return core::model($model, $dnsType);
}
/**
 * 全局对象数据设置与获取
 * $val 为空的时候是获取
 * @param $key     键
 * @param $val     值
 * @return mixed 
 */
function G($key,$val='')
{
	if($val==''){
		if(isset(core::$G[$key])) return core::$G[$key];
		else return '';
	}else{
	    core::$G[$key] = $val;
		return $val;
	}
}

/**
 * 格式化手机号码，将手机号码中间的五位设置为”*“
 * @param $tel
 * @param bool $isecho 是否显示
 * @return string
 */
function tel($tel, $isecho = true)
{
    $pattern = "/(1\d{1,2})\d\d(\d{0,3})/";
    $replacement = "\$1*****\$3";
    $uname = preg_replace($pattern, $replacement, $tel);
    if ($isecho) {
        echo @mb_substr($uname, 0, 15, 'utf8');
    } else {
        return @mb_substr($uname, 0, 15, 'utf8');
    }
}
/**
 * cookie操作方法
 * 删除一个cookie           cookie('key','')
 * 获取一个cookie           cookie('key')
 * 设置cookie              cookie('key','test');
 * @param $key            cookie名称
 * @param $value          cookie值
 * @param int $kptime     cookie有效时间
 * @param string $pa      cookie路径
 */
function cookie($key, $value = null, $kptime = 7200000, $pa = "/")
{
    $config          = G('config');
    $cfgDomainCookie = $config['cookeDomain'];
    $cfgCookieEncode = '!@_cookie_hailin_encode@!';

    if ($value) {
        //设置cookie
        setcookie($key, $value, time() + $kptime, $pa, $cfgDomainCookie);
        setcookie($key . '__ckMd5', substr(md5($cfgCookieEncode . $value), 0, 16), time() + $kptime, $pa, $cfgDomainCookie);
    } else {
        //writeLog($key.':'.$value.'|');
        //返回cookie
        if ($value === null) {
            //if (!isset($_COOKIE[$key]) || !isset($_COOKIE[$key . '__ckMd5'])) {
            if (isset($_COOKIE[$key])) {
                return $_COOKIE[$key];
            } else {
                return '';
            }
        } else {
            //删除cookie
            if($cfgDomainCookie){
                setcookie($key, '', time() - 360000, "/", $cfgDomainCookie);
                setcookie($key . '__ckMd5', '', time() - 360000, "/", $cfgDomainCookie);
            }else{
                setcookie($key, '', time() - 360000, "/");
                setcookie($key . '__ckMd5', '', time() - 360000, "/");
            }

        }
    }
}

/**
 * 字符串截取函数
 * @param $str              原始字符串
 * @param int $start        开始
 * @param $length           长度
 * @param string $charset   编码，默认utf-8
 * @param bool $suffix      是否显示省略号
 * @return string
 */

function cutstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr")) {
        return mb_substr($str, $start, $length, $charset);
    } elseif (function_exists("iconv_substr")) {
        return iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = implode("", array_slice($match[0], $start, $length));
        if ($suffix)
            return $slice . "...";
        else
            return $slice;
    }
}

/**
 * 加密函数
 * @param $string               需要加密的字符串
 * @param string $operation     加密、解密方式 加密ENCODE;解密DECODE
 * @param string $key           加密key
 * @param int $expiry           有效期
 * @return string
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
    // 当此值为 0 时，则不产生随机密钥
    $ckey_length = 4;
    // 密匙
    $key = md5($key ? $key : '@hailingr*Firn@25071591*hailingr@foxmail.com@');

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上并不会增加密文的强度
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

/**
 * 从seo配置文件中取得相应页面的seo信息
 * @param $type 页面标示 首页(index)，圈子首页(groupIndex).....,具体参见follow.txt
 * @param $arrs 二维数组 需要替换的动态参数变量 array(array('圈子名'=>'程序员'));
 * 比如 $seoInfo = getSeoInfo('groupInfo',array(''))
 */
function getSeoInfo($type, $arrs = null)
{
    $configs = include(APPROOT . 'data/seo/seo.txt');
    if (!empty($configs)) {
        $key = trim($type);
        if (isset($configs[$key])) {
            $title = $configs[$key]['title'];
            $discription = $configs[$key]['discription'];
            $keywords = $configs[$key]['keywords'];
            $author = $configs[$key]['author'];
            if ($arrs) {
                foreach ($arrs as $arr) {
                    if ($arr) {
                        foreach ($arr as $ak => $av) {
                            $title = str_replace('#' . $ak . '#', $av, $title);
                            $discription = str_replace('#' . $ak . '#', $av, $discription);
                            $keywords = str_replace('#' . $ak . '#', $av, $keywords);
                            $author = str_replace('#' . $ak . '#', $av, $author);
                        }

                    }
                }
            }
            return array(
                'title' => $title,
                'discription' => $discription,
                'keywords' => $keywords,
                'author' => $author
            );
        } else {
            return $configs['global'];
        }
    } else {
        return array(
            'title' => '景区酒店_租车_门票预订_最新周边自助游攻略活动-悠鹿自主游',
            'discription' => '悠鹿旅游，专注于周边自助游的一站式服务旅游网站，提供四川成都、九寨沟、峨眉山等景区周边酒店、旅游租车、门票和团购预订；以及提供景点自助游攻略与活动召集的网站。悠鹿，你的旅游你做主！',
            'keywords' => '',
            'author' => '悠鹿自主游@2014-2015'
        );
    }
}

/**
 * 获取客户端ip地址
 * @return string
 */
function getIp()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } elseif (getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = "0.0.0.0";
    }
    return $ip;
}

/**
 * 写日志函数
 * @param $content          需要写入的内容
 * @param string            $logFile 自定义文件名，支持路径
 * @param string            $type 文件写入类型，默认a
 * @param bull              $time 是否自动串接时间
 * @return bool             写入是否成功
 */
function writeLog($content, $logFile = '', $type = 'a', $time = true)
{
    if (!is_string($content)) $content = var_export($content, true);
    if ($time) {
        $content = "[" . date("Y-m-d H:i:s") . "] " . $content . "\r\n";
    }

    $logFileDir = APPROOT . 'data/log/';
    if (!file_exists($logFileDir)) mkdir($logFileDir);
    if (!$logFile) $logFile = $logFileDir . 'log.txt';
    $oldmask = @umask(0);
    $fp = @fopen($logFile, $type);
    @flock($fp, 3);
    if (!$fp) {
        return false;
    } else {
        @fwrite($fp, $content);
        @fclose($fp);
        @umask($oldmask);
        return true;
    }
}

/**
 * 检测文件是否在指定的秒数后过期,过期返回false，否则返回文件内容
 * @param $file
 * @param $times 秒
 */
function checkFileDateTime($file, $times)
{
    if (file_exists($file)) {
        $lastUpdateTime = filemtime($file);
        $noTime = time();
        $endTime = $lastUpdateTime + $times;
        if ($endTime < $noTime) {
            return false;
        } else {
            return file_get_contents($file);
        }

    } else {
        return false;
    }
}

/**
 * 给文件写入内容
 * @param $content
 * @param $file
 * @param string $type
 * @return bool
 */
function WriteFile($content, $file, $type = 'w')
{
    $oldmask = @umask(0);
    $fp = @fopen($file, $type);
    @flock($fp, 3);
    if (!$fp) {
        return false;
    } else {
        @fwrite($fp, $content);
        @fclose($fp);
        @umask($oldmask);
        return true;
    }
}

/**
 * 获取客户端系统类型
 * @return string
 */
function getOS()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $agent = strtolower($agent);
    if (strpos($agent, 'windows')) {
        $platform = 'windows';
    } elseif (strpos($agent, 'macintosh')) {
        $platform = 'mac';
    } elseif (strpos($agent, 'ipod')) {
        $platform = 'ipod';
    } elseif (strpos($agent, 'ipad')) {
        $platform = 'ipad';
    } elseif (strpos($agent, 'iphone')) {
        $platform = 'iphone';
    } elseif (strpos($agent, 'android')) {
        $platform = 'android';
    } elseif (strpos($agent, 'unix')) {
        $platform = 'unix';
    } elseif (strpos($agent, 'linux')) {
        $platform = 'linux';
    } else {
        $platform = 'other';
    }
    return $platform;
}

/**
 * 判断浏览器是否是移动浏览器
 * @return bool
 */
function isMobileRequest()
{
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    $client = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($client)))
        $mobile_browser++;
    if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;
    if (isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;
    $mobile_ua = strtolower(substr($client, 0, 4));
    $mobile_agents = array(
        'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
        'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
        'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
        'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
        'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
        'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
        'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
        'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
        'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
    );
    if (in_array($mobile_ua, $mobile_agents))
        $mobile_browser++;
    if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        $mobile_browser++;
    // Pre-final check to reset everything if the user is on Windows
    if (strpos(strtolower($client), 'windows') !== false)
        $mobile_browser = 0;
    // But WP7 is also Windows, with a slightly different characteristic
    if (strpos(strtolower($client), 'windows phone') !== false)
        $mobile_browser++;
    if ($mobile_browser > 0)
        return true;
    else
        return false;
}

/**
 * 生成静态化缓存文件
 * 当静态文件未过期则读取静态文件，否则生成静态文件
 * @return bool
 */
function goChtml()
{
    //定义需要及时访问的模块
    $limitMod = isset($_GET['m']) ? $_GET['m'] : '';
    $limitMod = trim($limitMod);
    //需要跳过静态处理的mod
    //if($limitMod == 'my' || $limitMod == 'ask' || $limitMod == 'pay' || $limitMod=='destes' || $limitMod=='hotels' || $limitMod=='ticket')
    if ($limitMod == 'my' || $limitMod == 'ask' || $limitMod == 'pay') {
        return true;
    }
    $limitAct = isset($_GET['a']) ? $_GET['a'] : '';
    //需要跳过的方法
    if ($limitAct == 'createactivity' || $limitAct == 'updateactivity' || $limitAct == 'groupcreate$' || $limitAct == 'editGroup' || $limitAct == 'createarticle' || $limitAct == 'editArticle' || $limitAct == 'travelpost' || $limitAct == 'editTravel') {
        return true;
    }

    //定义访问路径
    $requestUrl = $_SERVER['REQUEST_URI'];
    //可见的url路径
    $hostUrl = $_SERVER["SERVER_NAME"] . $requestUrl;
    //如果url中含有nohtml跳过
    if (strstr($hostUrl, 'nohtml')) {
        return true;
    }
    //真实的程序执行路径
    $hostRUrl = 'http://' . $_SERVER["SERVER_NAME"] . '/index.php?' . ($_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : '');

    $isIndex = strstr($requestUrl, 'index.php');
    //如果不是走index.php流程，那么走静态化缓存路线
    if (!$isIndex && $_SERVER['QUERY_STRING']) {
        //debug($hostRUrl);
        //debug($hostUrl);
        $htmlDir = APPROOT . '/data/html/';
        $htmlFile = $htmlDir . str_replace('/', '_', $requestUrl) . '.html';

        //debug($htmlFile);
        if (checkHtml($htmlFile, $hostRUrl)) {
            createHtml($hostRUrl, $htmlFile);
        }

        //直接输出html文件
        header("Content-type: text/html; charset=utf-8");
        echo readHtml($htmlFile);
        exit;
    }
}

/**
 * 创建静态文件
 * @param $httpUrl
 * @param $htmlFile
 */
function createHtml($httpUrl, $htmlFile)
{
    $content        = file_get_contents($httpUrl);
    $hp             = fopen($htmlFile, "w+");
    $lentContent    = strlen($content);
    if ($lentContent > 1000) {
        if (flock($hp, LOCK_EX)) {
            $content = unityImgAlt($content);
            //writeLog($content);
            fwrite($hp, $content);
            flock($hp, LOCK_UN);
            fclose($hp);
        }
    }
}

/**
 * 读取静态文件内容
 * @param $htmlFile
 * @return string
 */
function readHtml($htmlFile)
{
    //echo $htmlFile;
    return file_get_contents($htmlFile);
}

/**
 * 判断静态文件是否过期，过期true，未过期false
 * @param $htmlFile
 * @param string $hostRUrl
 * @return bool
 */
function checkHtml($htmlFile, $hostRUrl = '')
{
    //定义过期时间,5分钟
    $lostTime = 900;
    if (file_exists($htmlFile)) {
        $fileSize = filesize($htmlFile);
        if ($fileSize <= 0) {
            //debug('页面生成有误:'.$htmlFile.'|'.$hostRUrl);
            return true;
        }
        $checkTime = filemtime($htmlFile);
        $ccTime = time() - ($checkTime + $lostTime);
        if ($ccTime > 0) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

/**
 * 批量处理<img>标签里面的alt属性
 * @param $htmls
 * @return mixed
 */
function unityImgAlt($htmls)
{
    $title = "悠鹿自助游_";
    $pattern = "/<img[^>]+>/";
    preg_match_all($pattern, $htmls, $matches);
    if ($matches[0]) {
        for ($i = 0; $i <= count($matches[0]); $i++) {
            $is = isset($matches[0][$i]) ? $matches[0][$i] : '';
            if ($is) {
                if (!strstr($matches[0][$i], "alt=")) {
                    $html = str_replace("<img", "<img alt=\"{$title}\"", $matches[0][$i]);
                    $htmls = str_replace($matches[0][$i], $html, $htmls);
                }
            }
        }
        $reg = array("alt=\"\"", "alt=\" \"", "alt=\"  \"", "alt=''", "alt=' '", "alt='  '");
        $txt = "alt=\"悠鹿自助游_\"";
        $htmls = str_replace($reg, $txt, $htmls);
    }
    return $htmls;
}

/**
 * 对二维数组进行排序
 * @param $arr           二维数组
 * @param $keys          排序的字段
 * @param string $type   排序方式
 * @return array
 */
function arraySort($arr, $keys, $type = 'asc')
{
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    $index = 0;
    foreach ($keysvalue as $k => $v) {
        $new_array[$index] = $arr[$k];
        $index++;
    }
    return $new_array;
}

/**
 * 二维数组去重
 * @param $array2D
 * @return array
 */
function arrayUnique2d($arr,$key){
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
            unset($arr[$k]);
        } else {
            $tmp_arr[] = $v[$key];
        }
    }
    //sort($arr); //sort函数对数组进行排序
    return $arr;
}

/**
 * amr到mp3的转换
 * $mp3 = amrTomp3('\data\upload\vi\20141111101117_46172cece8ae8-7ae2-4831-b8be-509aa2678767.amr');
 * echo $mp3;
 * @param $file
 * @return mixed
 */
function amrTomp3($file, $code = '64')
{
    if ($file) {
        $httpFile = str_replace('.amr', '.mp3', $file);
        $httpFileMp3 = APPROOT . $httpFile;
        if (file_exists($httpFileMp3)) {
            return $httpFile;
        }
        $file = APPROOT . $file;
    }
    if (file_exists($file)) {
        //echo $file;
        $newfileName = str_replace('.amr', '.mp3', $file);
        //echo $newfileName;
        $execFile = APPROOT . '/ffmpeg/' . $code . '/ffmpeg.exe';
        $execCmd = $execFile . " -i " . $file . " " . $newfileName;
        //echo $execCmd;
        exec($execCmd);
        return $httpFile;
    }
}


function writeExeTime()
{
    $startTime = STARTTIME;
}

/**
 * 将对象转换为数组，长用于json对象的解析
 * @param $array
 * @return array
 */
function object2array($array)
{
    if (is_object($array)) {
        $array = (array)$array;
    }
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $array[$key] = object2array($value);
        }
    }
    return $array;
}

/**
 * 将一个数随机拆成指定份数
 * @param $num
 * @param $count
 * @return array
 */
function spiltNum($num, $count)
{
    $div = $count;
    $total = $num;
    $a = range(0, $div - 1);
    $base = ($total - array_sum($a)) / $div;
    for ($len = count($a), $i = 0; $i < $len; $i++) {
        $base = (int)$base;
        $a[$i] += $base;
    }
    $realTotal = array_sum($a);
    $errorNum = $total - $realTotal;
    if ($errorNum > 0) {
        $a[0] = $a[0] + $errorNum;
    }
    if ($errorNum < 0) {
        $a[0] = $a[0] - $errorNum;
    }
    return $a;
}

/**
 * 获取指定地址的经纬坐标
 * @param $address
 * @param string $city
 * @return mixed
 */
function getLngLat($address,$city='')
{
    $address = $address ? $address : '中国';
    $key     = isset(core::$G['config']['bmap']['ak']) ? core::$G['config']['bmap']['ak'] : '1MasrxSBSfRTUNMnck3fejCBc7amXYjPn';
    $httpUrl = "http://api.map.baidu.com/geocoder/v2/?address=".$address."&output=json&ak=".$key."";
    $return  = file_get_contents($httpUrl);
    return json_decode($return);
}

/**
 * 写入错误日志
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 */
function customError($errno, $errstr, $errfile, $errline)
{
    $errorFile          = APPROOT.'/data/log/php/error_'.date("Y-m-d").'.txt';
    $errorContent       = '发生时间:'.date("Y-m-d H:i:s").' No.'.$errno.' '."\r\n".'抛出文件:”'.$errfile.'“ 第'.$errline.'行'."\r\n".'错误描述:'.$errstr.''."\r\n-------------------------------------------\r\n";
    $errorShowContent   = '发生时间:'.date("Y-m-d H:i:s").' No.'.$errno.'<br />错误描述:'.$errstr.''."<hr>";
    //if(strstr($errorContent,'use mysqli')){return true;};
    echo $errorShowContent;
    $oldmask        = @umask(0);
    $fp             = @fopen($errorFile,'a');
    @flock($fp, 3);
    if (!$fp) {
        return false;
    } else {
        @fwrite($fp, $errorContent);
        @fclose($fp);
        @umask($oldmask);
        return true;
    }
}

/**
 * 生成图片验证码
 * @param int $num
 * @param int $size
 * @param int $width
 * @param int $height
 *
 * vCode(m,n,x,y) m个数字  显示大小为n   边宽x   边高y
 * http://blog.qita.in
 * 自己改写记录session $code
 * session_start();
 * vCode(4, 15);/4个数字，显示大小为15
 */
function vCode($num = 4, $size = 20, $width = 0, $height = 0) {
    !$width && $width = $num * $size * 4 / 5 + 5;
    !$height && $height = $size + 10;
    // 去掉了 0 1 O l 等
    $str = "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVW";
    $code = '';
    for ($i = 0; $i < $num; $i++) {
        $code .= $str[mt_rand(0, strlen($str)-1)];
    }
    // 画图像
    $im = imagecreatetruecolor($width, $height);
    // 定义要用到的颜色
    $back_color = imagecolorallocate($im, 235, 236, 237);
    $boer_color = imagecolorallocate($im, 118, 151, 199);
    $text_color = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
    // 画背景
    imagefilledrectangle($im, 0, 0, $width, $height, $back_color);
    // 画边框
    imagerectangle($im, 0, 0, $width-1, $height-1, $boer_color);
    // 画干扰线
    for($i = 0;$i < 5;$i++) {
        $font_color = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagearc($im, mt_rand(- $width, $width), mt_rand(- $height, $height), mt_rand(30, $width * 2), mt_rand(20, $height * 2), mt_rand(0, 360), mt_rand(0, 360), $font_color);
    }
    // 画干扰点
    for($i = 0;$i < 50;$i++) {
        $font_color = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $font_color);
    }
    // 画验证码
    @imagefttext($im, $size , 0, 5, $size + 3, $text_color, APPROOT.'/data/simsun.ttc', $code);

    $enCode = authcode($code,'ENCODE');
    cookie('imgCode',$enCode,5*60);

    header("Cache-Control: max-age=1, s-maxage=1, no-cache, must-revalidate");
    header("Content-type: image/png;charset=gb2312");
    imagepng($im);
    imagedestroy($im);
}

/**
 * 获取ip归属
 * @return string
 */
function getAreaInfo() {


    /*$url= 'http://int.dpool.sina.com.cn/iplookup/iplookup.php';
    $content = file_get_contents($url);
    $content = iconv("GB2312//IGNORE","UTF-8", $content);
    $content = str_replace("1",'',$content);
    $content = str_replace("-1",'',$content);
    $content = str_replace("-",'',$content);
    $content = str_replace("	",'',$content);*/

    $_content = cookie('ipaddr');
    if(!$_content){
        $api = 'http://ip.taobao.com//service/getIpInfo.php?ip='.getIp();
        //$api = 'http://ip.taobao.com//service/getIpInfo.php?ip=61.157.126.12';
        $content = file_get_contents($api);
        $json = json_decode($content,true);
        if($json['code']==0){
            $json = $json['data'];
            $content = $json['country'].' '.$json['region'].' '.$json['city'] . ' '.$json['isp'];
        }else{
            $content = '未知地区';
        }
        cookie('ipaddr',$content,7200);
    }else{
        $content = $_content;
    }

    return $content;
}

/**
 * 上传文件到腾讯云
 * @param $from
 * @param $to
 * @return string
 */
function uploadQQYunFile($from,$to)
{
    //dump($_SERVER);
    $domain = 'http://'.$_SERVER['SERVER_NAME'].'/cos-php-sdk-master/sample.php?from='.$from.'&to='.$to;
    //echo $domain;
    return file_get_contents($domain);
}

/**
 * 下载远程图片
 * @param $url
 * @param string $filename
 * @return bool|string
 */
function downImage($url,$filename="") {
    if($url==""):return false;endif;
    if($filename=="") {
        //$ext=strrchr($url,".");
        $ext = '.jpg';
        //if($ext!=".gif" && $ext!=".jpg"):return false;endif;
        $filename = date("dMYHis").$ext;
        $dir = APPROOT.'/data/download/';
        if(!file_exists($dir)) mkdir($dir);
        $filename = $dir.$filename;
    }
    ob_start();
    readfile($url);
    $img = ob_get_contents();
    ob_end_clean();
    $size = strlen($img);
    $fp2=@fopen($filename, "a");
    fwrite($fp2,$img);
    fclose($fp2);
    return $filename;
}

/**
 * emoji表情字符替换
 * @param $text
 * @return mixed|string
 */
function replaceEmojiImg($text,$html=false) {
    include_once(APPROOT.'/lib/emoji/emoji.php');
    //$text = str_replace('?','',$text);
    $text = emoji_docomo_to_unified($text);   # DoCoMo devices
    $text = emoji_kddi_to_unified($text);     # KDDI & Au devices
    $text = emoji_softbank_to_unified($text); # Softbank & pre-iOS6 Apple devices
    $text = emoji_google_to_unified($text);   # Google Android devices
    if($html) $text = emoji_unified_to_html($text);
    return $text;
}

/**
 * 生成二维码图片
 * @param $data
 */
function showQrCoder($data)
{
    include_once(APPROOT.'/lib/phpqrcode/phpqrcode.php');
    QRcode::png($data);
}




