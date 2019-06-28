<?php
class ApiBaoxianModel extends Model
{
    public $TUser_Pswd      = '123456';
    public $TUser_LoginName = '51251013';
    public $TAgency_ID      = '4429';


    /**
     * 生成投保的用户xml列表
     * @param $day
     * @param string $users
     * @return string
     */
    public function tplXml($day,$users='')
    {
        $day = date("Y-m-d",strtotime($day)+24*3600);
        if(!$users)
        {
            $users = $this->table('order_line a,order_line_visitor b,pro_line c')
                ->where("a.id=b.orderId and a.proId=c.id and a.startDay='".$day."' and b.isSendBaoXian=0")
                ->field("b.*,a.startDay,a.endDay,a.days,a.title,c.destTypeId")
                ->order("a.id ASC")
                ->limit(0,1000)->get();

        }else{
            $users = $this->table('order_line a,order_line_visitor b')
                ->where("a.id=b.orderId and a.startDay='".$day."' and b.id in ($users)  and b.isSendBaoXian=0")
                ->field("b.*,a.startDay,a.endDay,a.days,a.title")
                ->order("a.id ASC")
                ->limit(0,1000)->get();
        }

        //dump($users);
        if(!empty($users))
        {
            $xml       = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
            $xml      .= '<ApplyInfo>';
            $xml      .= '<GeneralInfo>';
	        $xml      .= '<UUID>YDLY'.date("YmdHis").'</UUID>'; //旅行社id+yyyyMMddhhmmssyyy
            $xml      .= '<TAgency_ID>'.$this->TAgency_ID.'</TAgency_ID>';
	        $xml      .= '<TUser_LoginName>'.$this->TUser_LoginName.'</TUser_LoginName>';
            $xml      .= '<TUser_Pswd>'.$this->TUser_Pswd.'</TUser_Pswd>';
            $xml      .= '</GeneralInfo>';
            $xml      .= '<PolicyInfos>';
            foreach($users as $item)
            {
                $isWai     = 0;//是否是外宾
                if($item['destTypeId']=='4') $isWai = 1;
                $xml      .= '<PolicyInfo>';
                $xml      .= '<SerialNo>'.$item['id'].'</SerialNo>';
                $xml      .= '<PolicyNo></PolicyNo>';
                //旅游类型id，后续确认 （1 一般 68 中老年 ）
                $xml      .= '<Policy_TravelTypeID>1</Policy_TravelTypeID>';

                $xml      .= '<Policy_TouristGuide>导游</Policy_TouristGuide>';
                $xml      .= '<Policy_Fax>旅行社传真</Policy_Fax>';
                $xml      .= '<Policy_TouristRoutes>'.$item['title'].'</Policy_TouristRoutes>';
                //旅游形式id，后续确认（3 常规 4自驾游 5自由行）
                $xml      .= '<Policy_TravelFromID>3</Policy_TravelFromID>';

                /*$xml      .= '<Policy_IsHighRisk>0</Policy_IsHighRisk>';
                $xml      .= '<Policy_IsGzTj>1</Policy_IsGzTj>';*/

                $xml      .= '<Policy_TravelFromDate>'.date("Y/m/d",strtotime($item['startDay'])).'</Policy_TravelFromDate>';
                $xml      .= '<Policy_TravelEndDate>'.date("Y/m/d",(strtotime($item['endDay'])-24*3600)).'</Policy_TravelEndDate>';
                //是否是外宾
                if($isWai){
                    $leiNums = 0;
                    $waiNums = 1;
                }else{
                    $leiNums = 1;
                    $waiNums = 0;
                }
                $leiType  = 41; //内宾方案
                $waiType  = 56; //外宾方案

                $xml      .= '<Policy_Num_DomesticGuests>'.$leiNums.'</Policy_Num_DomesticGuests>'; //内宾人数
                $xml      .= '<Policy_Num_ForeignGuest>'.$waiNums.'</Policy_Num_ForeignGuest>';
                $xml      .= '<Policy_Type_DomesticGuestsID>'.$leiType.'</Policy_Type_DomesticGuestsID>';
                $xml      .= '<Policy_Type_ForeignGuestID>'.$waiType.'</Policy_Type_ForeignGuestID>';

                $xml      .= '<TravellerInfos>';
                $xml      .= '<TravellerInfo>';

                $xml      .= '<Traveller_Name>'.$item['name'].'</Traveller_Name>';
                $xml      .= '<Traveller_Sex></Traveller_Sex>';
                $xml      .= '<Traveller_Birthday></Traveller_Birthday >';
                $xml      .= '<Traveller_Country>'.$item['type'].'</Traveller_Country>';
                $xml      .= '<Traveller_Identity>'.$item['coder'].'</Traveller_Identity>';
                $xml      .= '<Traveller_Tel>'.$item['tel'].'</Traveller_Tel>';
                $xml      .= '</TravellerInfo>';
                $xml      .= '</TravellerInfos>';
                $xml      .= '</PolicyInfo>';
            }
            $xml      .= '</PolicyInfos>';
            $xml      .= '</ApplyInfo>';
        }
        return $xml;
    }
    /**
     * 投保接口
     * @param $xml
     */
    public function addPolicy()
    {
        $http             = import('http','lib',true);
//        $postUrl = 'http://192.168.2.106:90/webService/Oe_Service.asmx?op=addPolicy';
        //$postUrl = 'http://vip-china.cn:90/webService/Oe_Service.asmx?op=addPolicy';
        $client = new SoapClient("http://vip-china.cn:90/WebService/Oe_Service.asmx?wsdl",array('encoding'=>'utf8'));
        //$funcs  = $client->__getFunctions();
        //$rows   = $client->getPolicyType();
        $xml = $this->tplXml(date("Y-m-d"));
        echo $xml;

        $para   = array('param0' => $xml);
        $rows   = $client->addPolicy($para);
        dump($rows);

        /*exit;
        echo $postUrl;
        $ch = curl_init();
        $header[] = "Content-type: text/xml";//定义content-type为xml
        curl_setopt($ch, CURLOPT_URL, $postUrl); //定义表单提交地址
        curl_setopt($ch, CURLOPT_POST, 1);   //定义提交类型 1：POST ；0：GET
        curl_setopt($ch, CURLOPT_HEADER, 1); //定义是否显示状态头 1：显示 ； 0：不显示
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//定义请求类型
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);//定义是否直接输出返回流
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml); //定义提交的数据，这里是XML文件
        $result = curl_exec($ch);
        curl_close($ch);//关闭

        dump($result);*/
    }
}