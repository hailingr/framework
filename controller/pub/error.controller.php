<?php

class PubErrorController extends Controller
{
    public function error404(){
        $this->template('pub.404');
    }

    public function error500(){
        $this->template('pub.500');
    }
}