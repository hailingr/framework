<?php
/**
 * 时间格式化工作处理类
 * 作者:Hailin<hailingr@foxmail.com>
 * 创建:2014.03.18 chengdu.china
 */
class Ptime
{
    /**
     * 返回当前日期的星期几属性
     * @param $day
     */
    function getWeekDay($day)
    {
        $index = date("w",strtotime($day));
        $nums = array(0=>'周日',1=>'周一',2=>'周二',3=>'周三',4=>'周四',5=>'周五',6=>'周六');
        return $nums[$index];
    }

    /**
     * 返回中文的一，二，三....
     * @param num $
     */
    function getChinestNum($num)
    {
        $nums = array(0 => '零', 1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '七', 8 => '八', 9 => '九',
            10 => '十', 11 => '十一', 12 => '十二', 13=>'十三',14=>'十四',15=>'十五',16=>'十六',17=>'十七',18=>'十八',19=>'十九',
            20=>'二十',21=>'二十一');
        return $nums[$num];
    }
    /**
     * 取得指定数量的未来、以前的月份
     * @param string $type
     * @param int $nums
     */
    function getMonths($type='+',$nums=6)
    {
        $month = array();
        $month[] = date("Y-m");
        if($type=='+'){
            $beginDate= date('Y-m-01', strtotime(date("Y-m-d")));
            $endDate  = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));

            if($endDate==date("Y-m-d")){
                $month = array();
                $nums = $nums + 1;
            }
            for($i=1;$i<$nums;$i++)
            {
                $str    = date("Y-m",strtotime("$beginDate +".$i." month"));
                /*echo $str;
                echo '<br>';*/
                $month[] = $str;
            }
        }else{
            for($i=1;$i<$nums;$i++)
            {
                $beginDate= date('Y-m-01', strtotime(date("Y-m-d")));
                $month[] = date("Y-m",strtotime("$beginDate -".$i." month"));
            }
        }
        return $month;
    }

    /**
     * 获得指定月的具体日期，并且以周的方式返回
     */
    function getMonthDays($year,$month)
    {
        $year   = $year;   //获得年份, 例如： 2006
        $month  = $month;  //获得月份, 例如： 04
        $day    = date ( 'j' );    //获得日期, 例如： 3
        $firstDay       = date ( "w", mktime ( 0, 0, 0, $month, 1, $year ) );
        //获得当月第一天
        $daysInMonth    = date ( "t", mktime ( 0, 0, 0, $month, 1, $year ) );
        //获得当月的总天数
        //echo $daysInMonth;
        $tempDays       = $firstDay + $daysInMonth;   //计算数组中的日历表格数
        $weeksInMonth   = ceil ( $tempDays/7 );   //算出该月一共有几周（即表格的行数）
        //创建一个二维数组
        $counter = 0 ;
        for($j = 0; $j < $weeksInMonth; $j ++) {
            for($i = 0; $i < 7; $i ++) {
                $counter ++;
                $week [$j] [$i] = $counter;
                //offset the days
                $week [$j] [$i] -= $firstDay;
                if (($week [$j] [$i] < 1) || ($week [$j] [$i] > $daysInMonth)) {
                    $week [$j] [$i] = "";
                }
            }
        }
        return $week;
    }

    /**
     * 传入原来的时间戳，会自动返回距离当前时间的格式化时间差
     * @param $time      原来的时间
     * @return string
     */
    function  time2Units  ($time,$later='')
    {
        if($later=='')
        {
            $later = time();
        }
        $time    = $later-$time;
        if($time<=1) return '刚刚';
        $year    =  floor ( $time  /  60  /  60  /  24  /  365 );
        $time   -=  $year  *  60  *  60  *  24  *  365 ;
        $month   =  floor ( $time  /  60  /  60  /  24  /  30 );
        $time   -=  $month  *  60  *  60  *  24  *  30 ;
        $week    =  floor ( $time  /  60  /  60  /  24  /  7 );
        $time   -=  $week  *  60  *  60  *  24  *  7 ;
        $day     =  floor ( $time  /  60  /  60  /  24 );
        $time   -=  $day  *  60  *  60  *  24 ;
        $hour    =  floor ( $time  /  60  /  60 );
        $time   -=  $hour  *  60  *  60 ;
        $minute  =  floor ( $time  /  60 );
        $time   -=  $minute  *  60 ;
        $second  =  $time ;
        $elapse  =  '' ;
        $unitArr  = array( '年'   => 'year' ,  '个月' => 'month' ,   '周' => 'week' ,  '天' => 'day' ,
            '小时'=> 'hour' ,  '分钟' => 'minute' ,  '秒' => 'second'
        );
        foreach (  $unitArr  as  $cn  =>  $u  )
        {
            if ($$u > 0)
            {
                $elapse  = $$u . $cn ;
                break;
            }
        }
        return  $elapse.'前' ;
    }
}