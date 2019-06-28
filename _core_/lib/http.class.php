<?php
/**
 * 简单http操作类
 * 可以向指定的url地址发送GET,POST请求
 * 作者:Hailin<hailingr@foxmail.com>
 * 创建:2014.03.07 chengdu.china
 */
class Http
{
    /**
     * post 方式提交url请求
     * @param $url
     * @param array $params
     * @return bool|mixed|string
     */
    function post($url,$params=array())
    {
        return $this->httpRequest($url,'post',$params);
    }

    /**
     * GET 方式提交url请求
     * @param $url
     * @param array $params
     * @return bool|mixed|string
     */
    function get($url,$params=array())
    {
        return $this->httpRequest($url,'get',$params);
    }

    /**
     * Http 请求
     * @param $url
     * @param $method
     * @param array $params
     * @return bool|mixed|string
     */
    function httpRequest($url,$method,$params=array()){
        if(trim($url)==''||!in_array($method,array('get','post'))){
            return false;
        }
        $curl=curl_init();
        $scheme = parse_url($url, PHP_URL_SCHEME);

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_HEADER,0 ) ;
        switch($method){
            case 'get':
                $str='?';
                foreach($params as $k=>$v){
                    $str.=$k.'='.$v.'&';
                }
                if(strstr($url,'?')){
                    $str='&'.substr($str,1);
                }
                $str=substr($str,0,-1);
                $url.=$str;
                curl_setopt($curl,CURLOPT_URL,$url);
                break;
            case 'post':
                curl_setopt($curl,CURLOPT_URL,$url);
                curl_setopt($curl,CURLOPT_POST,1 );
                curl_setopt($curl,CURLOPT_POSTFIELDS,$params);
                break;
            default:
                $result='';
                break;
        }
        if($scheme == 'https') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        $result=curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
     * @param string $url
     * @param string $postdata
     * @param array $options
     * @return mixed
     */
    function curl_post_https($url='', $postdata='', $options=array()){
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata); // Post提交的数据包
        //curl_setopt($curl, CURLOPT_COOKIEFILE, ‘cookie.txt’); // 读取上面所储存的Cookie信息
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * @param string $url
     * @param string $postdata
     * @param array $options
     * @return mixed
     */
    function curl_post($url='', $postdata='', $options=array()){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if (!empty($options)){
            curl_setopt_array($ch, $options);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * @param string $url
     * @param array $options
     * @return mixed
     */
    function curl_get($url='', $options=array()){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if (!empty($options)){
            curl_setopt_array($ch, $options);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * post方式发送
     * @param $url
     * @param $data_string
     * @return array
     */
    function curl_post_json($url,$data,$method='POST')
    {
        if (isset($data)) {
            $data_string = json_encode($data);
        }
        //echo $data_string;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (strtoupper($method) != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)'); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
            //'Accept: application/json',
            //'Authorization: Bearer ' . $this->getToken()
            // 'Content-Length: ' . strlen($data_string)
        ));
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        curl_close($ch);
        return $result;
    }
}
