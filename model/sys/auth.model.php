<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/8
 * Time: 15:01
 */
class SysAuthModel extends Model{


    /**
     * 获取岗位列表数据
     * @param $departId int 部门id
     * @param $key      string 关键字搜索
     * @param $page     int  搜索的第几页页数数
     * @param $pageSize  int 每页显示的条数
     * @param $userId    int 用户id
     * @return array
     */
    public function getJobList($departId,$key,$page,$pageSize,$userId){
        //var_dump($key);
        //初始化总条数
        $count  = 0;
        //起始条数
        $pages  = ($page-1)* $pageSize;
        //查询用户基本数据
        $doc     = array();
        $jobs    = array();
        $findKey = '"%'.$key.'%"';
        $findKeyStr = '`name` like '.$findKey;

       /* if($departId==0&&$userId==1){//当部门id为0，且用户id为1时，获取全部岗位
            $count = $this->table('core_job')->where('id!=1 and type=1 and departId!="null" and '.$findKeyStr)->order(array('departId'=>'ASC'))->count();
            $jobs  = $this->table('core_job')->where('id!=1 and type=1 and departId!="null" and '.$findKeyStr)->order(array('departId'=>'ASC'))->limit($pages,$pageSize)->get();
       }else{*/
            //读取该部门的的子部门
            $userMo = model('user.auth','mysql');
            $departs = $userMo->getChild($departId);
            $find    = array();
            foreach($departs as $item){
                $find[] = $item['id'];
            }
            $findIn  = implode(',',$find);
            //获取该部门及其子部门岗位
            $count = $this->table('core_job')->where('departId in('.$findIn.') and '.$findKeyStr)->count();
            $jobs  = $this->table('core_job')->where('departId in('.$findIn.') and '.$findKeyStr)->order(array('departId'=>'ASC'))->limit($pages,$pageSize)->get();
       // }

        //获取岗位下面的对应权限
        if(!empty($jobs)){
            //取得该职位的所有权限
            foreach($jobs as $k=>$job)
            {
                $jobId    = $job['id'];
                $pId      = $job['departId'];
                $departName = $this->table('core_depart')->where("id=$pId")->getOne();//单独获取部门名称，可能部门未分配权限，无法查找到部门名称
                $sql      = "select b.* from core_job_auth as a LEFT JOIN core_auth as b ON a.authId=b.id where a.jobId='".$jobId."' order by modCode ASC,sort ASC,funCode ASC";
                $lists   = $this->get($sql);
                $authStr = '';
                //当有数据时，组装数据
                if($lists){
                    $auth = array();
                    foreach($lists as $list){
                        $auth[] =  $list['funName'];
                    }
                    //$departName = $lists[0]['name'];
                    $authStr = implode(',',$auth);
                }
                $jobs[$k]['auth'] = $authStr;//权限字符串
                $jobs[$k]['departName'] = $departName['name'];//部门名称
            }

        }
        $return = array('data'=>$jobs,'row'=>$count);
        return $return;
    }

    /**
     * 获取部门及子部门中所有的权限
     * @param $departId int 部门id
     * @param $userId   int 用户id
     * @return array
     */
    public function getAuth($departId,$userId){
        $auths = array();
        if($departId==0&&$userId==1){//如果用户是超级管理员，获取全部权限
            $auths = $this->table('core_auth')->where(array())->order(array('modCode'=>'ASC','sort'=>'ASC','funCode'=>'ASC'))->get();
        }else{//如果用户是普通人员，获取其部门及其子部门权限
            //读取该部门的的子部门
            $userMo = model('user.auth','mysql');
            $departs = $userMo->getChild($departId);
            $find    = array();
            foreach($departs as $item){//遍历部门获取部门id
                $find[] = $item['id'];
            }
            $findIn = implode(',',$find);
            $jobs   = $this->table('core_job')->where('departId in('.$findIn.')')->order(array('departId'=>'ASC'))->get();//获取所有部门涉及的岗位
            //获取岗位下面的对应权限
            if(!empty($jobs)){

                //取得该职位的所有权限及该岗位对应的部门
                foreach($jobs as $k=>$job)
                {
                    $jobId    = $job['id'];
                    $sql      = "select b.* from core_job_auth as a LEFT JOIN core_auth as b ON a.authId=b.id where a.jobId='".$jobId."' order by modCode ASC,sort ASC,funCode ASC";
                    $lists   = $this->get($sql);
                    //当有数据时，组装数据
                    if($lists){
                        foreach($lists as $item){
                            $auths[] = $item;
                        }
                    }
                }
            }
            // dump($jobs);
        }
        //将权限按模块分类
        $authTree = array();
        if(!empty($auths)){
            foreach($auths as $auth)
            {
                //返回分类数
                $authTree[$auth['modName']][$auth['id']] = $auth;
                //$authTree[$auth['modName']][$auth['id']]['id'] = $auth['id'];
                //$authTree[$auth['modName']][$auth['id']]['funName'] = $auth['funName'];
            }
        }
        return $authTree;
        //dump($authTree);
    }

    /**
     * 保存或编辑岗位
     * @param $jobId  int 岗位id
     * @param $jobName string 岗位名称
     * @param $jobAuth array 权限
     * @param $departId int 部门id
     */
    public function saveJob($jobId,$jobName,$jobAuth,$departId){
        $data = array('name'=>$jobName,'departId'=>$departId);
        if($jobId==0){//如果jobId为零，则为新增岗位
            //将用户基本信息插入数据表中
            //dump($data);
            $result = $this->table('core_job')->insert($data);
            //dump($result);
            $jobId  = $result;
        }else{
            $result = $this->table('core_job')->where(array('id'=>$jobId))->update($data);
            //将原有权限删除
            $delAuth = $this->table('core_job_auth')->where(array('jobId'=>$jobId))->del();
            // dump($this->lastSql());

        }
        $items = array();
        if($jobAuth){
            //将数据岗位对应的权限保存
            foreach($jobAuth as $val){
                $item = array('jobId'=>$jobId,'authId'=>$val);
                //dump($item);
                $this->table('core_job_auth')->insert($item);
            }
        }
        return 1;
    }

    /**
     * 获取岗位信息
     * @param $jobId int 岗位id
     * @return array
     */
    public function getJobInfo($jobId){
        $return  = array();
        if($jobId==0){
            $return['departId'] = '';
            $return['jobName']  = '';
            $return['auth']     = array();
        }else{
            //查找岗位对应部门id和岗位名字
            $sql     = "select b.name as departName,a.name as jobName,a.departId from core_job as a LEFT JOIN core_depart as b ON a.departId=b.id where a.id='".$jobId."'";
            $result  = $this->getOne($sql);
            $return['departId'] = $result['departId'];
            $return['jobName']  = $result['jobName'];
            //查找已分配权限
            $auths   =  $this->table('core_job_auth')->where(array('jobId'=>$jobId))->get();
            $auth = array();
            if($auths){
                foreach($auths as $item){
                    $auth[] = $item['authId'];
                }
            }
            $return['auth'] = $auth;
        }
        //dump($auth);
        return $return;
        //dump($return);
    }



}