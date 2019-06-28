<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/20
 * Time: 10:39
 */
class SysNoticeModel extends Model
{
    private $tableName = 'base_notice';

    public function getAll($keywords,$timeStart,$timeEnd,$p,$pageSize){
        $page  = ($p-1)* $pageSize;
        $where = ' 1=1 ';
        if($keywords){
            $where .= ' and ( title like "%'.$keywords.'%" or content like"'.$keywords.'" )';
        }
        if($timeStart){
            $where .= ' and start_time>="'.$timeStart.'"';
        }
        if($timeEnd){
            $where .= ' and end_time<="'.$timeEnd.'"';
        }

        $count  = $this->table($this->tableName)->where($where)->count();
        $res    = $this->table($this->tableName)->where($where)->limit($page,$pageSize)->order('create_time desc')->get();
        $return = array('list'=>$res,'count'=>$count,'page'=>$p,'pageSize'=>$pageSize,'sql'=>$this->lastSql());
        return $return;
    }

    /**
     * 添加
     * @param $arr
     * @return mixed
     */
    public function addNotice($arr){
        $arr['create_time'] = date('Y-m-d H:i:s',time());
        $res = $this->table($this->tableName)->insert($arr);
        return $res;
    }

    /**
     * 修改
     * @param $id
     * @param $arr
     * @return mixed
     */
    public function upNotice($id,$arr){
        $res = $this->table($this->tableName)->where('id='.$id)->update($arr);
        return $res;
    }

    /**
     * 删除
     * @param $id
     * @return array
     */
    public function delNotice($id){
        $res = $this->table($this->tableName)->del("id={$id}");
        if($res===-1){
            return array('status'=>1);
        }else{
            return array('status'=>2,);
        }
    }
    //获取部门
    public function getDepart($dd){
        if($dd==0){
            $res = $this->table('core_depart')->get();
        }else{
            $res = $this->table('core_depart')->where('id='.$dd)->getOne();
        }
        return $res;
    }
    /**
     * 获取一条公告
     * @param $id
     * @return mixed
     */
    public function getOneData($id){
        $res = $this->table($this->tableName)->where('id='.$id)->getOne();
        return $res;
    }

    /**
     * 获取一定时间内一定类型的所有公告显示首页
     * @param $type
     * @param $target
     * @return mixed
     */
    public function getNotice($type,$target){
        $time  = date('Y-m-d H:i:s',time());
        $where = ' status=1 and start_time <= "'.$time.'" and end_time >= "'.$time.'"';
        if($type){
            $where .= ' and ( type=0 or type='.$type.' )';
        }
        if($target){
            $where .= ' and ( target like "%0%" or target like "%'.$target.'%") ';
        }
        $res = $this->table($this->tableName)->where($where)->get();
        //dump($this->lastSql());
        return $res;

    }

}