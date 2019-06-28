<?php
/**
 * csv文件生成下载
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/20
 * Time: 18:04
 */
class ToolsGetCsvModel extends Model{


    /**
     * @param $array array(('用户编号1','上班日期1','签到时间1','签退时间1'),('用户编号1','上班日期1','签到时间1','签退时间1'));
     * @param $head  array('用户编号','上班日期','签到时间','签退时间');
     * @param $filename string 自定义文件名
     * @return null|string
     */
    public function array2csv($array,$head,$filename)
    {
        //标题数据转码
        foreach($head as $headKey=>$item){
            $head[$headKey] =  iconv('utf-8', 'gbk', $item);
        }
        //头部数据生成
        header("Content-Type: application/vnd.ms-excel; charset=gbk");
        header("Content-Disposition: attachment;filename=".iconv('utf-8','gbk',$filename));
        //写入数据
        ob_start();
        $df = fopen("php://output", 'w');
        //头部录入
        if (!$head) {
            $head = array_keys(reset($array));
        }
        fputcsv($df, $head);
        //数据录入
        foreach ($array as $row) {
         foreach ($row as $key => $val) {
                $row[$key] = iconv('utf-8','gbk',$val);
            }
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }


}
