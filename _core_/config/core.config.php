<?php
/**
 * 配置文件
 */
$config = array(
    'defaultCon'        => 'm',
    'defaultAct'        => 'a',

    'dbns'              => array(
                       1=> array(
                           'dbdrive'    => 'mysql',
                           'dbhost'     =>  '192.168.2.2',
                           'dbname'     =>  'www.u6u.com',
                           'dbport'     =>  '3306',
                           'dbuser'     =>  'u6u_net',
                           'dbpwd'      =>  'u6u_net',
                           'dbpccon'    =>  false
                       )
    ),
);

return $config;