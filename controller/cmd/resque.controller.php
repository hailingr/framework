<?php
class CmdResqueController extends Controller
{
    public function index()
    {
        while(true)
        {
            $this->smsInfo();
            $this->wxtplInfo();
            $this->smsInfo();
            $this->emailInfo();
            sleep(2);
        }

    }

    /**
     * 发送系统消息
     */
    public function sysInfo()
    {
        $mo = model('resque','redis');
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        $mo->workTask('sysinfo',function($group,$task){
            if($task)
            {
                $huanxinOpt = G('config');
                $huanxinOpt = $huanxinOpt['huanxin'];
                //载入环信api接口
                import('huanxing','lib',false);
                $h      = new Huanxing($huanxinOpt);
                $send   = $task['send'];
                $data   = $task['log'];
                $rst    = ($h->sendText($send['from'],$send['target_type'],$send['target'],$send['content'],$send['ext']));
                if($rst){
                    $mo = model('resque','mysql');
                    $mo->table('base_msg')->insert($data);
                }
                echo $group.':'.$send['target'].'|result:'.$rst.'['.date("Y-m-d H:i:s")."]\n";
                if($rst) return true;
            }
        });
    }

    /**
     * 发送微信模板消息
     */
    public function wxtplInfo()
    {
        $mo = model('resque','redis');
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        $mo->workTask('wxtpl',function($group,$task){
            if($task)
            {
                $wx = import('weixin','lib',true);
                $return = $wx->sendTplInfo($task['tplId'],$task['to'],$task['url'],$task['data']);
                echo $group.':'.$task['to'].'|result:'.$return.'['.date("Y-m-d H:i:s")."]\n";
                $return = json_decode($return,true);
                if($return['errcode']==0) return true;
            }
        });
    }

    /**
     * 发送手机短信
     */
    public function smsInfo()
    {
        $mo = model('resque','redis');
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        $mo->workTask('smslist',function($group,$task){
            if($task)
            {
                $gateway = $task['send']['url'];
                $result  = file_get_contents($gateway);
                //echo $result;
                //$result = 1;
                if($result>0){
                    $resultMsg = '发送成功：'.$result;
                }else{
                    $resultMsg = '发送失败：'.$result;
                }
                $data = $task['log'];
                $data['result']         = $resultMsg;
                $mo = model('resque','mysql');
                $mo->table('base_sms_logs')->insert($data);
                echo $group.':'.$data['sendTel'].'|result:'.$resultMsg.'['.date("Y-m-d H:i:s")."]\n";
                if($result) return true;
            }
        });
    }

    /**
     * 发送email消息
     */
    public function emailInfo()
    {
        $mo = model('resque','redis');
        //执行任务处理逻辑，可以将该执行者写为常驻进程，需要返回执行失败与否
        $mo->workTask('email',function($group,$task){
            if($task)
            {
                $mail = import('email','lib',true);
                $rest = $mail->sendMail($task['sendTo'],$task['title'],$task['body'],$task['atts']);
                echo $group.':'.$task['sendTo'].'|result:'.$rest.'['.date("Y-m-d H:i:s")."]\n";
                if($rest) return true;
            }
        });
    }
}