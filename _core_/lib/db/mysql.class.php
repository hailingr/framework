<?php
class DbMysql
{
    public   $_con          = null;
    public   $_cons         = array();
    private  $_table        = '';
    private  $_fields       = '*';
    private  $_where        = '';
    private  $_jion         = '';
    private  $_group        = '';
    private  $_order        = '';
    private  $_limit        = '';
    public   $sql           = '';
    public   $debug         = '';
    public   $pcconnect     = false;

    public function connect($host,$port,$database,$user,$pwd,$coder,$debug,$dnsid)
    {
        $this->debug = $debug;
        if ($this->_con == null) {
            if($this->pcconnect){
                $_con = @mysql_pconnect($host.':'.$port, $user, $pwd);
            }else
            {
                $_con = @mysql_connect($host.':'.$port, $user, $pwd);
            }

            $this->_cons[$dnsid] = $_con;
            if ($_con == FALSE)
            {
                if($debug)
                {
                    echo("Connect to db server failed.".mysql_error($_con));
                    exit;
                }
            }
            if($database)
            {
                @mysql_select_db($database, $_con);
            }
            mysql_query("SET NAMES $coder",$_con);
        }
    }

    /**
     * 重置连接资源
     * @param $dnsid
     */
    public function setDns($dnsid)
    {
        @$this->_con = $this->_cons[$dnsid];
    }


    public function table($tablename)
    {
        $this->_table = $tablename;
        return $this;
    }
    public function jion($jions)
    {
        if($jions)
        {
            $this->_jion = ' '.$jions.' ';
        }
        return $this;
    }
    public function field($field)
    {
        if(is_array($field))
        {
            $_fields = implode(',',$field);
        }else
        {
            $_fields= $field;
        }
        if($_fields)
        {
            $this->_fields = ' '.$_fields.' ';
        }
        return $this;
    }
    public function where($where)
    {
        if (is_array($where))
        {
            $_where = $this->_getWhereString($where);
        }else
        {
            $_where = $where;
        }
        if($_where)
        {
            $this->_where = ' where '.$_where.' ';
        }
        return $this;
    }
    public function group($group)
    {
        if (is_array($group))
        {
            $_group = $this->_getParamValString($group,'');
        }else
        {
            $_group = $group;
        }
        if($_group)
        {
            $this->_group = ' group by '.$_group.' ';
        }
        return $this;
    }
    public function order($order)
    {
        if (is_array($order))
        {
            $_order = $this->_getKeyValString($order, ",",' ');
        }else
        {
            $_order = $order;
        }
        if($_order)
        {
            $this->_order = ' order by '.$_order.' ';
        }
        return $this;
    }
    public function limit($start,$limit)
    {
        $this->_limit = ' limit '.$start.','.$limit.' ';
        return $this;
    }

    public function query($sql)
    {
        $this->sql = $sql;
        $result = @mysql_query($sql,$this->_con);
        if ($result)
        {
            $result = $result;
        }else
        {
            if($this->debug)
            {
                echo 'Sql Error:['.$sql.']';
                exit;
            }
        }
        return $result;
    }

    public function get($sql=null)
    {
        if(!$sql)
        {
            $sql = "select ".$this->_fields." from ".$this->_table.$this->_jion.$this->_where.$this->_group.$this->_order.$this->_limit;
        }
        $result = $this->query($sql);
        $ret    = array();
        if($result)
        {
            while ($row = mysql_fetch_array($result,MYSQL_ASSOC))
            {
                $ret[] = $row;
            }
        }
        $this->clearsql();
        return $ret;
    }
    /**
     * 返回满足条件的记录条数
     * @param null $sql
     * @return int
     */
    public function count($sql=null)
    {
        $nums = 0;
        if(!$sql)
        {
            $sql = "select count(*) as nums from ".$this->_table.$this->_jion.$this->_where.$this->_group.$this->_order;
        }else{
            $sql = preg_replace("/^(.*)[\s]from[\s]/isU",'select count(*) as nums from ',$sql);
        }
        $result = $this->query($sql);
        $ret    = array();
        if($result)
        {
            $row = mysql_fetch_array($result,MYSQL_ASSOC);
            $nums= $row['nums'];
        }
        $this->clearsql();
        return $nums;
    }
    public function getOne($sql=null)
    {
        if(!$sql)
        {
            $sql = "select ".$this->_fields." from ".$this->_table.$this->_jion.$this->_where.$this->_group.$this->_order.$this->_limit;
        }
        $result = $this->query($sql);
        if($result)
        {
            $row    = mysql_fetch_array($result,MYSQL_ASSOC);
        }else
        {
            $row    = array();
        }
        $this->clearsql();
        return $row;
    }

    public function insert($params)
    {
        if ($params == null || !is_array($params))
        {
            return -1;
        }
        //dump($params);
        $keys = $this->_getParamKeyString($params);
        $vals = $this->_getParamValString($params);
        //dump($vals);
        $sql = "insert into " . $this->_table . "(" . $keys . ") values(" . $vals . ")";
        $result = @mysql_query($sql,$this->_con);
        $this->sql = $sql;
        if (!$result)
        {
            if($this->debug)
            {
                echo 'Sql Error:['.$sql.']';
                exit;
            }
            return -1;
        }
        return @mysql_insert_id($this->_con);
    }

    public function update($params, $where = null)
    {
        if ($params == null || !is_array($params))
        {
            return -1;
        }
        $upvals = $this->_getUpdateString($params);
        if($where)
        {
            $wheres = " where ".$this->_getWhereString($where);
        }else
        {
            $wheres = $this->_where;
        }
        $sql = "update " . $this->_table . " set " . $upvals . " " . $wheres;
        $this->sql = $sql;
        $result = @mysql_query($sql,$this->_con);
        if (!$result)
        {
            if($this->debug)
            {
                echo 'Sql Error:['.$sql.']';
                exit;
            }
            return -1;
        }
        return @mysql_affected_rows($this->_con);
    }

    public function delete($where=null)
    {
        if($where)
        {
            $wheres = " where ".$this->_getWhereString($where);
        }else
        {
            $wheres = $this->_where;
        }

        $sql = "delete from " . $this->_table . $wheres;
        $this->sql = $sql;
        $result = @mysql_query($sql,$this->_con);
        if (!$result) {
            if($this->debug)
            {
                echo 'Sql Error:['.$sql.']';
                exit;
            }
            return -1;
        }
        return @mysql_affected_rows($this->_con);
    }

    protected function _getParamKeyString($params)
    {
        $keys = array_keys($params);
        return implode(",", $keys);
    }

    protected function _getParamValString($params,$dou="'",$split=',')
    {
        $vals = array_values($params);
        foreach($vals as $k=>$val)
        {
            $vals[$k] = $this->checkSafeValue($val);
        }
        return "$dou" . implode("{$dou}{$split}{$dou}", $vals) . "$dou";
    }

    /**
     * mysql字符串安全处理
     * @param $val
     * @return string
     */
    protected function checkSafeValue($val)
    {
        if (get_magic_quotes_gpc())
        {
            $val = stripslashes($val);
        }
        $val =  mysql_real_escape_string($val,$this->_con);
        return $val;
    }


    private function _getUpdateString($params)
    {
        $sql = "";
        if (is_array($params)) {
            $sql = $this->_getKeyValString($params, ",");
        }else
        {
            $sql = $params;
        }
        return $sql;
    }

    private function _getWhereString($params)
    {
        $sql = "";
        if (is_array($params)) {
            $sql = $this->_getKeyValString($params, " and ");
        }else
        {
            $sql = $params;
        }
        return $sql;
    }

    private function _getKeyValString($params, $split,$li='=')
    {
        $str = "";
        $danyinghao = "'";
        if($li==' ') $danyinghao = '';
        if (is_array($params)) {
            $paramArr = array();
            foreach ($params as $key => $val) {
                $valstr = $this->checkSafeValue($val);
                if (is_string($val)) {
                    $valstr = "$danyinghao" . $this->checkSafeValue($val) . "$danyinghao";
                }
                $paramArr[] = $key . "$li" . $valstr;
            }

            $str = $str . implode($split, $paramArr);
        }
        return $str;
    }

    /**
     * 清空查询
     * */
    private function clearsql()
    {
          $this->_fields       = '*';
          $this->_where        = '';
          $this->_jion         = '';
          $this->_group        = '';
          $this->_order        = '';
          $this->_limit        = '';
    }

    public function release()
    {
        @mysql_close($this->_con);
    }
}