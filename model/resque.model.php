<?php
/**
 * 基于redis的一个队列处理模型
 * 本模型依赖与Redis内存数据库，一个任务会在redis中生成一个list
 * hailingr@foxmail.com
 * 2016-12-02 成都
 */
class ResqueModel extends Model
{

    /**
     * 插入任务队列,新增一个任务
     * @param $group    任务队列名称
     * @param $task     任务明细，数组，根据业务自定义
     * @return bool     任务操作结果
     */
    public function addTask($group,$task)
    {
        $redis = $this->redis;
        if($redis){
            if($group && $task){
                $rst = $redis->rPush($group,json_encode($task,JSON_UNESCAPED_UNICODE));
                return $rst;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 执行任务
     * 在调用回调方法处理完毕任务逻辑后，需要返回成功，失败，如果失败，任务将会被重新写入任务队列
     * @param $group        队列名称
     * @param $function
     * @return
     */
    public function workTask($group,$function)
    {
        $redis = $this->redis;
        $task  = $redis->lPop($group);
        $task  = json_decode($task,true);
        if($task)
        {
            $rst = $function($group,$task);
            if($rst===false){
                //dump($rst);
                $task['exenums'] = isset($task['exenums']) ? $task['exenums'] : 0;
                $task['exenums'] = $task['exenums'] + 1;
                $this->addTask($group,$task);
            }
            return true;
        }else{
            return 'error:no task';
        }
    }

}