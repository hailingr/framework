<?php
class ApiPostController extends Controller
{
    /**
     * 资金上传
     */
    public function up()
    {
        $mo         = model('api.post');
        $billInfo   = $mo->getBillInfo(1);
        //dump($billInfo);
        $uPCoder = '62103301';
        $uUCoder = 'ZG01';
        $money   = 300;
        $billTime   = date("Y-m-d H:i:s");
        $type       = 2;
        //20160313120112
        $coder      = date("YmdHis").rand(1000,9999);
        $coder      = '201610211037265265';
        //先请求，返回无再提交
        $query      = $mo->billQuery($coder,$type);
        //查询请求结果
        dump($query);
        $m = microtime();
        $ms = explode(' ',$m);
        echo '<br>';
        $queryStatus = isset($query->error) ? $query->error : 2;
        $m = microtime();
        $ms2 = explode(' ',$m);
        echo ($ms2[1] + $ms2[0]) - ($ms[1] + $ms[0]);
        echo '<br>';

        if($queryStatus==0)
        {
            echo '1:该笔资金数据已经存在<br>';
        }elseif($queryStatus==1)
        {
            //创建该笔资金
            echo '1:创建该笔资金<br>';
            $m = microtime();
            $ms = explode(' ',$m);
            $rest = $mo->moneyUpload($coder,$uPCoder,$uUCoder,$money,$billTime,$type);
            $m = microtime();
            $ms2 = explode(' ',$m);
            echo ($ms2[1] + $ms2[0]) - ($ms[1] + $ms[0]);
            echo '<br>';
            dump($rest);
        }
        elseif($queryStatus==2)
        {
            //异常，重新查询该笔资金<br>
            echo '2：重新查询该笔资金<br>';

            $query          = $mo->billQuery($coder,$type);

            $queryStatus    = isset($query->error) ? $query->error : 2;
            if($queryStatus==0)
            {
                echo '2：该笔资金数据已经存在<br>';
            }elseif($queryStatus==1)
            {
                echo '2:创建该笔资金<br>';
                $rest = $mo->moneyUpload($coder,$uPCoder,$uUCoder,$money,$billTime,$type);
                dump($rest);
            }elseif($queryStatus==2)
            {
                echo '2：系统异常，请稍候提交<br>';
            }
        }
    }

    public function query()
    {
        $mo = model('api.post');
        $billInfo = $mo->getBillInfo(1);
        $type  = 3;
        $coder    = '201611010917086353';
        $rest = $mo->billQuery($coder,$type);
        dump($rest);
    }

    public function downFtp()
    {
        $mo = model('api.post');
        $type = $this->getRequest('type');
        if($type=='com'){
            $file = $mo->getComCoderFile('/ydgl/out/brchno20161020.txt');
        }
        if($type=='user')
        {
            $file = $mo->getComCoderFile('/ydgl/out/opermng20161020.txt');
        }
        echo $file;
    }

    /**
     * 上传ftp测试
     */
    public function uploadFtp()
    {
        $mo = model('api.post');
        $upfile = 'ydgl/in/transaction20161026.txt';
        $localfile = APPROOT.'/data/ftp/transaction20161026.txt';
        $mo->sendBillFile($localfile,$upfile);
        echo 'uploadSuccess';
    }

    /**
     * 提交测试返回
     */
    public function returntest()
    {
        /*echo file_get_contents("php://input");
        echo '<br>';*/
        $re = array('error'=>0,'errorInfo'=>'成功','seqno'=>'20161112510075895');
        echo json_encode($re);
    }

    /**
     * 查询测试查询
     */
    public function returntest2()
    {
        /*echo file_get_contents("php://input");
        echo '<br>';*/
        $re = array('error'=>0,'seqno'=>'20161112510075895');
        echo json_encode($re);
    }
}
