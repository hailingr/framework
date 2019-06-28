<?php

/**
 * Class ToolsUploadModel
 * 文件上传模式
 * Create Hailin<2016-05-12>
 * hailingr@foxmail.com
 */
class ToolsUploadModel extends Model
{
    /**
     * 通用的文件上传处理方法
     * @param $filename    上传提交的表单文件域名称
     * @param $type        类型
     * @return array
     */
    public function upload($filename,$type='')
    {
        $upfile = $_FILES[$filename];
        if (empty($typelist)) {
            //允许的文件类型
            $typelist = array("image/gif", "image/jpg", "image/jpeg", "image/png", "text/plain");
        }
        //指定上传文件的保存路径（相对路径）
        $tempDir = '/data/upload/'.date("Ymd");
        //指定上传文件的保存路径（绝对路径）
        $path= APPROOT.$tempDir;
        if(!file_exists($path)) mkdir($path);
        //定义存放返回结果的数组
        $res = array("status" => false);
        //2.过滤上传文件件的错误号
        if ($upfile["error"] > 0) {
            switch ($upfile["error"]) {
                case 1:
                    $res["info"] = "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制";
                    break;
                case 2:
                    $res["info"] = "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项";
                    break;
                case 3:
                    $res["info"] = "文件只有部分被上传";
                    break;
                case 4:
                    $res["info"] = "没有文件被上传";
                    break;
                case 6:
                    $res["info"] = "找不到临时文件夹。";
                    break;
                case 7:
                    $res["info"] = "文件写入失败";
                    break;
                default:
                    $res["info"] = "未知错误！";
                    break;
            }
            return $res;
        }

        //3.本次文件大小的限制
        if ($upfile["size"] > 100000) {
            $res["info"] = "上传文件过大！";
            return $res;
        }

        //4. 过滤类型
        //dump($upfile["type"]);
        if (!in_array($upfile["type"], $typelist)) {
            $res["info"] = "上传类型不符！" . $upfile["type"];
            return $res;
        }

        //5. 初始化下信息(为图片产生一个随机的名字)
        $fileinfo = pathinfo($upfile["name"]);
        do {
            $newfile = date("YmdHis") . rand(1000, 9999) . "." . $fileinfo["extension"];//随机产生一个的文件名
        } while (file_exists($newfile));
        //获取的上传文件的访问http路径
        $domain = 'http://'.$_SERVER["HTTP_HOST"];
        $url    = $domain.$tempDir.'/'.$newfile;
        //6. 执行上传处理
        if (is_uploaded_file($upfile["tmp_name"])) {
            if (move_uploaded_file($upfile["tmp_name"], $path . "/" . $newfile)) {
                //传递了type参数后文件将被上传到腾讯云
                if($type){
                    //将上传成功后的文件名赋给返回数组
                    $form       = $path . "/" . $newfile;
                    $fileNames  = explode('/',$form);
                    $fileName   = $fileNames[count($fileNames)-1];
                    $typeName   = '';

                    if($type=='postimg')    $typeName = '/post/image/';
                    if($type=='postvideo')  $typeName = '/post/video/';
                    if($type=='tuya1')      $typeName = '/graffiti/background/';
                    if($type=='tuya2')      $typeName = '/graffiti/pendant/';
                    if($type=='banner')     $typeName = '/banner/';
                    if($type=='other')      $typeName = '/other/';
                    //if($type=='tuya2')      $typeName = '/graffiti/pendant/';

                    $to         = $typeName.$fileName;

                    $uploadRet  = uploadQQYunFile($form,$to);
                    $uploadRet  = json_decode($uploadRet,true);
                    $file       = $uploadRet['data']['access_url'];
                    if($file){
                        $res["info"] = $newfile;
                        $res["status"] = true;
                        $res['url']  = $file;
                    }
                }else{
                    //将上传成功后的文件名赋给返回数组
                    $res["info"] = $newfile;
                    $res["status"] = true;
                    $res['url']  = $url;
                }
                return $res;
            } else {
                $res["info"] = "权限不足,上传文件写入失败！";
            }
        } else {
            $res["info"] = "不是一个上传的文件！";
        }
        return $res;
    }

    /**
     * 用于百度上传组件
     * 前端采用百度上传组建
     */
    public function baiduUpload()
    {
        //文件头
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        //未提交直接退出
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        //调试信息
        if (!empty($_REQUEST['debug'])) {
            $random = rand(0, intval($_REQUEST['debug']));
            if ($random === 0) {
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }
        //十分钟超时
        @set_time_limit(10 * 60);
        //上传临时处理路径设置
        $targetDir = APPROOT . '/data/upload/upload_tmp';
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }
        //图片保存路径设置
        $uploadDir = APPROOT . '/data/upload/' . date('Ymd');

        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir);
        }

        //是否删除临时文件
        $cleanupTargetDir = true;
        //临时文件保留时间
        $maxFileAge = 5 * 3600; // Temp file age in seconds
        //设置文件名称
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }
        $fileNameExt = substr(strrchr($fileName, '.'), 1);
        //文件类型判断
        $limitExt = array('gif', 'jpg', 'jpeg', 'png', 'txt', 'zip', 'rar', 'gz', 'bz2', 'swf','doc','docx','xlsx','xls');
        if (in_array($fileNameExt, $limitExt) === false) {
            exit('非法的文件上传！');
        }

        $fileName = md5($fileName);
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName . '.' . $fileNameExt;
        $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName . '.' . $fileNameExt;

        $httpFileUrl = '/data/upload/' . date('Ymd') . '/' . $fileName . '.' . $fileNameExt;

        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        //删除临时文件
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                return ('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "临时文件删除失败"}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }
        // Open temp file
        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            return ('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                return ('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "文件保存失败"}, "id" : "id"}');
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                return ('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                return ('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");
        $index = 0;
        $done = true;
        for ($index = 0; $index < $chunks; $index++) {
            if (!file_exists("{$filePath}_{$index}.part")) {
                $done = false;
                break;
            }
        }
        if ($done) {
            if (!$out = @fopen($uploadPath, "wb")) {
                return ('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);
        }
        // Return Success JSON-RPC response
        return ('{"jsonrpc" :"2.0","result" :null,"id":"id","httpUrl":"' . $httpFileUrl . '"}');

    }


    /**
     * 用于富文本编辑器KinEditor编辑器的文件上传接口
     */
    public function kinderUpload()
    {
        header('Content-type: text/html; charset=UTF-8');
        //文件保存目录路径
        $savePath = APPROOT . '/data/upload/';
        if (!file_exists($savePath)) mkdir($savePath);
        $savePath = $savePath . date("Ymd") . '/';
        if (!file_exists($savePath)) mkdir($savePath);

        //文件访问URL目录
        $saveUrl = str_replace(APPROOT, '', $savePath);

        //定义允许上传的文件扩展名
        $extArr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png'),
            'flash' => array('swf'),
            'media' => array('swf'),
            'file' => array('txt', 'zip', 'rar', 'gz', 'bz2'),
        );

        //最大文件大小
        $maxSize = 1000000;

        $savePath = realpath($savePath) . '/';
        //PHP上传失败返回的信息
        if (!empty($_FILES['imgFile']['error'])) {
            switch ($_FILES['imgFile']['error']) {
                case '1':
                    $error = '超过php.ini允许的大小。';
                    break;
                case '2':
                    $error = '超过表单允许的大小。';
                    break;
                case '3':
                    $error = '图片只有部分被上传。';
                    break;
                case '4':
                    $error = '请选择图片。';
                    break;
                case '6':
                    $error = '找不到临时目录。';
                    break;
                case '7':
                    $error = '写文件到硬盘出错。';
                    break;
                case '8':
                    $error = 'File upload stopped by extension。';
                    break;
                case '999':
                default:
                    $error = '未知错误。';
            }
            return array('error' => 1, 'message' => $error);
        }
        //有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $fileName = $_FILES['imgFile']['name'];
            //服务器上临时文件名
            $tmpName = $_FILES['imgFile']['tmp_name'];
            //文件大小
            $fileSize = $_FILES['imgFile']['size'];
            //检查文件名
            if (!$fileName) {
                $error = ("请选择文件。");
                return array('error' => 1, 'message' => $error);
            }
            //检查目录
            if (@is_dir($savePath) === false) {
                $error = ("上传目录不存在。");
                return array('error' => 1, 'message' => $error);
            }
            //检查目录写权限
            if (@is_writable($savePath) === false) {
                $error = ("上传目录没有写权限。");
                return array('error' => 1, 'message' => $error);
            }
            //检查是否已上传
            if (@is_uploaded_file($tmpName) === false) {
                $error = ("上传失败。");
                return array('error' => 1, 'message' => $error);
            }
            //检查文件大小
            if ($fileSize > $maxSize) {
                $error = ("上传文件大小超过限制。");
                return array('error' => 1, 'message' => $error);
            }
            //检查文件类型
            $dirName = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
            if (empty($extArr[$dirName])) {
                $error = ("目录名不正确。");
                return array('error' => 1, 'message' => $error);
            }
            /*dump($extArr);
            dump($_REQUEST);
            exit;*/
            //获得文件扩展名
            $temp_arr = explode(".", $fileName);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);
            //检查扩展名
            if (in_array($file_ext, $extArr[$dirName]) === false) {
                $error = ("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $extArr[$dirName]) . "格式。");
                return array('error' => 1, 'message' => $error);
            }
            //新文件名
            $newFileName = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
            //移动文件
            $filePath = $savePath . $newFileName;
            if (move_uploaded_file($tmpName, $filePath) === false) {
                $error = ("上传文件失败。");
                return array('error' => 1, 'message' => $error);
            }
            @chmod($filePath, 0644);
            $fileUrl = $saveUrl . $newFileName;
            return array('error' => 0, 'url' => $fileUrl);

        }
    }
}