 **Firn's 快速开发框架 简要说明，需要有一定的MVC概念，阅读起来更容易理解** 
------------------------------------------------

作者 hailin <hailingr@foxmail.com>
更新 ChengDu(2017-05-09)

------------------------------------------------
框架核心目录 _core_
------------------------------------------------
特色 
---------
   - A：数据库操作的时候支持即时切换不同的数据库服务器(分布式数据库)，数据模型中采用$mode->setDns(2)进行切换
   - B：控制器类名需要添加后缀“Controller”，数据模型类名需要添加后缀“Model”
   - C：“.”规则，系统中的类文件（控制器类，视图文件，模型类，lib类）在调用的时候均支持名称中使用“.”符号
   - D：“.”符号可以理解为路径“/”，具体使用可见下方的“简要说明”章节
   - E：支持MongoDB
   - F：配合apache的rewrite模块，URL中可以省略参数m和a，即：/?m=d.a.gs&a=show可以简化为 /d.a.gs/show/
   - G：支持Redis
   - H：系统自动过滤风险输入（GET，REQUEST，COOKIE）
------------------------------------------------
简要使用说明
---------
- A：控制器类，lib类，模型类，视图模板文件均支持树状层级调用
- B：层级调用（命名）采用在相应的类名中加入“.”符号实现
- C：列子如下

```
1. 启动入口  http://example.com/?m=d.a.gs          
   映射到控制器“/controller/d/a/gs.controller.php”   
   对应控制器类名“DAGsController”
2. 视图模板  $this->template('hailin.ha.h')        
   映射到模板文件“/view/hailin/ha/h.htm”             
   模板文件采用php自身作为解释引擎“<?php echo $para;?>”,变量需要在控制器中采用$this->assign('para','hello')方可使用
3. 数据模型  $m1 = model('hailin.ha');             
   映射到模型“/model/hailin/ha.model.php”           
   对应模型类名”HailinHaModel“  
```  



- D：core::$G 全局对象,操作方法”G()“;


 ```
 /**
   *$val 为空的时候是获取
   *@param $key     键
   *@param $val     值
  **/ 
function G($key,$val=''){...}
```

------------------------------------------------
数据模型的相关操作(Mysql)
---------
- A：配置文件中配置可以多个数据库连接参数（“config/core.config.php”）

 
```
 'dbns' => array(
                 1=> array(
                     'dbdrive'    => 'mysql',
                     'dbhost'     =>  '192.168.1.2',
                     'dbname'     =>  'www.xxx.com',
                     'dbport'     =>  '3306',
                     'dbuser'     =>  'xxx',
                     'dbpwd'      =>  'xxx',
                     'dbpccon'    =>  false
                 ),
                  2=> array(
                      'dbdrive'    => 'mysql',
                      'dbhost'     =>  'localhost',
                      'dbname'     =>  '',
                      'dbport'     =>  '3306',
                      'dbuser'     =>  'root',
                      'dbpwd'      =>  'root',
                      'dbpccon'    =>  false
                  )
              ),
```

  
- B：分布式切换（可以在程序层实现读写等分离）
   

> 1. 在数据模型中需要切换到指定的”2“数据库服务器上去的时候调用方法$mode->setDns(2)
> 2. 需要切换其他的数据库服务器的时候再次调用$mode->setDns(1)


- C：数据模型支持链式写法，在链式写法中field,order,where,group支持参数为数组
 列如：


```
 1. 查询 $m1->table('tableName')->where('sss=www')->jion('left jion aa on (a=b)')->field('a,b,c')->limit(1,1)->order('sd asc,fg desc')->group('sss,ff,fff')->get();
 2. 更新 $m1->table('tableName')->where(array('id'=>1))->update(array('name'=>'hailin'))
 3. 插入 $m1->table('tableName')->insert(array('name'=>'hailin','year'=>'12'))
 4. 原生 $m1->query($sql);$m1->get($sql)
 5. 其他可详看Model类
```



   
------------------------------------------------
MongoDB操作
---------
- A：在配置文件中配置好相应的mongo服务器
- B：在数据模型初始化的时候增加参数“mongodb” 例如：model('user','mongodb');
- C：MongoDB的操作直接采用php的mongo扩展中的相应Api；
```
例如：$mo = model('user','mongodb');
$mo->mongo即为mogodb对象
```

------------------------------------------------
Redis操作
---------
- A：在配置文件中配置好相应的redis服务器
- B：在数据模型初始化的时候增加参数“redis” 例如：model('user','redis');
- C：Redis的操作直接采用php的Redis扩展中的相应Api；
```
例如：$mo = model('user','redis');
$mo->redis即为redis对象
```

------------------------------------------------
核心函数
---------
- import                 类载入
- controller             控制器载入
- model                  模型载入
- dump                   格式打印对象

------------------------------------------------
更多工具函数
---------
- 详见_core_/lib/lib.function.php


------------------------------------------------
其他（SQL计算两点之间距离）
---------

```
 SELECT address,lng,lat,
 ROUND(6378.138*2*ASIN(SQRT(POW(SIN(({29.64068}*PI()/180-lat*PI()/180)/2),2)+COS({29.64068}*PI()/180)*COS(lat*PI()/180)*POW(SIN(({102.126997}*PI()/180-lng*PI()/180)/2),2)))*1000)
 AS distance
 FROM sline_geo
 ORDER BY distance ASC
 LIMIT 316
 
 round(6378.138*2*asin(sqrt(pow(sin((29.643573*pi()/180-lat*pi()/180)/2),2)+cos(29.643573*pi()/180)*cos(lat*pi()/180)* pow(sin((102.124376*pi()/180-lng*pi()/180)/2),2)))*1000)

```
