<?php
/**
 * 配置文件
 */
$config = array(
    'defaultCon'        => 'm',
    'defaultAct'        => 'a',
    'gzip'              => false,
    /*微信配置*/
    'weixin'            => array(
                        'TOKEN'         => '1151804698',
                        'APPID'         => 'wx773b4135a3aa7a62',
                        'APPSECRET'     => 'c79a7bee0ee655b9e0e467f435e7e277',
                        'APPTOKEN'      => '',
                        'EncodingAESKey'=> 'lejqYkVo8ib6SEudluDgpeJGfnVrLtbhLKn7lqcKN3S',
                        'cacheType'     => 'mysql'
    ),
    /*数据库配置*/
    'dbns'              => array(
                       1=> array(
                           'dbdrive'    => 'mysqli',
                           'dbhost'     => '127.0.0.1',
//                           'dbhost'     => '192.168.2.2',
//                           'dbhost'     => '125.64.14.15',
//                           'dbuser'     => 'ydly',
//                           'dbpwd'      => '!@ydly@!',
                           'dbname'     => '_core_',
                           'dbport'     => '3306',
                           'dbuser'     => 'root',
                           'dbpwd'      => 'root',
                           'dbpccon'    => false
                       ),
                       2=> array(
                           'dbdrive'    => 'mongodb',
                           'dbhost'     => '',
                           'dbname'     => '',
                           'dbport'     => '',
                           'dbuser'     => '',
                           'dbpwd'      => '',
                           'dbpccon'    => false
                       ),
                        3=> array(
                            'dbdrive'    => 'redis',
                            'dbhost'     => '',
                            'dbname'     => '',
                            'dbport'     => '6379',
                            'dbuser'     => '',
                            'dbpwd'      => '',
                            'dbpccon'    => false
                        )
    ),
    /*环信配置*/
    'huanxin'           => array(
                        'client_id'      => 'YXA6nSEgMG25EeavgqMurK8P2w',
                        'client_secret'  => 'YXA6wfFXLASlyHZDjMfUDRZ7p1sEzeg',
                        'org_name'       => 'wwwu6ucom',
                        'app_name'       => 'ydly',
                        'default_pwd'    => '123'
    ),
    /*融云配置*/
    'rongyun'          => array(
                        'AppKey'=>'qd46yzrf44y4f',
                        'AppSecret'=>'dyYIiDfqbx'
    ),
    /*百度地图配置*/
    'bmap'              => array(
                        'ak'            => 'MasrxSBSfRTUNMnck3fejCBc7amXYjPn'
    ),
    /*smtp配置*/
    'smtp'             => array(
                        /*'sitename'      =>'邮电旅游',
                        'state'         => 1,
                        'server'        => 'smtp.163.com',
                        'port'          => 25,
                        'auth'          => 1,
                        'username'      => 'hailingr@163.com',
                        'password'      => 'jianghailin1',
                        'charset'       => 'utf8',
                        'mailfrom'      => 'hailingr@163.com',
                        'ssl'           => false*/
                        'sitename'      =>'邮电旅游',
                        'state'         => 1,
                        'server'        => 'smtp.exmail.qq.com',
                        'port'          => 465,
                        'auth'          => 1,
                        'username'      => 'server@scydgl.com',
                        'password'      => 'Ss520123',
                        'charset'       => 'utf8',
                        'mailfrom'      => 'server@scydgl.com',
                        'ssl'           => true

    ),
    /*cookie域名配置*/
    'cookeDomain'       => '',
);

return $config;
