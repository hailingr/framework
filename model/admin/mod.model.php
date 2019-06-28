<?php
class AdminModModel extends Model
{
    /**
     * 获取板块
     * 后期可以加上对权限的过滤
     * 传递用户id进来，进行权限过滤
     */
    public function getPlates($uid=0)
    {
        if($uid)
        {

        }
        $rows = $this->table('core_auth')->field(array('plate'))->group("plate")->where(array())->get();
        return $rows;
    }

    /**
     * 获取模块权限菜单
     * @param int    $uid
     * @param string $plate
     */
    public function getModMenus($plate,$uid=0)
    {
        if($uid)
        {

        }
        $plates = $this->table('core_auth')->field(array('modName as `text`','id'))->group("modName")->where(array('plate'=>$plate,'isDel'=>0,'isMenu'=>1))->get();
        if(!empty($plates))
        {
            foreach ($plates as $k=>$v)
            {
                $plates[$k]['id']       = 'p_'.$v['id'];
                $plates[$k]['state']    = 'open';
                $plates[$k]['iconCls']  = '';
                $modName = $v['text'];
                //获取menu
                $children = $this->table('core_auth')
                    ->field(array('funName as `text`','id',"'open' as `state`","funIco as iconCls","CONCAT_WS('/',modCode,funCode) as url"))
                    ->where(array('modName'=>$modName,'isDel'=>0,'isMenu'=>1))->order("sort ASC")->get();
                $plates[$k]['children'] = $children;
            }
        }
        return $plates;
    }
}