<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * php分页
 * Date: 2016/9/13
 * Time: 9:47
 */
class ToolsPageModel extends Model{

    /**
     * 页码分页处理
     * @param $count int 总条数
     * @param $page int  当前页页码
     * @param $pageSize  int 页面显示条数
     * @param $url       array 自定义链接url
     * @return array
     * //本框架自定义css样式
     * .pager{color: #676A6C;}.yd-pager{padding: 0;}.yd-pager a{margin: 0;display: inline-block;line-height: 32px;min-width: 34px;}
     */
    public function pager($count,$page,$pageSize,$url){

        $Page_size=$pageSize; //页码尺寸
        $page_count  = ceil($count/$Page_size);//页数计算

        $start    = ($page-1)*$pageSize+1;//开始条数
        $end      = ($page*$pageSize)>$count?$count:$page*$pageSize;//结束条数
        $countStr = ($count>0)?'显示 '.$start.' 到 '.$end.' 项，共 '.$count.' 项':'无记录';//自定义统计

        $init=1;
        $page_len=7;//页码显示数
        $max_p=$page_count;//最大页码
        $pages=$page_count;//页码条数

        //判断当前页码
        if(empty($page)||$page<0){
            $page=1;
        }

        $page_len = ($page_len%2)?$page_len:$page_len+1;//页码个数
        $pageOffset = ($page_len-1)/2;//页码个数左右偏移量

        //页码html
        $key='';
        //第一页&上一页
        if($page!=1){
            $key.='<button class="btn btn-white yd-pager"><a class="pager" href="?'.$url.'page=1"><i class="fa  fa-angle-double-left"></i></a></button>';    //第一页
            $key.='<button class="btn btn-white yd-pager"><a class="pager" href="?'.$url.'page='.($page-1).'"><i class="fa fa-angle-left">&nbsp;</i></a></button>'; //上一页
        }else {
            $key.='<button class="btn btn-white disabled"><i  class="fa  fa-angle-double-left"></i></button>';//第一页
            $key.='<button class="btn btn-white disabled"><i class="fa fa-angle-left">&nbsp;</i></button>'; //上一页
        }
         //中间页码
        if($pages>$page_len){
            //如果当前页小于等于左偏移
            if($page<=$pageOffset){
                $init=1;
                $max_p = $page_len;
            }else{//如果当前页大于左偏移
                //如果当前页码右偏移超出最大分页数
                if($page+$pageOffset>=$pages+1){
                    $init = $pages-$page_len+1;
                }else{
                    //左右偏移都存在时的计算
                    $init = $page-$pageOffset;
                    $max_p = $page+$pageOffset;
                }
            }
        }

        for($i=$init;$i<=$max_p;$i++){
            if($i==$page){
                $key.='<button class="btn btn-primary active">'.$i.'</button>';
            } else {
                $key.='<button class="btn btn-white yd-pager"><a class="pager" href="?'.$url.'page='.$i.'">'.$i.'</a></button>';
            }
        }
        //结束页码
        if($page!=$pages){
            $key.='<button class="btn btn-white yd-pager"><a class="pager" href="?'.$url.'page='.($page+1).'"><i class="fa fa-angle-right">&nbsp;</i></a></button>';//下一页
            $key.='<button class="btn btn-white yd-pager"><a class="pager" href="?'.$url.'page='.$pages.'"><i class="fa  fa-angle-double-right"></i></a></button>'; //最后一页
        }else {
            $key.='<button class="btn btn-white disabled"><i class="fa fa-angle-right">&nbsp;</i></button>';//下一页
            $key.='<button class="btn btn-white disabled"><i class="fa  fa-angle-double-right"></i></button>'; //最后一页
        }
        //数据返回
        $data = array('key'=>$key,'countStr'=>$countStr);
        return $data;
    }




}