<?php
class Pemail{

    /**
     * @param $sendTo  发送对象   多个邮箱 数组array('a@163.com','2@163.com')
     * @param $title   邮件标题
     * @param $body    邮件内容
     * @param $atts    附件 多个附件，数组 array('1,txt','2.txt')
     * @return bool
     */
    public function sendMail($sendTo,$title,$body,$atts)
    {
        include_once APPROOT.'/lib/PHPMailer/PHPMailerAutoload.php';
        $mailConf = G('config');
        $mailConf = $mailConf['smtp'];

        $mail = new PHPMailer;
        //$mail->SMTPDebug = 3;
        $mail->isSMTP();
        $mail->Charset  = 'UTF-8';
        $mail->Host     = $mailConf['server'];
        $mail->SMTPAuth = $mailConf['auth'];
        $mail->Username = $mailConf['username'];
        $mail->Password = $mailConf['password'];
        if($mailConf['ssl']) {
            $mail->SMTPSecure = 'ssl';
        }
        $mail->Port = $mailConf['port'];
        $mail->setFrom($mailConf['mailfrom'], $mailConf['sitename']);
        if(is_array($sendTo))
        {
            foreach($sendTo as $item){
                $mail->addAddress($item);
            }
        }else{
            $mail->addAddress($sendTo);
        }
        //$mail->addAddress('ellen@example.com');               // Name is optional
        //$mail->addReplyTo('hailingr@163.com', 'Information');
        /*$mail->addCC('cc@example.com');
        $mail->addBCC('bcc@example.com');*/
        if($atts)
        {
            if(is_array($atts)){
                foreach($atts as $item){
                    $mail->addAttachment($item);
                }
            }else{
                $mail->addAttachment($atts);
            }
        }
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body    = $body;
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        //发送邮件
        if(!$mail->send()) {
           /* echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;*/
            return false;
        }else{
            return true;
            /*echo 'Message has been sent';*/
        }
    }
}