<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/30
 * Time: 15:28
 */

class SysUserModel extends Model{


    /**
     * 读取员工列表
     */
   public function getUserList($departId,$key,$page,$pageSize){
       //var_dump($departId);
       //初始化总条数
       $count  = 0;
       //起始条数
       $pages  = ($page-1)* $pageSize;
       //查询用户基本数据
       $doc     = array();
       $fields  = array('name','nickName','id','departName','departId','status','tel','email','realName');
       /*if($departId==0){//当departId为零时 查出除总管理员的所有用户
           $count  = $this->table('core_user')->where('id!=1')->count();
           $doc    = $this->table('core_user')->where('id!=1')->field($fields)->order('departId desc')->limit($pages,$pageSize)->get();
       }else{*/
           //读取该部门的的子部门

           $userMo = model('user.auth','mysql');
           $departs = $userMo->getChild($departId,1);

           $find    = array();
           //将子部门id组合成字符串
           foreach($departs as $item){
               $find[] = $item['id'];
           }
       //dump($find);
            $findIn  = implode(',',$find);
            $findKey = '"%'.$key.'%"';
            $findKeyStr = '`name` like '.$findKey;
           //查询数据
            $doc   = $this->table('core_user')->where('departId in('.$findIn.') and '.$findKeyStr)->field($fields)->order('id desc')->limit($pages,$pageSize)->get();
            $count = $this->table('core_user')->where('departId in('.$findIn.') and '.$findKeyStr)->count();
      /* }*/
        //查询用户岗位
       foreach($doc as $k=>$val){
           $jobs   = array();
           $userId = $val['id'];
           $sql    = "select b.* from core_user_job as a LEFT JOIN core_job as b ON a.jobId=b.id where a.userId='".$userId."' order by id ASC";
           $lists  = $this->get($sql);
           //dump($lists);
          if(!empty($lists)){
               foreach($lists as $list){
                  $jobs[] = $list['name'];
               }
           }
           $doc[$k]['job'] = implode(',',$jobs);//岗位表
       }
       $return = array('data'=>$doc,'row'=>$count);
       return $return;
   }

    /**
     * 获取待编辑用户信息 名字 部门id 账户状态 岗位
     * @param $userId int 用户id
     * @return array|mixed
     */
public function getUserInfo($userId){
    $userInfo = array();
    if($userId==0){
        $userInfo['name']     = '';
        $userInfo['departId'] = '';
        $userInfo['tel']     = '';
        $userInfo['email'] = '';
        $userInfo['status']   = '1'; //新建用户时默认启用
        $userInfo['jobs']     = array();
    }else{
        //获取用户基本信息
        $filed  = array('name','departId','status','tel','email');
        $result = $this->table('core_user')->field($filed)->where(array('id'=>$userId))->getOne();
        //获取用户岗位
        $job   = $this->table('core_user_job')->where(array('userId'=>$userId))->get();
        $jobs  = array();
        if($job){
            foreach($job as $item){
                $jobs[] = $item['jobId'];
            }
        }
        $userInfo         = $result;
        $userInfo['jobs'] = $jobs;
    }
    //dump($userInfo);
    return $userInfo;
}

    /**
     * @param $name string 账户名称查重
     * @return bool
     */
    public function checkName($name){
        $return = true;
        $result = $this->table('core_user')->where(array('name'=>$name))->getOne();
        if($result){
            $return = false;
        }
        return $return;
    }


    /**
     * 保存员工信息 编辑用户不修改用户帐号
     * @param $status int 账号状态
     * @param $name   string 登录名
     * @param $job      array 岗位
     * @param $departId  int  部门Id
     * @return int
     */
public function saveUser($status,$name,$job,$departId,$userId,$tel,$email){
    $return = true;//默认返回成功

    $depart = $this->table('core_depart')->where(array('id'=>$departId))->getOne();//查找该部门名称
    //dump($tel);
    $departName = $depart['name'];
    $pwd        = md5(md5('111111').'yd');
     //保存用户基本信息
    $data['departId']   = $departId;//部门id
    $data['departName'] = $departName;//部门名称
    $data['status']     = $status;//用户状态
    $data['tel']        = $tel;//用户手机号码
    $data['email']      = $email;//用户电子邮箱
    if($userId==0){
        if($name){
            $data['name']   = $name;//登录账号
            $data['pwd']    = $pwd;//默认密码MD5 ‘111111’
            //将用户基本信息插入数据表中
            $result = $this->table('core_user')->insert($data);
            $userId  = $result;
        }else{
            $return = false;
        }

    }else{//如果为编辑用户，更新用户数据，
        $result = $this->table('core_user')->where(array('id'=>$userId))->update($data);
        //dump($userId);
        $delJob = $this->table('core_user_job')->where(array('userId'=>$userId))->del();//将用户的原有岗位删除
    }
    //插入、更新员工岗位表
    if(!empty($job)){
        foreach($job as $val){
            $item = array('userId'=>$userId,'jobId'=>$val);
            //dump($item);
            $this->table('core_user_job')->insert($item);
        }
    }

    return $return;
    //dump($result);
}


    /**
     * 添加新部门
     * @param $parent int 父级部门id
     * @param $name   string 新部门名称
     */
    public function saveDepart($parent,$name){
        $parent = intval($parent);
        if($parent==0){
            $parent_str = $name;
        }else{
            $parentStr  = $this->table('core_depart')->where(array('id'=>$parent))->field('parent_str')->getOne();
            $parent_str = $parentStr['parent_str'].','.$name;
        }

        $data   = array('parent'=>$parent,'name'=>$name,'parent_str'=>$parent_str);
        $result = $this->table('core_depart')->insert($data);
        return  $result;
    }


    /**
     * 重置用户密码为 111111
     * @param $userId
     * @return int
     */
    public function reKey($userId){
        $pwd = md5(md5('111111').'yd');
        $data['pwd']    = $pwd;//默认密码MD5 ‘111111’
        $this->table('core_user')->where(array('id'=>$userId))->update($data);
        return 1;
    }




    /**
     * 查找部门下面的职位
     */
    public function getJob($departId){
        //获取部门及其子部门数据
        $authMo     = model('user.auth','mysql');
        $departs    = $authMo -> getChild($departId,1);

        $jobs       = array();
         //取得部门全部职位
        if(!empty($departs)){
           foreach($departs as $depart){
               $departId = $depart['id'];
               $lists     = $this->table('core_job')->where(array('departId'=>$departId))->order('id asc')->get();
               if(!empty($lists)){
                   foreach($lists as $job){
                       $jobs[] = $job;
                   }
               }
           }
        }

        if(!empty($jobs)){
            $data['title'] = '请选择职位';
            $data['job']   = $jobs;
        }else{
            $data['title'] = '请为该部门创建职位';
            $data['job']   = array();
        }
        //dump($jobs);
        return $data;
    }


    /**
     * 获取部门数据字符串化
     * @param int $parent_id
     * @param int $t
     * @return array|mixed|string
     */
    public function getCategory($parentId=0,$t=-1) {
        $t++;
        global $departTemp;
        //获取子类数据
        $result = $this->table('core_depart')->where(array('parent'=>$parentId,'type'=>1))->get();
        $data = array();
        foreach($result as $v){
            $data[] = $v;
        }
        //当数据不为空时
        if(!empty($data))  {
            foreach ($data as $key => $val )   {

                $val['name'] = str_repeat('&nbsp;',$t*6).'|--'.$val['name'];
                $departTemp[] = $val;
                $this->getCategory($val['id'],$t);
            }
        }
        return $departTemp;
    }



    //获取当前用户部门数据
    public function getDepartCategory($id){
        if($id!=0){
            $result = $this->table('core_depart')->where(array('id'=>$id))->getOne();
        }else{
            $result = array('id'=>0,'name'=>'所属公司/部门');
        }
        return $result;
    }



    /**
     * 将数据格式化成树形数组结构 主要用于该框架的树形结构json
     * @param array $items
     * @return array
     */
    public function getCategoryArr($fid) {

        if($fid!=0){//当用户不为最顶级部门时，查找自己的所在类
            $parent = $this->table('core_depart')->where(array('id'=>$fid))->getOne();
            $pid    = $parent['parent'];
        }else{
            $pid    = 0;
        }
        //获取当前分类的子类
        $authMo = model("user.auth","mysql");

        $re = $authMo->getChild($fid,1);
         //dump($re);

        $items = array();
        foreach($re as $val){//将数组进行对齐
            $items[$val['id']]['text']   = $val['name'];
            $items[$val['id']]['id']     = $val['id'];
            //$items[$val['id']]['href']   = $val['id'];
            $items[$val['id']]['parent'] = $val['parent'];
        }
        //将数组进行格式化,用于该框架的树形结构json
        foreach ($items as $item)
            $items[$item['parent']]['nodes'][$item['id']] = &$items[$item['id']];
        return isset($items[$pid]['nodes']) ? $items[$pid]['nodes'] : array();
    }





    //获取某一分类的全部子类
    public function getTreeChild($fid) {
       // $data = $this->table('core_depart')->order('id asc')->get();
        $data = $this->arr_tree();
        $result = array();
        $fids = array($fid);
        do {
            $cids = array();
            $flag = false;
            foreach($fids as $fid) {
                for($i = count($data) - 1; $i >=0 ; $i--) {
                    $node = $data[$i];
                    if($node['parent'] == $fid) {
                        array_splice($data, $i , 1);
                        $result[] = $node;
                        $cids[] = $node['id'];
                        $flag = true;
                    }
                }
            }
            $fids = $cids;
        } while($flag === true);
        dump($result);
        return $result;
    }




}