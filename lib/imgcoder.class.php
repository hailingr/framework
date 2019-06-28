<?php
/**
 * $ImageCode = new ImageCode;
 * $ImageCode -> Show(130, 35, 'Num.ttf', 'code');
 * 图片验证码文件，加减计算方式
 */
 class Imgcoder{

     private $Jiashu  = 0;        //加数或者减数
     private $JianShu = 0;        //被加数或者被减数
     private $YunSuan = '';       //运算符
     private $DeShu   = 0;        //得数
     private $String  = '';       //字符串样式
     private $Img;                //图片对象
     private $Width   = 100;      //图片宽度
     private $Height  = 50;       //图片高度
     private $Ttf     = 'Num.ttf';//字体文件
     private $Session = 'code';   //Session变量

     private function JiaShu(){
         header('Content-type:image/png');
         $this -> Jiashu  = rand(1, 10);
         $this -> JianShu = rand(1, 10);
         $this -> YunSuan= $this -> Jiashu > $this -> JianShu ? '-' : '+';
         $this -> DeShu   = $this -> Jiashu > $this -> JianShu ? $this -> Jiashu - $this -> JianShu : $this -> Jiashu + $this -> JianShu;
     }

     public function Show( $W = 100, $H = 50, $T = 'Num.ttf', $Code = 'code' ){
         $this -> JiaShu();
         $this -> String = $this -> Jiashu . $this -> YunSuan . $this -> JianShu . '= ? ';
         $this -> Width  = $W;
         $this -> Height = $H;
         $this -> Ttf    = $T;
         $this -> Session= $Code;
         session_start();
         $_SESSION[$this -> Session] = $this -> DeShu;
         $this -> Images();
     }

     private function Images(){
         $this -> Img = imagecreate($this -> Width, $this -> Height);
         $background_color = imagecolorallocate ($this -> Img, 255, 255, 255);
         imagecolortransparent($this -> Img, $background_color);
         imagettftext($this -> Img, 14, 0, 1, 20, imagecolorallocate ($this -> Img, 0, 0, 0), $this -> Ttf, $this -> String );
         $this -> EchoImages();
     }

     private function EchoImages(){
         imagepng($this -> Img);
         imagedestroy($this -> Img);
     }

 }


