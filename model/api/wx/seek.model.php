<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/11
 * Time: 12:15
 */
class ApiWxSeekModel extends Model
{

    /**
     * 热门搜索词
     * @return mixed
     */
    public function getHotWords(){
       $hotWords = $this->table('base_hot_search')->field('keyWord')->get();
        return $hotWords;
    }

    /**
     * 出发城市
     * @return mixed
     */
    public function getStartCitys()
    {
        return $this->table('base_city')->field("name as title,id as value")->get();
    }


    /**
     * 线路搜索
     * @param $storeId      门店id
     * @param $city         出发城市
     * @param $type         线路类型
     * @param $dest         目的地
     * @param $days         天数
     * @param $price        价格区间
     * @param $key          关键字
     * @param $page         第几页
     * @param $pageSize     每页大小
     * @param $startDay     出发日期 2016-12-01
     * @param $orderBy      排序方式 1 价格由低到高 2出发日期最近
     * @return array
     */
    public function search($storeId, $city, $type, $dest, $days, $price, $key, $page, $pageSize,$startDay='',$orderBy='')
    {

        //$noTime = date("Y-m-d",time()+3600*24);
        $noTime = date("Y-m-d", time());
        //echo $noTime;
        $return = array();
        $sql = "select a.id,a.coder,a.name,a.subName,a.cover,a.tags,min(b.storeManPrice) as storeManPrice,b.storeChildrenPrice,b.depotNums,a.traffic,a.subject,d.name as destType from pro_line as a,pro_line_price as b,pro_line_price_type as c ,base_dest_type as d ";
        $sql .= "where a.id=b.lineId and a.platStatus=1 and c.status=1 and c.id=b.typeId and a.destTypeId=d.id and a.supplierStatus=1 and b.day>'" . $noTime . "' ";
        //+----------
        //生成查询条件
        //出发日期
        if($startDay)
        {
            $startDay = date("Y-m-d",strtotime($startDay));
            $sql .= " and b.day='".$startDay."' ";
        }

        //门店查询条件支持
        if ($storeId) {
            $sql .= "and (a.storeId like '%," . $storeId . ",%' or a.storeId='0') ";
        } else {
            $sql .= "and a.storeId='0' ";
        }
        //出发城市
        if ($city) {
            $lists = explode(',', $city);
            if (!empty($lists)) {
                $where = '';
                $or = '';
                foreach ($lists as $item) {
                    $where .= $or . "a.startCityId like '%," . $item . ",%'";
                    $or = ' or ';
                }
                if ($where) {
                    $sql .= "and (" . $where . ") ";
                }
            }
        }
        //线路类型
        if ($type) {
            $sql .= "and a.destTypeId = " . $type . " ";
        }
        //目的地
        if ($dest) {
            $lists = explode(',', $dest);
            if (!empty($lists)) {
                $where = '';
                $or = '';
                foreach ($lists as $item) {
                    $where .= $or . "a.destId like '%," . $item . ",%'";
                    $or = ' or ';
                }
                if ($where) {
                    $sql .= "and (" . $where . ") ";
                }
            }
        }
        //天数
        if ($days) {
            $lists = explode(',', $days);
            if (!empty($lists)) {
                $where = '';
                $or = '';
                foreach ($lists as $item) {
                    $where .= $or . "a.days = $item ";
                    $or = ' or ';
                }
                if ($where) {
                    $sql .= "and (" . $where . ") ";
                }
            }
        }
        //key关键字
        if ($key) {
            $sql .= "and (a.subName like '%" . $key . "%' or a.name like '%" . $key . "%' or a.tags like '%" . $key . "%') ";
        }
        //价格区间
        if ($price) {
            $lists = explode(',', $price);
            if (!empty($lists)) {
                $where = '';
                $or = '';
                foreach ($lists as $item) {
                    $items = explode('-', $item);
                    $where .= $or . "(b.storeManPrice < $items[1] and b.storeManPrice > $items[0])";
                    $or = ' or ';
                }
                if ($where) {
                    $sql .= "and (" . $where . ") ";
                }
            }
        }

        //分组排序
        $sql .= "group by a.id ";
        if($orderBy){
            if($orderBy==1){
                $sql .= "order by storeManPrice ASC,b.day ASC ";
            }
            if($orderBy==2){
                $sql .= "order by b.day ASC,storeManPrice ASC ";
            }
        }else{
            $sql .= "order by storeManPrice ASC ";
        }


        //总记录数,因为采用了group，需要特殊处理，
        $pageSql = "select count(*) from (" . $sql . ") AA";
        //echo $sql;
        $totalNums = $this->count($pageSql);
        //echo $this->lastSql();

        //分页操作
        $pageStart = ($page - 1) * $pageSize;
        $sql .= "limit $pageStart,$pageSize";
        //echo $sql;
        $lists = $this->get($sql);
         // dump($lists);
        //获取团期及其余位
        $data = array();
        if ($lists) {
            foreach ($lists as $key => $item) {
                //取得产品套餐
                //dump($item);
                $lineId = $item['id'];
                //$sql    = "select min(storeManPrice) as storeManPrice,storeChildrenPrice,depotNums,typeId,typeName from pro_line_price where lineId=$lineId and `day` > '".$noTime."' group by typeId order by storeManPrice ASC  limit 0,8";
                $sql = "select min(a.storeManPrice) as storeManPrice,a.storeChildrenPrice,a.depotNums,a.typeId,a.typeName,b.description from pro_line_price a,pro_line_price_type b where a.typeId=b.id and b.status=1 and a.lineId=$lineId and a.`day` > '" . $noTime . "' group by a.typeId order by a.storeManPrice ASC  limit 0,8";
                $prices = $this->get($sql);
                //dump($prices);
                $itemPrice = array();
                //取得套餐下面的具体团期
                foreach ($prices as $p => $price) {
                    $typeId = $price['typeId'];
                    $sql = "select storeManPrice,storeChildrenPrice,depotNums,typeId,typeName,`day` from pro_line_price where lineId=$lineId and typeId=$typeId and `day` > '" . $noTime . "' order by day ASC limit 0,8";
                    $temp = $this->get($sql);
                    $depot = '';
                    $ext1 = '';
                    $tuan = '';
                    //dump($temp);
                    foreach ($temp as $t) {
                        if ($t['depotNums'] < 0) $t['depotNums'] = '询';
                        $depot .= $ext1 . date("m.d", strtotime($t['day'])) . '(' . $t['depotNums'] . '位)';
                        $tuan .= $ext1 . date("m.d", strtotime($t['day']));
                        $ext1 = '/';

                    }
                    $price['depotStrs'] = $depot;
                    $price['dayStrs'] = $tuan;
                    if ($p == 0) {
                        $price['display'] = '';
                        $price['buttonClass'] = 'btn-primary';

                    } else {
                        $price['display'] = 'none';
                        $price['buttonClass'] = '';
                    }
                    $itemPrice[] = $price;
                }
                $lists[$key]['price'] = $itemPrice;
            }
        }
        //dump($lists);
        $return['totalRows'] = $totalNums;
        $return['pageSize'] = $pageSize;
        $return['page'] = $page;
        $return['lists'] = $lists;
        return $return;
    }
}