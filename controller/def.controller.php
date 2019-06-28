<?php
class DefController extends Controller
{
    /**
     * 默认进入主页面
     */
    public function auth()
    {

        $auth = model('user.auth','mysql');
        //$auth->getUserAuth(1);
        $authStatus = $auth->checkUserAuth(1,'sys','user');
        echo $authStatus;
    }

    public function index()
    {
        //dump($_REQUEST);
        //controller('plat.def','index');
        //dump($_REQUEST);
        $auth = model('msg','mysql');
        $auth->table('base_msg')->where("id=1")->update(array('`to`'=>',qdb,cpb,'));
        dump($auth->lastSql());

        echo $hailin;
        hailin();


        $auth2 = model('msg','mysql');

        $auth2->table('base_msg')->get();
        dump($auth2->lastSql());

    }





}