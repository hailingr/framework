<?php
class PublicPubController extends Controller
{

    //头部
    public function header()
    {
        $this->template('public.header');
    }
    //底部
    public function footer()
    {
        $this->template('public.footer');
    }

}