<?php

class ApiWxPayController extends Controller
{
    //微信支付回调接口
    public function wxPayResult(){
        error_reporting(0);
        //dump($_REQUEST);
        //writeLog($_REQUEST);
        //writeLog($GLOBALS['HTTP_RAW_POST_DATA']);
        //写入日志，测试
        ini_set('date.timezone','Asia/Shanghai');
        //error_reporting(E_ERROR);
        //测试xml，微信传递过来的数据
        /*$GLOBALS['HTTP_RAW_POST_DATA'] = '<xml>
  <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
  <attach><![CDATA[支付测试]]></attach>
  <bank_type><![CDATA[CFT]]></bank_type>
  <fee_type><![CDATA[CNY]]></fee_type>
  <is_subscribe><![CDATA[Y]]></is_subscribe>
  <mch_id><![CDATA[10000100]]></mch_id>
  <nonce_str><![CDATA[fblxdfq5d4wlqgcu8l01nhkg72hp9sqa]]></nonce_str>
  <openid><![CDATA[oUpF8uMEb4qRXf22hE3X68TekukE]]></openid>
  <out_trade_no><![CDATA[201612081722278659]]></out_trade_no>
  <result_code><![CDATA[SUCCESS]]></result_code>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <sign><![CDATA[B552ED6B279343CB493C5DD0D78AB241]]></sign>
  <sub_mch_id><![CDATA[10000100]]></sub_mch_id>
  <time_end><![CDATA[20170903131540]]></time_end>
  <total_fee>5100</total_fee>
  <trade_type><![CDATA[JSAPI]]></trade_type>
  <transaction_id><![CDATA[1004400740201409030005092168]]></transaction_id>
</xml>';*/
        //writeLog($GLOBALS['HTTP_RAW_POST_DATA']);
        require_once APPROOT."/wxpay/lib/WxPay.Api.php";
        require_once APPROOT.'/wxpay/lib/WxPay.Notify.php';
        require_once APPROOT.'/wxpay/example/log.php';
        $logHandler= new CLogFileHandler(APPROOT."/wxpay/logs/".date('Y-m-d').'.log');
        $log = Log::Init($logHandler, 15);
        require_once APPROOT.'/wxpay/example/notifyrel.php';
        //日志
        Log::DEBUG("begin notify");
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);
        $isPayYes = isset(core::$G['weixinpay']) ? core::$G['weixinpay'] : false;

        $getXml    = simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);
        //$isPayYes  = true;
        if($isPayYes){
            //writeLog('isPayYes:'.$isPayYes);
            //我们的回调处理
            $mo   = model('api.wx.pay','mysql');
            $mo->wxPayResult($getXml);
        }
        exit;
    }

    /**
     * 支付接口调用，生成微信统一订单接口
        payType= 1到店付，2支付宝，3微支付,4银行卡,5签单支付
     * 返回 数组，在前端调用微信的js支付口，发起支付
     */
    public function payItem()
    {
        $payType        = $this->getRequest('payType' , 3);//isset($_REQUEST['payType']) ? trim($_REQUEST['payType']) : 3;
        $billId         = $this->getRequest('billId' , '');//isset($_REQUEST['billId']) ? trim($_REQUEST['billId']) : '';
        $ponId          = $this->getRequest('ponId' , '');//isset($_REQUEST['disId']) ? trim($_REQUEST['disId']) : '81';//优惠卷id
        //$dep            = $this->getRequest('dep' , '');//当前门市id
        //writeLog('$_REQUEST:');
        //writeLog($_REQUEST);
        $return     = array('status'=>0,'msg'=>'支付方式','data'=>array());
        $memberId = cookie('memberId');
        if($memberId)
        {
            if($billId)
            {
                $mo   = model('api.wx.pay','mysql');
                if($ponId){
                    $time   = date('Y-m-d H:i:s',time());
                    $couponMo       = model('sys.coupon','mysql');
                    $orderMo        = model('api.wx.order','mysql');
                    $coupon = $couponMo->getOneCouponByReceiveCoder($ponId);
                    $mMyCoupons     = $couponMo->getMyCoupons($memberId,'coupon_unique');//获取我的抵用卷 coupon_unique
                    $mMyCoupons     = $mMyCoupons['list'];
                    if(!empty($mMyCoupons)){
                        $mMyCoupons = array_column($mMyCoupons, 'coupon_unique');//二维转一维
                        $mMyCoupons = array_unique($mMyCoupons);//去重
                    }
                    if($coupon['coupon_desCity'] != ',0,'){ //抵用卷目的地不为',0,'则说明限制了 使用目的地
                        //获取订单目的地
                        $desId  = $orderMo->table('order_line a')->jion('left join pro_line b on a.proId=b.id')
                            ->field('b.destId')->where(array('a.id'=>$billId))->getOne();
                        $ponDesId = trim($coupon['coupon_desCity'],',');//
                        $ponDesIdArr = explode(',',$ponDesId);
                        if(!in_array(trim($desId['destId'],','),$ponDesIdArr)){
                            $return['msg'] = '该抵用卷不能用于该目的地';
                            exit(json_encode($return));
                        }
                    }
                    if($coupon['coupon_bindDepart'] != ',0,'){
                        $ponDepId = trim($coupon['coupon_bindDepart'],',');//
                        $ponDepIdArr = explode(',',$ponDepId);//可使用抵用卷门市
                        //获取我的部门id
                        $myInfo  = $orderMo->table('base_members a')->field('a.departId')->where(array('a.id'=>$memberId))->getOne();
                        $myDepId = $myInfo['departId'];
                        if(!in_array($myDepId,$ponDepIdArr)){
                            $return['msg'] = '您所在的门市不能使用该抵用卷';
                            exit(json_encode($return));
                        }
                    }
                    if(!in_array($coupon['coupon_unique'],$mMyCoupons)){
                        $return['msg'] = '该抵用卷不属于你';
                    }elseif($coupon['coupon_status'] == 3){
                        $return['msg'] = '该抵用卷已被使用';
                    }elseif($coupon['coupon_status'] == 4){
                        $return['msg'] = '该抵用卷领已过期';
                    }elseif($coupon['coupon_status'] == 5){
                        $return['msg'] = '该抵用卷已作废';
                    }else if($time < $coupon['coupon_useTimeStart']){
                        $return['msg'] = '该抵用卷请于'.$coupon['coupon_useTimeStart'].'之后使用';
                    }else if($time >= $coupon['coupon_useTimeEnd']){
                        $return['msg'] = '该抵用卷领已过期';
                    }elseif($coupon['coupon_userType'] == 2){
                        $return['msg'] = '该抵用卷只能在门市使用';
                    }else{
                        //调用支付方法，生成相应的支付信息
                        $data = $mo->payItem($billId,$payType,$ponId);
                        $return['status'] = 1;
                        $return['data']   = $data;
                    }
                }else{
                    //调用支付方法，生成相应的支付信息
                    $data = $mo->payItem($billId,$payType,$ponId);
                    $return['status'] = 1;
                    $return['data']   = $data;
                }
            }else {
                $return['msg'] = '参数【billid】有误';
            }
        }else {
            $return['msg'] = '登陆过期，请重新登陆！';
        }
        exit(json_encode($return));
    }
    /**
     * 微信 确定退款
     */
    public function refund()
    {
        $payType        = $this->getRequest('payType' , 3);
        $billId         = $this->getRequest('billId' , '');
        $return     = array('status'=>0,'msg'=>'订单退款','data'=>array());
        $memberId = cookie('memberId');
        if($memberId) {
            if($billId) {
                $mo   = model('api.wx.pay','mysql');
                //调用支付方法，生成相应的支付信息
                $data = $mo->refundItem($billId,$payType);
                $return['status'] = 1;
                $return['data']   = $data;
            } else {
                $return['msg'] = '参数【billid】有误';
            }
        }else {
            $return['msg'] = '登陆过期，请重新登陆！';
        }
        exit(json_encode($return));
    }
    /**
     * 退款接口调用
     */
    public function refundItem()
    {
        $payType        = isset($_REQUEST['payType']) ? trim($_REQUEST['payType']) : 3;
        $orderId         = isset($_REQUEST['orderId']) ? trim($_REQUEST['orderId']) : '';
        //$refund_channel         = isset($_REQUEST['refund_channel']) ? trim($_REQUEST['refund_channel']) : 'ORIGINAL'; // 退回方式 1 ORIGINAL 原路； 2 BALANCE—退回到余额

        $return     = array('status'=>0,'msg'=>'退款接口调用','data'=>array());
        $openId = cookie('wxOpenId');
        /*$mo   = model('user.info','mysql');
        $user = $mo->loginIs(false);*/
        if($openId)
        {
            if($orderId)
            {
                $mo   = model('api.wx.pay','mysql');
                //调用支付方法，生成相应的支付信息
                $data = $mo->tuiItem($orderId,$payType);
                $return['status'] = 1;
                $return['data']   = $data;
            }else
            {
                $return['msg'] = '参数【orderId】有误';
            }

        }else
        {
            $return['msg'] = '登陆过期，请重新登陆！';
        }
        //dump($return);
        exit(json_encode($return));
    }

    //退款查询
    public function refundQuery(){
        $mo   = model('api.wx.pay','mysql');
        $mo->refundQuery();
    }
    //自动提交失败的退款
    public function checkRefundWeiXinAuto(){
        $mo   = model('api.wx.pay','mysql');
        $mo->checkRefundWeiXinAuto();
    }
}
