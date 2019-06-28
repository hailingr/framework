<?php

/**
 * Class CmdUzController
 * 用于邮政资金任务处理
 */
class CmdUzController extends Controller
{
    public function test()
    {
        //echo 1111111111111;
    }

    /**
     * 上传每日资金上传数据
     */
    public function upUzPayMoney()
    {
        $day = date("Y-m-d",time()-24*3600);
        //$day = '2016-11-01';
        $mo  = model('pro.pay');
        //取得指定日期的订单报表
        $rows = $mo->getPayUpMoneys($day);
        //dump($rows);
        $day  = str_replace('-','',$day);
        //$dir  = '';
        $upfile     = 'fromlxs/transaction'.$day.'.txt';
//        $upfile     = 'ydgl/in/transaction'.$day.'.txt';
        $localfile  = APPROOT.'/data/uzfile/upload/transaction'.$day.'.txt';
        //dump($rows);
        //exit;
        $fileStrs   = '网点|机构号|人员|柜员号|订单|邮政流水号|产品|订单类型|金额|公司酬金|个人酬金|客户名称|客户电话|人数|出团日期';
        foreach($rows as $row)
        {
            $fileStrs .= "\n".$row['storeName']."|".$row['storeUCoder']."|".$row['realName']."|".$row['saleUCoder']."|".$row['coder']."|"
                .$row['ucoder']."|".$row['title']."|".$row['statusMsg']."|".$row['payMoney']."|".$row['companyPer']."|".$row['userPer']."|"
                .$row['linkMan']."|".$row['linkTel']."|".$row['peopleNum']."|".$row['startDay'];
            //dump($row);
        }
        WriteFile($fileStrs,$localfile);
        //上传文件
        $mo = model('api.post');
        $rest = $mo->sendBillFile($localfile,$upfile);
        dump($rest);
    }

    /**
     * 上传每日资金退订数据
     */
    public function upUzTuiMoney()
    {
        $day = date("Y-m-d",time()-24*3600);
        //$day = '2016-11-01';
        $mo  = model('pro.pay');
        //取得指定日期的订单报表
        $rows = $mo->getTuiUpMoneys($day);
        //dump($rows);

        $day  = str_replace('-','',$day);
        $upfile     = 'fromlxs/Tui'.$day.'.txt';
//        $upfile     = 'ydgl/in/Tui'.$day.'.txt';
        $localfile  = APPROOT.'/data/uzfile/upload/Tui'.$day.'.txt';

        $fileStrs   = '订单号|机构号|人员|柜员号|退订金额|时间';
        foreach($rows as $row)
        {
            $fileStrs .= "\n".$row['coder'].'|'.$row['storeUCoder'].'|'.$row['realName'].'|'.$row['saleUCoder'].'|'.$row['money'].'|'.$row['create_time'];
            //dump($row);
        }
        WriteFile($fileStrs,$localfile);
        //上传文件
        $mo = model('api.post');
        $rest = $mo->sendBillFile($localfile,$upfile);
        dump($rest);
    }

    /**
     * 下载邮政柜员编号
     */
    public function downUzUserCoder()
    {
        $day = date("Y-m-d",time()-24*3600);
        $mo = model('api.post');
        $type = 'user';
        if($type=='user')
        {
            $day  = str_replace('-','',$day);
            $file = $mo->getComCoderFile('/tolxs/opermng'.$day.'.txt');
//            $file = $mo->getComCoderFile('/ydgl/out/opermng'.$day.'.txt');
            dump($file);
        }
    }

    /**
     * 下载邮政机构号
     */
    public function downUzComCoder()
    {
        $day = date("Y-m-d",time()-24*3600);
        $mo = model('api.post');
        $type = 'com';
        if($type=='com'){
            $day  = str_replace('-','',$day);
            $file = $mo->getComCoderFile('/tolxs/brchno'.$day.'.txt');
//            $file = $mo->getComCoderFile('/ydgl/out/brchno'.$day.'.txt');
            dump($file);
        }
    }

}