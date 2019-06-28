<?php


class ApiWxOrderModel extends Model
{
    /**
     * 创建微信用户订单
     * @param $lineId 线路id
     * @param $typeId 套餐 id
     * @param $startDay 出发日期
     * @param $manNums 成人数
     * @param $childrenNums 儿童数
     * @param $houseNums 单房数
     * @param $linkMan 联系人
     * @param $linkTel 联系电话
     * @param $linkEmail 联系邮箱
     * @param $visitor 游客 格式姓名|1(成人儿童类型)|身份证|证件号|电话##姓名|2|身份证|证件号|电话
     * @param $memo 备注信息
     * @return bool|int 返回值
     */
    public function createWxOrder($lineId,$typeId,$startDay,$manNums,$childrenNums,$houseNums,$linkMan,$linkTel,$linkEmail,$visitor,$memo){

        //获取微信用户wxOpenId ，若无则直接返回
        @$memberId = cookie('memberId');
        //$memberId = $memberId ? $memberId : 10;
        $member = $this->getMember($memberId,'wxOpenId,nickName,userId,name,departId');
        if($memberId){
            $linePrice = $this->getOneLinePrice($lineId,$typeId,$startDay,'*');
            $line      = $this->getOneLine($lineId,'id,name,days,startCityId,destTypeId,destId,supplierId,supplierName,waitTime');
            //writeLog($linePrice);
            //+---订单信息------ ↓
            $data                  = array();
            $data['coder']         = date("YmdHis") . rand(1000, 9999);//订单编号 18位
            $data['title']         = $line['name'];
            $data['type']          = 2;
            $data['startDay']      = $startDay;
            $data['days']          = $line['days'];
            $data['endDay']        = date('Y-m-d',(strtotime($startDay)+$line['days']*24*3600));
            //人数信息
            $totalPeople           = $manNums + $childrenNums ;
            $data['manNums']       = $manNums;
            $data['manPrice']      = $linePrice['persionManPrice'];
            $data['childrenNums']  = $childrenNums;
            $data['childPrice']    = $linePrice['persionChildrenPrice'];
            //$data['oldmanNums']  = 0;
            //$data['otherNums']   = 0;
            $data['houseNums']     = $houseNums;
            $data['housePrice']    = $linePrice['persionHousePrice'];
            //总金额
            $totalMoney            = $manNums * $linePrice['persionManPrice'] + $childrenNums * $linePrice['persionChildrenPrice'] + $houseNums * $linePrice['persionHousePrice'];
            $data['totalMoney']    = $totalMoney;
            //供应商信息及金额
            $data['supplierId']    = $line['supplierId'];
            $data['supplierName']  = $line['supplierName'];
            $supplierMoney         = $manNums * $linePrice['manPrice'] + $childrenNums * $linePrice['childrenPrice'] + $houseNums * $linePrice['housePrice'];
            $data['supplierMoney'] = $supplierMoney;
            $data['supplierPrice'] = $linePrice['manPrice'];
            $data['supplierChildPrice'] = $linePrice['childrenPrice'];

            //门市信息及佣金
            if($member['departId']){
                //计算佣金比值
                if($linePrice['storePer'] < 1){
                    $per = $linePrice['storePer'];
                }else{
                    $per = $linePrice['storePer'] / $linePrice['storeManPrice'];
                }
                $storeInfo            = $this->getStoreInfo($member['departId'],'name',3);
                $data['storeId']      = $member['departId'];
                $data['storeName']    = $storeInfo['name'];
                //$storePer             = $linePrice['storePer'] / $linePrice['persionManPrice'];
                $data['storePer']     = $per;
                $data['sorePerMoney'] = $totalMoney * $per;
            }

            //直客佣金
            if($linePrice['storePer'] < 1){
                $personPer = $linePrice['persionPer'];
            }else{
                $personPer = $linePrice['persionPer'] / $linePrice['persionManPrice'];
            }
            $data['personPer']      = $personPer;
                $data['personPerMoney'] = $totalMoney * $personPer;
            $data['proId']       = $linePrice['lineId'];
            $data['proTypeId']   = $linePrice['typeId'];
            $data['proTypeName'] = $linePrice['typeName'];

            $data['userId']      = $member['userId'];
            //获取门市用户（分享者）
            $userInfo = $this->getUserInfo($data['userId'],'id,nickName,realName');
            $data['userName']    = $userInfo['nickName'] ? $userInfo['nickName'] : $userInfo['realName'];

            $data['memberId']    = $memberId;
            $data['memberName']  = $member['nickName'];
            $data['linkMan']      = $linkMan;
            $data['linkTel']      = $linkTel;
            $data['linkEmail']    = $linkEmail;
            $data['linkWxOpenId'] = $member['wxOpenId'];
            $data['linkWxInfo']   = '';
            //$data['isSendNotice'] = 0;
            $status = 2;
            $newDepots = $linePrice['depotNums'] - $totalPeople;
            if($newDepots < 0){ $status = 1;}
            $data['status']      = $status;
            $time = time();
            $data['create_time'] = date('Y-m-d H:i:s',$time);
            $data['update_time'] = date('Y-m-d H:i:s',$time);
            $data['expire_time'] = date('Y-m-d H:i:s',$time+3600*$line['waitTime']);
            //返回库存处理
            if ($linePrice['depotNums'] <= 0) {
                $expireDepotNums = 0;
            } else {
                $totalNums = $manNums + $childrenNums;
                $tempNums = $linePrice['depotNums'] - $totalNums;
                if ($tempNums <= 0) $expireDepotNums = $linePrice['depotNums'];
                else $expireDepotNums = $totalNums;
            }
            $data['expire_depot_nums'] = $expireDepotNums;
            $data['memo'] = $memo;
            //+---订单信息 完------↑
            //writeLog($visitor);
            //插入订单信息
            $orderId = $this->table('order_line')->insert($data);
            if($orderId){
                //发送系统消息
                $msg = model('msg');
                //修改库存//插入游客信息//发送供应商系统信息//发送供应商微信信息//发送游客微信信息
                //修改库存
                if($newDepots < 0){$newDepots = 0;}
                $this->table('pro_line_price')->where(array('lineId' => $lineId, 'typeId' => $typeId, '`day`' => $startDay))->update(array('depotNums' => $newDepots));
                //发送供应商系统信息
                $sendMsg  = '预定通知:  ';
                $sendMsg  .= $data['memberName'] . " 预订 " . $data['title'] . "," . date("m月d日", strtotime($startDay)) . "出发,人数：成" . $manNums;
                if($childrenNums > 0){
                    $sendMsg .= ",童" . $childrenNums;
                }
                if($newDepots < 0){
                    $sendMsg .= '<span style="color: red;">,余位不足，请尽快补位！<a class="J_menuItem" href="/?m=plat.supplier.lines&a=lists&rand=' . rand(0, 99) . '" style="padding:0;">前往补位</a></span>';
                }else{
                    $sendMsg   .= '<a class="J_menuItem" href="/?m=plat.supplier.order&a=index&rand=' . rand(0, 99) . '" style="padding:0;">' . '订单列表</a>';
                }
                $users   = $this->getSupplierUsers($line['supplierId']);
                if($users){
                    $msg->sendSysNotice($sendMsg, $users, 'admin');
                }
                //发送供应商微信信息
                $users = implode('","',$users);
                $users = '"'.$users .'"';
                $reviewWx = $this->table('core_user')->where('`name` in ('.$users.')')->field('wxOpenId,wxName,realName')->get();//接收人员
                //$sendWx = $data['memberName'];//发送人员
                foreach($reviewWx as $wx){
                    if ($wx['wxOpenId']) {
                        $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                        $to = $wx['wxOpenId'];
                        $url = '';
                        if($wx['wxName'] == '' || $wx['wxName'] == null) $wx['wxName'] = $wx['realName'] ;
                        $wxData = array(
                            'first' => '尊敬的'.$wx['wxName'] . ', 您的产品'.$data['title'] . '"有新订单了。出团日期：'.$data['startDay'].'。',//标题抬头
                            'keyword1' => $data['coder'],//订单号
                            'keyword2' => '四川邮电旅游',//操作人/发送人员
                            'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                        );
                        $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                    }
                }

                //微信用户加入质量跟着系统
                /*$ser = array(
                    'nickName'=>$member['nickName'],
                    'realName'=>$data['linkMan'],
                    //'idType'=>'',
                    //'idNumber'=>'',
                    'tel'=>$data['linkTel'],
                    //'email'=>'',
                    //'qq'=>'',
                    //'wx'=>'',
                    'wxOpenId'=>$member['wxOpenId'],
                    'proId'=>$data['proId'],
                    'proName'=> $data['title'],
                    'proType'=> 1,
                    'orderCoder'=>$data['coder'],
                    'orderId'=>$data['orderId'],
                    'startDay'=>$data['startDay'] ,
                    'endDay'=>$data['endDay'] ,
                    //'status'=>0,
                    'supplierName'=>$data['supplierName'],
                    'supplierId'=>$data['supplierId'],
                    //'supplierTel'=>$users,
                    //'supplierWxOpenId'=>$users,
                    //'supplierEmail'=>$users,
                    'storeName'=>$data['storeName'],
                    'stroeId'=>$data['storeId'],
                    //'storeWxOpenId'=>'',
                    //'storeEmail'=>'',
                    //'storeTel'=>'',
                );
                $this->table('server_user')->insert($ser);*/
                //writeLog($this->lastSql());
                //发送游客微信信息
                if($data['linkWxOpenId']){
                    $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                    $to   = $data['linkWxOpenId'];
                    $toName = $member['nickName'] ? $member['nickName'] : $linkMan ;
                    $url = '';
                    $wxData = array(
                        'first' => '尊敬的'.$toName . ', 您的"'.$data['title'] .'" 预定成功,请尽快付款,避免订单过期,出发时间 :'. $data['startDay'].'。' ,//标题抬头
                        'keyword1' => $data['coder'],//订单号
                        'keyword2' => '四川邮电旅游',//操作人
                        'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                    );
                    $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                }
                //插入游客信息
                $visitor = explode('##',$visitor);
                foreach($visitor as $k=>$v){
                    $visitor[$k] = $orderId.'|'.$v.'|'.date('Y-m-d H:i:s',$time);
                }
                $visitor = implode('##',$visitor);
                $visitor = str_replace('|','","',$visitor);
                $visitor = str_replace('##','"),("',$visitor);
                $visitor = '("'.$visitor.'")';

                $sql = 'insert into order_line_visitor(orderId,`name`,personType,`type`,coder,tel,create_time) VALUES '.$visitor;
                $this->query($sql);
                return array('orderId'=>$orderId,'status'=>1);
            }else{
                return array('status'=>0);
            }
        }else{
            return array('status'=>0);
        }

    }
    /**
     * 获取微信用户所有订单
     * @param $p 页码
     * @param $pageSize 每页条数
     * @return array 返回值
     */
    public function getOrder($p,$pageSize){
        //echo md5('111111');
        $this->expireOrder();//更改过期订单
        //$pageSize = 10;
        @$memberId = cookie('memberId');
        $memberId = $memberId ? $memberId : 10;
        if($memberId){
            $page  = ($p-1)* $pageSize;
            //统计条数
            $countSql = "SELECT COUNT(*) as num FROM (select a.coder,a.create_time,2 as proType from custom_swim_order a WHERE a.memberId=$memberId and a.type=2 union select b.coder,b.create_time,1 as proType from order_line b  WHERE b.memberId=$memberId and b.type=2 ) c";
            $count = $this->getOne($countSql);
            //writeLog($count);
            //查询数据
            $resSql = "select a.id,a.title,a.totalMoney,a.create_time,a.type,a.startDay,a.manNums,a.childrenNums,a.status,2 as proType,d.cover,d.id as proId from custom_swim_order a left join custom_swim_line d on a.lineId=d.id  WHERE a.memberId=$memberId and a.type=2";

            $resSql .= " union select b.id,b.title,b.totalMoney,b.create_time,b.type,b.startDay,b.manNums,b.childrenNums,b.status,1 as proType,c.cover,c.id as proId from order_line b left join pro_line c on b.proId=c.id WHERE b.memberId=$memberId and b.type=2";

            $resSql .= " order by create_time DESC,startDay asc LIMIT $page,$pageSize";

            $res = $this->get($resSql);

            $return = array('list'=>$res,'count'=>$count['num'],'page'=>$p,'pageSize'=>$pageSize);

            return $return;
        }else{
            return array();
        }

    }
    /**
     * 获取一条订单及线路、客户
     * @param $orderId 订单id
     * @return mixed 返回值
     */
    public function getOneOrder($orderId){
        $this->expireOrder();//更改过期订单
        @$memberId = cookie('memberId');
        //$memberId = $memberId ? $memberId : 10;
        if($memberId){
            $orderId = (int)$orderId;
            $res = $this->table('order_line a')
                ->field('a.id,a.coder,a.title,a.startDay,a.endDay,a.days,a.manNums,a.manPrice,a.childrenNums,a.childPrice,a.houseNums,
            a.housePrice,a.totalMoney,a.proTypeName,a.linkMan,a.linkTel,a.linkEmail,a.status,a.supplierStatusInfo,
            a.create_time,a.memo,a.proTypeName,a.proTypeId,a.linkMan,a.linkTel,a.linkEmail,a.status,a.supplierStatusInfo,a.proId,c.dis_ponMoney')
                ->jion('left join pro_line b on a.proId=b.id left join order_line_discount c on a.id=c.dis_orderId')
                ->where(array('a.id'=>$orderId,'a.memberId'=>$memberId))
                ->getOne();
            //echo $this->lastSql();
            if($res){//判断是否能够进行退订
                $isTui = 0;
                $status = $res['status'];
                $nowTime = strtotime(date('Y-m-d',time()+3600*24));
                $startDay = strtotime($res['startDay']);
                if($status==6&&$nowTime<$startDay){
                    $isTui = 1;
                }
                $res['isTui'] = $isTui;
                $res['resStatus'] = 1;//登录且获取到订单
            }else{
                $res['resStatus'] = 0;//登录未获取到订单
            }
        }else{
               $res['resStatus'] = 2;//未登录
        }
        return $res;
    }
    /**
     * 获取 出发城市
     * @param $cityId 城市id
     * @param $fields 字段
     * @return string 返回值
     */
    public function getStartCity($cityId,$fields){
        $res = $this->table('base_city')->field($fields)->where('id in('.$cityId.')')->get();
        $item = '';
        if($res){
            $ext  = '';
            foreach($res as $v){
                $item .= $ext . $v['name'];
                $ext  = ',';
            }
        }
        return $item;
    }
    /**
     * 获取微信端订单联系人及游客
     * @param $orderId 订单号或者订单id
     * @return array 返回结果
     */
    public function getLinkManAndVisitor($orderId){
        $return = array();
        $memberId = cookie('memberId');
        //$memberId  = 4;
        if(strlen($orderId) == 18){//订单号
            $linkMan  = $this->table('order_line')->field('id,coder,linkMan,linkTel,linkEmail')->where(array('coder'=>$orderId,'memberId'=>$memberId))->getOne();
            $visitors = $this->table('order_line_visitor')->field('id,orderId,name,personType,type,coder,tel')->where(array('orderId'=>$linkMan['id']))->get();
        }else{
            $linkMan  = $this->table('order_line')->field('id,coder,linkMan,linkTel,linkEmail')->where(array('id'=>$orderId,'memberId'=>$memberId))->getOne();
            $visitors = $this->table('order_line_visitor')->field('id,orderId,name,personType,type,coder,tel')->where(array('orderId'=>$orderId))->get();
        }
        if($linkMan){
            $return['linkMan']  = $linkMan;
        }
        if($visitors){
            $return['visitors'] = $visitors;
        }
        return $return;
    }

    /**
     * 修改联系人信息
     * @param $data 联系人信息
     * @return mixed
     */
    public function changeLinkMan($data){
        $date     = date('Y-m-d',time());
        $memberId = cookie('memberId');
        //$memberId  = 4;
        if($memberId){
            $order    = $this->table('order_line')->where('id='.$data['orderId'].' and memberId='.$memberId.' and status in(1,2,6) and startDay>"'.$date.'"')->getOne();
            if($order){
                $up = array(
                    'linkMan'=>$data['linkMan'],
                    'linkTel'=>$data['linkTel'],
                    'linkEmail'=>$data['linkEmail'],
                );
                $res = $this->table('order_line')->where(array('id'=>$data['orderId'],'memberId'=>$memberId))->update($up);
                if($res){
                    return 1;
                }else{
                    return 2;
                }
            }else{
                return -1 ;
            }
        }else{
            return -1 ;
        }
    }

    /**
     * 修改 游客信息
     * @param $visitor 游客信息
     * @param $orderId 订单id
     * @return mixed
     */
    public function changeVi($orderId,$visitor){
        $memberId = cookie('memberId');
        //$memberId  = 4;
        $date     = date('Y-m-d',time());
        if($memberId){
            $order    = $this->table('order_line')->where('id='.$orderId.' and memberId='.$memberId.' and status in(1,2,6) and startDay>"'.$date.'"')->getOne();
            if($order){
                $i = 0;
                foreach($visitor as $v){
                    $i++;
                    $up = array(
                        'name'=>$v['name'],
                        'tel'=>$v['tel'],
                        'type'=>$v['type'],
                        'coder'=>$v['coder'],
                        'personType'=>$v['personType'],
                    );
                    //dump($up);
                    $this->table('order_line_visitor')->where(array('id'=>$v['visitorId']))->update($up);
                }
                if($i == count($visitor)){
                    return 1;
                }else{
                    return 2;
                }
            }else{
                return -1 ;
            }
        }else{
            return -1 ;
        }
    }

    /**
     * 获取一条订单售价
     * @param $lineId 线路id
     * @param $typeId 套餐类型id
     * @param $day 出发日期
     * @param $fields 查找字段
     * @return mixed 返回值
     */
    public function getOneLinePrice($lineId,$typeId,$day,$fields){
        $res = $this->table('pro_line_price')->field($fields)->where(array('lineId'=>$lineId,'typeId'=>$typeId,'`day`'=>$day))->getOne();
        //dump($res);
        //echo $this->lastSql();
        return $res;
    }
    /**
     * 获取一条线路
     * @param $id 线路id
     * @param $fields 查找字段
     * @return mixed 返回值
     */
    public function getOneLine($id,$fields){
        $id  = (int)$id;
        $res = $this->table('pro_line')->field($fields)->where(array('id'=>$id))->getOne();
        //dump($res);
        return $res;
    }
    /**
     * 获取门市信息
     * @param $id 门市id
     * @param $fields 查找字段
     * @param $type 部门 类型 门市为 3
     * @return mixed 返回值
     */
    public function getStoreInfo($id,$fields,$type){
        $id  = (int)$id;
        $res = $this->table('core_depart')->field($fields)->where(array('id'=>$id,'type'=>$type))->getOne();
        //dump($res);
        return $res;
    }

    /**
     * 获取一个用户的信息
     * @param $id
     * @param $fields
     * @return mixed
     */
    public function getUserInfo($id,$fields){
        $id  = (int)$id;
        $res = $this->table('core_user')->field($fields)->where(array('id'=>$id))->getOne();
        //dump($res);
        return $res;
    }
    /**
     * 获取指定供应商下所有用户
     * @param $supplierId 供应商 id
     * @return array 返回值
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
     * 取消订单
     * @param $orderId
     * @param $proType
     * @param $memo
     * @return bool|int
     */
    public function cancelOrder($orderId,$proType,$memo){
        $orderId = (int)$orderId;
        $memberId = cookie('memberId');
        $return = array('status'=>0);
        $res    = false ;
        $proTypeStr = "";
        $orderListUrl= '';
        $wxOrderUrl  = "";
        if($proType == 1){
            $order   = $this->table('order_line')->where(array('id' =>$orderId,'memberId'=>$memberId))->getOne();//只能查找微信用户自己的订单
            if($order && ($order['status'] == 1 || $order['status'] == 2)){//只有1.2状态允许取消
                $res = $this->table('order_line')->where(array('id' =>$orderId))->update(array('status'=>4,'memo'=>$memo));
            }else{
                $return['status'] = -1 ;
            }
            $orderListUrl  = '<a class="J_menuItem" href="/?m=plat.supplier.order&a=index&rand=' . rand(0, 99) . '" style="padding:0;">线路订单</a>';
            $wxOrderUrl    = "http://pro.scydgl.com/?m=weixin.wap.product&a=order_minute&orderId=".$orderId;
        }elseif($proType == 2){
            $proTypeStr = "定制游";
            $wxOrderUrl    = "http://pro.scydgl.com/?m=weixin.wap.dingzhi&a=orderPay&orderId=".$orderId;
            //定制游取消订单
            $order   = $this->table('custom_swim_order')->where(array('id' =>$orderId,'memberId'=>$memberId))->getOne();//只能查找微信用户自己的订单
            if($order && $order['status'] == 1 ){//只有1状态允许取消
                $res = $this->table('custom_swim_order')->where(array('id' =>$orderId))->update(array('status'=>2,'memo'=>$memo));
                //修改需求表中状态
                $this->table('custom_swim_demand')->where(array('id'=>$order['demandId']))->update(array("isDo"=>1));
                $this->table('custom_swim_line')->where(array('id'=>$order['lineId']))->update(array("isDo"=>10));
            }else{
                $return['status'] = -1 ;
            }
            $orderListUrl  = '<a class="J_menuItem" href="?m=plat.custom.order&a=suOrderPage&rand=' . rand(0, 99) . '" style="padding:0;">线路订单</a>';
        }else{
            $return['status'] = -1 ;
        }


        if($res){
            $member = $this->getMember($memberId,'id,nickName');
            //释放合同
            $this->table('contract_numbers')->where(array('orderId' =>$orderId,'status'=>2))->update(array('orderId'=>'','status'=>1,'use_user'=>''));
            //还原库存
            if($proType == 1){
                $nums = $order['expire_depot_nums'];
                if ($nums > 0) {
                    $sql = "update pro_line_price set depotNums=depotNums+" . $nums . " where lineId=" . $order['proId'] . " and typeId=" . $order['proTypeId'] . " and `day`='" . $order['startDay'] . "'";
                    $this->query($sql);
                }
            }
            //发送系统消息
            $msg = model('msg');
            $sendMsg  = '订单取消通知：您有一条'.$proTypeStr.'订单取消，订单号：'.$order['coder'].'。   ';
            $sendMsg .= $orderListUrl;//系统订单列表链接
            $users   = $this->getSupplierUsers($order['supplierId']);
            if($users){
                $msg->sendSysNotice($sendMsg, $users, 'admin');
            }
            //供应商微信消息
            $users = implode('","',$users);
            $users = '"'.$users .'"';
            $reviewWx = $this->table('core_user')->where('`name` in ('.$users.')')->field('wxOpenId,wxName,realName')->get();//接收人员
            //$sendWx = $memberName;
            foreach($reviewWx as $wx){
                if ($wx['wxOpenId']) {
                    $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                    $to = $wx['wxOpenId'];
                    $url = '';
                    if($wx['wxName'] == '' || $wx['wxName'] == null) $wx['wxName'] = $wx['realName'] ;
                    $wxData = array(
                        'first' => '尊敬的'.$wx['wxName'] . ',您的'.$proTypeStr.'线路"'.$order['title'] .'" 有一条订单已取消。',//标题抬头
                        'keyword1' => $order['coder'],//订单号
                        'keyword2' => '四川邮电旅游',//操作人//发送人员
                        'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                    );
                    $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                }
            }
            //用户微信信息
            $linkWx = $order['linkWxOpenId'];
            if ($linkWx) {
                $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                $to = $linkWx;
                $url = $wxOrderUrl;
                $wxData = array(
                    'first' => '尊敬的'.$member['nickName'] . ',您的'.$proTypeStr.'订单"'.$order['title'] .'" 已成功取消。',//标题抬头
                    'keyword1' => $order['coder'],//订单号
                    'keyword2' => '四川邮电旅游',//操作人//发送人员
                    'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                );
                $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
            }
            $return['status'] = 1 ;
        }
        return $return ;
    }
    /**
     * 自动检测过期订单并处理
     */
    public function expireOrder(){
        $noTime = date("Y-m-d H:i:s");
        $noDate = date("Y-m-d");
        $data = array('status' => 10);
        //未付款出发日期就过期了的订单
        $this->table('order_line')->where("(status = 2 or status = 1) and startDay < '$noDate'")->update($data);
        //未付款且付款时间过期了的订单，需要还原其库存
        $rows = $this->table('order_line')->where("(status = 2 or status = 1) and expire_time <= '$noTime'")->field("proId,proTypeId,startDay,expire_depot_nums")->get();
        //dump($rows);
        if (!empty($rows)) {
            foreach ($rows as $item) {
                $nums = $item['expire_depot_nums'];
                if ($nums > 0) {
                    $sql = "update pro_line_price set depotNums=depotNums+" . $nums . " where lineId=" . $item['proId'] . " and typeId=" . $item['proTypeId'] . " and `day`='" . $item['startDay'] . "'";
                    $this->query($sql);
                }
            }
        }
        //还原库存后修改该订单
        $this->table('order_line')->where("(status = 2 or status = 1) and expire_time <= '$noTime'")->update($data);
    }

    public function getVisitors($orderId){
        return $this->table('order_line_visitor')->field('id,orderId,name,personType,type,coder,tel')->where(array('orderId'=>$orderId))->get();
    }
    //订单退订申请
    public function refundApply($orderId,$memo){
        $memberId = cookie('memberId');
        if($memberId){
            $order   = $this->table('order_line')->where(array('id'=>$orderId,'type'=>2,'memberId'=>$memberId))->getOne();
            if($order && $order['status'] == 6 ){//只有6状态允许门市变更
                //$visitor = $this->table('order_line_visitor')->where(array('id'=>$orderId))->get();
                $manNum = $order['manNums'];
                $childNum = $order['childrenNums'];
                $houseNum = $order['houseNums'];
                $_totalMoney = $order['totalMoney'];
                //$suppMoney = $manNum * $order['supplierPrice'] + $childNum * $order['supplierChildPrice'] + $houseNum * $order['housePrice'];
                //$_totalMoney = $manNum * $order['manPrice'] + $childNum * $order['childPrice'] + $houseNum * $order['housePrice'];
                $payPre   = 1;
                $in = array(
                    'change_orderId'=>$orderId,
                    'change_type'=>2,
                    'change_manNum'=>$manNum,
                    'change_childrenNum'=>$childNum,
                    'change_houseNum'=>$houseNum,
                    'change_totalMoney'=>$_totalMoney,
                    'change_status'=>1,
                    'change_lastStatus'=>6,
                    'change_create_time'=>date('Y-m-d H:i:s'),
                    'change_payPre'=>$payPre,
                    'change_memo'=>$memo,
                );
                //订单状态修改
                //writeLog('客户点击开始申请：id：'.$orderId);
                $res = $this->table('order_line')->where(array('id' =>$orderId))->update(array('status'=>7));
                if($res){

                    $this->table('order_line_change')->insert($in);
                    $this->table('order_line_visitor')->where(array('orderId'=>$orderId))->update(array('isCancel'=>3));
                    //发送系统消息
                    $msg = model('msg');
                    $sendMsg  = '退订申请：您的产品"'.$order['title'].'"有一条退款申请,请尽快处理，订单号：'.$order['coder'].'。   ';
                    $sendMsg .= '<a class="J_menuItem" href="/?m=plat.supplier.order&a=index&rand=' . rand(0, 99) . '" style="padding:0;">线路订单</a>';
                    $users   = $this->getSupplierUsers($order['supplierId']);
                    if($users){
                        $msg->sendSysNotice($sendMsg, $users, 'admin');
                    }
                    //微信消息
                    $users = implode('","',$users);
                    $users = '"'.$users .'"';
                    $reviewWx = $this->table('core_user')->where('`name` in ('.$users.')')->field('wxOpenId,wxName')->get();//接收人员
                    //$sendWx = $this->table('core_user')->where('id="'.$order['userId'].'"')->field('nickName,name')->getOne();//发送人员
                    foreach($reviewWx as $wx){
                        if ($wx['wxOpenId']) {
                            $tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';
                            $to = $wx['wxOpenId'];
                            $url = '';
                            if($wx['wxName'] == '' || $wx['wxName'] == null) $wx['wxName'] = '用户' ;
                            $wxData = array(
                                'first' => '尊敬的'.$wx['wxName'] . ', 您的产品"'.$order['title'].'"有一条退款申请,请尽快处理。',//标题抬头
                                'keyword1' => $order['coder'],//订单号
                                'keyword2' => '四川邮电旅游',//操作人
                                'keyword3' =>  date('Y-m-d H:i:s', time())//时间
                            );
                            $msg->sendWxTplMsg($to, $tplId, $wxData, $url);
                        }
                    }
                    //writeLog('客户申请执行成功：id：'.$orderId);
                    return 1;
                }else{
                    //writeLog('客户申请执行失败id：'.$orderId);
                    return false;
                }
            }else{
                return -1 ;
            }
        }else{
            return -2 ;
        }

    }
    //获取微信用户 member
    public function getMember($memberId,$field){
        $res = $this->table('base_members')->field($field)->where(array('id'=>$memberId))->getOne();
        //dump($res);
        return $res;
    }


}