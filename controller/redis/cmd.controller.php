<?php
class RedisCmdController extends Controller
{
    public $mod      = null;
    public $smsUid   = 'CDJS001178';
    public $smsPwd   = 'zm0513@';
    public $mail     = null;
    public $mo       = null;
    public $smsNums  = 1;
    public $emailNums= 1;
    public $sysNums  = 1;
    public $wxNums   = 1;

    /**
     * 执行任务处理逻辑(发送系统消息和微信模板消息)
     */
    public function index()
    {
        $this->mod = model('resque','redis');
        $this->mo  = model('msg');
        $notTimes = 3600 * 24 * 7;
        $this->mo->query("SET wait_timeout = ".$notTimes.";");
        $this->mo->query("SET interactive_timeout = ".$notTimes.";");
        while(true)
        {
            $this->sendSysMsg();
            $this->sendWxTplMsg();
            sleep(2);
        }
    }

    /**
     * 执行任务处理逻辑(手机短信)
     */
    public function exeSendSms()
    {
        $this->mod = model('resque','redis');
        $this->mo  = model('msg');
        $notTimes = 3600 * 24 * 7;
        $this->mo->query("SET wait_timeout = ".$notTimes.";");
        $this->mo->query("SET interactive_timeout = ".$notTimes.";");
        //执行任务处理逻辑
        while(true)
        {
            $this->sendSmsMsg();
            sleep(1);
        }
    }

    /**
     * 执行任务处理逻辑(发送email)
     */
    public function exeSendEmail()
    {
        $this->mod = model('resque','redis');
        $this->mail= import('pemail','lib',true);
        //执行任务处理逻辑
        while(true)
        {
            $this->sendEmail();
            sleep(3);
        }
    }


    /**
     * 发送email
     */
    public function sendEmail()
    {
        $mod = $this->mod;
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        //发送email
        $mod->workTask('email',function($group,$task){
            $sendTo = $task['sendTo'];
            $title  = $task['title'];
            $body   = $task['body'];
            $atts   = $task['atts'];
            $rest   = $this->mail->sendMail($sendTo,$title,$body,$atts);
            echo '+-----------'."\n";
            //echo ($this->emailNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rest,JSON_UNESCAPED_UNICODE).'|Task|'.json_encode($task,JSON_UNESCAPED_UNICODE)."\n";
            echo ($this->emailNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rest,JSON_UNESCAPED_UNICODE)."\n";

            //dump($group);
            //dump($task);
            return true;
        });
    }

    /**
     * 发送系统提醒消息
     */
    public function sendSysMsg()
    {
        $mod = $this->mod;
        $mod->workTask('sysinfo',function($group,$task){
            $huanxinOpt = G('config');
            $huanxinOpt = $huanxinOpt['huanxin'];
            //载入环信api接口
            import('huanxing','lib',false);
            $h      = new Huanxing($huanxinOpt);
            $from           = $task['send']['`from`'];
            $target_type    = $task['send']['`target_type`'];
            $target         = $task['send']['`target`'];
            $content        = $task['send']['`content`'];
            $ext            = $task['send']['`ext`'];
            //发送系统消息
            $rst            = ($h->sendText($from,$target_type,$target,$content,$ext));
            //dump($rst);
            if($rst){
                //写入发送消息记录
                $logs = $task['log'];
                //dump($mo);
                $this->mo->table('base_msg')->insert($logs);
            }
            echo '+-----------'."\n";
            echo ($this->sysNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rst,JSON_UNESCAPED_UNICODE)."\n";
            //echo ($this->sysNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rst,JSON_UNESCAPED_UNICODE).'|Task|'.json_encode($task,JSON_UNESCAPED_UNICODE)."\n";

            //dump($group);
            //dump($task);
            return true;
        });
    }

    /**
     * 发送微信模板消息
     */
    public function sendWxTplMsg()
    {
        $mod = $this->mod;
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        $mod->workTask('wxtpl',function($group,$task){
            $wx = import('weixin','lib',true);
            $tplId  = $task['tplId'];
            $to     = $task['to'];
            $url    = $task['url'];
            $data   = $task['data'];
            $rest   = $wx->sendTplInfo($tplId,$to,$url,$data);
            echo '+-----------'."\n";
            //echo ($this->wxNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rest,JSON_UNESCAPED_UNICODE).'|Task|'.json_encode($task,JSON_UNESCAPED_UNICODE)."\n";
            echo ($this->wxNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rest,JSON_UNESCAPED_UNICODE)."\n";
            //dump($group);
            //dump($task);
            return true;
        });
    }

    /**
     * 发送手机短信
     */
    public function sendSmsMsg()
    {
        $mod = $this->mod;
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        $mod->workTask('smsinfo',function($group,$task){
            //发送接口地址
            //$sendUrl = "http://sdk2.028lk.com:9880/sdk2/";
            //$gateway = $sendUrl."BatchSend2.aspx?CorpID=".$this->smsUid."&Pwd=".$this->smsPwd."&Mobile=".$telphone."&Content=".$content."&Cell=&SendTime=";
            $gateway = $task['send']['url'];
            $gateway = iconv("UTF-8","gbk",$gateway);
            $logs    = $task['log'];
            $result  = file_get_contents($gateway);
            if($result>0){
                $resultMsg = '发送成功：'.$result;
            }else{
                $resultMsg = '发送失败：'.$result;
            }
            $logs['result']         = $resultMsg;
            $this->mo->table('base_sms_logs')->insert($logs);
            //$this->mo->table('base_sms_logs')->insert($logs);

            echo '+-----------'."\n";
            echo ($this->smsNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($result,JSON_UNESCAPED_UNICODE)."\n";
            //echo ($this->smsNums++).':'.$group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($result,JSON_UNESCAPED_UNICODE).'|Task|'.json_encode($task,JSON_UNESCAPED_UNICODE)."\n";
            //dump($group);
            //dump($task);
            if($result>0){
                return true;
            }else{
                return false;
            }
        });
    }




}