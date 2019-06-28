<?php

class CaijiLoadController extends Controller
{
    //cai
    //https://doc.querylist.cc/
    public function index()
    {
        //载入采集模块
        import('caiji', 'lib', false);

        //北京
        $pageUrlList = 'http://jiudian.cncn.com/beijing_0_0_0_0_0_0_0_1.htm';
        $rules = array(
            'hotel' => array('.J_title > a ', 'href')
        );
        //采集主函数
        $pageUrlListHtml = get_fcontent($pageUrlList);
        $data = Caiji::Query($pageUrlListHtml, $rules,'','UTF-8','GB2312')->data;
        echo '<meta charset="utf-8">';
        //dump($data);

        foreach ($data as $item)
        {
            $hotelUrl = 'http://jiudian.cncn.com'.$item['hotel'];
            echo $hotelUrl;
        }

    }
}

function get_fcontent($url, $timeout = 5)
{
    $url = str_replace("&amp;", "&", urldecode(trim($url)));
    $cookie = tempnam("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    //模拟浏览器 在HTTP请求中包含一个"User-Agent: "头的字符串。
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");//需要获取的URL地址，也可以在 curl_init()函数中设置。
    curl_setopt($ch, CURLOPT_URL, $url);
    //连接结束后保存cookie信息的文件。
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //HTTP请求头中"Accept-Encoding: "的值。支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，请求头会发送所有支持的编码类型。在cURL 7.10中被加入。
    curl_setopt($ch, CURLOPT_ENCODING, "");
    //将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    ////当根据Location:重定向时，自动设置header中的Referer:信息。
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    //禁用后cURL将终止从服务端进行验证。使用CURLOPT_CAINFO选项设置证书使用CURLOPT_CAPATH选项设置证书目录 如果CURLOPT_SSL_VERIFYPEER(默认值为2)被启用，CURLOPT_SSL_VERIFYHOST需要被设置成TRUE否则设置为FALSE。
    //自cURL 7.10开始默认为TRUE。从cURL 7.10开始默认绑定安装。
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //在发起连接前等待的时间，如果设置为0，则无限等待。
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    //设置cURL允许执行的最长毫秒数。
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    //指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的。
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}