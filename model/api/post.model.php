<?php
class ApiPostModel extends Model
{

     //public $upIp = 'http://127.0.0.1/?m=api.post&a=returntest';
    public $upIp = 'http://7.7.7.55:7001/scqz/ydgl/jiaofei';
//    public $upIp = 'http://192.168.33.240:7001/scqz/ydgl/jiaofei';

//    public $quIp = 'http://127.0.0.1/?m=api.post&a=returntest2';
    public $quIp = 'http://7.7.7.55:7001/scqz/ydgl/chaxun';
//    public $quIp = 'http://192.168.33.240:7001/scqz/ydgl/chaxun';

    public $ftpIp= '7.7.7.55';
    public $ftpPort = '21';
    public $ftpUser = 'yyp';
    public $ftpPwd  = 'yyp_201501';
    /*public $ftpIp= '125.64.14.15';
    public $ftpPort = '21';
    public $ftpUser = 'ydgl';
    public $ftpPwd  = 'ydgl';*/
    //public $ftpIp= 'http://127.0.0.1';

    public function getBillInfo($id)
    {
        $rows = $this->table('order_line')->where(array('id'=>$id))->getOne();
        return $rows;
    }

    /**
     * 下载机构图片
     * @param $file
     * @return string
     */
    public function getComCoderFile($file)
    {
        import('ftp','lib',false);
        $ftp = new Ftp($this->ftpIp,$this->ftpPort,$this->ftpUser,$this->ftpPwd);
        //dump($ftp);
        //下载ftp文件
        $files = explode('/',$file);
        //dump($files);
        $fileIndex = count($files)-1;
        $newFileMe = $files[$fileIndex];

        $newFile = APPROOT.'/data/uzfile/down/'.$newFileMe;

        $return  = $ftp->down_file($file,$newFile);
        $return = array('return'=>$return,'tofile'=>$newFile,'downfile'=>$file);
        return $return;
    }

    /**
     * 上传订单明细文件
     * @param $file
     */
    public function sendBillFile($pathfile,$file)
    {
        import('ftp','lib',false);
        $ftp = new Ftp($this->ftpIp,$this->ftpPort,$this->ftpUser,$this->ftpPwd);
        $rs  = $ftp->up_file($pathfile,$file);
        $rst['return'] = $rs;
        $rst['upfile'] = $pathfile;
        $rst['tofile'] = $file;
        return $rst;
    }

    /**
     * 邮政资金上传接口
     * @param $billCoder  订单
     * @param $uPCoder    机构号
     * @param $uUCoder    柜员号
     * @param $money      金额
     * @param $billTime   订单时间
     * @param $type       1 定金 2余额 3全额
     */
    public function moneyUpload($billCoder,$uPCoder,$uUCoder,$money,$billTime,$type)
    {
        if($type==1)
        {
            $billCoder = $billCoder.'A';
        }
        if($type==2)
        {
            $billCoder = $billCoder.'B';
        }
        //生成sha上传json数据格式
        $upData = array();
        $upData['billCoder'] = $billCoder;
        $upData['money'] = $money;
        $upData['oNumber'] = $uPCoder;
        $upData['pNumber'] = $uUCoder;
        $upData['billTime'] = $billTime;
        //echo '<hr>send:';
        //dump($upData);

        $rest = json_encode($upData);
        //echo $rest.'<hr>';
        //echo $this->upIp;
        //$info = $http->post($this->upIp,$rest);
        $info = $this->http_post_data($this->upIp,$rest);
        //echo '<hr>back:';
        //$info = json_decode($info);
        //echo $info;
        $info = json_decode($info);
        //dump($info);

        return  $info;
    }

    /**
     * 邮政订单查询接口
     * @param $billCoder
     * @param $type       1 定金 2余额 3全额
     */
    public function billQuery($billCoder,$type)
    {
        if($type==1)
        {
            $billCoder = $billCoder.'A';
        }
        if($type==2)
        {
            $billCoder = $billCoder.'B';
        }
        //生成sha上传json数据格式
        $upData = array('billCoder'=>$billCoder);
        //echo '<hr>send:';
        //dump($upData);
        $rest = json_encode($upData);
        //echo $rest.'<hr>';
        $info = $this->http_post_data($this->quIp,$rest);
        //echo '<hr>back:';
        //echo $info;
        $info = json_decode($info);
        //dump($info);
        return  $info;
    }

    function http_post_data($url, $data_string) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //return array($return_code, $return_content);
        return $return_content;
    }
}