<?php
class AdminController extends Controller
{
    /**
     * 后台首页演示
     */
    public function index()
    {
        //获取板块列表
        $plates = model('admin.mod')->getPlates();


        $this->assign('plates',$plates);
        $this->template('admin.main');
    }

    /**
     * 后台右侧首页
     */
    public function right()
    {
        echo 'The Framework Index ....';
    }

    /**
     * 登陆
     */
    public function login()
    {

    }

    /**
     * 修改密码
     */
    public function pwd()
    {
        $this->template('admin.user.pwd');
    }

    /**
     * 修改密码模拟成功
     */
    public function resetPwd()
    {
        echo '{
                  "statusCode": 200,
                  "title": "操作提示",
                  "message": "恭喜你，操作成功！当statusCode为200时，返回成功提示信息。"
                }';
    }

    /**
     * 模拟获取模块的菜单
     */
    public function getModMenus()
    {
        $plate = $this->getRequest('plate','');
        $menus = model('admin.mod')->getModMenus($plate);
        //dump($menus);
        exit(json_encode($menus));

    }

}