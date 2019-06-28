<?php
include_once(APPROOT.'/lib/ServerAPI.php');
class Rongyun {
    public $rongyunObj = null;
    public function Rongyun(){

    }

    public function newRongyunServer($appKey,$appSer)
    {
        $obj = new ServerAPI($appKey,$appSer);
        $this->rongyunObj = $obj;
        return $obj;
    }

}