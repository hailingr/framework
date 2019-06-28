<?php
class ToolsController extends Controller
{
    public function imgCode()
    {
        vCode(4);
    }

    /**
     * 上传图片
     */
    public function uploadImg()
    {
        $mo     = model('user.pub');
        $user   = $mo->isLogin();
        $type   = $this->getRequest('type');

        $mo     = model('tools.upload');
        $rst    = $mo->upload('ufile',$type);

        $result = array('statusCode'=>300,'message'=>'');
        if($rst['status']){
            $result['statusCode'] = 200;
            $result['message']    = '上传成功';
            $result['filename']    = $rst['url'];
        }else{
            $result['message']    = $rst['info'];
        }
        exit(json_encode($result));
    }

    public function city()
    {
        include_once(APPROOT.'/data/area.php');
        $mo = model('def.index');
        foreach($areaData as $v1)
        {
            $provice = trim($v1[0]);

            $data = array();
            $data['country'] = 'cn';
            $data['name']    = $provice;
            $inID = $mo->table('tb_provinces')->insert($data);

            if($provice=='北京' || $provice=='天津' || $provice=='上海' || $provice=='重庆')
            {
                $data = array();
                $data['name'] = $provice;
                $data['province_id'] = $inID;
                $data['province_name'] = $provice;
                $mo->table('tb_citys')->insert($data);

            }else
            {
                $citys = $v1[1];
                foreach($citys as $city)
                {
                    $cityName = trim($city[0]);
                    $data = array();
                    $data['name'] = $cityName;
                    $data['province_id'] = $inID;
                    $data['province_name'] = $provice;
                    $mo->table('tb_citys')->insert($data);
                }
            }
        }
        dump($areaData);

    }

    /**
     * 写入用户的坐标
     */
    public function setLatLng()
    {
        $openid = $this->getRequest('openid');
        $lat    = $this->getRequest('lat');
        $lng    = $this->getRequest('lng');
        $mo     = model('def.index');
        $data   = array();
        $data['lat'] = $lat;
        $data['lng'] = $lng;
        $mo->table('tb_members')->where(array('openid'=>$openid))->update($data);
        exit('ok');
    }

    /**
     * 百度图片上传
     */
    public function baiduUpload()
    {
        //登陆判断
        $user     = model('user.info');
        $userInfo = $user->loginIs();
        //登陆判断
        if($userInfo)
        {
            $mo     = model('tools.upload');
            $return = $mo->baiduUpload();
            exit($return);
        }else{

        }
    }

    /**
     * 消息（系统通知，邮件通知，微信模板通知）发送实例
     */
    public function sendTest()
    {
        $msg = model('msg');

        //发送系统通知消息
        /*$msgInfo = '小伙，你有一个来之成都新都区大丰镇支局的订单,点击<a class="J_menuItem" href="./?m=plat.server.oversee&a=oversee'.rand(0,99).'" style="padding:0;">质量跟踪</a> 前往处理吧！！';
        $to  = 'jhl';
        $to = array('zzf','jhl','ls0');
        $rst    = $msg->sendSysNotice($msgInfo,$to,'admin');*/
        //+-------------------------------------

        //发送微信模板消息
        /*$sendTo = 'hailingr@foxmail.com'; //多个邮箱，array('hailingr@foxmail.com','hailingr@163.com')
        $sendTo = array('hailingr@foxmail.com','821273766@qq.com');
        $title  = date("Y-m-d H:i:s").'hailingr：你好,这是标题';
        $body   = '<span style="color: red">红色Html展示</span>你好，亲爱的朋友<h1>你可真好</h1>';
        $attr   = 'root.txt'; //多个附件，array('root.txt','favicon.ico');
        $attr   = array('root.txt','favicon.ico');
        $rst    = $msg->sendEmail($sendTo,$title,$body,$attr);*/
        //+-------------------------------------

        //发送微信模板通知消息
        $tplId = 'zj1awozZjfhZYt74QcHVCpPxIy5_J1WGzK1Bm-j_Oi8';//这个id为服务评价消息模板
        $to    = 'objVGwrExzSzZYvhAZHt5LYSH6mA';
        $url   = 'http://pro.scydgl.com/?m=plat.userServer.comment&a=comment&serverUserId=1&day=2016-09-14';
        $data = array(
            'first'=>'邀请您参与今日行程服务评价',
            'keyword1'=>'10001',
            'keyword2'=>date("Y-m-d"),
            'remark'=>'今天的行程快结束了，邮电旅游邀请您对今日旅程中我们的服务进行评价，如有服务不好的地方，明天我们即刻改进...'
        );
        /*$tplId = '1s6oVQFG5HDjPc68TZBddvc6r_JSB8L3uCselzp6zys';//这个id为审核结果消息模板
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'keyword1'=>'信息ID',
            'keyword2'=>'详情',
            'remark'=>'尾部说明'
        );*/

        /*$tplId = '984jn66BB-TaSrTFtUYFXkEHBaLkDD5ClzReI0M98Og';//这个id为退款通知消息模板
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'reason'=>'退款原因',
            'refund'=>'退款金额',
            'remark'=>'尾部说明'
        );*/

        /*$tplId = 'Zn-f7Lx_KrbZPK8eXCCRygAJAx_YQpyI2A5wZv6QJzk';//这个id为订单提醒通知消息模板
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'keyword1'=>'订单号',
            'keyword2'=>'操作人',
            'keyword3'=>'时间',
            'remark'=>'尾部说明'
        );*/

        /*$tplId = 'J9duXgZaOYfRRcorFgAwHe2jljP3NULeDhD1QxbXVZY';//这个id为预订成功通知消息模板
        $to    = '';
        $url   = '';
        $data = array(
            'first'=>'标题抬头',
            'type'=>'对象类型（供应商名称/门店名称/客户名称）',
            'name'=>'对象名称',
            'productType'=>'预订服务',
            'serviceName'=>'产品名称',
            'time'=>'时间',
            'remark'=>'尾部说明'
        );*/

        $rst = $msg->sendWxTplMsg($to,$tplId,$data,$url);
        //+-------------------------------------




        dump($rst);
    }

    public function test()
    {
        dump($_SERVER);
        /*echo 'send email';
        $mail = import('email','lib',true);
        $rest = $mail->sendMail('hailingr@foxmail.com',date("Y-m-d H:i:s").'hailingr：你好,这是标题','<span style="color: red">红色Html展示</span>你好，亲爱的朋友<h1>你可真好</h1>','root.txt');
        //dump($rest);
        echo '<hr>';

        echo strstr('hailinqu','hailin');*/
        //返回记录条数
        $mo     = model('user.info','mysql');
        //$sql    = "select * FROM core_user where id<>1";
        $nums   = $mo->table('core_user')->where('id<>1')->count();
        echo '<hr>';
        echo $nums;
    }

    /**
     * 发送邮件列子
     */
    public function sendEmail()
    {
        /*//$mail = import('email','lib',true);
        $sendTo = 'hailingr@foxmail.com';
        $title  = date("Y-m-d H:i:s").'hailingr：你好,这是标题';
        $body   = '<span style="color: red">红色Html展示</span>你好，亲爱的朋友<h1>你可真好</h1>';
        $attr   = 'root.txt';
        //$rest = $mail->sendMail($sendTo,$title,$body,$attr);
        $mail = model('msg');
        echo 33333333333;
        $rest = $mail->sendEmail($sendTo,$title,$body,$attr);

        dump($rest);*/

        //发送email
        $mod = model('resque','redis');
        $mod->workTask('email',function($group,$task){
            $mail = import('email','lib',true);
            $sendTo = $task['sendTo'];
            $title  = $task['title'];
            $body   = $task['body'];
            $atts   = $task['atts'];
            $rest   = $mail->sendMail($sendTo,$title,$body,$atts);
            echo '+-----------'."\n";
            echo $group.'|'.date("Y-m-d H:i:s").'|'.'result|'.json_encode($rest,JSON_UNESCAPED_UNICODE).'|Task|'.json_encode($task,JSON_UNESCAPED_UNICODE)."\n";

            //dump($group);
            //dump($task);
            return true;
        });
    }

    /**
     * 创建邮箱发送信息
     */
    public function sendEmailc()
    {
        //$mail = import('email','lib',true);
        $sendTo = '25071591@qq.com';
        //$sendTo = 'hailingr@foxmail.com';
        $title  = date("Y-m-d H:i:s").'hailingr：你好,这是标题';
        $body   = '<span style="color: red">红色Html展示</span>你好，亲爱的朋友<h1>你可真好</h1>';
        $attr   = 'root.txt';
        //$rest = $mail->sendMail($sendTo,$title,$body,$attr);
        $mail = model('msg');
        echo 33333333333;
        $rest = $mail->sendEmail($sendTo,$title,$body,$attr);

        dump($rest);


    }



    /**
     * 发送手机短信
     */
    public function sendSms()
    {
        $msg = model('msg');
        $from= 'admin';
        $to  = array('13540633386');
        $msgt = '你好，尊敬的用户，邮政旅游邀请你参加今天的活动。。。';
        $rst  = $msg->sendSmsMsg($from,$to,$msgt);
        dump($rst);
    }

    /**
     * 发送微信通知消息
     */
    public function sendWx()
    {
        $wx = import('weixin','lib',true);
        //发送微信模板消息,不同的消息模板对应的sendData明细字数不一样

        //发送服务评价通知
        $tplId = 'zj1awozZjfhZYt74QcHVCpPxIy5_J1WGzK1Bm-j_Oi8';
        $to    = '';
        $url   = '';
        $sendData = array(
            'first'=>'',
            'keyword1'=>'',
            'keyword2'=>'',
            'remark'=>''
        );
        $return = $wx->sendTplInfo($tplId,$to,$url,$sendData);
        dump($return);
    }

    /**
     * 环信接口相关实列
     *
     */
    public function sendHx()
    {
        //获取环信配置
        $huanxinOpt = G('config');
        $huanxinOpt = $huanxinOpt['huanxin'];
        $userName   = 'firn';
        //载入环信api接口
        import('huanxing','lib',false);
        $h  = new Huanxing($huanxinOpt);
        //调用列子i=35发送文本效率列子
        $i=35;
        switch($i){
            case 10://获取token
                $token=$h->getToken();
                dump($token);
                break;
            case 11://创建单个用户$userName,
                dump($h->createUser($userName,G('config')['huanxin']['default_pwd']));
                break;
            case 12://创建批量用户
                dump($h->createUsers(array(
                    array(
                        "username"=>"zhangsan",
                        "password"=>"123456"
                    ),
                    array(
                        "username"=>"lisi",
                        "password"=>"123456"
                    )
                )));
                break;
            case 13://重置用户密码
                dump($h->resetPassword("zhangsan","123456"));
                break;
            case 14://获取单个用户
                dump($h->getUser("zhangsan"));
                break;
            case 15://获取批量用户---不分页(默认返回10个)
                dump($h->getUsers());
                break;
            case 16://获取批量用户----分页
                $cursor=$h->readCursor("userfile.txt");
                dump($h->getUsersForPage(10,$cursor));
                break;
            case 17://删除单个用户
                dump($h->deleteUser("zhangsan"));
                break;
            case 18://删除批量用户
                dump($h->deleteUsers(2));
                break;
            case 19://修改昵称
                dump($h->editNickname("zhangsan","小B"));
                break;
            case 20://添加好友------400
                dump($h->addFriend("zhangsan","lisi"));
                break;
            case 21://删除好友
                dump($h->deleteFriend("zhangsan","lisi"));
                break;
            case 22://查看好友
                dump($h->showFriends("zhangsan"));
                break;
            case 23://查看黑名单
                dump($h->getBlacklist("zhangsan"));
                break;
            case 24://往黑名单中加人
                $usernames=array(
                    "usernames"=>array("wangwu","lisi")
                );
                dump($h->addUserForBlacklist("zhangsan",$usernames));
                break;
            case 25://从黑名单中减人
                dump($h->deleteUserFromBlacklist("zhangsan","lisi"));
                break;
            case 26://查看用户是否在线
                dump($h->isOnline("zhangsan"));
                break;
            case 27://查看用户离线消息数
                dump($h->getOfflineMessages("zhangsan"));
                break;
            case 28://查看某条消息的离线状态
                dump($h->getOfflineMessageStatus("zhangsan","77225969013752296_pd7J8-20-c3104"));
                break;
            case 29://禁用用户账号----
                dump($h->deactiveUser("zhangsan"));
                break;
            case 30://解禁用户账号-----
                dump($h->activeUser("zhangsan"));
                break;
            case 31://强制用户下线
                dump($h->disconnectUser("zhangsan"));
                break;
            case 32://上传图片或文件
                dump($h->uploadFile("./resource/up/pujing.jpg"));
                //dump($h->uploadFile("./resource/up/mangai.mp3"));
                //dump($h->uploadFile("./resource/up/sunny.mp4"));
                break;
            case 33://下载图片或文件
                dump($h->downloadFile('01adb440-7be0-11e5-8b3f-e7e11cda33bb','Aa20SnvgEeWul_Mq8KN-Ck-613IMXvJN8i6U9kBKzYo13RL5'));
                break;
            case 34://下载图片缩略图
                dump($h->downloadThumbnail('01adb440-7be0-11e5-8b3f-e7e11cda33bb','Aa20SnvgEeWul_Mq8KN-Ck-613IMXvJN8i6U9kBKzYo13RL5'));
                break;
            case 35://发送文本消息
                $from='admin';
                $target_type="users";
                //$target_type="chatgroups";
                $target=array("admin","ls","dr");
                //$target=array("122633509780062768");
                $content='小伙，你有一个订单<a class="J_menuItem" href="./?m=plat.server.oversee&a=oversee'.rand(0,99).'" data-index="0">1质量跟踪</a>';
                //发送系统消息，必须设置扩展属性“msg=sys”
                $ext['msg']     =   "sys";
                $ext['other']   =   "other";
                dump($h->sendText($from,$target_type,$target,$content,$ext));
                break;
            case 36://发送透传消息
                $from='admin';
                $target_type="users";
                //$target_type="chatgroups";
                $target=array("firn","lisi","wangwu");
                //$target=array("122633509780062768");
                $action="Hello HuanXin!";
                $ext['a']="a";
                $ext['b']="b";
                dump($h->sendCmd($from,$target_type,$target,$action,$ext));
                break;
            case 37://发送图片消息
                $filePath="./resource/up/pujing.jpg";
                $from='admin';
                $target_type="users";
                $target=array("firn","lisi");
                $filename="pujing.jpg";
                $ext['a']="a";
                $ext['b']="b";
                dump($h->sendImage($filePath,$from,$target_type,$target,$filename,$ext));
                break;
            case 38://发送语音消息
                $filePath="./resource/up/mangai.mp3";
                $from='admin';
                $target_type="users";
                $target=array("zhangsan","lisi");
                $filename="mangai.mp3";
                $length=10;
                $ext['a']="a";
                $ext['b']="b";
                dump($h->sendAudio($filePath,$from="admin",$target_type,$target,$filename,$length,$ext));
                break;
            case 39://发送视频消息
                $filePath="./resource/up/sunny.mp4";
                $from='admin';
                $target_type="users";
                $target=array("zhangsan","lisi");
                $filename="sunny.mp4";
                $length=10;//时长
                $thumb='https://a1.easemob.com/easemob-demo/chatdemoui/chatfiles/c06588c0-7df4-11e5-932c-9f90699e6d72';
                $thumb_secret='wGWIyn30EeW9AD1fA7wz23zI8-dl3PJI0yKyI3Iqk08NBqCJ';
                $ext['a']="a";
                $ext['b']="b";
                dump($h->sendVedio($filePath,$from="admin",$target_type,$target,$filename,$length,$thumb,$thumb_secret,$ext));
                break;
            case 40://发文件消息

                break;
            case 41://获取app中的所有群组-----不分页（默认返回10个）
                dump($h->getGroups());
                break;
            case 42:////获取app中的所有群组--------分页
                $cursor=$h->readCursor("groupfile.txt");
                dump($h->getGroupsForPage(2,$cursor));
                break;
            case 43://获取一个或多个群组的详情
                $group_ids=array("1445830526109","1445833238210");
                dump($h->getGroupDetail($group_ids));
                break;
            case 44://创建一个群组
                $options ['groupname'] = "group001";
                $options ['desc'] = "this is a love group";
                $options ['public'] = true;
                $options ['owner'] = "zhangsan";
                $options['members']=Array("fengpei","lisi");
                dump($h->createGroup($options));
                break;
            case 45://修改群组信息
                $group_id="124113058216804760";
                $options['groupname']="group002";
                $options['description']="修改群描述";
                $options['maxusers']=300;
                dump($h->modifyGroupInfo($group_id,$options));
                break;
            case 46://删除群组
                $group_id="124113058216804760";
                dump($h->deleteGroup($group_id));
                break;
            case 47://获取群组中的成员
                $group_id="122633509780062768";
                dump($h->getGroupUsers($group_id));
                break;
            case 48://群组单个加人------
                $group_id="122633509780062768";
                $username="lisi";
                dump($h->addGroupMember($group_id,$username));
                break;
            case 49://群组批量加人
                $group_id="122633509780062768";
                $usernames['usernames']=array("wangwu","lisi");
                dump($h->addGroupMembers($group_id,$usernames));
                break;
            case 50://群组单个减人
                $group_id="122633509780062768";
                $username="test";
                dump($h->deleteGroupMember($group_id,$username));
                break;
            case 51://群组批量减人-----
                $group_id="122633509780062768";
                //$usernames['usernames']=array("wangwu","lisi");
                $usernames='wangwu,lisi';
                dump($h->deleteGroupMembers($group_id,$usernames));
                break;
            case 52://获取一个用户参与的所有群组
                dump($h->getGroupsForUser("zhangsan"));
                break;
            case 53://群组转让
                $group_id="122633509780062768";
                $options['newowner']="lisi";
                dump($h->changeGroupOwner($group_id,$options));
                break;
            case 54://查询一个群组黑名单用户名列表
                $group_id="122633509780062768";
                dump($h->getGroupBlackList($group_id));
                break;
            case 55://群组黑名单单个加人-----
                $group_id="122633509780062768";
                $username="lisi";
                dump($h->addGroupBlackMember($group_id,$username));
                break;
            case 56://群组黑名单批量加人
                $group_id="122633509780062768";
                $usernames['usernames']=array("lisi","wangwu");
                dump($h->addGroupBlackMembers($group_id,$usernames));
                break;
            case 57://群组黑名单单个减人
                $group_id="122633509780062768";
                $username="lisi";
                dump($h->deleteGroupBlackMember($group_id,$username));
                break;
            case 58://群组黑名单批量减人
                $group_id="122633509780062768";
                $usernames['usernames']=array("wangwu","lisi");
                dump($h->deleteGroupBlackMembers($group_id,$usernames));
                break;
            case 59://创建聊天室
                $options ['name'] = "chatroom001";
                $options ['description'] = "this is a love chatroom";
                $options ['maxusers'] = 300;
                $options ['owner'] = "zhangsan";
                $options['members']=Array("man","lisi");
                dump($h->createChatRoom($options));
                break;
            case 60://修改聊天室信息
                $chatroom_id="124121390293975664";
                $options['name']="chatroom002";
                $options['description']="修改聊天室描述";
                $options['maxusers']=300;
                dump($h->modifyChatRoom($chatroom_id,$options));
                break;
            case 61://删除聊天室
                $chatroom_id="124121390293975664";
                dump($h->deleteChatRoom($chatroom_id));
                break;
            case 62://获取app中所有的聊天室
                dump($h->getChatRooms());
                break;
            case 63://获取一个聊天室的详情
                $chatroom_id="124121939693277716";
                dump($h->getChatRoomDetail($chatroom_id));
                break;
            case 64://获取一个用户加入的所有聊天室
                dump($h->getChatRoomJoined("zhangsan"));
                break;
            case 65://聊天室单个成员添加--
                $chatroom_id="124121939693277716";
                $username="zhangsan";
                dump($h->addChatRoomMember($chatroom_id,$username));
                break;
            case 66://聊天室批量成员添加
                $chatroom_id="124121939693277716";
                $usernames['usernames']=array('wangwu','lisi');
                dump($h->addChatRoomMembers($chatroom_id,$usernames));
                break;
            case 67://聊天室单个成员删除
                $chatroom_id="124121939693277716";
                $username="zhangsan";
                dump($h->deleteChatRoomMember($chatroom_id,$username));
                break;
            case 68://聊天室批量成员删除
                $chatroom_id="124121939693277716";
                //$usernames['usernames']=array('zhangsan','lisi');
                $usernames='zhangsan,lisi';
                dump($h->deleteChatRoomMembers($chatroom_id,$usernames));
                break;
            case 69://导出聊天记录-------不分页
                $ql="select+*+where+timestamp>1435536480000";
                dump($h->getChatRecord($ql));
                break;
            case 70://导出聊天记录-------分页
                $ql="select+*+where+timestamp>1435536480000";
                $cursor=$h->readCursor("chatfile.txt");
                //dump($h->$cursor);
                dump($h->getChatRecordForPage($ql,10,$cursor));
                break;
        }
    }

    public function baoxian()
    {
        $webserver = model('api.baoxian');

        $rst       = $webserver->addPolicy();

        dump($rst);

    }

    public function phpinfo()
    {
        phpinfo();
    }

    public function weixinurl()
    {
        $storeId = '122';   //门店id
        $userId  = '0';     //柜员id
        $isFollow= 'yes';     //是否强制关注,yes 需要强制关注，no不需要关注
        $goUrl   = 'http://pro.scydgl.com/?m=weixin.wap.index';  //跳转url地址
        $baseUrl = 'http://pro.scydgl.com/';
        echo '首页:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.mine&a=person';
        echo '<br/>个人中心:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.product&a=order_list';
        echo '<br/>我的订单:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.mine&a=stroke';
        echo '<br/>我的足迹:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.index&a=bournType&typeId=3';
        echo '<br/>国内游:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);


        $goUrl   = $baseUrl.'?m=weixin.wap.index&a=bournType&typeId=2';
        echo '<br/>省内游:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.index&a=bournType&typeId=5';
        echo '<br/>自由行:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.index&a=nearby&typeId=4';
        echo '<br/>出境游:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);

        $goUrl   = $baseUrl.'?m=weixin.wap.getRedPacket&a=attention';
        $isFollow= 'no';
        echo '<br/>活动抵用卷:'.'http://pro.scydgl.com/?m=weixin.bind.wap&storeId='.$storeId.'&userId='.$userId.'&isFollow='.$isFollow.'&goUrl='.base64_encode($goUrl);
    }

    public function showFun()
    {
        $mod = model('test');
        //$mod->getOne()->limit()fdfg

        //$mod->
    }

}