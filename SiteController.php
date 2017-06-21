<?php

namespace app\controllers;

use app\components\client_db;
use app\models\Sessions;
use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
//        var_dump(file_get_contents('/home/bluedon/bdscan/bdshared/include/common.h'));die;
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'maxLength'=>4,
                'minLength'=>4,
                'fontFile'=>'@yii/captcha/georgia.ttf',
            ],
        ];
    }



    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
//        $content=file_get_contents('/home/bluedon/bdscan/bdshared/include/common.h');
//        var_dump(explode("\n",$content));die;
        global $db;
        $the_ver = $db->result_first("select pname from bd_sys_sysinfo WHERE id=1");
        if(!empty($the_ver)){
            if($the_ver=='蓝盾安全扫描系统'){
                $logo = "/resource/skin/blue/images/logo-putong.png";
                $ver_t  = "蓝盾安全扫描系统";
            }elseif ($the_ver=='蓝盾安全漏洞扫描系统'){
                $logo = "/resource/skin/blue/images/logo-shemi.png";
                $ver_t  = "蓝盾安全漏洞扫描系统";
            }else{
                $logo = "/resource/skin/blue/images/logo-putong.png";
                $ver_t  = "蓝盾安全扫描系统";
            }
        }else{
            $logo = "/resource/skin/blue/images/logo-putong.png";
            $ver_t  = "蓝盾安全扫描系统";
        }
        $last_logintime=$db->result_first("select last_logintime from bd_sys_user WHERE id={$_SESSION['userid']}");
//        var_dump($last_logintime);die;
        $this->layout=false;
        return $this->render('/site/index',[
            'logo'=>$logo,
            'ver_t'=>$ver_t,
            'last_logintime'=>date('Y-m-d H:i:s',$last_logintime)
        ]);
    }

    function actionHome(){
        $data = array();
        tpl('home', $data);
    }
    function actionHomeData(){
        $aJson=array();
        $aJson['cpurate'] = $this->getCPURate();
        $aJson['memrate'] = $this->getMemoryRate();
        $aJson['diskrate'] = $this->getDiskRate();
        $aJson['netrate'] = $this->getNetRate();
        echo json_encode($aJson);
        exit;
    }

    //登录
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        if( Yii::$app->request->isAjax ){

          //  var_dump($_POST);die;
        //if( isset($_GET["callback"]) ){
//            header("Access-Control-Allow-Origin: *");//同源策略 跨域请求 头设置
//            header('content-type:text/html;charset=utf8 ');
//            //获取回调函数名
//            $jsoncallback = htmlspecialchars($_REQUEST['callback']);//把预定义的字符转换为 HTML 实体。
//
//            $zhi = htmlspecialchars($_REQUEST['pwd']);
//
//            $arr=yii::$app->db->createCommand("select * from bd_sys_user")->queryAll();//用的like进行模糊查询
//
//            $json_data=json_encode($arr);//转换为json数据
//
//            //输出jsonp格式的数据
//            echo $jsoncallback . "(" . $json_data . ")";die;


            global $db,$timestamp,$exp_time,$act,$show;
            $ClienIp = onlineIp();
            $hdata =array();
            $data  = array('success'=>false, 'message'=>'登录失败');
            //登录IP限制
            $allow_login_ips = $db->fetch_first("SELECT allow_login_ips FROM ".getTable('scanset')." WHERE iId =1 "); //查找配置文件
            if(!empty($allow_login_ips['allow_login_ips']) && $allow_login_ips['allow_login_ips']!=$ClienIp){
                $data['success'] = false;
                $data['message'] = '被禁止的ip，不允许登录';
                $hdata['sDes'] = '用户登录';
                $hdata['user_name'] = filterStr($_POST['name']);
                $hdata['status'] ='被禁止的ip，不允许登录';
                $hdata['action'] = $act.'/'.$show;
                $sql = "SELECT role_id role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                $roleRes = $db->fetch_first($sql);
                $hdata['role'] = $roleRes['role'];
                saveOperationLog($hdata);
                echo json_encode($data);exit;
            }
            $name=Yii::$app->request->post('name');
            $pwd=Yii::$app->request->post('pwd');
            $vcode=Yii::$app->request->post('vcode');

            $hdata =array();
            $time = time();
            $lock = $db->fetch_first("SELECT * FROM ".getTable('userconfig')." WHERE iId =1 "); //查找配置文件
            $pwd = md5(md5($_POST['pwd']).$name);
            //echo $pwd;die;
            $row = $db->fetch_first("SELECT id, username,password,role_id as role,errors,locktime,status,allow_login_ips FROM ".getTable('user')." WHERE username='$name'");


//             var_dump(strtolower($vcode) ,$_SESSION['__captcha/site/captcha']);die;
            if(strtolower($vcode) != strtolower($_SESSION['__captcha/site/captcha'])){ //判断验证码
                if($lock['maxError'] !=0){
                    if($row['errors'] >= $lock['maxError']){
                        $data['message'] = "用户已锁,".$lock['lockTime']."分钟后重新登录";
                        $hdata['sRs'] ='失败，账号已被锁定';
                        $errorsql = "update ".getTable('user')." set errors = errors+1 ,locktime =".$time." where username = '".$name."'";
                        $db->query($errorsql);
                    }else{
                        $data['message'] = '验证码错误';
                        $hdata['sRs'] = '失败，验证码错误';
                    }
                }
                $errorsql = "Update ".getTable('user')." set errors = errors+1 where username = '".$name."'";
                $db->query($errorsql);
                $hdata['sDes'] = '用户登录';
                $hdata['username'] = filterStr($_POST['name']);
                $hdata['sAct'] = $act.'/'.$show;

                $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                $roleRes = $db->fetch_first($sql);
                $hdata['role'] = $roleRes['role'];

                saveOperationLog($hdata);
                echo json_encode($data);
                exit;
            }
            if(empty($row)){
                $data['message'] = '用户名或密码错误';
                $data['success'] = false;
                echo json_encode($data);
                exit;
            }else{
                if($row['status'] == 0){
                    $data['success'] = false;
                    $data['message'] = '用户已被禁止登录';
                    $hdata['sDes'] = '用户登录';
                    $hdata['username'] = filterStr($_POST['name']);
                    $hdata['sRs'] ='账号已被禁止';
                    $hdata['sAct'] = $act.'/'.$show;

                    $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                    $roleRes = $db->fetch_first($sql);
                    $hdata['role'] = $roleRes['role'];
                    saveOperationLog($hdata);
                    echo json_encode($data);
                    exit;
                }else{
                    $user_role = $db->fetch_first("select role_id as role from bd_sys_user where username='".filterStr($_POST['name'])."'");
                    if(empty($row['errors'])) $row['errors']=0;
                    //如果等于零表示系统处于初始化，不限制登录失败次数，如果登录失败次数做了限制
                    $locktime=$row['locktime']+$lock['lockTime']*60 -time();
                    if($lock['maxError'] != 0){
                        //判断是否超过最大错误数
                        if(intval($row['errors']) >= intval($lock['maxError'])){  //超过最大登录数
                            if($row['locktime'] ==0){
                                $errorsql = "update ".getTable('user')." set errors = errors+1 ,locktime =".$time." where username = '".$name."'";
                                $db->query($errorsql);
                                $data['success'] = false;
                                $data['message'] = "用户已锁,".$lock['lockTime']."分钟后重新登录";
                                $data['lockTime']=$locktime>0 ? $locktime :0;
                                $hdata['sDes'] = '用户登录';
                                $hdata['username'] = filterStr($_POST['name']);
                                $hdata['sRs'] ='账号已被锁定';
                                $hdata['sAct'] = $act.'/'.$show;

                                $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                                $roleRes = $db->fetch_first($sql);
                                $hdata['role'] = $roleRes['role'];

                                saveOperationLog($hdata);
                                echo json_encode($data);
                                exit;
                            }else{
                                if(time() < ($row['locktime']+$lock['lockTime']*60)){ //还在锁定时间
                                    // $errorsql = "update ".getTable('user')." set errors = errors+1 ,locktime =".$time." where username = '".$name."'";
                                    $errorsql = "update ".getTable('user')." set errors = errors+1  where username = '".$name."'";
                                    $db->query($errorsql);
                                    $data['success'] = false;
                                    $data['message'] = "用户已锁,".$lock['lockTime']."分钟后重新登录";
                                    $data['lockTime']=$locktime>0 ? $locktime :0;
                                    $hdata['sDes'] = '用户登录';
                                    $hdata['username'] = filterStr($_POST['name']);
                                    $hdata['sRs'] ='账号已被锁定';
                                    $hdata['sAct'] = $act.'/'.$show;

                                    $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                                    $roleRes = $db->fetch_first($sql);
                                    $hdata['role'] = $roleRes['role'];

                                    saveOperationLog($hdata);
                                    echo json_encode($data);
                                    exit;
                                }else{
                                    if($pwd == $row['password']){
                                        $uuid = md5(rand(0,999999));
                                        //var_dump($_SESSION);die;
                                        if(isset($_SESSION['username']) && isset($_SESSION['uuid'])){
                                            $db->execute("DELETE FROM "."bd_sys_sessions"." WHERE username='$name' and uuid='{$_SESSION['uid']}'");
                                        }
                                        $exptime=$timestamp+$exp_time;
                                        $sql= "INSERT INTO bd_sys_sessions set username='{$row['username']}',dateline='$timestamp',role='{$row['role']}',exptime=$exptime,uuid='{$uuid}'";
                                        //echo $sql;die;
                                        $db->execute($sql);
                                        $db->query("UPDATE ".getTable('user')." SET errors='0',locktime='0' WHERE username='$name'");
                                        $_SESSION['username'] = $row['username'];
                                        $_SESSION['userid'] = $row['id'];
                                        $_SESSION['waf_userid'] = $row['id'];
                                        $_SESSION['role'] = $row['role'];
                                        $_SESSION['expiretime'] = time()+$exp_time;
                                        $_SESSION['uuid'] = $uuid;

//                                        if($res = $this->isChangePass($name, $pwd)){
//                                            $data['message'] = $res['message'];
//                                            $hdata['sDes'] = $res['sDes'];
//                                            echo json_encode($data);
//                                            exit;
//                                        }

                                        $data['success'] = true;
                                        $data['message'] = '登录成功';
                                        $hdata['sDes'] = '用户登录';
                                        $hdata['username'] = filterStr($_POST['name']);
                                        $hdata['sRs'] ='成功';
                                        $hdata['sAct'] = $act.'/'.$show;

                                        $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                                        $roleRes = $db->fetch_first($sql);
                                        $hdata['role'] = $roleRes['role'];

                                        saveOperationLog($hdata);

                                        if(filterStr($_POST['name'])=='sec_admin'||$user_role['role']==4){
                                            $hdata['username'] = 'System';
                                            $hdata['sDes'] = 'CPU使用率:'.getCPURate().'%,内存使用率:'.getMemoryRate().'%,硬盘使用率:'.getDiskRate().'%';
                                            $hdata['sRs'] ='成功';
                                            saveOperationLog($hdata);
                                        }

                                        $db->execute("update bd_sys_user set last_logintime=$timestamp WHERE id=  {$_SESSION['userid']}");
                                        echo json_encode($data);exit;
                                    }else{
                                        $db->query("UPDATE ".getTable('user')." SET errors=0,locktime=0  WHERE username='$name'");
                                        $db->query("UPDATE ".getTable('user')." SET errors=errors+1  WHERE username='$name'");
                                        $data['success'] = false;
                                        $data['message'] = '账号或密码错误';
                                        $errors=$db->query("select errors from ".getTable('user')."  WHERE username='$name'");
                                        $max_errors=$db->query("select maxError from ".getTable('userconfig')." ");
                                        if($errors>$max_errors){
                                            $data['message'] = "用户已锁,".$lock['lockTime']."分钟后重新登录";
                                            $data['lockTime']=$locktime>0 ? $locktime :0;
                                        }

                                        $hdata['sDes'] = '用户登录';
                                        $hdata['username'] = filterStr($_POST['name']);
                                        $hdata['sRs'] ='账号或密码错误';
                                        $hdata['sAct'] = $act.'/'.$show;

                                        $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                                        $roleRes = $db->fetch_first($sql);
                                        $hdata['role'] = $roleRes['role'];

                                        saveOperationLog($hdata);
                                        echo json_encode($data);
                                        exit;
                                    }
                                }
                            }
                        }else{
                            if($pwd == $row['password']){
                               // $session_id=session_id();
                                $uuid = md5(rand(0,999999));
                                //var_dump($_SESSION);die;
                                if(isset($_SESSION['username']) && isset($_SESSION['uuid'])){
                                    $db->execute("DELETE FROM "."bd_sys_sessions"." WHERE username='$name' and uuid='{$_SESSION['uid']}'");
                                }
                                $exptime=$timestamp+$exp_time;
                                $sql= "INSERT INTO bd_sys_sessions set username='{$row['username']}',dateline='$timestamp',role='{$row['role']}',exptime=$exptime,uuid='{$uuid}'";
                                //echo $sql;die;
                                $db->execute($sql);
                                $db->query("UPDATE ".getTable('user')." SET errors='0',locktime='0' WHERE username='$name'");

                                $_SESSION['username'] = $row['username'];
                                $_SESSION['userid'] = $row['id'];
                                $_SESSION['role'] = $row['role'];
                                $_SESSION['expiretime'] = $timestamp+$exp_time;
                                $_SESSION['uuid'] = $uuid;
;
//                                if($res = $this->isChangePass($name, $pwd)){
//                                    $data['message'] = $res['message'];
//                                    $hdata['sDes'] = $res['sDes'];
//                                    echo json_encode($data);
//                                    exit;
//                                }
                                $data['success'] = true;
                                $data['message'] = '登录成功';
                                $hdata['sDes'] = '用户登录';
                                $hdata['username'] = filterStr($_POST['name']);
                                $hdata['sRs'] ='成功';
                                $hdata['sAct'] = $act.'/'.$show;

                                $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                                $roleRes = $db->fetch_first($sql);
                                $hdata['role'] = $roleRes['role'];

                                $db->execute("update bd_sys_user set last_logintime=$timestamp WHERE id=  {$_SESSION['userid']}");
                                saveOperationLog($hdata);

                                if(filterStr($_POST['name'])=='sec_admin'||$user_role['role']==4){
                                    $hdata['username'] = 'System';
                                    $hdata['sDes'] = 'CPU使用率:'.getCPURate().'%,内存使用率:'.getMemoryRate().'%,硬盘使用率:'.getDiskRate().'%';
                                    $hdata['sRs'] ='成功';
                                    saveOperationLog($hdata);
                                }
                                /**
                                 * 登陆成功yii操作
                                 */
                                $model=new LoginForm();
                                $post['LoginForm']['username']=$_POST['name'];
                                $post['LoginForm']['password']=$pwd;
//var_dump($model->load($post),$model->login(),Yii::$app->user->getIsGuest());die;
                                if($model->load($post) && $model->login()){
                                    echo json_encode($data);exit;
                                }

                            }else{
                                $db->query("UPDATE ".getTable('user')." SET errors=errors+1 WHERE username='$name'");
                                $data['success'] = false;
                                $data['message'] = '账号或密码错误';
                                $hdata['sDes'] = '用户登录';
                                $hdata['username'] = filterStr($_POST['name']);
                                $hdata['sRs'] ='密码错误';
                                $hdata['sAct'] = $act.'/'.$show;

                                $sql = "SELECT role_id as role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                                $roleRes = $db->fetch_first($sql);
                                $hdata['role'] = $roleRes['role'];

                                saveOperationLog($hdata);
                                echo json_encode($data);
                                exit;
                            }
                        }
                    }else{
                        if($pwd == $row['password']){
                            $uuid = md5(rand(0,999999));
                            //var_dump($_SESSION);die;
                            if(isset($_SESSION['username']) && isset($_SESSION['uuid'])){
                                $db->execute("DELETE FROM "."bd_sys_sessions"." WHERE username='$name' and uuid='{$_SESSION['uid']}'");
                            }
                            $exptime=$timestamp+$exp_time;
                            $sql= "INSERT INTO bd_sys_sessions set username='{$row['username']}',dateline='$timestamp',role='{$row['role']}',exptime=$exptime,uuid='{$uuid}'";
                            //echo $sql;die;
                            $db->execute($sql);
                            $db->query("UPDATE ".getTable('user')." SET errors='0',locktime='0' WHERE username='$name'");

                            $_SESSION['username'] = $row['username'];
                            $_SESSION['userid'] = $row['id'];
                            $_SESSION['waf_userid'] = $row['id'];
                            $_SESSION['role'] = $row['role'];
                            $_SESSION['expiretime'] = time()+$exp_time;
                            $_SESSION['uuid'] = $uuid;

//                            if($res = $this->isChangePass($name, $pwd)){
//                                $data['message'] = $res['message'];
//                                $hdata['sDes'] = $res['sDes'];
//                                echo json_encode($data);exit;
//                            }

                            $data['success'] = true;
                            $data['message'] = '登录成功';
                            $hdata['sDes'] = '用户登录';
                            $hdata['username'] = filterStr($_POST['name']);
                            $hdata['sRs'] ='成功';
                            $hdata['sAct'] = $act.'/'.$show;
                            saveOperationLog($hdata);

                            if(filterStr($_POST['name'])=='sec_admin'||$user_role['role']==4){
                                $hdata['username'] = 'System';
                                $hdata['sDes'] = 'CPU使用率:'.getCPURate().'%,内存使用率:'.getMemoryRate().'%,硬盘使用率:'.getDiskRate().'%';
                                $hdata['sRs'] ='成功';
                                saveOperationLog($hdata);
                            }

                            $db->execute("update bd_sys_user set last_logintime=$timestamp WHERE id=  {$_SESSION['userid']}");
                            echo json_encode($data);
                            exit;
                        }else{
                            $db->query("UPDATE ".getTable('user')." SET errors=errors+1 WHERE username='$name'");
                            $data['success'] = false;
                            $data['message'] = '账号或密码错误';
                            $hdata['sDes'] = '用户登录';
                            $hdata['username'] = filterStr($_POST['name']);
                            $hdata['sRs'] ='密码错误';
                            $hdata['sAct'] = $act.'/'.$show;

                            $sql = "SELECT role_id role FROM ".getTable('user')." WHERE username='".$hdata['username']."' limit 1";
                            $roleRes = $db->fetch_first($sql);
                            $hdata['role'] = $roleRes['role'];

                            saveOperationLog($hdata);
                            echo json_encode($data);
                            exit;
                        }
                    }
                }
            }
        }
        $model = new LoginForm();
        $db=new client_db();
        $the_ver = $db->result_first("select pname from bd_sys_sysinfo WHERE id=1");

        if(!empty($the_ver)){
            if($the_ver=='蓝盾安全扫描系统'){
                $log_bg = "/resource/skin/blue/images/log_bg-putong.jpg";
                $ver_t  = "蓝盾安全扫描系统";
            }elseif ($the_ver=='蓝盾安全漏洞扫描系统'){
                $log_bg = "/resource/skin/blue/images/log_bg-shemi.jpg";
                $ver_t  = "蓝盾安全漏洞扫描系统";
            }else{
                $log_bg = "/resource/skin/blue/images/log_bg-putong.jpg";
                $ver_t  = "蓝盾安全扫描系统";
            }
        }else{
            $log_bg = "/resource/skin/blue/images/log_bg-putong.jpg";
            $ver_t  = "蓝盾安全扫描系统";
        }
        $this->layout=false;
        return $this->render('login', [
            'model' => $model,
            'log_bg'=>$log_bg,
            'ver_t'=>$ver_t
        ]);

    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {

        global $db,$act,$show;
        $db->query("DELETE FROM bd_sys_sessions WHERE username='".filterStr($_SESSION['username'])."' and uuid='{$_SESSION['uuid']}'");
        $hdata['sDes'] = '退出系统';
        $hdata['sRs'] ='成功';
        $hdata['username'] = filterStr($_SESSION['username']);
        $hdata['sAct'] = $act.'/'.$show;
        saveOperationLog($hdata);
        session_unset();
        session_destroy();
        $data['success'] = true;
        $data['message'] = '退出成功';
        Yii::$app->user->logout();
        return $this->redirect('/site/login');
//        echo json_encode($data);
//        exit;
    }


    /**
     * @ 判断是否到更改密码周期
     * return Boolean true为到期
     */
    function isChangePass($username,$password){
        global $db;
        $pData = unserialize(file_get_contents(DIR_ROOT . "../config/data/system/pswstrategy.config"));
        $userInfo = $db->fetch_first("SELECT update_pwd_time FROM ".getTable('user')." WHERE username = '".$username."' and password='".$password."'");
        if( !empty($userInfo) && (time()-$userInfo['update_pwd_time']  >= $pData['pPeriod']*24*60*60) ){
            $data['message'] = '密码过期';
            $data['sDes'] = '用户登录';
            return $data;
        }else{
            return false;
        }
    }

    /**
     * CPU使用率
     */
    function getCPURate($speed = 0.5)
    {
        if (false === ($prevVal = @file("/proc/stat"))) {
            return false;
        }
        $prevVal = implode($prevVal, PHP_EOL);
        $prevArr = explode(' ', trim($prevVal));
        $prevTotal = $prevArr[2] + $prevArr[3] + $prevArr[4] + $prevArr[5];
        $prevIdle = $prevArr[5];
        usleep($speed * 1000000);
        $val = @file("/proc/stat");
        $val = implode($val, PHP_EOL);
        $arr = explode(' ', trim($val));
        $total = $arr[2] + $arr[3] + $arr[4] + $arr[5];
        $idle = $arr[5];
        $intervalTotal = intval($total - $prevTotal);
        return round(100 * (($intervalTotal - ($idle - $prevIdle)) / $intervalTotal), 2);
    }
    /**
     * 内存使用率
     */
    function getMemoryRate()
    {

        if (false === ($str = @file("/proc/meminfo"))) return false;
        $str = implode("", $str);
        preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
        preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);
        $res = array();
        $res['memTotal'] = round($buf[1][0] / 1024, 2);
        $res['memFree'] = round($buf[2][0] / 1024, 2);
        $res['memBuffers'] = round($buffers[1][0] / 1024, 2);
        $res['memCached'] = round($buf[3][0] / 1024, 2);
        $res['memUsed'] = $res['memTotal'] - $res['memFree'];
        $res['memPercent'] = (floatval($res['memTotal']) != 0) ? round($res['memUsed'] / $res['memTotal'] * 100, 2) : 0;

        $res['memRealUsed'] = $res['memTotal'] - $res['memFree'] - $res['memCached'] - $res['memBuffers']; //真实内存使用
        $res['memRealFree'] = $res['memTotal'] - $res['memRealUsed']; //真实空闲
        $res['memRealPercent'] = (floatval($res['memTotal']) != 0) ? round($res['memRealUsed'] / $res['memTotal'] * 100, 2) : 0; //真实内存使用率

        $res['memCachedPercent'] = (floatval($res['memCached']) != 0) ? round($res['memCached'] / $res['memTotal'] * 100, 2) : 0; //Cached内存使用率

        $res['swapTotal'] = round($buf[4][0] / 1024, 2);
        $res['swapFree'] = round($buf[5][0] / 1024, 2);
        $res['swapUsed'] = round($res['swapTotal'] - $res['swapFree'], 2);
        $res['swapPercent'] = (floatval($res['swapTotal']) != 0) ? round($res['swapUsed'] / $res['swapTotal'] * 100, 2) : 0;

        //判断内存如果小于1G，就显示M，否则显示G单位

        if ($res['memTotal'] < 1024) {
            $memTotal = $res['memTotal'] . " M";
            $mt = $res['memTotal'] . " M";
            $mu = $res['memUsed'] . " M";
            $mf = $res['memFree'] . " M";
            $mc = $res['memCached'] . " M"; //cache化内存
            $mb = $res['memBuffers'] . " M"; //缓冲
            $st = $res['swapTotal'] . " M";
            $su = $res['swapUsed'] . " M";
            $sf = $res['swapFree'] . " M";
            $swapPercent = $res['swapPercent'];
            $memRealUsed = $res['memRealUsed'] . " M"; //真实内存使用
            $memRealFree = $res['memRealFree'] . " M"; //真实内存空闲
            $memRealPercent = $res['memRealPercent']; //真实内存使用比率
            $memPercent = $res['memPercent']; //内存总使用率
            $memCachedPercent = $res['memCachedPercent']; //cache内存使用率
        } else {
            $memTotal = round($res['memTotal'] / 1024, 3) . " G";
            $mt = round($res['memTotal'] / 1024, 3) . " G";
            $mu = round($res['memUsed'] / 1024, 3) . " G";
            // $mf = round($res['memFree'] / 1024, 3) . " G";
            $mc = round($res['memCached'] / 1024, 3) . " G";
            $mb = round($res['memBuffers'] / 1024, 3) . " G";
            $st = round($res['swapTotal'] / 1024, 3) . " G";
            $su = round($res['swapUsed'] / 1024, 3) . " G";
            $sf = round($res['swapFree'] / 1024, 3) . " G";
            $swapPercent = $res['swapPercent'];
            $memRealUsed = round($res['memRealUsed'] / 1024, 3) . " G"; //真实内存使用
            $memRealFree = round($res['memRealFree'] / 1024, 3) . " G"; //真实内存空闲
            $memRealPercent = $res['memRealPercent']; //真实内存使用比率
            $memPercent = $res['memPercent']; //内存总使用率
            $memCachedPercent = $res['memCachedPercent']; //cache内存使用率
        }

        $res['u_memTotal'] = $memTotal;
        $res['u_memTotal'] = $mt;
        $res['u_memUsed'] = $mu;
        // $res['u_memFree'] = $mf;
        $res['u_memCached'] = $mc; //cache化内存
        $res['u_memBuffers'] = $mb; //缓冲
        $res['u_swapTotal'] = $st;
        $res['u_swapUsed'] = $su;
        $res['u_swapFree'] = $sf;
        $res['u_swapPercent'] = $swapPercent;
        $res['u_memRealUsed'] = $memRealUsed; //真实内存使用
        $res['u_memRealFree'] = $memRealFree; //真实内存空闲
        $res['u_memRealPercent'] = $memRealPercent; //真实内存使用比率
        $res['u_memPercent'] = $memPercent; //内存总使用率
        $res['u_memCachedPercent'] = $memCachedPercent; //cache内存使用率
        return $res['u_memRealPercent'];
    }

    /**
     * 硬盘使用率
     */

    function getDiskRate()
    {
        /*
        $iTotal = round(@disk_total_space("/") / (1024 * 1024 * 1024), 3); //总
        $iUsableness = round(@disk_free_space("/") / (1024 * 1024 * 1024), 3); //可用
        $iImpropriate = $iTotal - $iUsableness; //已用
        $iPercent = (floatval($iTotal) != 0) ? round($iImpropriate / $iTotal * 100, 2) : 0;
        */

        $fp = popen('df -lh | grep -E "^(/)"',"r");
        $rs = fread($fp,1024);
        pclose($fp);
        $rs = preg_replace("/\s{2,}/",' ',$rs);  //把多个空格换成 “_”
        $hd = explode(" ",$rs);
        //print_r($hd);
        /*$hd_avail = trim($hd[1],'G'); //磁盘大小，/dev/sda1
        $hd_avail2 = trim($hd[6],'G'); //磁盘大小，/dev/sda3
        $hd_usage = trim($hd[2],'G'); //磁盘可用空间大小 单位G
        $hd_usage2  = trim($hd[7],'G'); //*/
        $hd_avail = !strrpos($hd[1],"T")?trim($hd[1],'G'):trim($hd[1],'T')*1024;
        $hd_avail2 = !strrpos($hd[6],"T")?trim($hd[6],'G'):trim($hd[6],'T')*1024;
        $hd_usage = !strrpos($hd[2],"T")?trim($hd[2],'G'):trim($hd[2],'T')*1024;
        $hd_usage2 = !strrpos($hd[7],"T")?trim($hd[7],'G'):trim($hd[7],'T')*1024;
        //echo $hd_avail."/".$hd_avail2."/".$hd_usage."/".$hd_usage2;
        $bili = ($hd_usage+$hd_usage2)/($hd_avail+$hd_avail2);
        $iPercent = round($bili*100,2);
        return $iPercent;

    }

    //网络带宽使用率
    public function getNetRate(){
        if (false === ($prevVal = @file("/proc/net/dev"))) {
            return false;
        }
        $result = file_get_contents("/proc/net/dev");

        preg_match("/lo:\s+\d+\s+\d+/", $result, $preg_str);
        $preg_str = str_replace("lo:",'',$preg_str);
        $preg_arr=explode(' ',$preg_str[0]);
        //var_dump($preg_arr,$result);die;
        $bytes = $preg_arr[1];
        $packets = $preg_arr[2];
        $rate = round($packets*100/$bytes,2);
        // var_dump($rate);die;
        return $rate;
    }




    function hbw($size) {
        $size *= 8;
        if($size > 1024 * 1024 * 1024) {
            $size = round($size / 1073741824 * 100) / 100 . ' Gbps';
        } elseif($size > 1024 * 1024) {
            $size = round($size / 1048576 * 100) / 100 . ' Mbps';
        } elseif($size > 1024) {
            $size = round($size / 1024 * 100) / 100 . ' Kbps';
        } else {
            $size = $size . ' Bbps';
        }
        return $size;
    }

    //平台语言切换
    public function  actionSetlang($lang)
    {
        if (isset($lang) && $lang != "") {
            Yii::$app->session->set("language", $lang);
        }
        if (!Yii::$app->session->get("language")){
            Yii::$app->session->set("language", "zh_CN");
        }
        return $this->goBack();
    }

    public function actionHelp(){
        template2('/site/help');
    }

    public function actionTest(){
//        setlocale(LC_ALL,array('zh_CN.gbk','zh_CN.gb2312','zh_CN.gb18030'));
//        $file = fopen(Yii::$app->basePath.'/messages/en_US/en.csv',"r");
//        print_r(fgetcsv($file));
//        fclose($file);

        $fh=fopen(Yii::$app->basePath.'/messages/en_US/en1.csv',"r");
        $arr=[];
        while ($line=fgetcsv($fh,1000,",")){
//var_dump($line);
            $Name=$line[0];
            $Name=iconv('gbk','utf-8',$line[0]);
           // var_dump($Name);
            //echo $Name;
            $arr[$Name] = $line[1];
        }
        file_put_contents(Yii::$app->basePath.'/messages/en_US/app1.php');
       // var_dump($arr);
        //var_dump(fgetcsv(Yii::$app->basePath.'/messages/en_US/en.csv'));
        $db=new client_db();
        $res =$db->result_first("select * from t_rule",'db_jx');
        var_dump($res);die;
        var_dump(Yii::$app->db_jx->createCommand("select * from t_rule")->queryAll());die;
    }
}
