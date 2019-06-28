<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/21
 * Time: 17:57
 */
class ContractManagerModel extends Model
{
    private $tableName = 'contract_numbers';
    //合同数据显示
    public function getConList($a,$keywords,$type,$status,$p,$pageSize){
        $page  = ($p-1)* $pageSize;
        $where = 'a.departId = c.id and c.type=3';
        if($status){
            $status = ($status==6) ? 0 : $status;
            if($status==7){
                $where  .= ' and a.status <> 0';
            }else{
                $where  .= ' and a.status ='.$status;
            }
        }else{
            if($a==1){
                $where .= ' ';
            }else{
                $where .= 'and ( a.status = 1 or a.status =2 )';
            }
        }
        if($type){
            $where .= ' and a.type='.$type;
        }
        if($keywords){
            $where .= ' and ( a.coder like "%'.$keywords.'%" or c.name like "%'.$keywords.'%" )';
        }

        $count  = $this->table('contract_numbers as a')->jion('left join core_user as b on a.create_user = b.id,core_depart as c')
                        ->where($where)->count();
        $res    = $this->table('contract_numbers as a')
                       ->field('a.*,b.name as username,c.name as departname')
                       ->jion('left join core_user as b on a.create_user = b.id,core_depart as c')
                       ->where($where)->order(' create_time desc')->limit($page,$pageSize)->get();
        $return = array('list'=>$res,'count'=>$count,'page'=>$p,'pageSize'=>$pageSize,'sql'=>$this->lastSql());
        return $return;
    }

    //获取门市数据
    public function getDepart(){
        $res = $this->table('core_depart')->where('type=3')->get();
        return $res;
    }

    //获取一条合同信息
    public function getOneCon($id){
        $res = $this->table($this->tableName)->where('id='.$id)->getOne();
        return $res;
    }

    //通过订单id获取一条合同
    public function getOneByOrderId($id){
        $res = $this->table($this->tableName)->where('orderId='.$id)->getOne();
        return $res;
    }

    //获取订单详情
    public function getOrder($orderId){
        $res = $this->table('order_line')->where('id='.$orderId)->getOne();
        return $res;
    }
    //获取订单内的游客信息
    public function getOrderVisitor($orderId){
        $res = $this->table('order_line_visitor')->where('isCancel=2 and orderId='.$orderId)->get();
        return $res;
    }
    //获取订单支付信息
    public function getOrderPay($orderId){
        $res = $this->table('order_line_pay')->where('orderId='.$orderId.' and type=1 and payType<>0')->order('create_time asc')->get();
        $pay = array('未付','现金支付','支付宝','微支付','银联在线','pos刷卡');
        $arr = array('type'=>'','time'=>'');
        $and = '';
        foreach ($res  as $k=>$re){
            if(@$res[$k-1]['payType']!=$re['payType']){
                $arr['type'] .= $and.$pay[$re['payType']];
            }
            $and = '+';
        }
        $arr['time']=end($res)['create_time'];
        return $arr;
    }

    //添加合同                            合同编码前缀 起始--结束                 绑定部门
    public function addCon($type,$proType,$coder,$coderS,$coderE,$create_user,$departId){
        //拼装合同编码
        $coderArr = array();
        $str = '';
        for($i=$coderS;$i <= $coderE;++$i){
            $coderArr[] = $coder.sprintf("%05d", $i);
            $str .= '"'.$coder.sprintf("%05d", $i).'",';
        }
        $where = 'coder in ('.$str.'"000")';
        $res = $this->table($this->tableName)->where($where)->get();
        //dump($this->lastSql());
        //dump($res);exit;
        if($res){
            return array('statu'=>2,'msg'=>'合同已存在，请重新创建');
        }
        foreach ($coderArr as $c){
            $this->table($this->tableName)->insert(array('type'=>$type,'proType'=>$proType,'coder'=>$c,'status'=>0,'create_user'=>$create_user,'create_time'=>date('Y-m-d H:i:s',time()),'departId'=>$departId));
        }
        return array('statu'=>1,'msg'=>'合同创建完成');
    }

    /**
     * 合同审核
     * @param int $id        合同id
     * @param int $auth_user 审核人id
     * @return mixed
     */
    public function upAudit($id,$auth_user=''){
        if(ctype_digit($id)) {
            if ($auth_user) {
                $res = $this->table($this->tableName)->where('id=' . $id)->update(array('status' => 1, 'auth_user' => $auth_user, 'auth_time' => date('Y-m-d H:i:s', time())));
            } else {
                $res = $this->table($this->tableName)->where('id=' . $id)->update(array('status' => 1, 'auth_time' => date('Y-m-d H:i:s', time())));
            }
            return $res;
        }
        return false;
    }

    /**
     * 合同使用 绑定订单
     * @param int $id         合同id
     * @param int $orderId    订单id
     * @param int $use_user   合同使用人id （可选）
     * @return mixed
     */
    public function useContract($id,$orderId,$use_user=''){
        if(ctype_digit($id)) {
            $res = $this->table($this->tableName)->where('id=' . $id . ' and status=2 ')->update(array('status' => 2, 'orderId' => $orderId, 'use_user' => $use_user, 'use_time' => date('Y-m-d H:i:s', time())));
            return $res;
        }
        return false;
    }

    //合同废弃
    /*public function dieContract($id){
        $res = $this->table($this->tableName)->where('id='.$id)->update(array('status'=>3));
        return $res;
    }*/

    //通过订单id获取行程数据
    public function getTour($id){
        if(ctype_digit($id)) {

            $res = $this->table('pro_line_journey as a')
                ->field('a.*,b.title,b.startDay,b.proId as proLineId')
                ->jion('left join order_line as b on a.lineId=b.proId')
                ->order('`day` asc')
                ->where('b.id=' . $id)->get();
            $r = array();
            if ($res) {
                foreach ($res as &$re) {
                    $re['active'] = $this->table('pro_line_journey_active')->where('journeyId=' . $re['id'])->order('`time` asc')->get();
                }
                $r = $this->table('pro_line')->where('id='.$res[0]['proLineId'])->getOne();
            }
            $arr = array('res'=>$res,'pro_line'=>$r);
            return $arr;
        }
        return false;
    }

    /**
     * 将html生成pdf
     * @param string $url           url地址
     * @param string $fileName      文件名及路径
     * @param int    $type          1 页面显示  2 下载  3保存在服务器
     */
    public function htmlToPdf($url,$fileName='download',$type=1){
        require_once './lib/MPDF57/mpdf.php';
        //实例化mpdf
        $mPdf=new mPDF('utf-8','A4','','',16,16,20,10);
        //设置字体,解决中文乱码
        $mPdf->useAdobeCJK = true;
        $mPdf->SetAutoFont(AUTOFONT_ALL);
        //获取要生成的静态文件
        $html=file_get_contents($url);
        //设置pdf显示方式
        $mPdf->SetDisplayMode('fullpage');
        //设置pdf的尺寸为270mm*397mm
        //$mpdf->WriteHTML('<pagebreak sheet-size="270mm 397mm" />');
        //创建pdf文件
        $mPdf->WriteHTML($html);
        //加密     SetProtection($permissions=array(),$user_pass='',$owner_pass=null, $length=40)
        //array('print','modify','copy','annot-forms','fill-forms','extract','assemble',,'print-highres');
        $mPdf->SetProtection(array('print'),'','nimbdwc'.time());
        if($type==1){
            $mPdf->Output();//输出pdf
        }elseif ($type==2){
            $mPdf->Output($fileName.'.pdf','D');//下载pdf
        }elseif ($type==3){
            $mPdf->Output($fileName.'.pdf','F');//保存在服务器
            return $fileName.'.pdf';
        }
    }
}