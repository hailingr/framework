<?php
error_reporting(0);
/**
 * 微信接口相关操作类
 * 作者:Hailin<hailingr@foxmail.com>
 * 创建:2014.03.07 chengdu.china
 */
class Weixin
{
    public $token       = 'YdGl2016';
    public $appId       = '';
    public $appSecret   = '';
    public $EncodingAESKey = '';
    //动态token获取
    public $appToken    = '';
    public $http        = '';
    //微信token存放方式
    public $cacheType   = 'mysql';
    public function __construct()
    {
        $config                 = core::$G['config']['weixin'];
        $this->token            = $config['TOKEN'];
        $this->appId            = $config['APPID'];
        $this->appSecret        = $config['APPSECRET'];
        $this->EncodingAESKey   = isset($config['EncodingAESKey'])?$config['EncodingAESKey']:'';
        if($config['cacheType'] && isset($config['cacheType'])) $this->cacheType = $config['cacheType'];
        $this->http             = import('http','lib',true);
    }

    /**
     * 首次微信地址绑定验证
     * @return bool
     */
    public function firstVoid()
    {

        $signature  = $_GET["signature"];
        $timestamp  = $_GET["timestamp"];
        $nonce      = $_GET["nonce"];
        $token      = $this->token;
        $echostr    = $_REQUEST['echostr'];
        $tmpArr     = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr     = implode( $tmpArr );
        $tmpStr     = sha1( $tmpStr );
		
        if( $tmpStr == $signature ){
            echo $echostr;
            return true;
        }else{
            return false;
        }
    }

    /**
     * 通过给用户发送信息，样子token是否有效
     * @return bool
     */
    public function checkToken()
    {
        $openId = 'objVGwrExzSzZYvhAZHt5LYSH6mA';
        $content = '你好，hailin！';
        $info = $this->sendServerInfo($openId,$content);
		$result = json_decode($info,true);
		if($result['errcode']=='40002'){
			return  false;
		}else{
			return true;
		}
    }

    /**
     * 获取token
     * @return string
     */
    public function getToken()
    {
        //文件模式
        if($this->cacheType=='file'){
            $tokenFile  = APPROOT.'/data/wxtoken.data';
            $token      = checkFileDateTime($tokenFile,7000);
        }else{
            //数据库模式
            $noTime     = date("Y-m-d H:i:s",time());
            $mod        = model('sys.auth','mysql');
            $tokenObj   = $mod->table('core_ini')->where("`index` = 'weixinTokey' and lostTime >= '".$noTime."'")->getOne();
            //echo 'local'."\n";
			//echo $mod->lastSql();
			//dump($tokenObj);
            $token      = isset($tokenObj['value']) ? $tokenObj['value'] : '';

        }
        //验证一次Token是否有效
        //if(!$this->checkToken()){ $token = '';}

        //token不存在的情况下，获取token
        if(!$token){
            //echo 'get https:'."\n";
            $apiUrl     = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential';
            $params     = array('appid'=>$this->appId,'secret'=>$this->appSecret);
            //dump($params);
            $json       = $this->http->get($apiUrl,$params);
            //dump($json);
            $result     = json_decode($json,true);
            if($result['access_token'])
            {
                //文件模式
                if($this->cacheType=='file'){
                    //写入到缓存文件里面去
                    @unlink($tokenFile);
                    WriteFile($result['access_token'],$tokenFile,'w');
                    return $result['access_token'];
                }else{
                    //数据库模式
                    //写入到数据库配置表
                    $updateData = array(
                        'value'=>$result['access_token'],
                        'createTime'=>date("Y-m-d H:i:s",time()),
                        'lostTime'=>date("Y-m-d H:i:s",time()+7000)
                    );
                    $rst = $mod->table('core_ini')->where(array('`index`'=>'weixinTokey'))->update($updateData);
                    return $result['access_token'];
                }
            }else
            {
                return '';
            }
        }
        else{
            return $token;
        }
    }

    /**
     * 获取jsticket
     */
    public function getJsTicket()
    {
        //文件模式
        if($this->cacheType=='file'){
            $ticketFile  = APPROOT.'/data/wxjsticket.data';
            $ticket      = checkFileDateTime($ticketFile,7000);
        }else{
            //数据库模式
            $noTime     = date("Y-m-d H:i:s",time());
            $mod        = model('sys.auth','mysql');
            $tokenObj   = $mod->table('core_ini')->where("`index` = 'weixinJsTicket' and lostTime >= '".$noTime."'")->getOne();
            $ticket     = isset($tokenObj['value']) ? $tokenObj['value'] : '';
        }


        if(!$ticket){
            $accessToken = $this->getToken();
            $apiUrl     = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$accessToken;
            if($accessToken)
            {
                $params     = '';
                $json       = $this->http->get($apiUrl,$params);
                $result     = json_decode($json,true);
                //dump($result);
                $ticket = $result['ticket'];
                if($ticket)
                {
                    //文件模式
                    if($this->cacheType=='file'){
                        //写入到缓存文件里面去
                        @unlink($ticketFile);
                        WriteFile($ticket,$ticketFile,'w');
                        return $ticket;
                    }else{
                        //数据库模式
                        //写入到数据库配置表
                        $updateData = array(
                            'value'=>$ticket,
                            'createTime'=>date("Y-m-d H:i:s",time()),
                            'lostTime'=>date("Y-m-d H:i:s",time()+7000)
                        );
                        $rst = $mod->table('core_ini')->where(array('`index`'=>'weixinJsTicket'))->update($updateData);
                        return $ticket;
                    }
                }else
                {
                    return '';
                }
            }else{
                return '';
            }
        }
        else{
            return $ticket;
        }
    }

    /**
     * 获取微信js签名
     * @return array
     */
    public function getJsQianMin()
    {
        $jsTicket  = $this->getJsTicket();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url      = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $weixinConf= G('config');
        $weixinConf= $weixinConf['weixin'];
        $appId     = $weixinConf['APPID'];
        $signPackage = array(
            "appId"     => $appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 生成签名需求的随机字符串
     * @param int $length
     * @return string
     */
    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 格式化从微信服务发送过来的数据<xml格式>
     * @return SimpleXMLElement|string
     */
    public function getInfo()
    {
        $postStr = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '';
        if($postStr)
        {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //writeLog($postObj,APPROOT.'/data/log/weixin.txt');
            return $postObj;
        }else
        {
            return '';
        }
    }

    /**
     * 微信消息监控
     */
    public function route()
    {
        $info = $this->getInfo();
        if($info)
        {
            core::$G['weixinpost'] = $info;
            $fromUsername   = $info->FromUserName;
            $toUsername     = $info->ToUserName;
            //内容
            $content        = trim($info->Content);
            //消息类型
            $msgType        = trim($info->MsgType);
            $time           = time();
            //消息事件
            $event          = $info->Event;

            //对各种事件消息的回应
            if($msgType=='event')
            {
                //关注
                if($event=='subscribe')
                {
                    //特殊处理，是否存在二维码场景id值
                    $eventKey = isset($info->EventKey) ? trim($info->EventKey) : '';
                    if($eventKey){
                        //调用二维码场景处理控制器
                        controller('weixin.exec','weixinqr');
                    }else{
                        //调用关注控制器方法
                        controller('weixin.exec','subscribe');
                    }
                }
                //取消关注
                if($event=='unsubscribe')
                {
                    //调用取消关注控制器方法
                    controller('weixin.exec','unsubscribe');
                }
                if($event=='LOCATION')
                {
                    //位置自动上报
                    controller('weixin.exec','replayLocationAuto');
                }
                //已经关注的之后如果存在二维码场景值
                if($event=='SCAN')
                {
                    //特殊处理，是否存在二维码场景id值
                    $eventKey = isset($info->EventKey) ? trim($info->EventKey) : '';
                    if($eventKey){
                        //调用二维码场景处理控制器
                        controller('weixin.exec','weixinqr');
                    }
                }
                //点击事件
                if($event=='CLICK')
                {
                    //点击事件
                    //$eventKey = isset($info->EventKey) ? trim($info->EventKey) : '';
                    controller('weixin.exec','menuclick');
                }

            }
            //非事件类的消息回复
            if($msgType=='text')
            {
                controller('weixin.exec','replaytxt');
            }
            if($msgType=='image')
            {
                controller('weixin.exec','replaytxt');
            }
            if($msgType=='voice')
            {
                controller('weixin.exec','replaytxt');
            }
            if($msgType=='video')
            {
                controller('weixin.exec','replaytxt');
            }
            if($msgType=='location')
            {
                //发送了位置
                controller('weixin.exec','replayLocation');
            }
            if($msgType=='link')
            {
                controller('weixin.exec','replaytxt');
            }
        }
    }

    /**
     * 获取临时二维码图片
     */
    public function getTempQrImg($id)
    {
        $times       = 2592000;
        //$times       = 30;
        $ticketFile  = APPROOT.'/data/weixinqr/'.$id.'.txt';
        $ticket      = checkFileDateTime($ticketFile,($times-100));
        $fileName    = '/data/weixinqr/temp_'.$id.'.jpg';
        $qrName      = APPROOT.$fileName;
        //echo $ticket;
        //echo 1111;
        if(!$ticket){
            //echo 222;
            $token = $this->getToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token;
            //获取ticket
            $postJson = '{"expire_seconds": '.$times.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$id.'}}}';
            $rst = $this->http->post($url,$postJson);
            $rst = json_decode($rst);
            //dump($rst);
            $ticket = isset($rst->ticket) ? $rst->ticket : '';
            if($ticket){
                WriteFile($ticket,$ticketFile);
                $getQrImgUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
                $content = file_get_contents($getQrImgUrl);
                WriteFile($content,$qrName);
                //缩放图片
                $src=imagecreatefromjpeg($qrName);
                //取得源图片的宽度和高度
                $size_src=getimagesize($qrName);
                $w=$size_src['0'];
                $h=$size_src['1'];
                //150像素大小
                $max = 150;
                if($w > $h){
                    $w=$max;$h=$h*($max/$size_src['0']);
                }else{
                    $h=$max;$w=$w*($max/$size_src['1']);
                }
                $image=imagecreatetruecolor($w, $h);
                imagecopyresampled($image, $src, 0, 0, 0, 0, $w, $h, $size_src['0'], $size_src['1']);
                $content = imagejpeg($image,$qrName);
                imagedestroy($image);
            }
        }
        return $fileName;
    }

    /**
     * 生成永久二维码图片
     * @param $id
     */
    public function getLongQrImg($id)
    {
        $fileName    = '/data/weixinqr/long_'.$id.'.jpg';
        $ticketFile  = APPROOT.$fileName;
        if(!file_exists($ticketFile)){
            $token = $this->getToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token;
            //获取ticket
            $postJson = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$id.'}}}';
            $rst = $this->http->post($url,$postJson);
            $rst = json_decode($rst);
            $ticket = isset($rst->ticket) ? $rst->ticket : '';
            if($ticket){
                $getQrImgUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
                $content = file_get_contents($getQrImgUrl);
                WriteFile($content,$ticketFile);
                //缩放图片
                $src=imagecreatefromjpeg($ticketFile);
                //取得源图片的宽度和高度
                $size_src=getimagesize($ticketFile);
                $w=$size_src['0'];
                $h=$size_src['1'];
                $max = 300;
                if($w > $h){
                    $w=$max;$h=$h*($max/$size_src['0']);
                }else{
                    $h=$max;$w=$w*($max/$size_src['1']);
                }
                $image=imagecreatetruecolor($w, $h);
                imagecopyresampled($image, $src, 0, 0, 0, 0, $w, $h, $size_src['0'], $size_src['1']);
                $content = imagejpeg($image,$ticketFile);
                //WriteFile($image,$ticketFile);
                imagedestroy($image);
            }
        }
        return $fileName;
    }

    /**
     * 发送微信客服消息
     * @param $openId
     * @param $array
     *
     * 客服消息内容格式 {
        array('msgtype'=>'text',"text"=>array('content'=>'内容'))
     */
    public function sendServerInfo($openId,$array)
    {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$this->getToken();
        if(is_string($array)){
            $newArray = array();
            $newArray['touser']     = $openId;
            $newArray['msgtype']    = 'text';
            $newArray['text']       = array('content'=>$array);
            $array = $newArray;
        }else{
            $array['touser'] = $openId;
            if(!isset($array['msgtype'])) $array['msgtype'] = 'text';
        }
        $msg = json_encode($array,JSON_UNESCAPED_UNICODE);
        $result = $this->http->httpRequest($apiUrl,'post',$msg);
        return $result;
    }

    /**
     * 获取用户的基本信息
     * 用于网页授权时的获取
     */
    public function getUserInfoByWeb()
    {
        error_reporting(0);
        $appid     = $this->appId;
        $returnUrl = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
        $returnUrl = urldecode($returnUrl);
        $scope     = 'snsapi_userinfo';
        $state     = 'ydgl001';
        $appSecret = $this->appSecret;

        $returnCode = isset($_REQUEST['code']) ? trim($_REQUEST['code']) : '';
        //writeLog('$returnCode:'.$returnCode);
        //writeLog($_REQUEST);
        if($returnCode){
            //获取到用户的code
            $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$appSecret.'&code='.$returnCode.'&grant_type=authorization_code';

            //writeLog('获取app_token地址：'.$returnUrl);
            $return = $this->http->get($apiUrl);
            $return = json_decode($return);
            //writeLog($return);
            $accessToken = isset($return->access_token) ? (string)$return->access_token : '';
            $openid      = isset($return->openid) ? (string)$return->openid : '';
            //是否插入用户基本信息
            if($openid && $accessToken){
                //写入cookie
                $wxData = array();
                $wxData['openid'] = $openid;
                //获取用户信息
                $apiUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$accessToken.'&openid='.$openid.'&lang=zh_CN';
                $return = $this->http->get($apiUrl);
                $return = json_decode($return);
                G('Wx',$return);
                //在发起页面通过G('Wx')获取到微信的用户信息;
            }else{
                writeLog('获取授权appcoder失败');
            }
        }else{
            //writeLog('回调地址：'.$returnUrl);
            //前往获取用户的code
            $apiUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$returnUrl.'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
            header("Location: $apiUrl");
            exit();
        }

    }

    /**
     * 获取指定微信用户的基本信息
     * @param $openId
     */
    public function getUserInfo($openId)
    {
        $accToken = $this->getToken();
        if($accToken)
        {
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$accToken.'&openid='.$openId.'&lang=zh_CN';
            $return = $this->http->get($url);
            $return = json_decode($return);
			//dump($return);
            //writeLog('user fase ---------------------'."\n");
            //writeLog($return );
            $user = array();
            $user['openId'] = $openId;
            $isSubscribe = isset($return->subscribe) ? $return->subscribe : '';
            $isSubscribe = $isSubscribe ? $isSubscribe : '';
            if($isSubscribe){
                $user['status']     = 1;
                $user['name']       = $return->nickname;
                $user['sex']        = $return->sex;
                $user['city']       = $return->city;
                $user['country']    = $return->country;
                $user['province']   = $return->province;
                $user['face']       = $return->headimgurl;
                $user['groupid']    = $return->groupid;
                $user['createTime'] = $return->subscribe_time;
            }else{
                $user['status']     = 0;
                $user['name']       = '';
                $user['sex']        = '';
                $user['city']       = '';
                $user['country']    = '';
                $user['province']   = '';
                $user['face']       = '';
                $user['groupid']    = '';
                $user['createTime'] = '';
            }
            return $user;
        }
    }

    /***
     * 发送微信消息模板
     * @param $tplId    消息模板
     * @param $to       发送对象 微信openid
     * @param $url      消息url地址
     * @param $data     消息内容，和模板存在对应关系 array('first'=>'消息','second'=>'内容')
     */
    public function sendTplInfo($tplId,$to,$url,$sendData)
    {
        $token  = $this->getToken();
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;

        //构造模板消息内容
        $data = array();
        $data['touser']         = $to;
        $data['template_id']    = $tplId;
        $data['url']            = $url;
        //构造内容明细
        $child = array();
        foreach($sendData as $key=>$val)
        {
            $child[$key] = array('value'=>$val);
        }
        /*$child['first'] = array('value'=>'用户邀请组队消息发送成功');
        $child['keyword1'] = array('value'=>'组队邀请');
        $child['keyword2'] = array('value'=>'发送成功');
        $child['keyword3'] = array('value'=>date("Y-m-d H:i:s"));
        $child['keyword4'] = array('value'=>'Firn');
        $child['remark'] = array('value'=>'Firn 邀请你参加他的组团，给你发送了一条邀请！');*/

        $data['data'] = $child;
        $sendInfo     = json_encode($data);
        $info = $this->http->post($apiUrl,$sendInfo);
        return $info;
    }

}
