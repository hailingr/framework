/**
 * 移动Wap程序初始化定义
 * 本方法依赖于Framework7，需要在应用本文件前先引用framework7
 * Create By Hailin（hailingr@foxmail.com）
 * */

//定义frameork7初始化对象参数
var appIniObj = {
 // App root element
  root: '#app',
  // App Name
  name: 'My App',
  // App id
  id: 'com.myapp.item1',
  // Enable swipe panel
  panel: {
    swipe: 'left',
  },
  // Add 增加旅游，本项配置可以单独设置为一个独立的路由设置文件
  routes: [
    {
	  name: 'about our',
      path: '/about/(.*)',
      url: '/mobile/about.html',
	  on:{
		pageInit:function(e, page){
				console.log(api.getRouteParas());
                console.log(page);
                var app2 = new Framework7();
                app2.request.postJSON('http://shopapi.kyxsys.com/api/banner', { username:'foo', password: 'bar' }, function (data) {
                    console.log(data);
                  });
				
			},
		}
    }
	
  ],
};

//初始化framework7
var app = new Framework7(appIniObj);
//创建主视图
var mainView = app.views.create('.view-main',{pushState:true});
//+-------------------------------------------------
//##
//自定义本地接口对象
var api = {};
//获取路由URL的参数值
api.getRouteParas = function()
{
	return app.utils.parseUrlQuery(window.href);
}
//结束
//+-------------------------------------------------

//+-------------------------------------------------
/**
 * 自定义JSON本地储存
 *  var jsonData = [{b:2},{a:1}];
 *  api.setItem('key',jsonData);
 *  api.getItem('key');
 */
//获取
api.getDataItem = function(key){
    var rest = null;
    var val = window.localStorage[key];
    if(val){
        var rest = JSON.parse(val);
    }
    return rest;
};
//设置
api.setDataItem = function(key,val){
    val = JSON.stringify(val);
    window.localStorage[key] = val;
};
//移除
api.removeDataItem = function(key){
    window.localStorage.removeItem(key);
};
//结束
//+-------------------------------------------------



//+-------------------------------------------------
/**
 * 生成随机字符串
 * @param min
 * @param max
 * @param randomFlag
 * @returns {string}
 */
api.randomStr = function (min,max,randomFlag){
    var str = "",
        range = min,
        arr = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    // 随机产生
    if(!randomFlag) randomFlag = true;
    if(!max) max = min;
    if(randomFlag){
        range = Math.round(Math.random() * (max-min)) + min;
    }
    for(var i=0; i<range; i++){
        pos = Math.round(Math.random() * (arr.length-1));
        str += arr[pos];
    }
    str = str.toUpperCase();
    JsonStorage.setItem('randomStr',str);
    return str;
}
//结束
//+-------------------------------------------------

//+-------------------------------------------------
/**
 * Ajax方式请求远程API接口
 * @param url   远程接口地址
 * @param data  请求参数对 {key:value}
 * @param func  回调函数
 * @param rtype 返回类型 默认Json
 * @param type  请求类型 默认POST
 * @param async 是否异步 默认异步
 */
api.load = function (url,data,func,rtype,type,async) {
    if(!type) type = 'post';
    if(!rtype) rtype = 'json';
    if(async==undefined) async = true;
    url = '/api'+url;
    //参数对处理
    var tempData = {};
    for(var key in data)
    {
        //console.log(data);
        var tempKey = data[key];
        var isJson = typeof(tempKey) == "object" && Object.prototype.toString.call(tempKey).toLowerCase() == "[object object]" && !tempKey.length;
        var isArray = (Object.prototype.toString.call(tempKey) == '[object Array]');
        //console.log(isJson);
        //console.log(isArray);
        if(isJson || isArray) tempData[key] = JSON.stringify(tempKey);
        else tempData[key] = tempKey;
    }
    //console.log(tempData);
    $.ajax({
        url:url,
        data:tempData,
        //dataType:rtype,
        type:type,
        async:async,
        success:function (redata) {
            //console.log(redata);
            if(rtype=='json') {
                var isjson = typeof(redata) == "object" && Object.prototype.toString.call(redata).toLowerCase() == "[object object]" && !redata.length;
                if(!isjson) redata = JSON.parse(redata);
            }

            //+-----------------------------
            //特殊URL处理
            //过期
            if(redata.status==-2)
            {

            }
            //失败
            if(redata.status==1 && url!= '/api/appservice/basic/login' && url!= '/api/appservice/basic/resetPwd' && url!= '/api/appservice/basic/bind' ){
                $.alert(redata.msg);
                return false;
            }

            if(redata.msg=='系统异常')
            {
                //top.layer.msg(redata.msg+JSON.stringify(redata.data),{time:1500});
                $.alert(redata.msg);
            }

            //console.log(redata);
            //调起回调函数
            func(redata);
        },error: function(XMLHttpRequest, textStatus, errorThrown) {
            //错误处理
            console.log(XMLHttpRequest.status);
            console.log(XMLHttpRequest.readyState);
            console.log(textStatus);
            $.alert('服务器请求异常，请稍候重试');
        }
    });
};

//结束
//+-------------------------------------------------

//+-------------------------------------------------

//结束
//+-------------------------------------------------

//+-------------------------------------------------
