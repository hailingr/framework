<?php

/**
 * 直客线路订单 控制器
 * Class PlatStoreOrderController
 */
class ApiWxOrderController extends Controller
{
    /**
     * 获取权限下所有订单
     */
    public function getOrder(){
        $return  = array();
        $memberId = cookie('memberId');//是否登录
        if(!$memberId){
            $return['msg']    = '请登录后操作!';
        }else{
            $page   = $this->getRequest('page' , 1);
            $pageSize   = $this->getRequest('pageSize' , 10);
            $mo     = model('api.wx.order');
            $list   = $mo->getOrder($page,$pageSize);
            if($list){
                $return['count'] = $list['count'] ;
                $return['page'] = $list['page'] ;
                $return['pageSize'] = $list['pageSize'] ;
                unset($list['count']);
                unset($list['page']);
                unset($list['pageSize']);
                $return['status'] = 1 ;
                $return['msg'] = '获取订单成功' ;
                $return['data'] = $list ;
            }else{
                $return['status'] = 2 ;
                $return['msg'] = '获取订单失败' ;
            }
            //dump($return);
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }
    /**
     * 获取一条订单
     */
    public function getOneOrder(){
        $return  = array();
        $memberId = cookie('memberId');//是否登录
        if(!$memberId){
            $return['msg']    = '请登录后操作!';
        }else{
            $orderId = $this->getRequest('orderId' , '');
            $mo      = model('api.wx.order');
            //获取一条订单
            $order   = $mo->getOneOrder($orderId);
            if($order){
                //获取一条线路
                $line    = $mo->getOneLine($order['proId'],'startCityId,buyInfo');
                //dump($line);
                //获取 出发城市
                $startCityId         = trim($line['startCityId'],',');
                $startCity           = $mo->getStartCity($startCityId,'name');
                $order['startCity']  = $startCity;
                $order['buyInfo']    = str_replace(array("\r\n", "\r", "\n"),'<br/>',$line['buyInfo']);
                //dump($startCity);
                //获取一条订单的游客
                $visitors            = $mo->getVisitors($orderId);
                //dump($visitors);
                $return['order']     = $order;
                //$return['line']    = $line;
                $return['visitors']  = $visitors;

            }
        }
        //writeLog($return);
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 变更订单信息
     */
    public function changeOrderInfo(){
        $return  = array();
        $memberId = cookie('memberId');//是否登录
        if(!$memberId){
            $return['msg']    = '请登录后操作!';
        }else{
            $data = $this->getRequest('data' , '');
            if(!empty($data) && $data['orderId']){
                $mo      = model('api.wx.order');
                //联系人信息
                $res     = $mo->changeLinkMan($data);
                if($res == 1){
                    $return['status'] = 1 ;
                    $return['msg'] = '修改成功' ;
                }else{
                    $return['status'] = 2 ;
                    $return['msg'] = '操作失败' ;
                }
            }else{
                $return['status'] = 2 ;
                $return['msg'] = '非法操作' ;
            }
        }

        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     *  生成微信用户订单
     */
    public function createWxOrder(){
        //model('user.info','mysql')->loginIs(false);//是否登录
        $return         = array();
        $memberId = cookie('memberId');//是否登录
        if(!$memberId){
            $return['msg']    = '请登录后操作!';
        }else{
            $mo             = model('api.wx.order');
            $typeId         = $this->getRequest('typeId');//
            $lineId         = $this->getRequest('lineId');//线路ID
            $startDay       = $this->getRequest('startDay');//出发时间
            $manNums        = $this->getRequest('manNums',1);//成人数量
            $childrenNums   = $this->getRequest('childrenNums');//儿童数量
            $houseNums      = $this->getRequest('houseNums');//单房数量
            $linkMan        = $this->getRequest('linkMan','');//联系人
            $linkTel        = $this->getRequest('linkTel','');//联系电话
            $linkEmail      = $this->getRequest('linkEmail');//；联系邮箱
            $visitor        = $this->getRequest('visitor','');
            $memo           = $this->getRequest('memo');//备注
            //writeLog($_REQUEST);
            if($typeId && $lineId && $startDay && ($manNums || $childrenNums) && $linkMan && $linkTel && $visitor ){
                $res              = $mo->createWxOrder($lineId,$typeId,$startDay,$manNums,$childrenNums,$houseNums,$linkMan,$linkTel,$linkEmail,$visitor,$memo);
                if($res['status'] == 1){
                    $return['orderId'] = $res['orderId'];
                    $return['status'] = 1;
                    $return['msg']    = '订单创建成功,请继续完成支付';
                }else{
                    $return['status'] = 2;
                    $return['msg']    = '订单创建失败,请重新操作';
                }
            }else{
                $return['status'] = 2;
                $return['msg']    = '请填写完整信息后再提交订单';
            }
            //dump($res);
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }


    /**
     * 判断库存是否足够
     */
    public function getDepot(){
        $typeId   = $this->getRequest('typeId');
        $lineId   = $this->getRequest('lineId');
        $startDay = $this->getRequest('startDay');
        $depotNum = $this->getRequest('depotNum');
        $mo       = model('api.wx.order');
        $res      = $mo->getOneLinePrice($lineId,$typeId,$startDay,'depotNums');
        if($res && ($res['depotNums'] - $depotNum > 0)){
            exit(json_encode(1,JSON_UNESCAPED_UNICODE));
        }else{
            exit(json_encode(-1,JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 获取微信端订单联系人及游客
     */
    public function getLinkManAndVisitor(){
        $return = array();
        $memberId = cookie('memberId');//是否登录
        if(!$memberId){
            $return['msg']    = '请登录后操作!';
        }else{
            $orderId  = $this->getRequest('orderId');
            $mo       = model('api.wx.order');
            $res      = $mo->getLinkManAndVisitor($orderId);
            if($res){
                $return['status']  = 1;
                $return['msg']     = '获取信息成功';
                $return['list']    = $res;
            }else{
                $return['status'] = 2;
                $return['msg']    = '获取信息失败';
            }
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 取消订单
     */
    public function cancelOrder(){
        $return = array('status'=>-1);
        $memberId = cookie('memberId');//是否登录
        if(!$memberId){
            $return['msg']    = '请登录后操作!';
        }else{
            $orderId = $this->getRequest('id' , '');
            $proType = $this->getRequest('proType' , '');
            $memo    = $this->getRequest('memo' , '');
            $mo     = model('api.wx.order');
            if(!$orderId){
                $return['msg'] = '错误的参数【id】' ;
            }else{
                $res = $mo->cancelOrder($orderId,$proType,$memo);
                if($res['status'] == 1){
                    $return['status'] = 1 ;
                    $return['msg'] = '订单取消成功' ;
                }elseif($res['status'] == -1){
                    $return['msg'] = '非法操作' ;
                }else{
                    $return['msg'] = '操作失败' ;
                }
            }
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 修改游客信息
     */
    public function changeVi(){
        $return  = array();
        @$memberId = cookie('memberId');
        if($memberId){
            $visitor = $this->getRequest('visitor' , '');
            $orderId = $this->getRequest('orderId' , '');
            if($visitor && $orderId){
                $mo      = model('api.wx.order');
                $res     = $mo->changeVi($orderId,$visitor);
                if($res == 1){
                    $return['status'] = 1 ;
                    $return['msg'] = '修改成功' ;
                }elseif($res == -1){
                    $return['status'] = 2 ;
                    $return['msg'] = '非法操作' ;
                }else{
                    $return['status'] = 2 ;
                    $return['msg'] = '操作失败' ;
                }
            }else{
                $return['status'] = 2 ;
                $return['msg'] = '非法操作' ;
            }
        }else{
            $return['status'] = 2 ;
            $return['msg'] = '请登录后操作' ;
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 修改联系人
     */
    public function changeLinkMan(){
        $return  = array();
        @$memberId = cookie('memberId');
        if($memberId){
            $data = $this->getRequest('data' , '');
            //dump($data);die;
            if(!empty($data) && $data['orderId']){
                $mo      = model('api.wx.order');
                $res     = $mo->changeLinkMan($data);
                if($res == 1){
                    $return['status'] = 1 ;
                    $return['msg'] = '修改成功' ;
                }elseif($res == -1){
                    $return['status'] = 2 ;
                    $return['msg'] = '非法操作' ;
                }else{
                    $return['status'] = 2 ;
                    $return['msg'] = '操作失败' ;
                }
            }else{
                $return['status'] = 2 ;
                $return['msg'] = '非法操作' ;
            }
        }else{
            $return['status'] = 2 ;
            $return['msg'] = '请登录后操作' ;
        }

        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 微信用户退款申请
     */
    public function refundApply(){
        $return  = array();
        @$memberId = cookie('memberId');
        if($memberId){
            $orderId = $this->getRequest('orderId' , '');
            $memo = $this->getRequest('memo' , '');
            if($orderId){
                $mo      = model('api.wx.order');
                $res     = $mo->refundApply($orderId,$memo);
                if($res == 1){
                    $return['status'] = 1 ;
                    $return['msg'] = '退款申请提交成功' ;
                }elseif($res == -2){
                    $return['status'] = 2 ;
                    $return['msg'] = '未登录或登录超时' ;
                }elseif($res == -1){
                    $return['status'] = 2 ;
                    $return['msg'] = '非法操作' ;
                }else{
                    $return['status'] = 2 ;
                    $return['msg'] = '退款申请提交失败' ;
                }
            }else{
                $return['status'] = 2 ;
                $return['msg'] = '非法操作' ;
            }
        }else{
            $return['status'] = 2 ;
            $return['msg'] = '请登录后操作' ;
        }

        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 微信退款查询
     */
    public function refundQuery()
    {
        //线路游微信订单退款查询
        $mo   = model('api.wx.pay','mysql');
        $mo->refundQuery(true);
        //定制游微信订单退款查询
        $dzMo   = model('weixin.wap.dingzhi','mysql');
        $dzMo->refundQuery(true);
    }





    //+----------------------↓
    /**
     * 变更申请页面
     */
    /*public function changeOrderPage(){
        model('user.info','mysql')->loginIs();//是否登录
        $orderId = $this->getRequest('orderId' , '');
        $mo     = model('store.order');
        //获取一条订单
        $order  = $mo->getOneOrder($orderId);

        //获取一条相关 线路
        $line  = $mo->getOneLine($order['proId'] , 'tuiInfo');
        $tuiInfo = $line['tuiInfo'];
        //获取一条订单的游客
        $visitors = $mo->getVisitors($orderId);
        //dump( $visitors );
        $this->assign('order',$order);
        $this->assign('tuiInfo',$tuiInfo);
        $this->assign('visitors',$visitors);
        $this->template('plat.store.changeVisitor');
    }*/

    /**
     * 变更处理
     */
    /*public function changeOrder(){
        $return  = array();
        $data = $this->getRequest('data' , '');
        //dump($_REQUEST);die;
        $mo     = model('store.order');
        $res = $mo->changeOrder($data);
        if($res == 1){
            $return['status'] = 1 ;
            $return['msg'] = '变更信息已提交,等待供应商确认!' ;
        }elseif($res == -1){
            $return['status'] = 2 ;
            $return['msg'] = '非法操作!' ;
        }else{
            $return['status'] = 0 ;
            $return['msg'] = '操作失败，请重新操作!' ;
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }*/

    /*public function tuiMoneyPage(){
        model('user.info','mysql')->loginIs();//是否登录
        $coder = $this->getRequest('coder' , '');
        $orderId = $this->getRequest('orderId' , '');
        $changeId = $this->getRequest('changeId' , '');
        $this->assign('coder',$coder);
        $this->assign('orderId',$orderId);
        $this->assign('changeId',$changeId);
        $this->template('plat.store.accInfo');
    }*/

    /*public function getWxImg(){
        $id = $this->getRequest('id' , '');
        $url = "http://pro.scydgl.com/index.php?m=weixin.bind.bill&serverBillId=".$id;
        showQrCoder($url);
    }*/
}
