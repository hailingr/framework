<?php

class ApiWxCustomPay extends Model
{
    //微信支付回调处理
    public function wxPayResult($data){
        if($data->return_code == 'SUCCESS'){
            if($data->out_trade_no && $data->result_code == 'SUCCESS'){
                echo "success";//收到成功支付成功后立即输出 success
                $order = $this->table('custom_swim_order a')
                    ->field("a.*,b.payType,b.payPer")
                    ->jion("left join custom_swim_line b on a.lineId=b.id")
                    ->where(array('a.id'=>$data->attach))->getOne();
                //订单状态
                $status   = $order['status'];
                //writeLog('订单及抵用卷信息：');
                //writeLog($order);
                if($status == 1||$status == 3){
                    $payMoney = $data->total_fee;
                    $allMoney = $order['totalMoney']*100;
                    //支付数据
                    $payArr = array(
                        'coder' => $data->out_trade_no,
                        'orderId'=>$order['id'],
                        'create_time'=>date('Y-m-d H:i:s'),
                        'payType'=>3,
                        'payCoder'=>$data->transaction_id,
                        'type'=>1,
                    );

                    if($status==1){//第一次付款
                        if($payMoney==$allMoney){//支付金额与总金额一致,则为全额支付
                            $payArr['name'] = "定制游-微端全款";
                            $payArr['money'] = $order['totalMoney'];
                            $payArr['supplierGetMoney'] = $order['supplierMoney'];
                            //修改订单状态
                            $lineStatus = 4;
                        }else{//支付金额与总金额不一致，则为定金支付
                            $payArr['name'] = "定制游-微端定金";
                            $payArr['money'] = $order['totalMoney']*$order['payPer'];
                            $payArr['supplierGetMoney'] = $order['supplierMoney']*$order['payPer'];
                            //修改订单状态
                            $lineStatus = 3;
                        }
                    }else{//余额支付
                        $payArr['name'] = "定制游-微端余额";
                        $payArr['money'] = $order['totalMoney']*(1-$order['payPer']);
                        $payArr['supplierGetMoney'] = $order['supplierMoney']*(1-$order['payPer']);
                        //修改订单状态
                        $lineStatus = 4;
                    }
                    //修改订单中的状态
                    $this->table('custom_swim_order')->where(array('id'=>$order['id']))->update(array('status'=>$lineStatus));
                    //支付日志处理
                    $memberId = $order['memberId'];//会员信息
                    //$memberId = $memberId ? $memberId : 10;
                    $member = $this->getMember($memberId,'wxOpenId,nickName,userId,name,departId');
                    $msg    = model('msg');//消息模型
                    $mo     = model('pro.line', 'mysql');//资金日志模型
                    //支付记录
                    if($order['storeId']){
                        $payArr['storeGetMoney'] = $payArr['money'] * $order['storePer'];
                    }
                    //writeLog('支付记录：');
                    //writeLog($payArr);
                    //查询优惠 并修改状态
                    //$dis = $this->table('order_line_discount')->where(array('dis_orderId'=>$order['id']))->getOne();
                    //修改支付记录
                    $this->table('custom_swim_pay')->insert($payArr);

                    //供应商 资金流
                    $supplierMoney = $payArr['supplierGetMoney'];
                    $about = $payArr['name'].'：'.'订单：'.$order['coder'].', 产品：'.$order['title'];
                    $mo->addFundInfo($order['supplierId'], $order['supplierName'], $order['userId'], $order['userName'], $order['memberId'], $order['memberName'], $payArr['name'], $about, $supplierMoney, 1);//写入资金日志
                    //$money = $data->total_fee / 100 ;

                    //门市 资金日志
                    //$storeMoney =  $order['sorePerMoney'];
                    $storeMoney =  $payArr['money']*$order['storePer'];
                    //writeLog('门市佣金:');
                    //writeLog($storeMoney);
                    if($order['storeId'] && $storeMoney > 0){
                        $mo->addFundInfo($order['storeId'], $order['storeName'], $order['userId'], $order['userName'], $order['memberId'], $order['memberName'], $payArr['name'], $about, $storeMoney, 1);
                    }
                    //系统消息
                    $sendMsg  = '支付通知：您有一条订单已进行'.$payArr['name'].'支付，订单号：'.$order['coder'].'。   ';
                   // $sendMsg .= '<a class="J_menuItem" href="/?m=plat.supplier.order&a=index&rand=' . rand(0, 99) . '" style="padding:0;">线路订单</a>';
                    $users   = $this->getSupplierUsers($order['supplierId']);
                    if($users){
                        $msg->sendSysNotice($sendMsg, $users, 'admin');
                    }
                    //微信消息
                    $users = $this->getSupplierUsers($order['supplierId']);
                    $users = implode('","',$users);
                    $users = '"'.$users .'"';
                    $reviewWx = $this->table('core_user')->where('`name` in ('.$users.')')->field('wxOpenId,wxName,realName')->get();//接收人员
                    //$sendWx   = $order['linkMan'];//发送人员
                    foreach($reviewWx as $wx){
                        if ($wx['wxOpenId']) {
                            $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                            $to = $wx['wxOpenId'];
                            $url = '';
                            if($wx['wxName'] == '' || $wx['wxName'] == null) $wx['wxName'] = $wx['realName'] ;
                            $wxData = array(
                                'first' => '尊敬的'.$wx['wxName'] . ', 您的产品 "' .$order['title']. '" 游客已进行'.$payArr['name'].'支付',//标题抬头
                                'keyword1' => $order['coder'],//订单号
                                'keyword2' => '四川邮电旅游',//$order['linkMan'],//发送人员
                                'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                            );
                            if($to){
                                $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                            }
                        }
                    }
                    $tels    = $order['linkTel'];
                    if($tels){
                        //用户短信提醒
                        if($lineStatus==4){
                            //发短信
                            $notice  = '尊敬的'.$order['linkMan'] .',感谢您选择四川邮电旅游的定制游产品,您的订单"'.$order['title'] . '"已支付成功。';
                            $notice .= '出游日期：'.$order['startDay'].',我们将通过短信为您发送出团通知,请注意查收。';
                            $notice .= '预祝您旅途愉快！';
                            $msg->sendSmsMsg('admin',$tels,$notice);
                        }
                    }
                    //微信消息
                    if ($memberId) {
                        $tplId   = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                        $to      = $member['wxOpenId'];//接收用户
                        if($lineStatus==3){
                            $notice  = '尊敬的'.$member['nickName'] .',感谢您选择四川邮电旅游的定制游产品,您的订单"'.$order['title'] . '"定金已支付成功。';
                        }else{
                            $notice  = '尊敬的'.$member['nickName'] .',感谢您选择四川邮电旅游的定制游产品,您的订单"'.$order['title'] . '"已支付成功。';
                            $notice .= '出游日期：'.$order['startDay'].',我们将通过短信为您发送出团通知,请注意查收。';
                            $notice .= '预祝您旅途愉快！';
                        }

                        $url     = '';
                        $wxData  = array(
                            'first'    => $notice,//标题抬头
                            'keyword1' => $order['coder'],//订单号
                            'keyword2' => '四川邮电旅游',//操作人
                            'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                        );
                        if($to){
                            $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                        }
                    }

                    if($lineStatus==4){
                    //支付成功后 微信用户加入质量跟踪系统
                    $ser = array(
                        'nickName'=>$member['nickName'],
                        'realName'=>$order['linkMan'],
                        'tel'=>$order['linkTel'],
                        'wxOpenId'=>$member['wxOpenId'],
                        'proId'=>$order['lineId'],
                        'proName'=> $order['title'],
                        'proType'=> 2,
                        'orderCoder'=>$order['coder'],
                        'orderId'=>$order['id'],
                        'startDay'=>$order['startDay'] ,
                        'endDay'=>$order['endDay'] ,
                        'supplierName'=>$order['supplierName'],
                        'supplierId'=>$order['supplierId'],
                        'storeName'=>$order['storeName'],
                        'stroeId'=>$order['storeId'],
                    );
                    $this->table('server_user')->insert($ser);
                    echo "success";
                }else{
                    echo 'success';
                }
                }

            }else{
                echo "fail";
            }
        }else{
            echo "fail";
        }
    }

    /**
     * 微信支付成功后调用的订单处理逻辑
     * 参考
     * @param $data
     */
    public function wxpayByWap($data)
    {
        //支付成功处理订单
        //echo 'ok';
        //writeLog('weixinpayok:');
        //dump($data);
        //writeLog($data);
        $orderNo    = (string)$data->out_trade_no;
        $totalFee   = (string)$data->total_fee;
        $tradeNo    = (string)$data->transaction_id;
        $tradeType  = (string)$data->trade_type;
        //$orderNo = '20150924152419831030';

        $find = array('billCoder'=>$orderNo);
        //dump($find);
        $doc  = $this->db->bills->findOne($find);
        //dump($doc);
        //exit;
        $billId = (string)$doc['_id'];
        if($doc['status']=='0' && $doc['payType']=='0')
        {
            $data = array();
            $find = array('_id'=>new MongoId($billId));
            $checkTime  = date("Y-m-d H:i:s");
            //完成支付设置
            $udata      = array('$set'=>array('payType'=>'3','status'=>'2','checkTime'=>$checkTime,'weixinCoder'=>$tradeNo,'weixinMoney'=>$totalFee,'weixinType'=>$tradeType));
            $this->db->bills->update($find,$udata);
            //添加会员酒店
            $this->addMemberHotel($doc);
            //添加酒店会员
            $this->addHotelMember($doc);
            //增加提现余额
            $totalFee = $totalFee/100;
            $this->addCash($doc,$totalFee);

            //修改报价单状态为已支付
            $this->canaldemandQuote($billId);
            //生成支付统计数据
            $this->setDataPays($doc,'微支付');
            //发送付款完毕通知短信
            $this->sendPayEndSmsInfo($doc,3);
            //像B端发送推送消息
            $this->sendPushBMsg($doc,'weixinpay');
            //库存操作
            $this->checkDepotItem($doc);
            //卡券生成
            $this->createCouponMoney($doc);
            //使用抵用券
            $this->useCoupon($doc);
            //echo "success";
        }
    }

    /**
     * 获取指定供应商下面的用户
     * @param $supplierId
     */
    public function getSupplierUsers($supplierId)
    {
        $lists = $this->table('core_user')->where(array('departId' => $supplierId))->field('name')->get();
        $names = array();
        foreach ($lists as $item) {
            $names[] = $item['name'];
        }
        return $names;
    }

    /**
     * 取得支付信息
     * @param $orderId 订单编号
     * @param $payType 支付类型 到店 支付宝 签单 微信
     * @param $payStyle 支付方式 1 全额 2 定金
     * @return array
     */
    public function payItem($orderId,$payType,$payStyle)
    {
        //支付体系
        //+-------------------------------------
        //到店支付
        /*if($payType=='1')
        {
            $return = $this->checkPayCash($billId);
            return $return;
        }*/
        //+-------------------------------------

        //在线支付支付宝
        /* if($payType=='2')
         {
             $return = $this->checkAlipay($billId);
             return $return;
         }*/

        //签单支付，调用到店付支持流程
        /*if($payType=='5')
        {
            $return = $this->checkPayCash($billId,$payType);
            return $return;
        }*/

        //微信支付
        if($payType=='3')
        {
            $return = $this->checkPayWeiXin($orderId,$payStyle);
            return $return;
        }

        exit('pay...');
    }

    /**
     * 微信支付模式
     * @param $tokenInfo
     * @param $billId
     * @param $disType
     * @param $disId
     */
    public function checkPayWeiXin($orderId,$payStyle)
    {
        ini_set('date.timezone','Asia/Shanghai');
        $find  = array('a.id'=>$orderId);
        $rows  = $this->table('custom_swim_order a')->where($find)->field("a.*,b.payType,b.payPer")->jion("left join custom_swim_line b on b.id=a.lineId")->getOne();
        if(empty($rows) || $rows['status']=='10'){
            return array('payType'=>'3','status'=>'0','msg'=>'已经过期，不可支付','info'=>array());
        }
        if($rows['status']=='4')
        {
            return array('payType'=>'3','status'=>'0','msg'=>'订单已经付款完毕，不可以重复支付','info'=>array());
        }
        if($rows['status']=='1'||$rows['status']=='3')
        {
            //调用微信统一下单接口生成预支付信息
            //$body       = (string)($rows['title'].'日期['.date("Y年m月d日",strtotime($rows['startDay'])).'到'.date("Y年m月d日",strtotime($rows['endDay'])).'],数量[成人'.$rows['manNums'].',儿童'.$rows['childrenNums'].']');

            $body = (string)($rows['title'].'');
            if($payStyle==1&&$rows['status']=='1'){//当订单支付状态为1时，才可发起全额支付
                $billCoder  = (string)$rows['coder'];
                $money      = $rows['totalMoney'];
            }else if($payStyle==2){//当订单支付方式为2时 可进行定金或余额支付

                    if($rows['status']=='1'){//定金第一次付款

                        if($rows['payType']==2){//判断线路里面是否允许使用定金支付
                            $money  = $rows['totalMoney']*$rows['payPer'];
                            $billCoder  = (string)($rows['coder']."A");
                         }else{//如果线路里面为全额支付
                            return array('payType'=>'3','status'=>'0','msg'=>'订单支付状态异常，暂时不可支付','info'=>array());
                        }

                    }else{//定金第二次付款（余额支付）
                        $pay = $this->table('custom_swim_pay')->field("SUM(money) as payMoney")->where("orderId=$orderId")->get();

                        if($pay[0]['payMoney']&&$pay[0]['payMoney']<$rows['totalMoney']){//获取支付表内已支付金额,如果总金额大于已支付金额
                            $money  = ($rows['totalMoney']*1)-($pay[0]['payMoney']*1);
                            $billCoder  = (string)($rows['coder']."B");
                        }else{
                            return array('payType'=>'3','status'=>'0','msg'=>'订单支付状态异常，暂时不可支付','info'=>array());
                        }

                    }

            }else{
                return array('payType'=>'3','status'=>'0','msg'=>'订单支付状态异常，暂时不可支付','info'=>array());
            }
            //writeLog('使用抵用卷前的金额:');
            //writeLog($money);
            //writeLog('使用抵用卷后的金额:');
            //writeLog($money);

            $member    = $this->table('base_members')->field("wxOpenId")->where(array('id'=>$rows['memberId']))->getOne();
            $openId     = $member['wxOpenId'];

            require_once APPROOT."/wxpay/lib/WxPay.Api.php";
            require_once APPROOT."/wxpay/example/WxPay.JsApiPay.php";
            $tools      = new JsApiPay();
            $input      = new WxPayUnifiedOrder();
            $input->SetBody($body);
            $input->SetAttach($orderId);
            //$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
            $input->SetOut_trade_no($billCoder);
            //金额至少为1元 discountType()方法已作判断 此处无需再判断
            $money = $money * 100;
            $money = (string)$money;
            //echo $money;
            //exit;
            $input->SetTotal_fee($money);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            //$input->SetGoods_tag("test");
            $input->SetNotify_url("http://pro.scydgl.com/pay/wxVip");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            //dump($input);
            //执行微信统一下单
            $order = WxPayApi::unifiedOrder($input);
            //writeLog('首次下单');
            //writeLog($order);
            //如果统一下单执行失败，需要重新下单
            $orderError = isset($order['err_code']) ? $order['err_code'] : '';
            if($orderError=='OUT_TRADE_NO_USED' || $orderError=='INVALID_REQUEST'){
                if($payStyle==1){
                    //重新生成流水号
                    $billCoder  = date("YmdHis").rand(1000,9999);
                }else{//如果为定金支付
                    if($rows['status']=='1'){
                        $billCoder  = date("YmdHis").rand(1000,9999)."A";
                    }else{
                        $billCoder  = date("YmdHis").rand(1000,9999)."B";
                    }
                }

                //writeLog($this->lastSql());
                $input->SetOut_trade_no($billCoder);
                //再次执行统一下单
                $order = WxPayApi::unifiedOrder($input);
                //writeLog('再次下单');
                //writeLog($order);
            }
            //writeLog($order);
            $jsApiParameters = $tools->GetJsApiParameters($order);
            $jsApiParameters = json_decode($jsApiParameters);
            //echo 111;
            //返回微信支付相关信息
            //dump($jsApiParameters);
            $data['typeName']           = '微信移动支付';
            $data['msg']                = '调起微信移动支付';
            $data['billId']             = $orderId;
            $data['billCoder']          = $billCoder;
            $data['appId']              = (string)$jsApiParameters->appId;
            $data['nonceStr']           = (string)$jsApiParameters->nonceStr;
            $data['package']            = (string)$jsApiParameters->package;
            $data['paySign']            = (string)$jsApiParameters->paySign;
            $data['signType']           = (string)$jsApiParameters->signType;
            $data['timeStamp']          = (string)$jsApiParameters->timeStamp;
            return array('payType'=>'3','status'=>'1','msg'=>'微支付','info'=>$data);
        }else{
            return array('payType'=>'3','status'=>'0','msg'=>'订单支付状态异常，暂时不可支付','info'=>array());
        }

        //dump($doc);
    }

    /**
     * 根据优惠方式 记录优惠信息并计算应付金额
     * @param $order 订单
     * @param $ponId 优惠卷id
     * @return mixed 返回应付金额
     */
    public function discountType($order,$ponId){
        $pon = $this->table('base_coupon')->where(array('coupon_receiveCoder'=>$ponId))->getOne();
        if($pon){
            $this->table('order_line_discount')->where(array('dis_orderId'=>$order['id'],'dis_orderStatus'=>2,'dis_ponStatus'=>2))->del();// 清楚该订单未付款优惠
            $disInfo = array(
                'dis_orderId'=>$order['id'],
                'dis_orderStatus'=>2,
                'dis_orderMoney'=>$order['totalMoney'],
                'dis_orderName'=>$order['title'],
                'dis_type'=>1,
                'dis_ponTypeId'=>$pon['coupon_typeId'],
                'dis_ponId'=>$pon['coupon_id'],
                'dis_ponStatus'=>$pon['coupon_status'],
                'dis_ponMoney'=>$pon['coupon_money'],
                'dis_createTime'=>date('Y-m-d H:i:s'),
                'dis_memberId'=>$order['memberId']
            );
            if(($order['totalMoney']-$pon['coupon_money']) <= 1){
                $disInfo['dis_ponMoney'] = $order['totalMoney'] - 1 ;//抵用金额
            }else{
                $disInfo['dis_ponMoney'] = $pon['coupon_money'];//抵用金额
            }
            $id    = $this->table('order_line_discount')->insert($disInfo);
            if($id){
                //writeLog('写入优惠信息成功:');
                //writeLog($disInfo);
                $money = $order['totalMoney'] - $disInfo['dis_ponMoney'];//实付金额 = 总金额-抵用卷金额  至少为1
                //writeLog('实付金额:');
                //writeLog($money);
                return $money;
            }else{return $order['totalMoney'] ;}
        }else {return $order['totalMoney'] ;}
    }

    /**
     * 微信退款
     * @param $billId
     * @param $payType
     */
    public function refundItem($billId,$payType){
        if($payType=='3') {
            $return = $this->checkRefundWeiXin($billId);
            return $return;
        }
        exit;
    }

    //执行微信退款
    public function checkRefundWeiXin($billId){
        //writeLog('进入退款流程：'.$billId);
        ini_set('date.timezone','Asia/Shanghai');
        $find  = array('a.id'=>$billId);
        //$date  = date('Y-m-d',time());
        $order  = $this->table('order_line a')
            ->jion('left join order_line_pay b on a.id=b.orderId and b.type=1 left join order_line_discount c on a.id=c.dis_orderId and c.dis_orderStatus=6')
            ->where($find)->field('a.*,b.*,a.id as orderId,a.coder as orderCoder,c.dis_id,c.dis_orderId,c.dis_ponMoney,c.dis_orderStatus')->getOne();
        //writeLog($order);die;
        if(empty($order) || $order['status']=='10'){
            return array('payType'=>'3','status'=>'-1','msg'=>'已经过期，无法发起退款','info'=>array());
        }elseif($order['status']=='9'){
            return array('payType'=>'3','status'=>'-1','msg'=>'订单已退款，无法再次退款','info'=>array());
        }elseif($order['status']=='8' || $order['status']=='11'){//供应商同意退款 和 退款失败
            //优先扣除用户金额
            if($order['money'] - $order['supplierTuiMoney'] < 0){//如果支付金额小于团损 则直接 修改成退款完成
                $rst = $this->table('order_line')->where(array('id' =>$order['orderId']))->update(array('status'=>9));
                if($rst){
                    $this->table('order_line_pay')->where(array('type'=>2,'orderId' =>$order['orderId'],'id'=>$order['id']))->update(array('money'=>'-'.$order['money']));
                    $this->table('order_line_change')->where(array('change_orderId' =>$order['orderId']))->update(array('change_isPass'=>2,'change_platIsPass'=>1));//,'change_isPass'=>2
                    //微信用户 退款后 从质量跟踪中删除
                    $this->table('server_user')->where(array('orderCoder' =>$order['coder']))->del();
                    return array('payType'=>'3','status'=>'8','msg'=>'退款完成','info'=>array());
                }else{
                    return array('payType'=>'3','status'=>'-1','msg'=>'退款失败,请稍后再试','info'=>array());
                }
            }else{
                $transaction_id = $order['payCoder'];//微信订单号
                $out_trade_no   = $order['coder'];//系统订单号
                $total_fee      = (int)($order['money'] * 100);//订单总金额 金额取整 分
                $refund_fee     = (int)(($order['money'] - $order['supplierTuiMoney']) * 100);//退款金额 分
                require_once APPROOT."/wxpay/lib/WxPay.Api.php";
                require_once APPROOT."/wxpay/example/WxPay.JsApiPay.php";
                $input = new WxPayRefund();
                $input->SetTransaction_id($transaction_id);
                $input->SetOut_trade_no($out_trade_no);
                $input->SetTotal_fee($total_fee);
                $input->SetRefund_fee($refund_fee);
                $input->SetOut_refund_no(WxPayConfig::MCHID.date("YmdHis"));
                $input->SetOp_user_id(WxPayConfig::MCHID);
                //writeLog('微信退款提交数据:');
                //writeLog($input);
                $res = WxPayApi::refund($input);
                //writeLog('微信退款结果:');
                //writeLog($res);
                if($res['return_code'] && $res['return_code'] == 'SUCCESS'){
                    if($res['result_code'] && $res['result_code'] == 'SUCCESS'){
                        $return = array();
                        //writeLog('退款信息接收成功');
                        //writeLog($order);
                        $this->table('order_line')->where(array('id' =>$order['orderId']))->update(array('status'=>12));
                        $this->table('order_line_pay')->where(array('type'=>2,'orderId' =>$order['orderId']))->update(array('payCoder'=>$res['refund_id']));
                        return array('payType'=>'3','status'=>'8','msg'=>'请求提交成功,请稍后查询','info'=>$return);
                    }else{
                        return array('payType'=>'3','status'=>'-1','msg'=>'数据接收失败,请稍后再试','info'=>array());
                    }
                }else{
                    return array('payType'=>'3','status'=>'-1','msg'=>'请求提交失败,请稍后再试','info'=>array());
                }
            }
        }elseif($order['status']=='7'){
            return array('payType'=>'3','status'=>'-1','msg'=>'订单正在退款中，无法再次发起退款','info'=>array());
        }elseif($order['status']=='6'){
            return array('payType'=>'3','status'=>'-1','msg'=>'请先申请退款','info'=>array());
        }else{
            return array('payType'=>'3','status'=>'-1','msg'=>'订单未付款，无法发起退款','info'=>array());
        }
    }



    //退款查询
    public function refundQuery($isWx = false){
        ini_set('date.timezone','Asia/Shanghai');
        //error_reporting(E_ERROR);//不报错
        require_once APPROOT."/wxpay/lib/WxPay.Api.php";
        require_once APPROOT."/wxpay/example/WxPay.JsApiPay.php";
        $msg = model('msg');//消息模型
        $input = new WxPayRefundQuery();
        if($isWx){
            $memberId = cookie('memberId');
            //获取当前用户的 退款中的订单
            $orders = $this->table('order_line a')
                ->where(array('a.memberId'=>$memberId,'a.type'=>2,'a.status'=>12))
                ->field('a.*,b.*,a.id as orderId,a.coder as orderCoder,c.dis_id,c.dis_orderId,c.dis_ponMoney,c.dis_orderStatus')
                ->jion('left join order_line_pay b on a.id=b.orderId and b.type=2 left join order_line_discount c on a.id=c.dis_orderId and c.dis_orderStatus=6')
                ->get();
        }else{
            //获取所有用户的 退款中的订单
            $orders = $this->table('order_line a')
                ->where(array('a.type'=>2,'a.status'=>12))
                ->field('a.*,b.*,a.id as orderId,a.coder as orderCoder,c.dis_id,c.dis_orderId,c.dis_ponMoney,c.dis_orderStatus')
                ->jion('left join order_line_pay b on a.id=b.orderId and b.type=2 left join order_line_discount c on a.id=c.dis_orderId and c.dis_orderStatus=6')
                ->get();
        }
        //writeLog($this->lastSql());
        //writeLog('所有退订:');
        //writeLog($orders);
        if(!empty($orders)){
            foreach($orders as $v){
                $transaction_id = $v['payCoder'];//微信退款订单号
                $out_trade_no   = $v['orderCoder'];//系统订单号
                $input ->SetOut_trade_no($out_trade_no);
                $input ->SetRefund_id($transaction_id);
                $res   = WxPayApi::refundQuery($input);
                //writeLog('---------');
                //writeLog('遍历查询结果：');
                //writeLog($res);
                //dump($res);
                if($res['return_code'] == 'SUCCESS'){
                    if($res['result_code'] && $res['result_code'] == 'SUCCESS'){
                        if($res['refund_status_0'] == 'SUCCESS') {//成功
                            $this->table('order_line')->where(array('id' =>$v['orderId']))->update(array('status'=>9));
                            $totalMoney = $res['total_fee']/100;//支付金额 分
                            $platTui = $res['refund_fee']/100;//退款金额 分
                            //$id  = $this->table('order_line_pay')->field('id')->where('type=2 and orderId='.$v['id'].' and id=(select max(id) from order_line_pay where orderId='.$v['id'].')')->getOne();
                            $this->table('order_line_pay')->where(array('type'=>2,'orderId' =>$v['orderId'],'id'=>$v['id']))->update(array('money'=>'-'.$totalMoney));
                            //writeLog($this->lastSql());
                            $res = $this->table('order_line_change')->where(array('change_orderId' =>$v['orderId']))->update(array('change_isPass'=>2,'change_platIsPass'=>1,'change_tui'=>$platTui));//,'change_isPass'=>2
                            //writeLog($this->lastSql());
                            //$this->table('order_line')->where(array('id' =>$order['id']))->update(array('status'=>9));
                            //微信用户 退款后 从质量跟踪中删除
                            $this->table('server_user')->where(array('orderCoder' =>$v['orderCoder']))->del();
                            if($res){
                                $memberId = $v['memberId'];
                                //用户提醒
                                //发短信
                                $notice  = '尊敬的'.$v['linkMan'] .',感谢您选择四川邮电旅游的线路产品,您的订单"'.$v['title'] . '"已退款成功。';
                                $notice .= '退款金额:'.$platTui .'元，';
                                if($platTui < $totalMoney){
                                    $notice .= '供应商团损:'.( $totalMoney - $platTui) .'元，';
                                }
                                $notice .= '请注意查收。';
                                $tels    = $v['linkTel'];
                                if($tels){
                                    $msg->sendSmsMsg('admin',$tels,$notice);
                                }
                                //微信消息
                                if ($memberId) {
                                    $member = $this->getMember($memberId,'wxOpenId,nickName,userId,name,departId');
                                    $tplId   = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                                    $to      = $member['wxOpenId'];//接收用户
                                    $notice  = '尊敬的'.$member['nickName'] .',感谢您选择四川邮电旅游的线路产品,您的订单"'.$v['title'] . '"已退款成功。';
                                    $notice .= '退款金额:'.$platTui .'元，';
                                    if($platTui < $totalMoney){
                                        $notice .= '供应商团损:'.( $totalMoney - $platTui) .'元，';
                                    }
                                    $notice .= '请注意查收。';
                                    $url     = '';
                                    $wxData  = array(
                                        'first'    => $notice,//标题抬头
                                        'keyword1' => $v['orderCoder'],//订单号
                                        'keyword2' => '四川邮电旅游',//操作人
                                        'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                                    );
                                    if($to){
                                        $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                                    }
                                }
                            }
                        }elseif($res['refund_status_0'] == 'FAIL'){//失败  //转代发
                            $this->table('order_line')->where(array('id' =>$v['orderId']))->update(array('status'=>11));
                        }elseif($res['refund_status_0'] == 'PROCESSING'){ //退款中

                        }elseif($res['refund_status_0'] == 'CHANGE'){//转代发
                            $this->table('order_line')->where(array('id' =>$v['orderId']))->update(array('status'=>13));
                        }
                    }else{
                        //$this->table('order_line')->where(array('id' =>$v['orderId']))->update(array('status'=>11));//
                    }
                }else{

                }
            }
        }
    }



    //获取微信用户 member
    public function getMember($memberId,$field){
        $res = $this->table('base_members')->field($field)->where(array('id'=>$memberId))->getOne();
        //dump($res);
        return $res;
    }
}