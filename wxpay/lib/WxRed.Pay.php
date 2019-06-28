<?php
/**
$money = 100;
$mch_billno = MCHID.date('YmdHis').rand(1000, 9999);  //订单号
$act_name = "红包";  //活动名称，没鸟用，目前微信版本里没显示的地方

$wxhb = new wxhb();
$wxhb->setPara("nonce_str", $wxhb->createNoncestr()); //随机字符串
$wxhb->setPara("mch_billno", $mch_billno); //订单号
$wxhb->setPara("mch_id", MCHID); //商户号
$wxhb->setPara("wxappid", APPID);  //公众号
$wxhb->setPara("nick_name", '昵称'); //昵称，没鸟用，目前微信版本里没显示的地方
$wxhb->setPara("send_name", '网站名'); //红包发送者名称
$wxhb->setPara("re_openid", $openid); //发放者openid
$wxhb->setPara("total_amount", $money); //付款金额，单位分
$wxhb->setPara("min_value", $money); // 最小红包金额，单位分
$wxhb->setPara("max_value", $money); // 最大红包金额，单位分
$wxhb->setPara("total_num", 1); //红包发放总人数
$wxhb->setPara("wishing", '恭喜恭喜！'); //红包祝福诧
$wxhb->setPara("client_ip", ''); //调用接口的机器 Ip 地址
$wxhb->setPara("act_name", $act_name); //活动名称
//下面的都没鸟用，目前微信版本里没显示的地方
$wxhb->setPara("remark", '');//备注信息
$wxhb->setPara("logo_imgurl", ''); //商户logo的url
$wxhb->setPara("share_content", ''); //分享文案
$wxhb->setPara("share_url", ''); //分享链接
$wxhb->setPara("share_imgurl", ''); //分享的图片url

$postxml = $wxhb->createXml();
$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
$response = $wxhb->curlPostSsl($url, $postxml);

$responseObj = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
 */
define("APPID","wxbdf94c9d9d42220e");   //公众号
define('MCHID', "1218931401");  //商户号
define("APIKEY","963258741qazxswedcvfr753159eszqs");  //微信现金红包api key
define("PATHCERT",APPROOT."/wxpay/cert/red/apiclient_cert.pem"); //cert存放位置,不放在网站下
define("PATHKEY",APPROOT."/wxpay/cert/red/apiclient_key.pem"); //key存放位置,不放在网站下
define("PATHCA",APPROOT."/wxpay/cert/red/rootca.pem"); //ca存放位置,不放在网站下

class WxRedPay {
    var $para;
    function __construct(){
    }
    function setPara($key,$value){
        $this->para[$key] = $value;
    }

    /**
     * 创建随机数
     * @param int $length
     * @return string
     */
    function createNoncestr( $length = 24 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 签名参数设置
     * @return bool
     */
    function checkSignPara(){
        if($this->para["nonce_str"] == null ||
            $this->para["mch_billno"] == null ||
            $this->para["mch_id"] == null ||
            $this->para["wxappid"] == null ||
            $this->para["nick_name"] == null ||
            $this->para["send_name"] == null ||
            $this->para["re_openid"] == null ||
            $this->para["total_amount"] == null ||
            $this->para["max_value"] == null ||
            $this->para["total_num"] == null ||
            $this->para["wishing"] == null ||
            $this->para["client_ip"] == null ||
            $this->para["act_name"] == null ||
            $this->para["remark"] == null ||
            $this->para["min_value"] == null
        )
        {
            return false;
        }
        return true;

    }

    function createSign(){
        if($this->checkSignPara() == false) {
            echo "签名参数错误！";
        }
        ksort($this->para);
        $tempsign = "";
        foreach ($this->para as $k => $v){
            if (null != $v && "null" != $v && "sign" != $k) {
                $tempsign .= $k . "=" . $v . "&";
            }
        }
        $tempsign = substr($tempsign, 0, strlen($tempsign)-1); //去掉最后的&
        $tempsign .="&key=". APIKEY;  //拼接APIKEY

        return strtoupper(md5($tempsign));
    }

    /**
     * //签名并生成xml格式的预提交数据
     * @return string
     */
    function createXml(){
        $this->setPara('sign', $this->createSign());
        return $this->ArrayToXml($this->para);
    }

    /**
     * 数组转xml
     * @param $arr
     * @return string
     */
    function ArrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.= "</xml>";
        return $xml;
    }

    /**
     * curl通过post提交数据
     * @param $url
     * @param $vars
     * @param int $second
     * @return bool|mixed
     */
    function curlPostSsl($url, $vars, $second=30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

        //以下两种方式需选择一种
        //第一种方法，cert 与 key 分别属于两个.pem文件
        curl_setopt($ch,CURLOPT_SSLCERT,PATHCERT);
        curl_setopt($ch,CURLOPT_SSLKEY,PATHKEY);
        curl_setopt($ch,CURLOPT_CAINFO,PATHCA);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));

        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        }
        else {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }
}