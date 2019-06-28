<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28
 * Time: 9:16
 */
class ContractInvoiceModel extends Model
{
    private $tableName = 'voucher_numbers';

    /**
     * @param int        $s       1为审核数据    2开票数据
     * @param $keywords
     * @param $type
     * @param $timeStart
     * @param $timeEnd
     * @param $p
     * @param $pageSize
     * @return array
     */
    public function getInvList($s,$keywords,$status,$timeStart,$timeEnd,$p,$pageSize){
        $page  = ($p-1)* $pageSize;
        $where = 'a.departId=c.id';
        if($status==6){
            if($s==1){
                $where .= '';
            }else{
                $where .= ' and a.status <>0';
            }
        }elseif ($status==5){
            if($s==1){
                $where .= ' and ( a.status=1 or a.status=2 )';
            }
        }else{
            $where .= ' and a.status='.$status;
        }
        if($keywords){
            $where .= ' and ( a.header like "%'.$keywords.'%" or a.username like "%'.$keywords.'%" or a.userTel like "%'.$keywords.'%" or b.coder like "%'.$keywords.'%" or b.title like "%'.$keywords.'%" or c.name like "%'.$keywords.'%" )';
        }
        if($timeStart){
            $where .=' and a.create_time>="'.$timeStart.'"';
        }
        if($timeEnd){
            $where .=' and a.create_time<="'.$timeEnd.'"';
        }

        $count = $this->table('voucher_numbers as a')->jion('left join order_line as b on a.orderId=b.id,core_depart as c')->where($where)->count();
        $res   = $this->table('voucher_numbers as a')
                      ->field('a.*,b.coder as orderCoder,b.title,c.name')
                      ->jion('left join order_line as b on a.orderId=b.id,core_depart as c')
                      ->where($where)->order('create_time desc')
                      ->limit($page,$pageSize)->get();
        $return = array('list'=>$res,'count'=>$count,'page'=>$p,'pageSize'=>$pageSize,'sql'=>$this->lastSql());
        return $return;
    }

    /**
     * 发票审核处理
     * @param $id
     * @param $auth_user
     * @return bool
     */
    public function auditInv($id,$auth_user){
        if(ctype_digit($id)){
            $res = $this->table($this->tableName)->where('id='.$id)->update(array('status'=>1,'auth_user'=>$auth_user,'auth_time'=>date('Y-m-d H:i:s',time())));
            return $res;
        }
        return false;
    }

    /**
     * 开票处理
     * @param $id
     * @param $send_user
     * @return bool
     */
    public function billingInv($id,$send_user){
        if(ctype_digit($id)){
            $res = $this->table($this->tableName)->where('id='.$id)->update(array('status'=>2,'send_user'=>$send_user,'send_time'=>date('Y-m-d H:i:s',time())));
            return $res;
        }
        return false;
    }

    public function getInvOne($id){
        if(ctype_digit($id)){
            $res = $this->table('voucher_numbers as a')->field('a.*,b.name,c.name as sName')
                        ->jion('left join core_depart as b on a.departId=b.id,core_user as c')
                        ->where('a.create_user=c.id and a.id='.$id)->getOne();
            if($res){
                $res['order'] = $this->table('order_line')->where('id='.$res['orderId'])->getOne();
                $res['order'] = is_array($res['order'])?$res['order']:array();
                $res['pay'] = $this->table('order_line_pay')->where('orderId='.$res['orderId'])->get();
                $res['pay'] = is_array($res['pay'])?$res['pay']:array();
            }
            return $res;
        }
        return false;
    }
}