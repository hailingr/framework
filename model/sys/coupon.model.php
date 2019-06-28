<?php

class SysCouponModel extends Model
{
    //所有抵用卷 活动
    public function getAllCouponType($couponReceiveTime,$couponUseTime,$couponCreateTime,$couponKeywords,$p,$pageSize){
        $this->detectionExpireCoupon();//检出过期 未使用的抵用卷
        $page  = ($p-1)* $pageSize;
        $where = 'a.pon_type_id>0';
        if($couponReceiveTime){//领取时间
            $where .= ' and a.pon_type_receiveTimeStart <="'.$couponReceiveTime.'" and a.pon_type_receiveTimeEnd >="'.$couponReceiveTime.'"';
        }
        if($couponUseTime){//使用时间
            $where .= ' and a.pon_type_useTimeStart <="'.$couponUseTime.'" and a.pon_type_useTimeEnd >="'.$couponUseTime.'"';
        }
        if($couponCreateTime){//创建时间
            $where .= ' and a.pon_type_createTime like"'.$couponCreateTime.'%"';
        }
        if($couponKeywords){//活动名称,抵用卷创建人
            $where .= ' and (a.pon_type_createUser like "%'.$couponKeywords.'%" or a.pon_type_name like "%'.$couponKeywords.'%")';
        }
        $count = $this->table('base_coupon_type a')->where($where)->count();
        $res = $this->table('base_coupon_type a')
            ->where($where)
            ->limit($page,$pageSize)
            ->order('a.pon_type_createTime desc,a.pon_type_id desc')
            ->get();
        $return = array('list'=>$res,'count'=>$count,'page'=>$p,'pageSize'=>$pageSize,'sql'=>$this->lastSql());
        //dump($return);
        return $return;
    }
    //所有能领取抵用卷的 活动 //不显示无抵用卷活动 微信
    public function getCouponTypeId($desTypeId,$proTypeId,$desCityId,$depId='',$isHide=2){
        $time      = $time = date('Y-m-d H:i:s',time());
        //$memberId  = cookie('memberId');
        //$memberDep = $this->table('base_members')->field('departId')->where(array('id'=>$memberId))->getOne();
        //$memberDep = $memberDep['departId'] ;
        $where     = 'a.pon_type_isHide='.$isHide.' and a.pon_type_receiveTimeStart<="'.$time .'" and a.pon_type_receiveTimeEnd>"'.$time.'"';
        $where    .= ' and a.pon_type_totalNum>pon_type_receiveNum and a.pon_type_status=2 ';
        //$where    .= ' and (b.coupon_bindDepart=",0," or b.coupon_bindDepart like "%,'.$memberDep .',%")';

        if($desTypeId){
            $where   .= ' and (b.coupon_destType=1 or b.coupon_destType='.$desTypeId .')';
        }else{
            $where   .= ' and b.coupon_destType=1';
        }
        if($proTypeId){
            $where   .= ' and (b.coupon_proType=1 or b.coupon_proType='.$proTypeId .')';
        }else{
            $where   .= ' and b.coupon_proType=1';
        }
        if($depId){
            $where   .= ' and (b.coupon_bindDepart=",0," or b.coupon_bindDepart like "%,'.$depId .',%")';
        }else{
            $where   .= ' and b.coupon_bindDepart=",0,"';
        }
        if($desCityId){
            $desCityId  = trim($desCityId,',');
            $desCityIds = explode(',',$desCityId);
            $where     .= ' and (b.coupon_desCity=",0,"';
            foreach($desCityIds as $v){
                $where   .= ' or b.coupon_desCity like "%,'.$v .',%"' ;
            }
            $where   .= ')';
        }else{
            $where   .= ' and b.coupon_desCity=",0,"';
        }
        //echo $where ;
        $res = $this->table('base_coupon_type a')
            ->where($where)
            ->jion('RIGHT JOIN base_coupon b ON a.pon_type_id=b.coupon_typeId and b.coupon_status=1')
            ->field('a.pon_type_id,a.pon_type_name,a.pon_type_receiveTimeEnd,a.pon_type_totalNum-a.pon_type_receiveNum as ponNum')
            ->order('a.pon_type_createTime asc,a.pon_type_id desc')
            ->group('a.pon_type_id')
            ->get();
        return $res;
    }
    //某个取抵用卷的 子分类
    public function getCouponTypeChild($ponTypeId,$desTypeId,$proTypeId,$desCityId,$depId = ''){
        $time  = $time = date('Y-m-d H:i:s',time());
        $memberId  = cookie('memberId');
        $memberDep = $this->table('base_members')->field('departId')->where(array('id'=>$memberId))->getOne();
        $memberDep = $memberDep['departId'] ;
        $where     = 'coupon_typeId='.$ponTypeId.' and coupon_status=1 and coupon_receiveTimeStart<="'.$time .'" and coupon_receiveTimeEnd>"'.$time.'"';
        $where    .= ' and (coupon_bindDepart=",0," or coupon_bindDepart like "%,'.$memberDep .',%")';
        if($desTypeId){
            $where   .= ' and (coupon_destType=1 or coupon_destType='.$desTypeId .')';
        }else{
            $where   .= ' and coupon_destType=1';
        }
        if($proTypeId){
            $where   .= ' and (coupon_proType=1 or coupon_proType='.$proTypeId .')';
        }else{
            $where   .= ' and coupon_proType=1';
        }
        if($depId){
            $where   .= ' and (coupon_bindDepart=",0," or coupon_bindDepart like "%,'.$depId .',%")';
        }else{
            $where   .= ' and coupon_bindDepart=",0,"';
        }
        if($desCityId){
            $desCityId  = trim($desCityId,',');
            $desCityIds = explode(',',$desCityId);
            $where     .= ' and (coupon_desCity=",0,"';
            foreach($desCityIds as $v){
                $where   .= ' or coupon_desCity like "%,'.$v .',%"' ;
            }
            $where   .= ')';
        }else{
            $where   .= ' and coupon_desCity=",0,"';
        }
        $res = $this->table('base_coupon')
            ->where($where)
            ->field('coupon_userType,coupon_destType,coupon_proType,coupon_money,coupon_useMinMoney,coupon_unique,coupon_receiveTimeStart,coupon_receiveTimeEnd,coupon_desCity')
            ->group('coupon_money,coupon_proType,coupon_destType,coupon_desCity')
            ->order('coupon_money asc')
            ->get();
        //echo $this->lastSql();
        return $res;
    }
    //获取一类抵用卷的所有卷
    public function getCoupons($couponTypeId,$ponDesType,$ponUserType,$ponProType,$status,$keywords,$p,$pageSize){
        $where = 'a.coupon_typeId='.$couponTypeId;
        if($ponDesType){
            $where .= ' and a.coupon_destType='.$ponDesType;
        }
        if($ponUserType){
            $where .= ' and a.coupon_userType='.$ponUserType;
        }
        if($ponProType){
            $where .= ' and a.coupon_proType='.$ponProType;
        }
        if($status){
            if($status == 2){
                $where .= ' and (a.coupon_status=3 or a.coupon_status='.$status.')';
            }else{
                $where .= ' and a.coupon_status='.$status;
            }
        }
        if($keywords){
            $where .= ' and (a.coupon_receiveCoder like "%'.$keywords.'%" or b.nickName like "%'.$keywords.'%" or c.realName like "%'.$keywords.'%" or c.name like "%'.$keywords.'%")';
        }
        $count = $this->table('base_coupon a')->where($where)
            ->jion('left join base_members b on a.coupon_memberId=b.id left join core_user c on a.coupon_userId=c.id')
            ->count();
        if($p && $pageSize){
            $page   = ($p-1)* $pageSize;
            $join   = ' left join base_members b on a.coupon_memberId=b.id ';
            $join  .= ' left join core_user c on a.coupon_userId=c.id';
            $res = $this->table('base_coupon a')->where($where)
                ->jion($join)
                ->field('a.*,b.nickName,c.realName,c.name')
                ->limit($page,$pageSize)
                ->order('a.coupon_id')
                ->get();
        }else{
            $res = $this->table('base_coupon a')->where($where)
                ->jion('left join base_members b on a.coupon_memberId=b.id left join core_user c on a.coupon_userId=c.id')
                ->field('a.*,b.nickName,c.realName,c.name')
                ->order('a.coupon_id')
                ->get();
        }
        if($res){
            foreach($res as $k=>$v){
                if($v['coupon_bindDepart']){
                    $dep = $this->getPonDepart($v['coupon_bindDepart']);
                    $res[$k]['bindDepart'] = $dep;

                }
                if($v['coupon_desCity']){
                    $des = $this->getPonDes($v['coupon_desCity']);
                    $res[$k]['bindDesCity'] = $des;
                }
            }
        }
        $return = array('list'=>$res,'count'=>$count,'page'=>$p,'pageSize'=>$pageSize,'sql'=>$this->lastSql());
        //dump($return);
        return $return;
    }
    //获取一张抵用卷
    public function getOneCoupon($coupon_coderId){
        $res = $this->table('base_coupon')->where(array('coupon_id'=>$coupon_coderId))->getOne();
        return $res;
    }
    //通过领取码查询抵用卷
    public function getOneCouponByReceiveCoder($receiveCoder){
        $res = $this->table('base_coupon a')->jion('left join base_coupon_type b on a.coupon_typeId=b.pon_type_id')
            ->field('a.*,b.pon_type_isHide')
            ->where(array('a.coupon_receiveCoder'=>$receiveCoder))->getOne();
        return $res;
    }
    //获取最后一条抵用卷编号
    public function getLastCoupon($typeId){
        if($typeId){
            $where = ' coupon_id=(select max(coupon_id) from base_coupon where coupon_typeId='.$typeId.')';
            $res = $this->table('base_coupon')
                ->field('coupon_id,coupon_typeId,coupon_coder,coupon_receiveCoder,coupon_receiveTimeStart,coupon_receiveTimeEnd,coupon_useTimeStart,coupon_useTimeEnd,coupon_expireDay')
                ->where($where)->getOne();
        }else{
            $res = false;
        }
        return $res;
    }
    //创建一个抵用卷分类
    public function createCouponType($userInfo,$data){
        $return = array('status'=>0,'msg'=>'增加卷分类');
        if($userInfo && $data){
            $date = date('Y-m-d H:i:s',time());
            if(!$data['pon_receiveTimeStart']){
                $data['pon_receiveTimeStart'] = $date ;//开始领取
            }
            if(!$data['pon_receiveTimeEnd']){
                $data['pon_receiveTimeEnd'] = date('Y-m-d H:i:s',strtotime($data['pon_receiveTimeStart'])+3600*24*365) ;//结束领取//未填写则默认一年
            }
            if(!$data['pon_useTimeStart']){
                $data['pon_useTimeStart'] = $date ;//开始使用
            }
            if(!$data['pon_useTimeEnd']){
                $data['pon_useTimeEnd'] = date('Y-m-d H:i:s',strtotime($data['pon_useTimeStart'])+3600*24*365) ;//结束使用（过期）
            }
            if($data['editPon']){//编辑
                $up = array(
                    'pon_type_name'=>$data['pon_name'],
                    'pon_type_isHide'=>$data['pon_isHide'],
                    'pon_type_receiveTimeStart'=>$data['pon_receiveTimeStart'],
                    'pon_type_receiveTimeEnd'=>$data['pon_receiveTimeEnd'],
                    'pon_type_useTimeStart'=>$data['pon_useTimeStart'],
                    'pon_type_useTimeEnd'=>$data['pon_useTimeEnd'],
                    'pon_type_expireDay'=>$data['pon_expireDay'],
                    'pon_type_isActivity'=>2,
                );
                $id = $this->table('base_coupon_type')->where(array('pon_type_id'=>$data['pon_id']))->update($up);// 修改活动信息
                if($id){
                    $upPon = array(
                        'coupon_receiveTimeStart'=>$data['pon_receiveTimeStart'],
                        'coupon_receiveTimeEnd'=>$data['pon_receiveTimeEnd'],
                        'coupon_useTimeStart'=>$data['pon_useTimeStart'],
                        'coupon_useTimeEnd'=>$data['pon_useTimeEnd'],
                        'coupon_expireDay'=>$data['pon_expireDay'],
                    );
                    $this->table('base_coupon')->where(array('coupon_typeId'=>$data['pon_id'],'coupon_status'=>1))->update($upPon);//修改未领取抵用卷信息
                    $return['status'] = 1 ;
                    //$return['pon_type_id']  = $id ;
                }
            }else{//新增
                $coupon_createUser = $userInfo['realName'] ? $userInfo['realName'] : $userInfo['nickName'];

                $time = date('YmdHis',time());
                $unique = md5($time);
                $in = array(
                    'pon_type_name'=>$data['pon_name'],
                    'pon_type_isHide'=>$data['pon_isHide'],
                    'pon_type_receiveTimeStart'=>$data['pon_receiveTimeStart'],
                    'pon_type_receiveTimeEnd'=>$data['pon_receiveTimeEnd'],
                    'pon_type_useTimeStart'=>$data['pon_useTimeStart'],
                    'pon_type_useTimeEnd'=>$data['pon_useTimeEnd'],
                    'pon_type_createUserId'=>$userInfo['id'],
                    'pon_type_createUser'=>$coupon_createUser,
                    'pon_type_createTime'=>$date,
                    'pon_type_expireDay'=>$data['pon_expireDay'],
                    'pon_type_unique'=>$unique,
                );
                $id = $this->table('base_coupon_type')->insert($in);
                if($id){
                    $return['status'] = 1 ;
                }
            }
        }
        return $return ;
    }

    //获取一个抵用卷分类
    public function getOnePonType($typeId){
        $res = $this->table('base_coupon_type')->where(array('pon_type_id'=>$typeId))->getOne();
        if($res){
            $res['status'] = 1 ;
        }
        return $res;
    }
    //新增抵用卷 顺序领取码
    public function createCoupon($typeId,$data){
        $return = array('status'=>0,'msg'=>'新增抵用卷');
        $type = $this->getOnePonType($typeId);//查询一个抵用卷分类
        //$last = $this->getLastCoupon($typeId);//获取最后一条抵用卷编号
        $coupon_desType = array('','','省内','国内','出境','自由','游轮');//目的地
        $coupon_proType = array('','','线路','门票','车票');//产品
        $coupon_type = array('','','门市','直客');//游客
        $coupon_coder_des_prefix  = $coupon_desType[$data['pon_destType']];
        $coupon_coder_pro_prefix  = $coupon_proType[$data['pon_proType']];
        $coupon_coder_type_prefix = $coupon_type[$data['pon_userType']];
        $zeroArr = array('','0','00','000','0000','00000','000000','0000000','00000000');
        $num = (int)abs((int)$data['pon_num_end'] - (int)$data['pon_num_start']) + 1;
        $pon_depart = '';
        if(is_array($data['pon_depart'])){ //门市
            $pon_depart .= ',';
            foreach($data['pon_depart'] as $v){
                $pon_depart .= $v .',' ;
            }
        }else{
            $pon_depart .= ',0,';
        }
        $pon_desCity = '';
        if(is_array($data['pon_destCity'])){
            $pon_desCity  .= ',';
            foreach($data['pon_destCity'] as $v){
                $pon_desCity .= $v .',' ;
            }
        }else{
            $pon_desCity .= ',0,';
        }
        $coupon_rCoder_start = $data['pon_num_start'];
        $values = '';
        $ext    = '';
        $fields  = 'coupon_typeId,coupon_coder,coupon_receiveCoder,coupon_userType,coupon_destType,';
        $fields .= 'coupon_proType,coupon_bindDepart,coupon_desCity,coupon_money,coupon_useMinMoney,';
        $fields .= 'coupon_receiveTimeStart,coupon_receiveTimeEnd,coupon_useTimeStart,coupon_useTimeEnd,';
        $fields .= 'coupon_expireDay,coupon_unique';
        $chStr = '';
        for($i = 0; $i < $num; $i++){
            $item   = $coupon_coder_des_prefix . $coupon_coder_pro_prefix . $coupon_coder_type_prefix;
            $rItem  = (int)$coupon_rCoder_start ++ ;
            $coupon_rCoder  = $data['pon_num_prefix'].$rItem;
            if(strlen($rItem) < strlen($data['pon_num_start'])){
                $coupon_rCoder  = $data['pon_num_prefix'].$zeroArr[strlen($data['pon_num_start'])-strlen((int)$rItem)].$rItem;
            }
            $chStr .= $ext . '"'.$coupon_rCoder .'"' ;
            $values .= $ext.'("'.$typeId.'","'.$item.'","'.$coupon_rCoder.'","'.$data['pon_userType'].'","'.$data['pon_destType'].'",';
            $values .= '"'.$data['pon_proType'].'","'.$pon_depart.'","'.$pon_desCity.'","'.$data['pon_money'].'","'.$data['pon_useMinMoney'].'",';
            $values .= '"'.$type['pon_type_receiveTimeStart'].'","'.$type['pon_type_receiveTimeEnd'].'","'.$type['pon_type_useTimeStart'].'","'.$type['pon_type_useTimeEnd'].'",';
            $values .= '"'.$type['pon_type_expireDay'].'","'.$type['pon_type_unique'].'")';
            $ext     = ',';
        }
        $ch = $this->getOne('select coupon_receiveCoder from base_coupon WHERE coupon_receiveCoder in ('.$chStr.')');
        if($ch){
            $return['msg'] = $ch['coupon_receiveCoder'];
        }else{
            $sql = 'insert into base_coupon ('.$fields.') VALUES '.$values;
            $res = $this->query($sql);
            if($res){
                $sql = "update base_coupon_type set pon_type_totalNum=pon_type_totalNum+".$num." where pon_type_id=" . $typeId ;
                $this->query($sql);
                $return['status'] = 1;
            }
        }
        return $return ;
    }
    /*无序领取码*/
    /*public function createCoupon($typeId,$data){
        $type = $this->getOnePonType($typeId);//查询一个抵用卷分类
        $last = $this->getLastCoupon($typeId);//获取最后一条抵用卷编号
        //writeLog($last);
        $coupon_desType = array('AL','','SN','GN','CJ','ZY','YL','99'=>'MA');//目的地
        $coupon_proType = array('AL','XL','MP','CP','99'=>'CA');//产品
        $coupon_type = array('AL','MS','ZK','99'=>'YA');//游客
        $chars='GHIJKLMNOPQRSTUVWXYZghijklmnopqrstuvwxyz';
        $coupon_coder_des_prefix  = $coupon_desType[$data['pon_destType']];
        $coupon_coder_pro_prefix  = $coupon_proType[$data['pon_proType']];
        $coupon_coder_type_prefix = $coupon_type[$data['pon_userType']];
        $zeroArr = array('','0','00','000','0000','00000','000000','0000000','00000000');
        if($last){
            $coupon_coder_last = (int)substr($last['coupon_coder'],-8);
            $coupon_id_last = $last['coupon_id'];
            $coupon_coder_start = $coupon_coder_last + 1;
            $coupon_id_start = $coupon_id_last ;
        }else{
            $coupon_coder_start = 1 ;
            $coupon_id_start = 0 ;
        }
        $values = '';
        $ext    = '';
        $fields  = 'coupon_typeId,coupon_coder,coupon_receiveCoder,coupon_userType,coupon_destType,';
        $fields .= 'coupon_proType,coupon_money,coupon_useMinMoney,';
        $fields .= 'coupon_receiveTimeStart,coupon_receiveTimeEnd,coupon_useTimeStart,coupon_useTimeEnd,';
        $fields .= 'coupon_expireDay,coupon_unique';
        for($i = 0; $i < $data['pon_num']; $i++){
            $item = $coupon_coder_des_prefix . $coupon_coder_pro_prefix . $coupon_coder_type_prefix;
            if(strlen($coupon_coder_start) < 8){
                $len     = 8 - strlen($coupon_coder_start);
                $strItem = (int)$coupon_coder_start++;
                $strItem = (string)($zeroArr[$len].$strItem);
                $item   .= $strItem;
            }else{
                $item .= (int)$coupon_coder_start ++;
            }
            $coupon_id_start ++ ;
            $coupon_id_start16 = dechex($coupon_id_start);//16进制
            $coupon_id_len   = strlen($coupon_id_start16);
            $rand1 = '';
            for($j=0;$j<(7-$coupon_id_len);$j++){
                $rand1 .=  substr($chars,rand(0,38),1);
            }
            $rand2 = substr($chars,rand(0,38),1);
            $receiveCoder = (string)($rand1.$coupon_id_start16.$rand2);
            $values .= $ext.'("'.$typeId.'","'.$item.'","'.$receiveCoder.'","'.$data['pon_userType'].'","'.$data['pon_destType'].'",';
            $values .= '"'.$data['pon_proType'].'","'.$data['pon_money'].'","'.$data['pon_useMinMoney'].'",';
            $values .= '"'.$type['pon_type_receiveTimeStart'].'","'.$type['pon_type_receiveTimeEnd'].'","'.$type['pon_type_useTimeStart'].'","'.$type['pon_type_useTimeEnd'].'",';
            $values .= '"'.$type['pon_type_expireDay'].'","'.$type['pon_type_unique'].'")';
            $ext    = ',';
        }
        $sql = 'insert into base_coupon ('.$fields.') VALUES '.$values;
        $res = $this->query($sql);
        if($res){
            $sql = "update base_coupon_type set pon_type_totalNum=pon_type_totalNum+".$data['pon_num']." where pon_type_id=" . $typeId ;
            $this->query($sql);
        }
        return $res ;
    }*/
    //我的抵用卷 微信端
    public function getMyCoupons($memberId,$field,$p=0,$pageSize=0){
        $this->detectionExpireCoupon();//检出过期 未使用的抵用卷
        if($p && $pageSize){
            $return = array();
            $page  = ($p-1)* $pageSize;
            $count = $this->table('base_coupon')->where(array('coupon_memberId'=>$memberId))->count();
            $res = $this->table('base_coupon')
                ->field($field)
                ->where(array('coupon_memberId'=>$memberId))
                ->limit($page,$pageSize)
                ->get();
            if($count && $res){
                $return = array('list'=>$res,'count'=>$count,'page'=>$p,'pageSize'=>$pageSize);
            }
            return $return;
        }else{
            $res = $this->table('base_coupon')
                ->field($field)
                ->where(array('coupon_memberId'=>$memberId))
                ->get();
            $return = array('list'=>$res);
            return $return;
        }

    }
    //绑定抵用卷
    public function bindCoupon($coupon,$memberId){
        $up = array(
            'coupon_status'=>2,
            'coupon_memberId'=>$memberId,
            'coupon_receiveTime'=>date('Y-m-d H:i:s',time()),
        );
        if($coupon['coupon_expireDay'] > 0){
            $up['coupon_useTimeEnd'] = date('Y-m-d H:i:s',(time()+$coupon['coupon_expireDay']*24*3600));
        }
        $res = $this->table('base_coupon')->where(array('coupon_id'=>$coupon['coupon_id']))->update($up);
        if($res){
            $sql = "update base_coupon_type set pon_type_receiveNum=pon_type_receiveNum+1 where pon_type_id=" . $coupon['coupon_typeId'] ;
            $rst = $this->query($sql);
        }else{
            $rst = false ;
        }
        return $rst;
    }
    //使用抵用卷
    public function useCoupon($typeId,$memberId,$coupon_id){
        $user = $this->table('base_members')->where(array('id'=>$memberId))->getOne();
        $up = array(
            'coupon_status'=>3,
            'coupon_storeId'=>$user['departId'],
            'coupon_useTime'=>date('Y-m-d H:i:s',time()),
        );
        $res = $this->table('base_coupon')->where(array('coupon_id'=>$coupon_id,'coupon_memberId'=>$memberId))->update($up);
        if($res){
            $sql = "update base_coupon_type set pon_type_usedNum=pon_type_usedNum+1 where pon_type_id=" . $typeId ;
            $rst = $this->query($sql);
        }else{
            $rst = false ;
        }
        return $rst;
    }
    //随机获取一条抵用卷
    public function getRandomCoupon($mMyCoupons,$couponTypeId,$couponProType,$couponDesType,$coupon_money,$couponDesCity=''){
        //$unique = $this->table('base_coupon_type')->where(array('pon_type_id'=>$couponTypeId))->getOne();
        //$unique = $unique['pon_type_unique'];
        $this->detectionExpireCoupon();//检出过期 未使用的抵用卷
        $time = $time = date('Y-m-d H:i:s',time());
        //状态 1 未领取 2 已领取  3 未使用 4 已使用 5 过期 6 人工作废
        $where  = 'a.coupon_userId=0 and a.coupon_memberId=0 and a.coupon_status=1 and (a.coupon_userType=1 or a.coupon_userType=3)';
        $where .= ' and a.coupon_typeId='.$couponTypeId.' and a.coupon_receiveTimeStart<="'.$time .'" and a.coupon_receiveTimeEnd>"'.$time.'"';

        /*if($mMyCoupons){//
            dump($mMyCoupons);
            $desTypeItem = array();
            $desCityItem = array();
            foreach($mMyCoupons as $v){
                if($v['coupon_unique'] == $unique){
                    $desTypeItem[] = $v['coupon_destType'];
                    $desCityItem[] = $v['coupon_desCity'];
                    dump($v);
                }
            }
            dump($desTypeItem);
            dump($desCityItem);
            if(!empty($desTypeItem)){
                $desItem = array_unique($desTypeItem);
                $typeItem = '';
                $ext      = '';
                foreach($desItem as $desV){
                    $typeItem .= $ext .'"'.$desV .'"';
                    $ext       = ',';
                }
                $desCityItem = array_unique($desCityItem);
                $cityItem = '';
                $ext      = '';
                foreach($desCityItem as $cityV){
                    $cityItem .= $ext .'"'.$cityV .'"';
                    $ext       = ',';
                }
                //$desItem = implode(',',$desItem);
                $where  .= ' and (coupon_destType not in ('.$typeItem.') and coupon_desCity not in ('.$cityItem .'))';
            }
        }*/
        if($couponDesCity){
            $where .= ' and (a.coupon_desCity=",0," or a.coupon_desCity="'.$couponDesCity.'")';
        }else{
            $where .= 'a.coupon_desCity=",0,"';
        }
        /*if($couponUserType){//游客类型
            $where .= ' and (coupon_userType=1 or coupon_userType='.$couponUserType.')';
        }else{
            $where .= ' and coupon_userType=1';
        }*/
        if($couponProType){//产品类型
            $where .= ' and (a.coupon_proType=1 or a.coupon_proType='.$couponProType.')';
        }else{
            $where .= ' and a.coupon_proType=1';
        }
        if($couponDesType){//目的地类型
            $where .= ' and (a.coupon_destType=1 or a.coupon_destType='.$couponDesType.')';
        }else{
            $where .= ' and a.coupon_destType=1';
        }
        if($coupon_money){//金额
            $where .= ' and a.coupon_money='.$coupon_money;
        }

        $sql = 'select a.*,b.pon_type_isHide from base_coupon a left join base_coupon_type b on a.coupon_typeId=b.pon_type_id WHERE '.$where.' order by rand()';//随机获取1条
        //$res = $this->table('base_coupon')->where($where)->getOne();
        $res = $this->getOne($sql);
        //echo $this->lastSql();die;
        return $res;
    }
    //检出过期 未使用的抵用卷
    public function detectionExpireCoupon(){
        $time = $time = date('Y-m-d H:i:s',time());
        $where = 'coupon_status in(1,2) and coupon_useTimeEnd<="'.$time.'"';
        $this->table('base_coupon')->where($where)->update(array('coupon_status'=>4));
        $where = 'pon_type_status in(1,2) and pon_type_useTimeEnd<="'.$time.'"';
        $this->table('base_coupon_type')->where($where)->update(array('pon_type_status'=>4));

    }
    //获取目的地
    public function getDesCity($desId,$field){
        $where = array();
        if($desId){
            if($desId == 1){
                $res = $this->table('base_dest')->field($field)->get();
            }else{
                $where['typeId'] = $desId ;
                $res = $this->table('base_dest')->field($field)->where($where)->get();
            }

        }else{
            $res = $this->table('base_dest')->field($field)->get();
        }

        return $res ;
    }
    //获取所有门市
    public function getStores($type,$field){
        $where = array('type'=>$type);
        $res = $this->table('core_depart')->field($field)->where($where)->get();
        //dump($return);
        return $res;
    }
    //获取抵用卷绑定门市
    public function getPonDepart($ids){
        $ids = trim($ids,',');
        $item = '';
        if($ids){
            $dep   = $this->table('core_depart')
                ->field('name')
                ->where('id in('.$ids.')')
                ->get();
            $ext = '';
            foreach($dep as $k =>$v){
                $item .= $ext.$v['name'];
                $ext   = '，';
            }
        }
        return $item;
    }
    //获取可使用抵用卷的目的地
    public function getPonDes($ids){
        $ids = trim($ids,',');
        $item = '';
        if($ids){
            $dep   = $this->table('base_dest')
                ->field('name')
                ->where('id in('.$ids.')')
                ->get();
            $ext = '';
            foreach($dep as $k =>$v){
                $item .= $ext.$v['name'];
                $ext   = '，';
            }
        }
        return $item;
    }
    //设置关注微信的赠送 活动
    public function setAct($typeId,$isAct){
        //$time  = $time = date('Y-m-d H:i:s',time());
        $pon = $this->table('base_coupon_type')->where(array('pon_type_id'=>$typeId))->getOne();
        if($isAct == 2){
            $res = $this->table('base_coupon_type')->where(array('pon_type_id'=>$typeId))->update(array('pon_type_isActivity'=>1));
            $this->table('base_coupon_type')->where('pon_type_id <>'.$typeId .' and pon_type_isHide='.$pon['pon_type_isHide'])->update(array('pon_type_isActivity'=>2));
        }else{
            $res = $this->table('base_coupon_type')->where(array('pon_type_id'=>$typeId))->update(array('pon_type_isActivity'=>2));
        }
        return $res;
    }
    //批量领取活动专用
    public function justInside($ponTypeId,$userType=3){
        $time  = $time = date('Y-m-d H:i:s',time());
        $memberId  = cookie('memberId');
        $memberDep = $this->table('base_members')->field('departId')->where(array('id'=>$memberId))->getOne();
        $memberDep = $memberDep['departId'] ;
        //$where     = 'pon_type_isHide=1 and coupon_status=1';
        $where     = ' coupon_status=1';
        $where    .= ' and (coupon_userType=1 or coupon_userType='.$userType.') and coupon_typeId='.$ponTypeId.' and coupon_receiveTimeStart<="'.$time .'" and coupon_receiveTimeEnd>"'.$time.'"';
        $where    .= ' and (coupon_bindDepart=",0," or coupon_bindDepart like "%,'.$memberDep .',%")';
        //$where    .= ' and pon_type_isHide='.$isHide;
        $res = $this->table('base_coupon')
            ->where($where)
            ->field('*')
            ->group('coupon_money,coupon_proType,coupon_destType,coupon_desCity')
            ->order('coupon_money asc')
            ->get();
        //echo $this->lastSql();
        return $res;
    }
}