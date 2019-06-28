<?php

/**
 * Class smtp
 * 邮件发送驱动
 */
class email{
    /*邮件用户名*/
    public $mailUser    = '';
    /*邮件密码*/
    public $mailPwd     = '';
    /*邮件服务器地址*/
    public $server      = '';
    /*邮件端口*/
    public $port        = '';
    public $timeout     = '';
    /*邮件编码*/
    public $charset     = '';
    /*邮件发送者email,用于显示给接收者*/
    public $senderMail  = '';
    /*发用者名称*/
    public $senderName  = '';
    /*是否使用ssl安全操作*/
    public $useSSL      = '';
    /*是否显示错误信息*/
    public $showError   = 1;
    public $needLogin   = 1;
    /*附件数组*/
    public $attachMent  = array();
    public $failed      = false;
    private static $smtpCon;
    private $stop       = "\r\n";
    private $status     = 0;

    public function __construct(){

        if(self::$smtpCon){
            return;
        }

        $mailConf = G('config');
        $mailConf = $mailConf['smtp'];
        // 使用示例
        ini_set('memory_limit','128M');
        //set_time_limit(120);
        date_default_timezone_set('PRC');

        $this->mailUser     = $mailConf['username'];
        $this->mailPwd      = $mailConf['password'];
        $this->senderName   = $mailConf['sitename'];
        $this->server       = $mailConf['server'];
        $this->senderMail   = $mailConf['mailfrom'];
        $this->port         = $mailConf['port'];
        $this->useSSL       = $mailConf['ssl'];
        $this->timeout      = 30;
        $this->charset      = 'utf-8';

        if($this->mailUser==''){
            $this->error('请配置好邮件登录用户名!');
            return false;
        }

        if($this->mailPwd==''){
            $this->error('请配置好邮件登录密码!');
            return false;
        }

        if($this->server==''){
            $this->error('请配置好邮服务器地址!');
            return false;
        }

        if(!is_numeric($this->port)){
            $this->error('请配置好邮服务器端口!');
            return false;
        }

        /*ssl使用**/
        $server = $this->server;
        if($this->useSSL == true){
            $server = "ssl://".$this->server;
        }

        self::$smtpCon = @fsockopen($server, $this->port, $errno, $errstr,10);;


        if(!self::$smtpCon){
            $this->error($errno.$errstr);
            return false;
        }


        socket_set_timeout(self::$smtpCon,0,250000);

        /*开始邮件指令*/
        $this->getStatus();
        $resp = true;
        $resp = $resp && $this->helo();
        if($this->needLogin == '1'){
            $resp = $resp && $this->login();
        }

        if(!$resp){
            $this->failed = true;
        }

    }

    /**
     * @param $sendTo  发送对象   多个邮箱 数组array('a@163.com','2@163.com')
     * @param $title   邮件标题
     * @param $body    邮件内容
     * @param $atts    附件 多个附件，数组 array('1,txt','2.txt')
     * @return bool
     */
    public function sendMail($sendTo,$title,$body,$atts)
    {
        //付件
        if($atts){
            if(is_string($atts)) $atts = array($atts);
            $this->attachMent = $atts;
        }
        if($sendTo && is_string($sendTo)) $sendTo = array($sendTo);
        if($this->send($sendTo,$body,$title)){
            $this->close();
            return true;
        }else{
            $this->close();
            return false;
        }
    }

    /*
    发送邮件
    @param string $to 接收邮件地址
    @param string $msg 邮件主要内容
    @title string $title 邮件标题
    */
    public function send($to,$msg,$title=''){

        if($msg=='' ){

            return false;
        }
        if(is_array($to)){

            if($to!=null){
                foreach($to as $k=>$e){

                    if(!preg_match('/^[a-z0-9A-Z_-]+@+([a-z0-9A-Z_-]+\.)+[a-z0-9A-Z]{2,3}$/',$e)){

                        unset($to[$k]);
                    }
                }
            }else{
                return false;
            }

            if($to == null){
                return false;
            }

        }else{

            if(!preg_match('/^[a-z0-9A-Z_-]+@+([a-z0-9A-Z_-]+\.)+[a-z0-9A-Z]{2,3}$/',$to)){

                return false;
            }

        }


        if(!self::$smtpCon){
            return false;
        }

        $this->sendSmtpMsg('MAIL FROM:<'.$this->senderMail.'>');

        if(!is_array($to)){
            $this->sendSmtpMsg('RCPT TO:<'.$to.'>');
        }else{

            foreach($to as $k=>$email){
                $this->sendSmtpMsg('RCPT TO:<'.$email.'>');
            }
        }

        $this->sendSmtpMsg("DATA");


        if($this->status !='354'){
            $this->error('请求发送邮件失败!');
            $this->failed = true;
            return false;
        }

        $msg  = base64_encode($msg);
        $msg = str_replace($this->stop . '.', $this->stop . '..', $msg);
        $msg    = substr($msg, 0, 1) == '.' ? '.' . $msg : $msg;

        if($this->attachMent!=null){

            $headers = $this->mimeHeader($msg,$to,$title);
            $this->sendSmtpMsg($headers,false);

        }else{

            $headers = $this->mailHeader($to,$title);
            $this->sendSmtpMsg($headers,false);
            $this->sendSmtpMsg('',false);
            $this->sendSmtpMsg($msg,false);
        }
        $this->sendSmtpMsg('.');//发送结束标识符

        if($this->status != '250'){
            $this->failed = true;
            $this->error($this->readSmtpMsg());
            return false;
        }

        return true;
    }

    /*
    关闭邮件连接
    */
    public function close(){

        $this->sendSmtpMsg('Quite');
        @socket_close(self::$smtpCon);
    }

    /*
    添加普通邮件头信息
    */
    protected function mailHeader($to,$title){
        $headers = array();
        $headers[] = 'Date: '.$this->gmtime('D j M Y H:i:s').' '.date('O');

        if(!is_array($to)){
            $headers[] = 'To: "'.'=?'.$this->charset.'?B?'.base64_encode($this->getMailUser($to)).'?="<'.$to.'>';
        }else{
            foreach($to as $k=>$e){
                $headers[] = 'To: "'.'=?'.$this->charset.'?B?'.base64_encode($this->getMailUser($e)).'?="<'.$e.'>';
            }
        }

        $headers[] = 'From: "=?'.$this->charset.'?B?'.base64_encode($this->senderName).'?="<'.$this->senderMail.'>';
        $headers[] = 'Subject: =?'.$this->charset.'?B?'.base64_encode($title).'?=';
        $headers[] = 'Content-type: text/html; charset='.$this->charset.'; format=flowed';
        $headers[] = 'Content-Transfer-Encoding: base64';

        $headers = str_replace($this->stop . '.', $this->stop . '..', trim(implode($this->stop, $headers)));
        return $headers;
    }

    /*
    带付件的头部信息
    */
    protected function mimeHeader($msg,$to,$title){

        if($this->attachMent!=null){

            $headers = array();
            $boundary = '----='.uniqid();
            $headers[] = 'Date: '.$this->gmtime('D j M Y H:i:s').' '.date('O');
            if(!is_array($to)){
                $headers[] = 'To: "'.'=?'.$this->charset.'?B?'.base64_encode($this->getMailUser($to)).'?="<'.$to.'>';
            }else{
                foreach($to as $k=>$e){
                    $headers[] = 'To: "'.'=?'.$this->charset.'?B?'.base64_encode($this->getMailUser($e)).'?="<'.$e.'>';
                }
            }

            $headers[] = 'From: "=?'.$this->charset.'?B?'.base64_encode($this->senderName).'?="<'.$this->senderMail.'>';
            $headers[] = 'Subject: =?'.$this->charset.'?B?'.base64_encode($title).'?=';
            $headers[] =  'Mime-Version: 1.0';
            $headers[] = 'Content-Type: multipart/mixed;boundary="'.$boundary.'"'.$this->stop;
            $headers[]='--'.$boundary;

            $headers[]='Content-Type: text/html;charset="'.$this->charset.'"';
            $headers[]='Content-Transfer-Encoding: base64'.$this->stop;
            $headers[] = '';
            $headers[]= $msg.$this->stop;

            foreach($this->attachMent as $k=>$filename){

                $f = @fopen($filename, 'r');
                $mimetype = $this->getMimeType(realpath($filename));
                $mimetype = $mimetype == '' ? 'application/octet-stream' : $mimetype;

                $attachment = @fread($f, filesize($filename));
                $attachment = base64_encode($attachment);
                $attachment = chunk_split($attachment);

                $headers[] = "--" . $boundary;
                $headers[] = "Content-type: ".$mimetype.";name=\"=?".$this->charset."?B?". base64_encode(basename($filename)).'?="' ;
                $headers[] = "Content-disposition: attachment; name=\"=?".$this->charset."?B?". base64_encode(basename($filename)).'?="';
                $headers[] = 'Content-Transfer-Encoding: base64'.$this->stop;
                $headers[] = $attachment.$this->stop;



            }
            $headers[] = "--" . $boundary . "--";
            $headers = str_replace($this->stop . '.', $this->stop . '..', trim(implode($this->stop, $headers)));
            return $headers;

        }
    }

    /*
    获取返回状态
    */
    protected function getStatus(){

        $this->status = substr($this->readSmtpMsg(),0,3);
    }


    /*
    获取邮件服务器返回的信息
    @return string 信息字符串
    */
    protected function readSmtpMsg(){
        if(!is_resource(self::$smtpCon)){
            return false;
        }
        $return = '';
        $line   = '';
        if($this->useSSL){
            $line    = @fgets(self::$smtpCon, 512);
            $return .= $line;
        }else{
            while (strpos($return, $this->stop)=== false OR $line{3}!== ' ')
            {
                $line    = fgets(self::$smtpCon, 512);
                $return .= $line;
            }
        }
        return trim($return);
    }

    /*
    给邮件服务器发给指定命令消息
    */
    protected function sendSmtpMsg($cmd,$chStatus=true){
        if (is_resource(self::$smtpCon))
        {
            fwrite(self::$smtpCon, $cmd . $this->stop, strlen($cmd) + 2);
        }
        if($chStatus == true){
            $this->getStatus();
        }

        return true;
    }

    /*
    邮件时间格式
    */
    protected function gmtime(){

        return (time() - date('Z'));

    }

    /*
    获取付件的mime类型
    */
    protected function getMimeType($file){

        $mimes = array(
            'chm'=>'application/octet-stream', 'ppt'=>'application/vnd.ms-powerpoint',
            'xls'=>'application/vnd.ms-excel', 'doc'=>'application/msword', 'exe'=>'application/octet-stream',
            'rar'=>'application/octet-stream', 'js'=>"javascrīpt/js", 'css'=>"text/css",
            'hqx'=>"application/mac-binhex40", 'bin'=>"application/octet-stream", 'oda'=>"application/oda", 'pdf'=>"application/pdf",
            'ai'=>"application/postsrcipt", 'eps'=>"application/postsrcipt", 'es'=>"application/postsrcipt", 'rtf'=>"application/rtf",
            'mif'=>"application/x-mif", 'csh'=>"application/x-csh", 'dvi'=>"application/x-dvi", 'hdf'=>"application/x-hdf",
            'nc'=>"application/x-netcdf", 'cdf'=>"application/x-netcdf", 'latex'=>"application/x-latex", 'ts'=>"application/x-troll-ts",
            'src'=>"application/x-wais-source", 'zip'=>"application/zip", 'bcpio'=>"application/x-bcpio", 'cpio'=>"application/x-cpio",
            'gtar'=>"application/x-gtar", 'shar'=>"application/x-shar", 'sv4cpio'=>"application/x-sv4cpio", 'sv4crc'=>"application/x-sv4crc",
            'tar'=>"application/x-tar",'ustar'=>"application/x-ustar",'man'=>"application/x-troff-man", 'sh'=>"application/x-sh",
            'tcl'=>"application/x-tcl", 'tex'=>"application/x-tex", 'texi'=>"application/x-texinfo",'texinfo'=>"application/x-texinfo",
            't'=>"application/x-troff", 'tr'=>"application/x-troff", 'roff'=>"application/x-troff",
            'shar'=>"application/x-shar", 'me'=>"application/x-troll-me", 'ts'=>"application/x-troll-ts",
            'gif'=>"image/gif", 'jpeg'=>"image/pjpeg", 'jpg'=>"image/pjpeg", 'jpe'=>"image/pjpeg", 'ras'=>"image/x-cmu-raster",
            'pbm'=>"image/x-portable-bitmap", 'ppm'=>"image/x-portable-pixmap", 'xbm'=>"image/x-xbitmap", 'xwd'=>"image/x-xwindowdump",
            'ief'=>"image/ief", 'tif'=>"image/tiff", 'tiff'=>"image/tiff", 'pnm'=>"image/x-portable-anymap", 'pgm'=>"image/x-portable-graymap",
            'rgb'=>"image/x-rgb", 'xpm'=>"image/x-xpixmap", 'txt'=>"text/plain", 'c'=>"text/plain", 'cc'=>"text/plain",
            'h'=>"text/plain", 'html'=>"text/html", 'htm'=>"text/html", 'htl'=>"text/html", 'rtx'=>"text/richtext", 'etx'=>"text/x-setext",
            'tsv'=>"text/tab-separated-values", 'mpeg'=>"video/mpeg", 'mpg'=>"video/mpeg", 'mpe'=>"video/mpeg", 'avi'=>"video/x-msvideo",
            'qt'=>"video/quicktime", 'mov'=>"video/quicktime", 'moov'=>"video/quicktime", 'movie'=>"video/x-sgi-movie", 'au'=>"audio/basic",
            'snd'=>"audio/basic", 'wav'=>"audio/x-wav", 'aif'=>"audio/x-aiff", 'aiff'=>"audio/x-aiff", 'aifc'=>"audio/x-aiff",
            'swf'=>"application/x-shockwave-flash", 'myz'=>"application/myz"
        );

        $ext = substr(strrchr($file,'.'),1);
        $type = @$mimes[$ext];


        unset($mimes);
        return $type;
    }

    /*
    邮件helo命令
    */
    private function helo(){

        if($this->status != '220'){

            $this->error('连接服务器失败!');
            return false;
        }

        return $this->sendSmtpMsg('HELO '.$this->server);

    }


    /*
    登录
    */
    private function login(){

        if($this->status!='250'){

            $this->error('helo邮件指令失败!');
            return false;
        }

        $this->sendSmtpMsg('AUTH LOGIN');
        if($this->status!='334'){
            $this->error('AUTH LOGIN 邮件指令失败!');
            return false;
        }

        $this->sendSmtpMsg(base64_encode($this->mailUser));
        if($this->status!='334'){
            $this->error('邮件登录用户名可能不正确!'.$this->readSmtpMsg());
            return false;
        }

        $this->sendSmtpMsg(base64_encode($this->mailPwd));
        if($this->status !='235'){
            $this->error('邮件登录密码可能不正确!');
            return false;
        }

        return true;

    }

    private function getMailUser($to){

        $temp = explode('@',$to);
        return $temp[0];
    }
    /*
    异常报告
    */
    private function error($exception){
        if($exception){
            file_put_contents(APPROOT.'/data/log/mail_log.txt',date("Y-m-d H:i:s").':'.$exception.'|status:'.$this->status."\n",FILE_APPEND);
            return;
        }
        /*if(class_exists('error') && is_object($GLOBALS['error'])){
            $GLOBALS['error']->showErrorStr($exception,'javascript:',false);
        }else{
            throw new Exception($exception);
        }*/
    }

}