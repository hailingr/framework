<?php
/**
 * 电信天翼189短信专用发送接口
 * Class Sms189Model
 */
class Sms189Model extends Model
{
    public $appId       = '386812010000263957';
    public $appSect     = 'e21e312514562abe0b43ff001033dd98';
    public $tplId       = '91552703';

    /**
     * 发送天翼模板短信
     * 尊敬的{$userName}用户，您的验证码为{$vcoder}，本验证码{$vtime}分钟内有效，感谢您的使用！
     * 调用方式
     * $sms = model('sms189','mysql');
        $userTel = '13540633386';
        $userName= '13540633386';
        $vcoder = 'jhsljk';
        $vtime = '5';
        $return = $sms->sendVMsg($userTel,$userName,$vcoder,$vtime);
        if($return) echo 'send success';
        else echo 'send fail';
     * @param $userTel
     * @param $userName
     * @param $vcoder
     * @param $vtime
     */
    public function sendVMsg($userTel,$userName,$vcoder,$vtime)
    {
        $apiUrl      = 'http://api.189.cn/v2/emp/templateSms/sendSms';
        $access_token = $this->getToken();
        $timestamp = date('Y-m-d H:i:s');
        $template_param = '{';
        if($userName) $template_param .= '"param1":"'.$userName.'",';
        else $template_param .= '"param1":"",';
        $template_param .= '"param2":"'.$vcoder.'",';
        $template_param .= '"param3":"'.$vtime.'"';
        $template_param .= '}';

        $param = array();
        $param['app_id']= "app_id=".$this->appId;
        $param['access_token'] = "access_token=".$access_token;
        $param['acceptor_tel'] = "acceptor_tel=".$userTel;
        $param['template_id'] = "template_id=".$this->tplId;
        $param['template_param'] = "template_param=".$template_param;
        $param['timestamp'] = "timestamp=".$timestamp;
        //echo $access_token;
        if($access_token)
        {
            ksort($param);
            $plaintext = implode("&",$param);
            $param['sign'] = "sign=".rawurlencode(base64_encode(hash_hmac("sha1", $plaintext, $this->appSect, $raw_output=True)));
            ksort($param);
            $apiUrlData = implode("&",$param);
            //echo $apiUrlData;
            $http = import('http','lib',true);
            $result = $http->curl_post($apiUrl,$apiUrlData);
            $resultArray = json_decode($result,true);
            //dump($resultArray);
            $code = isset($resultArray['res_code']) ? $resultArray['res_code'] : '2';
            if($code==0)
            {
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 获取令牌
     * @return array|mixed
     */
    public function getToken()
    {
        //增加令牌本地缓存
        $tempTokenFile = APPROOT.'/data/sms189.bat';
        if(!file_exists($tempTokenFile)) WriteFile('<?php ',$tempTokenFile,'w');
        include_once($tempTokenFile);

        $result = @$tokenInfo;
        $noTime    = time();
        //echo '<hr>';
        $accessToken = isset($tokenInfo['accessToken']) ? $tokenInfo['accessToken'] : '';
        //echo '<hr>';
        $expireTime  = isset($tokenInfo['expireTime']) ? $tokenInfo['expireTime'] : '';
        //echo '<hr>';
        if(($expireTime > $noTime) && $accessToken)
        {
            return $accessToken;
        }else
        {
            $app_id = $this->appId;
            $app_secret = $this->appSect;
            $grant_type = 'client_credentials';
            $send = 'app_id='.$app_id.'&app_secret='.$app_secret.'&grant_type='.$grant_type;
            /*if($grant_type=="refresh_token")
                $send .='&refresh_token='.$refreshtoken;*/
            //echo $send;
            $http = import('http','lib',true);
            $access_token   = $http->curl_post_https("https://oauth.api.189.cn/emp/oauth2/v2/access_token", $send);

            $tempToken      = json_decode($access_token, true);
            //dump($tempToken);
            $expireTime     = time()+$tempToken['expires_in'];
            $doc['accessToken']     = $tempToken['access_token'];
            $doc['expireTime']      = $expireTime;
            $doc['resCode']         = $tempToken['res_code'];
            $info  = "<?php \n ";
            $info .= '$tokenInfo["accessToken"]="'.$doc['accessToken'].'";'."\n";
            $info .= '$tokenInfo["expireTime"]="'.$doc['expireTime'].'";'."\n";
            $info .= '$tokenInfo["resCode"]="'.$doc['resCode'].'";'."\n";
            WriteFile($info,$tempTokenFile,'w');
            return $doc['accessToken'];
        }


    }
}