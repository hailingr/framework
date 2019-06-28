<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/11
 * Time: 12:11
 */
class ApiWxSeekController extends Controller
{

    /**
     * 线路搜索
     */
    public function searchLine(){
        //如果是门店，按照门店逻辑进行查询，暂时不支持直客
        $city   = $this->getRequest('city');
        $storeId= $this->getRequest('storeId');
        $type   = $this->getRequest('typeId','');
        $dest   = $this->getRequest('dests','');
        $days   = $this->getRequest('days','');
        $price  = $this->getRequest('prices','');
        $key    = $this->getRequest('key');
        $page   = $this->getRequest('page',1);
        $pageSize = $this->getRequest('pageSize',10);
        $line  = model('api.wx.seek');
        //清理过期订单
        $lines    = $line->search($storeId,$city,$type,$dest,$days,$price,$key,$page,$pageSize);
        exit(json_encode($lines));

    }
}