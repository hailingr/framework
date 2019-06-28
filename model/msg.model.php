<?php

/**
 * 统一的消息发送模型
 * Class MsgModel
 */
class MsgModel extends Model
{
    public $smsUid   = 'CDJS001178';
    public $smsPwd   = 'zm0513@';
    public $sendType = 'resque'; //消息的发送方式，sync 异步队列，当此种模式时候，需要依赖redis+resque.model.php模块的支持
    //public $sendType = 'nomal'; //消息的发送方式，sync 异步队列，当此种模式时候，需要依赖redis+resque.model.php模块的支持
    /**
     * @param $msg    消息内容
     * @param $from   消息发送者
     * @param $to     消息接收者 多个接收者数组格式 array('jhl','firn')
     */
    public function sendSysNotice($msg,$to,$from='admin')
    {
        $huanxinOpt = G('config');
        $huanxinOpt = $huanxinOpt['huanxin'];
        //载入环信api接口
        import('huanxing','lib',false);
        $h  = new Huanxing($huanxinOpt);

        //发送类型
        $target_type="users";
        //$target_type="chatgroups";
        //发送对象
        $target = array();
        if(is_string($to)){
            $target[] = $to;
        }else{
            $target = $to;
        }
        //$target     = array("admin","ls","dr");
        //$target=array("122633509780062768");
        $content    = '小伙，你有一个订单<a class="J_menuItem" href="./?m=plat.server.oversee&a=oversee'.rand(0,99).'" data-index="0">1质量跟踪</a>';
        //发送内容
        $content    = $msg;

        //发送系统消息，必须设置扩展属性“msg=sys”
        $ext['msg']     =   "sys";
        $ext['other']   =   "other";
        $ext['datetime']= date("Y-m-d H:i:s");
        //任务队列发送方式
        if($this->sendType=='resque'){
            $resque = model('resque','redis');
            $data = array();
            $data['log']['`from`']           = $from;
            $data['log']['`fromCreateTime`'] = date("Y-m-d H:i:s");
            $data['log']['`to`']             = ','.implode(',',$target).',';
            $data['log']['`content`']        = $content;
            $data['log']['`ext`']            = extract($ext);
            $data['log']['`contentType`']    = 'text';
            $data['log']['`msgType`']        = 'notice';

            $data['send']['`from`']            = $from;
            $data['send']['`target_type`']     = $target_type;
            $data['send']['`target`']          = $target;
            $data['send']['`content`']         = $content;
            $data['send']['`ext`']             = $ext;
            $rst = $resque->addTask('sysinfo',$data);
        }else{
            $rst = ($h->sendText($from,$target_type,$target,$content,$ext));
            if($rst){
                //写入发送消息记录
                $data = array();
                $data['`from`']           = $from;
                $data['`fromCreateTime`'] = date("Y-m-d H:i:s");
                $data['`to`']             = ','.implode(',',$target).',';
                $data['`content`']        = $content;
                $data['`ext`']            = extract($ext);
                $data['`contentType`']    = 'text';
                $data['`msgType`']        = 'notice';
                $this->table('base_msg')->insert($data);
            }
        }

        return $rst;
    }

    /**
     * @param $sendTo  发送对象   多个邮箱 数组array('a@163.com','2@163.com')
     * @param $title   邮件标题
     * @param $body    邮件内容
     * @param $atts    附件 多个附件，数组 array('1,txt','2.txt')
     * @return bool
     */
    public function sendEmail($sendTo,$title,$body,$atts)
    {
        /*$sendTo = 'hailingr@foxmail.com';
        $title  = date("Y-m-d H:i:s").'hailingr：你好,这是标题';
        $body   = '<span style="color: red">红色Html展示</span>你好，亲爱的朋友<h1>你可真好</h1>';
        $attr   = 'root.txt';*/
        //任务队列发送方式
        //echo $this->sendType;
        if($this->sendType=='resque'){
            $data = array();
            $data['sendTo'] = $sendTo;
            $data['title']  = $title;
            $data['body']   = $body;
            $data['atts']   = $atts;
            $resque = model('resque','redis');
            $rest   = $resque->addTask('email',$data);
        }else{
            $mail = import('pemail','lib',true);
            $rest = $mail->sendMail($sendTo,$title,$body,$atts);
        }

        return ($rest);
    }

    /**
     * 发送微信模板消息
     * @param $to       微信用户openid
     * @param $tplId    微信消息模板id
     * @param $data     消息模板id对应的数据结构
     * @param $url      模板url打开的地址
     * #####################################################
     * 常用模板
     * //这个id为服务评价消息模板
     *  $tplId = 'zj1awozZjfhZYt74QcHVCpPxIy5_J1WGzK1Bm-j_Oi8';
        $to    = 'objVGwrExzSzZYvhAZHt5LYSH6mA';
        $url   = '';
        $data = array(
        'first'=>'邀请您参与今日行程服务评价',
        'keyword1'=>'10001',
        'keyword2'=>date("Y-m-d"),
        'remark'=>'今天的行程快结束了，邮电旅游邀请您对今日旅程中我们的服务进行评价，如有服务不好的地方，明天我们即刻改进...'
        );
     *
     * //这个id为审核结果消息模板
        $tplId = '1s6oVQFG5HDjPc68TZBddvc6r_JSB8L3uCselzp6zys';
        $to    = '';
        $url   = '';
        $data = array(
        'first'=>'标题抬头',
        'keyword1'=>'信息ID',
        'keyword2'=>'详情',
        'remark'=>'尾部说明'
        );

     * //这个id为退款通知消息模板
        $tplId = '984jn66BB-TaSrTFtUYFXkEHBaLkDD5ClzReI0M98Og';
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'reason'=>'退款原因',
            'refund'=>'退款金额',
            'remark'=>'尾部说明'
        );

     * //这个id为订单提醒通知消息模板
        $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'keyword1'=>'订单号',
            'keyword2'=>'操作人',
            'keyword3'=>'时间',
            'remark'=>'尾部说明'
        );
     *
     * //这个id为申请审核通知消息模板
        $tplId = 'cQIPzmzl66typ3jet3fP5KjJd-dnvBoZABoAVx2s99U';
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'keyword1'=>'申请人',
            'keyword2'=>'申请单位',
            'keyword3'=>'申请时间',
            'remark'=>'尾部说明'
        );

     * //这个id为预订成功通知消息模板
        $tplId = 'KaWQ2BCiZkCJN8ZpGDPCO-UYMX3WZ6yoigoe-XpvnXQ';
        $to    = '';
        $url   = '';
        $data = array(
        'first'=>'标题抬头',
        'keyword1'=>'订单号（供应商名称/门店名称/客户名称）',
        'keyword2'=>'订单金额',
        'keyword3'=>'订单时间',
        'remark'=>'尾部说明'
        );
     *
     * * //这个id为投诉消息模板
    $tplId = 'IFpvMTt0hAp2h5__wpec-ftVyH7BQyt3Fm-89C0IM1Y';
    $to    = '';
    $url   = '';
    $data = array(
    'first'=>'标题抬头',
    'keyword1'=>'投诉人',
    'keyword2'=>'投诉时间',
    'remark'=>'尾部说明'
    );
     * //这个id为出团提醒消息模板
    $tplId = '5MNQmOL089JQWTb2fv6sIsvMmKVxCO8uV2yroNjXQL0';
    $to    = '';
    $url   = '';
    $data = array(
    'first'=>'标题抬头',
    'keyword1'=>'订单号',
    'keyword2'=>'预订线路',
    'keyword3'=>'出发日期',
    'remark'=>'尾部说明'
    );
     *
     * * //这个id为定制游需求单通知模板
    $tplId = 'v4Ys8-qfkLH37Kas8G3Qo74a3g2Amw8x44iT7wUEAVg';
    $to    = '';
    $url   = '';
    $data = array(
    'first'=>'标题抬头',
    'keyword1'=>'需求名称',
    'keyword2'=>'需求状态',
    'remark'=>'尾部说明'
    );
     *
     *
     * * //这个id为定制游需求确认，需求反馈通知，消息模板
    $tplId = 'G43rLys49J44sKquazgJu6lqQLtTRUx-7SA0WMsmp_s';
    $to    = '';
    $url   = '';
    $data = array(
    'first'=>'标题抬头“亲爱的，您的行程已成交”',
    'keyword1'=>'需求所属项目',
    'keyword2'=>'时间',
    'remark'=>'尾部说明“感谢您对春秋旅行定制产品的认可，我们将尽快为您制定行程并发送报价。”'
    );
     *
     * *
     * * //这个id为交易提醒通知
    $tplId = 'zeopIzMAw5cJ3AhG0BmtWHQzC48SZD0ez80z7MTwoaI';
    $to    = '';
    $url   = '';
    $data = array(
    'first'=>'尊敬的会员， 感谢您购买我们的产品与服务',
    'keyword1'=>'交易内容',
    'keyword2'=>'交易方式',
    'keyword3'=>'交易金额',
    'keyword4'=>'交易时间',
    'remark'=>'尾部说明“如有疑问，请拨打服务电话xxxxxx”'
    );
     *
     * //这个id为需求受理进度通知模板
     *$tplId = 'wO8KnVJaIpje6ABdC5TZp6ZKrGXn7mjOOR4TAnyg_wY';
    $to    = '';
    $url   = '';
    $data = array(
    'first'=>'尊敬的会员， 感谢您购买我们的产品与服务',
    'keyword1'=>'需求摘要',
    'keyword2'=>'需求类型',
    'keyword3'=>'需求状态',
    'keyword4'=>'提交时间',
    'remark'=>'尾部说明“如有疑问，请拨打服务电话xxxxxx”'
    );
     */
    public function sendWxTplMsg($to,$tplId,$data,$url)
    {
        $wx = import('weixin','lib',true);
        //发送微信模板消息,不同的消息模板对应的sendData明细字数不一样
        //发送服务评价通知
        /*$tplId = 'zj1awozZjfhZYt74QcHVCpPxIy5_J1WGzK1Bm-j_Oi8';
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'',
            'keyword1'=>'',
            'keyword2'=>'',
            'remark'=>''
        );*/
        //任务队列发送方式
        if($this->sendType=='resque'){
            $sdata = array();
            $sdata['tplId']     = $tplId;
            $sdata['to']        = $to;
            $sdata['url']       = $url;
            $sdata['data']      = $data;
            $resque = model('resque','redis');
            $return = $resque->addTask('wxtpl',$sdata);
        }else
        {
            $return = $wx->sendTplInfo($tplId,$to,$url,$data);
        }
        return ($return);
    }

    /**
     * 手机短信发送接口
     * @param $from    发送人
     * @param $to      发送对象（手机号码），多个手机号码用数组
     * @param $msg
     */
    public function sendSmsMsg($from,$to,$msg)
    {
        //发送接口地址
        $sendUrl = "http://sdk2.028lk.com:9880/sdk2/";
        //组装发送参数是
        if(is_array($to)){
            $tels = array_unique($to);
        }
        if(is_string($to)){
            $to = explode(',',$to);
            $to = array_unique($to);
        }
        $telphone = implode(',',$to);
        $content  = iconv("UTF-8","gbk",$msg);
        //$content = $msg;
        $gateway = $sendUrl."BatchSend2.aspx?CorpID=".$this->smsUid."&Pwd=".$this->smsPwd."&Mobile=".$telphone."&Content=".$content."&Cell=&SendTime=";
        //任务队列发送方式
        if($this->sendType=='resque'){
            $resque = model('resque','redis');
            $sdata  = array();
            $gatewayL = $sendUrl."BatchSend2.aspx?CorpID=".$this->smsUid."&Pwd=".$this->smsPwd."&Mobile=".$telphone."&Content=".$msg."&Cell=&SendTime=";
            $sdata['send']['url'] =  $gatewayL;
                $data = array();
                $data['sendFrom']       = $from;
                $data['sendTel']        = $telphone;
                $data['msg']            = $msg;
                $data['result']         = '';
                $data['create_time']    = date("Y-m-d H:i:s");
            $sdata['log']           =  $data;
            $result = $resque->addTask('smsinfo',$sdata);
        }else{
            //echo $gateway;
            $result  = file_get_contents($gateway);
            //echo $result;
            //$result = 1;
            if($result>0){
                $resultMsg = '发送成功：'.$result;
            }else{
                $resultMsg = '发送失败：'.$result;
            }
            $data = array();
            $data['sendFrom']       = $from;
            $data['sendTel']        = $telphone;
            $data['msg']            = $msg;
            $data['result']         = $resultMsg;
            $data['create_time']    = date("Y-m-d H:i:s");
            $this->table('base_sms_logs')->insert($data);
        }
        return $result;
    }
}