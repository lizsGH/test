<?php
/**
 * Created by PhpStorm.
 * User: 20160817
 * Date: 2017/4/7
 * Time: 16:33
 */
namespace app\controllers;
use app\components\client_db;

class JxhcController extends BaseController
{

/**
 * @列表页
 */
function actionIndex(){
    global $act;
    $hPost = $_POST;
    $aPost = (array)$hPost;
    $arr = array();
    if(isset($aPost['soc'])){
        $arr['soc'] = $aPost['soc'];
    }
    template2($act.'/index', $arr);
}

/**
 * @新增和编辑页
 */
function actionEdit(){
    global $act;
    template2($act.'/edit', array());
}

/**
 * @查看页
 */
function actionView(){
    global $act;
    template2($act.'/view', array());
}

function rules()
{
    return array(
        array('allow',
            'actions' => array('index','indexListData'),
            'users' => array('*'),
        )
    );
}

function actionSave()
{
    global $act;
    $db_jx= new client_db();

    $data = array();
    $devtypearr = array();
    //查出所有规范
    $standData = $db_jx->fetch_all("select * from t_standard where id = 49 or id = 80","db_jx");

    //$standData = getTree($standData);

    $data['standData'] = $standData;

    //资产类型
    $devTypeData = $db_jx->fetch_all("select * from t_dev_type where is_base_os = 1","db_jx");
    foreach($devTypeData as $d){
        $devtypearr[$d['mask']] = $d['type_name'];
    }

    $data['devtpye'] = $devtypearr;

    template2($act.'/save',$data);
}

/*无限极分类*/
function getTree(&$list,$pid=0,$level=0){
    static $tree = array();
    foreach($list as $v){
        if($v['parent'] == $pid){
            $tmp = array(
                'id'=>$v['id'],
                'parent'=>$v['parent'],
                'name'=>$v['name'],
                'level'=>$level
            );
            $tree[] = $tmp;
            $this->getTree($list,$v['id'],$level+1);
        }
    }
    return $tree;
}

/**
 * 列表
 * @remotable
 */
function actionIndexListData()
{

    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $page = intval($hPost['start']);
    $perpage = intval($hPost['length']);
    $page  = $page > 1 ? $page : 1;
    $con = '';
    $c_f = 0;
    if (!empty($hPost['p_standard_id'])) {
        $con .= "WHERE sp.standard_id=".intval($hPost['p_standard_id']);

        $c_f  = 1;
    }
    if (!empty($hPost['standard_id'])) {
        if($c_f==1){
            $con .=" AND sp.standard_id=".intval($hPost['standard_id']);
        }else{
            $con .="WHERE sp.standard_id=".intval($hPost['standard_id']);
        }

    }

    $total = $db_jx->result_first("select COUNT(`id`) from t_task","db_jx");
    $maxPage = ceil($total/$perpage);
    $page    = $page >= $maxPage ? $maxPage : $page;

    $start = ($page-1)*$perpage;
    $t_sql = "select a.*,b.task_status,b.checknum,b.stanum,b.devnum,b.id as instanceid,b.create_time as last_time,b.use_time1 as use_time 
              from t_task a 
              left join (select d.*,d.use_time as use_time1,count(DISTINCT e.dev_id) as devnum,COUNT(DISTINCT e.standard_id) as stanum,COUNT(e.id) as checknum 
                        from (select max(c.id),c.* from t_task_instance c GROUP BY c.task_id) d 
                        LEFT JOIN t_task_log e on d.id = e.task_instance_id GROUP BY d.id) b on a.id = b.task_id ".$con."order by last_time desc,id desc limit $start,$perpage";
    $hData = $db_jx->fetch_all($t_sql,'db_jx');

    $aData = array();
    $timeType = array(
        0 => Yii::t('app', '立即执行'),
        1 => Yii::t('app', '某个时刻执行'),
        2 => Yii::t('app', '每天一次'),
        3 => Yii::t('app', '每周一次'),
        4 => Yii::t('app', '每月一次（按日期）'),
        5 => Yii::t('app', '每月一次（按星期）'),
    );
    foreach ($hData as $k => $v) {
        $v['use_time'] = isset($v['use_time'])&&!empty($v['use_time']) ? $this->time2second($v['use_time']) : $this->time2second(0);
        $aTmp = array(
            'id'=>$v['id'],
            'task_name'=>$v['task_name'],
            'create_time'=>$v['create_time'],
            'creator'=>$v['creator'],
            'time_type'=>$v['time_type'],
            'cron'=>$v['cron'],
            'time_type_h'=>isset($timeType[$v['time_type']])?$timeType[$v['time_type']]:'',
            'task_status'=>$v['task_status'],
            'instanceid'=>$v['instanceid'],
            'last_time'=>$v['last_time'],
            'use_time'=>$v['use_time'],
            'infostr'=>($v['devnum'] ? $v['devnum'] : 0 )."|".($v['stanum'] ? $v['stanum'] : 0 )."|".($v['checknum'] ? $v['checknum'] : 0 ),
            'current_user'=>$_SESSION['username']
        );
        array_push($aData, $aTmp);
    }
    $aJson = array();
    $aJson['Rows'] = $aData;
    $aJson['Total'] = $total;
    echo json_encode($aJson);
    exit;
}

function time2second($seconds){
    if($seconds<1000){
        return !empty($seconds) ? $seconds . Yii::t('app', '毫秒') : '0' . Yii::t('app', '毫秒');
    }
    $time = !empty($seconds) ? ($seconds/1000) : 0;
    //取得整数部分
    $seconds = floor($time);
    $seconds = (int)$seconds;
    if( $seconds<86400 ){//如果不到一天
        $format_time = gmstrftime('%H时%M分%S秒', $seconds);
    }else{
        $time = explode(' ', gmstrftime('%j %H %M %S', $seconds));//Array ( [0] => 04 [1] => 14 [2] => 14 [3] => 35 )
        $format_time = ($time[0]-1).Yii::t('app', '天').$time[1].Yii::t('app', '时').$time[2].Yii::t('app', '分').$time[3].Yii::t('app', '秒');
    }
    $format_time = str_replace('00' . Yii::t('app', '天'), '', $format_time);
    $format_time = str_replace('00' . Yii::t('app', '时'), '', $format_time);
    $format_time = str_replace('00' . Yii::t('app', '分'), '', $format_time);
    $format_time = str_replace('00' . Yii::t('app', '秒'), '', $format_time);
    return $format_time;
}

function editTaskitem($task_id,$aPost,$edit,$dFieldValue){
    global $act,$show;
    $db_jx= new client_db();
    $aJson = array();
    $hdata = array();

    if($edit == 'edit'){
        $dFieldValue = rtrim($dFieldValue, ",");
        /*更新相应设备信息*/
        $sql_d = "UPDATE t_dev SET ".$dFieldValue." WHERE id='".intval($aPost['dev_id'])."'";
        $db_jx->query($sql_d,'db_jx');
    }

    //var_dump($aPost);exit;
    //先删除与task_id相关的记录
    $sql = "delete from t_task_item where task_id =".intval($task_id);
    $db_jx->query($sql,'db_jx');

    $tiData = array(
        'task_id' => intval($task_id),
        'dev_id' => intval($aPost['dev_id']),
        'standard_id' => intval($aPost['standard_id'])
    );
    if (isset($tiData)) {
        /**
         * 转换与数据库相同的字段
         */
        $sField = "";
        $sValue = "";
        foreach($tiData as $k=>$v){
            $sField .= $k . ",";
            $sValue .= "'" .$v. "',";
        }
        $sField = rtrim($sField, ",");
        $sValue = rtrim($sValue, ",");
        $sql = "INSERT INTO t_task_item (".$sField.") VALUES (".$sValue.")";
        if ($db_jx->query($sql,'db_jx')) {
            //保存生成
            $success = true;
            $aJson ['msg'] = Yii::t('app', '成功添加设备和规范');
            /***
             * 操作日志
             */
            $hdata['sDes'] = Yii::t('app', '添加设备和规范');
            $hdata['sRs'] = Yii::t('app', "成功");
            $hdata['sAct'] = $act.'/'.$show;
            saveOperationLog($hdata);

        } else {
            $success = false;
            $aJson ['msg'] = Yii::t('app', '添加设备和规范失败');
            $hdata['sDes'] = Yii::t('app', '添加设备和规范');
            $hdata['sRs'] = "失败";
            $hdata['sAct'] = $act.'/'.$show;
            saveOperationLog($hdata);
        }
        $aJson ['success'] = $success;
    }

    return $aJson;
}

/**
 * 编辑
 * 没有加@formHandler就没有POST的参数过来,data为null
 * @remotable
 * @formHandler
 */
function actionAddAndEditData()
{
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    global $act,$show;
    $db_jx= new client_db();
    $aJson = array();
    $hdata = array();
    $success = false;
    $aPost = $_REQUEST;
    $cron  = '';
    $id    = '';
    //$aPost = (array)$aPost;
    if (isset($aPost)) {
        /**
         * 转换与数据库相同的字段
         */
        if($aPost['time_type']==1){
            $tpart = explode(' ',$aPost['time_1']);
            $part_o = explode("-",$tpart[0]);
            $part_t = explode(":",$tpart[1]);
            //$second = $part_t[2];
            $minu = $part_t[1];
            $hour = $part_t[0];
            $day = $part_o[2];
            $month = $part_o[1];
            $year = $part_o[0];
            //$cron = $second." ".$minu." ".$hour." ".$day." ".$month." ? ".$year;
            $cron = $minu." ".$hour." ".$day." ".$month." ? ".$year;
        }elseif($aPost['time_type']==2){
            $part_t = explode(":",$aPost['day_2']);
            //$second = $part_t[2];
            $minu = $part_t[1];
            $hour = $part_t[0];
            //$cron = $second." ".$minu." ".$hour." * * ? *";
            $cron = $minu." ".$hour." * * ? *";
        }elseif($aPost['time_type']==3){
            $part_t = explode(":",$aPost['time_3']);
            //$second = $part_t[2];
            $minu = $part_t[1];
            $hour = $part_t[0];
            //$cron = $second." ".$minu." ".$hour." ? * ".$aPost['week_3']." *";
            $cron = $minu." ".$hour." ? * ".$aPost['week_3']." *";
        }elseif($aPost['time_type']==4){
            $part_t = explode(":",$aPost['time_4']);
            //$second = $part_t[2];
            $minu = $part_t[1];
            $hour = $part_t[0];
            //$cron = $second." ".$minu." ".$hour." ".$aPost['day_4']." * ? *";
            $cron = $minu." ".$hour." ".$aPost['day_4']." * ? *";
        }elseif($aPost['time_type']==5){
            $part_t = explode(":",$aPost['time_5']);
            //$second = $part_t[2];
            $minu = $part_t[1];
            $hour = $part_t[0];
            //$cron = $second." ".$minu." ".$hour." ? * ".$aPost['order_5']."#".$aPost['week_5'];
            $cron = $minu." ".$hour." ? * ".$aPost['order_5']."#".$aPost['week_5'];
        }elseif($aPost['time_type']==0){
            $cron = '';
        }
        $taskData = array(
            'id'=>intval($aPost['id']),
            'task_name'=>filterStr($aPost['task_name']),
            //'creator'=>Yii::app()->user->getState('sLoginAccount'),
            'creator'=>filterStr($_SESSION['username']),
            'time_type'=>intval($aPost['time_type']),
            'cron'=>filterStr($cron)
        );

        $devData= array(
            'dev_no'=>filterStr($aPost['ip_addr']),
            'dev_name'=>filterStr($aPost['ip_addr']),
            'dev_type'=>intval($aPost['dev_type']),
            'ip_addr'=>filterStr($aPost['ip_addr']),
            'port'=>intval($aPost['port']),
            'login_protocol'=>intval($aPost['login_protocol']),
            'login_account'=>filterStr($aPost['login_account']),
            'login_password'=>filterStr($aPost['login_password']),
            'privileged_password'=>filterStr($aPost['privileged_password']),
            'create_time'=>time()

        );

        if (empty ($taskData ['id'])) {
            unset($taskData['id']);
            $sDesc = Yii::t('app', "增加任务成功");

        } else {
            $id = $taskData['id'];
            $sDesc = Yii::t('app', "编辑任务成功");
        }

        if ($id!='') {//编辑
            $sFieldValue = "";
            $dFieldValue = "";
            foreach($taskData as $k=>$v){
                $sFieldValue .= $k . "= '" .$v. "',";
            }
            foreach($devData as $k=>$v){
                $dFieldValue .= $k . "= '" .$v. "',";
            }
            $sFieldValue = rtrim($sFieldValue, ",");
            $dFieldValue = rtrim($dFieldValue, ",");
            $sql = "UPDATE t_task SET ".$sFieldValue." WHERE id='".$id."'";

            if($db_jx->query($sql,'db_jx')){
                //保存生成
                $success = true;
                $aJson ['msg'] = Yii::t('app', '编辑任务成功');
                $aJson ['id'] = $id;
                $dev_id = $db_jx->fetch_first("select dev_id from t_task_item where task_id = ".$id,"db_jx");
                $aPost['dev_id'] = $dev_id['dev_id'];
                /*向task_item表插入数据*/
                $res = $this->editTaskitem($aJson ['id'],$aPost,'edit',$dFieldValue);
                $ret = \BDRpc::call("add_task", array("task_id" => $aJson ['id']));
                $success = true;
                /***
                 * 操作日志
                 */
                //$pData = new stdClass();
                //$pData->sDesc = $sDesc;
                //$this->saveOperationLog($pData);
                $hdata['sDes'] = Yii::t('app', '编辑基线任务');
                $hdata['sRs'] = Yii::t('app', "成功");
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }else{
                $success = false;
                $aJson ['msg'] = Yii::t('app', '编辑任务失败');
                $hdata['sDes'] = '编辑基线任务';
                $hdata['sRs'] = Yii::t('app', "失败");
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }

        } else {//新增
            $sField = "";
            $sValue = "";
            $dField = "";
            $dValue = "";
            foreach($taskData as $k=>$v){
                $sField .= $k . ",";
                $sValue .= "'" .$v. "',";
            }
            foreach($devData as $k=>$v){
                $dField .= $k . ",";
                $dValue .= "'" .$v. "',";
            }
            $sField = rtrim($sField, ",");
            $sValue = rtrim($sValue, ",");
            $dField = rtrim($dField, ",");
            $dValue = rtrim($dValue, ",");
            $sql = "INSERT INTO t_task (".$sField.") VALUES (".$sValue.")";
            $sql_dev = "INSERT INTO t_dev (".$dField.") VALUES (".$dValue.")";
            if($db_jx->query($sql,'db_jx')){
                //保存生成
                $success = true;
                $aJson ['msg'] = Yii::t('app', '增加任务成功');
                $aJson ['id'] = $db_jx->insert_id();

                /*向t_dev表插入数据*/
                $db_jx->query($sql_dev,'db_jx');
                $aPost['dev_id'] = $db_jx->insert_id();
                /*向task_item表插入数据*/
                $this->editTaskitem($aJson ['id'],$aPost,'add','');

                //$shell = "/usr/bin/python2 bvsd.py";
                //shellResult($shell);
                $ret = \BDRpc::call("add_task", array("task_id" => $aJson ['id']));
                $success = true;
                $aJson ['msg'] = Yii::t('app', '增加任务成功');
                /***
                 * 操作日志
                 */
                $hdata['sDes'] = Yii::t('app', '新增基线任务');
                $hdata['sRs'] = Yii::t('app', "成功");
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }else{
                $success = false;
                $aJson ['msg'] = Yii::t('app', '新增任务失败');
                $hdata['sDes'] = Yii::t('app', '新增基线任务');
                $hdata['sRs'] = Yii::t('app', "失败");
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }
        }
        $aJson ['success'] = $success;
    }
    echo json_encode($aJson);
    exit;
}



/**
 * 删除
 * @remotable
 */
function actionDelData()
{
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    global $act,$show;
    $db_jx= new client_db();
    $hPost = $_REQUEST;
    $aJson = array();
    $hdata = array();
    $id = filterStr($hPost['sId']);
    $sql = "delete from t_task where id in(".$id.")";

    if ($db_jx->query($sql,'db_jx')) {
        $aJson ['success'] = true;
        $aJson ['msg'] = Yii::t('app', '操作成功');
        /***
         * 操作日志
         */
        $sDesc = Yii::t('app', "删除任务成功");
        $hdata['sDes'] = Yii::t('app', '删除基线任务');
        $hdata['sRs'] = Yii::t('app', "成功");
        $hdata['sAct'] = $act.'/'.$show;
        saveOperationLog($hdata);

        $dev_id = $db_jx->fetch_first("select dev_id from t_task_item where task_id = ".$id,"db_jx");

        /*删除t_dev相关设备*/
        $sql_d  = "delete from t_dev where id = ".$dev_id['dev_id'];
        $db_jx->query($sql_d,'db_jx');


        /*删除t_task_item相关记录*/
        $sql_i = "delete from t_task_item where task_id in(".$id.")";
        $db_jx->query($sql_i,'db_jx');
        /*后台删除任务*/
        $sid = explode(",", $id);
        foreach($sid as $taskid){
            $ret = \BDRpc::call("del_task", array("task_id" => $taskid));
        }
    } else {
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '删除任务失败');
        $hdata['sDes'] = Yii::t('app', '删除基线任务');
        $hdata['sRs'] = Yii::t('app', "失败");
        $hdata['sAct'] = $act.'/'.$show;
        saveOperationLog($hdata);
    }
    echo json_encode($aJson);
    exit;
}


function actionDetail()
{
    global $act;
    $aData = array();
    $aData = $this->getInfoData();
    template2($act.'/detail',$aData);
}

function getInfoData()
{
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $pData =  $db_jx->fetch_all('select * from t_task where id ='.intval($hPost['id']),'db_jx');
    return $pData;
}

function actionGettaskitem(){
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $pData = $db_jx->fetch_all('select * from t_task_item where task_id = '.intval($hPost['id']),'db_jx');
    $dData = $db_jx->fetch_first('select * from t_dev where id = '.$pData[0]['dev_id'],'db_jx');
    $pData[0]['dev_type'] = $dData['dev_type'];
    $pData[0]['dev_name'] = $dData['dev_name'];
    $pData[0]['dev_no'] = $dData['dev_no'];
    $pData[0]['ip_addr'] = $dData['ip_addr'];
    $pData[0]['login_protocol'] = $dData['login_protocol'];
    $pData[0]['port'] = $dData['port'];
    $pData[0]['login_account'] = $dData['login_account'];
    $pData[0]['login_password'] = $dData['login_password'];
    $pData[0]['privileged_password'] = $dData['privileged_password'];
    echo json_encode($pData);
    exit;
}

function actionStarttask(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $hPost = $_REQUEST;
    $aJson = array();
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        $ret = \BDRpc::call("start_task", array("task_id" => $hPost['task_id'],
            "client_ip" => $this->getClientIp()));
        $aJson ['success'] = true;
        $aJson ['msg'] = Yii::t('app', '操作成功');
    }else{
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '操作失败');
    }
    echo json_encode($aJson);
    exit;
}

function actionResumetask(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $hPost = $_REQUEST;
    $aJson = array();
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        $ret = \BDRpc::call("resume_task", array("task_id" => $hPost['task_id'],
            "client_ip" => $this->getClientIp()));
        $aJson ['success'] = true;
        $aJson ['msg'] = Yii::t('app', '操作成功');
    }else{
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '操作失败');
    }
    echo json_encode($aJson);
    exit;
}

function actionStoptask(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $hPost = $_REQUEST;
    $aJson = array();
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        $ret = \BDRpc::call("stop_task", array("task_id" => $hPost['task_id'],
            "client_ip" => $this->getClientIp()));
        $aJson ['success'] = true;
        $aJson ['msg'] = Yii::t('app', '操作成功');
    }else{
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '操作失败');
    }
    echo json_encode($aJson);
    exit;
}

function actionRestarttask(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $hPost = $_REQUEST;
    $aJson = array();
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        $ret = \BDRpc::call("restart_task", array("task_id" => $hPost['task_id'],
            "client_ip" => $this->getClientIp()));
        $aJson ['success'] = true;
        $aJson ['msg'] = Yii::t('app', '操作成功');
    }else{
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '操作失败');
    }
    echo json_encode($aJson);
    exit;
}

function actionPausetask(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $hPost = $_REQUEST;
    $aJson = array();
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        $ret = \BDRpc::call("pause_task", array("task_id" => $hPost['task_id'],
            "client_ip" => $this->getClientIp()));
        $aJson ['success'] = true;
        $aJson ['msg'] = Yii::t('app', '操作成功');
    }else{
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '操作失败');
    }
    echo json_encode($aJson);
    exit;
}

function getClientIp() {
    $ip = 'unknown';
    $unknown = 'unknown';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
        // 使用透明代理、欺骗性代理的情况
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
        // 没有代理、使用普通匿名代理和高匿代理的情况
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // 处理多层代理的情况
    if (strpos($ip, ',') !== false) {
        // 输出第一个IP
        $ip = reset(explode(',', $ip));
    }

    return $ip;
}

function actionGettaskprogress(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $aJson = array();
    $aData = array();
    if(isset($hPost['taskidstr'])&&!empty($hPost['taskidstr'])){
        $taskidPost = explode(',',$hPost['taskidstr']);
        $row = count($taskidPost);
        $taskidarr = array();
        for($i=0;$i<$row;$i++){
            $taskidarr[] = intval($taskidPost[$i]);
        }
        $taskidstr = implode(',',$taskidarr);

        //$taskidstr = "`".$hPost['taskidstr']."`";//单个

        //获取任务执行时长和最后一次检查时间
        $sql = "select d.create_time,d.maxid,d.task_status,d.task_id,d.use_time as use_time from (select max(c.id) as maxid,c.* from t_task_instance c where c.task_id in (".$taskidstr.") GROUP BY c.task_id) d";
        $hData = $db_jx->fetch_all($sql,'db_jx');
        //var_dump($sql);
        foreach($hData as $v){
            $ret = \BDRpc::call("get_task_state", array("task_id" => $v['task_id'], "client_ip" => $this->getClientIp()));
            //$res = CJSON::decode($ret);
            //$per = isset($res['result'])&&!empty($res['result'])?$res['result']:0;
            //计算检查有问题的记录 的百分比
            $sql = "select count(`id`) as total,status from t_task_log where task_instance_id=".$v['maxid']." GROUP BY status";
            $starr = $db_jx->fetch_all($sql,'db_jx');

            //获取任务执行状态
            $sql = "select task_status from t_task_instance where task_id = ".$v['task_id'];
            $task_status = $db_jx->fetch_all($sql,'db_jx');
            $rscount = 0;
            $sttotal=0;
            $stsuccess=0;
            $sterror=0;
            $loginfail=0;
            $sttotalper=0;
            // `status` int(11) NOT NULL DEFAULT '0' COMMENT '核查状态 0-未核查 1-核查没问题 2-核查有问题 3-登录失败 4-检查失败',
            //核查有问题 - 特指某检查项不符合规范
            //检查失败 - 指因不确定原因，致使未能完成检查过程
            //登录失败 - 指登录不上对方机器

            foreach($starr as $st){
                $rscount += $st['total'];		# 计算总的检查项数量

                if($st['status'] > 0){
                    $sttotal += $st['total'];	# 已经检查完的数量

                    if($st['status'] > 1){
                        $sterror += $st['total'];	# 检查失败的数量

                        if($st['status'] > 2){		# 登录失败的数量
                            $loginfail += $st['total'];
                        }
                    }
                }
            }
            if($sttotal==0){
                $sttotalper = 0;
                $stsuccess = 0;
                $errorper = 0;
                $loginfailper = 0;
            }else{
                $sttotalper = $sttotal ? floor($sttotal * 100 / $rscount) : 0;//包含：1-2-3-4 已经检查完的

                $stsuccess = $sttotal ? floor(($sttotal - $sterror) * 100 / $rscount) : 0;//1-核查没问题 - 符合规范

                $errorper = $sterror ? floor(($sterror - $loginfail) * 100 / $rscount) : 0;//2-核查有问题 - 不符合规范

                $loginfailper = $loginfail ? floor($loginfail * 100 / $rscount) : 0;//包含：3-登录失败 4-检查失败'  - 检查失败
            }
            $aItem = array(
                'create_time'=>$v['create_time'],
                'task_status'=>$task_status,
                'task_id'=>$v['task_id'],
                'use_time'=>$this->time2second($v['use_time']),
                'per'=>$sttotalper,//用于进度条右边的数字显示
                'successper'=>$stsuccess,//符合标准的比率
                'errorper'=>$errorper,
                'loginfailper'=>$loginfailper
            );
            /* foreach($starr as $st){
                 $rscount += $st['total'];		# 计算总的检查项数量

                 if($st['status'] > 0){
                     $sttotal += $st['total'];	# 已经检查完的数量

                     if($st['status'] > 1){
                         $sterror += $st['total'];	# 检查失败的数量

                         if($st['status'] == 3){		# 登录失败的数量
                             $loginfail = $st['total'];
                         }
                     }
                 }
             }
             if($sttotal==0){
                 $errorper = 0;
                 $loginfailper = 0;
             }else{
                 $errorper = $sterror ? floor($sterror * 100 / $rscount) : 0;
                 #$loginfailper = $loginfail && isset($res['result']) ? floor(($loginfail/$rscount)*$res['result']) : 0;
                 $loginfailper = $loginfail ? floor($loginfail * 100 / $rscount) : 0;

                 $sttotalper = $sttotal ? floor($sttotal * 100 / $rscount) : 0;
             }
             $aItem = array(
                 'create_time'=>$v['create_time'],
                 'task_status'=>$task_status,
                 'task_id'=>$v['task_id'],
                 'use_time'=>$this->time2second($v['use_time']),
                 'per'=>$sttotalper,
                 'errorper'=>$errorper,
                 'loginfailper'=>$loginfailper
             );*/
            array_push($aData,$aItem);
        }
        $aJson = $aData;
    }
    echo json_encode($aJson);
    exit;
}

function actionGettaskstate(){
    require_once DIR_ROOT."../include/xmlrpc/BDRpc.php";
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $aJson = array();
    $use_time='';
    $create_time = '';
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        $hPost['task_id'] = intval($hPost['task_id']);
        //获取任务执行时长和最后一次检查时间
        /*$sql = "select d.create_time,d.maxid,d.task_status,d.use_time from (select max(c.id) as maxid,c.* from t_task_instance c where c.task_id = :task_id) d";
        $hData = Yii::app()->bd->createCommand($sql)
            ->queryRow(true,array(':task_id'=>$hPost->task_id));*/

        $sql = "select d.create_time,d.maxid,d.task_status,d.use_time from (select max(c.id) as maxid,c.* from t_task_instance c where c.task_id =".$hPost['task_id'].") d";
        $hData = $db_jx->fetch_first($sql,'db_jx');

        //存在实例id才有最后执行时间和耗费时间
        if($hData['maxid']){
            if($hData['use_time']){
                $use_time = $hData['use_time'];
            }else{
                $use_time = 0;
            }
            if($hData['create_time']){
                $create_time = $hData['create_time'];
            }

            //获取任务当前执行状态
            $task_status = $hData['task_status'] ? $hData['task_status'] : 0;
            $ret = \BDRpc::call("get_task_state", array("task_id" => $hPost['task_id'], "client_ip" => $this->getClientIp()));

            $res = json_decode($ret);//这里$res变成了一个对象
            //var_dump($ret);
            //var_dump($res);
            //计算检查有问题的记录 的百分比 和登录失败的百分比
            /*$sql = "select count(id) as total,status from t_task_log where task_instance_id=:task_instance_id GROUP BY status";
            $starr = Yii::app()->bd->createCommand($sql)
                ->queryAll(true,array(':task_instance_id'=>$hData['maxid']));*/

            $sql = "select count(id) as total,status from t_task_log where task_instance_id=".$hData['maxid']." GROUP BY status";
            $starr = $db_jx->fetch_all($sql,'db_jx');
            $rscount = 0;
            $sttotal=0;
            $sterror=0;
            $loginfail=0;

            foreach($starr as $st){
                $rscount += $st['total'];		# 计算总的检查项数量

                if($st['status'] > 0){
                    $sttotal += $st['total'];	# 已经检查完的数量

                    if($st['status'] > 1){
                        $sterror += $st['total'];	# 检查失败的数量

                        if($st['status'] == 3){		# 登录失败的数量
                            $loginfail = $st['total'];
                        }
                    }
                }
            }
            if($sttotal==0){
                $errorper = 0;
                $stsuccess = 0;
                $loginfailper = 0;
                $sttotalper = 0;
            }else{
                $sttotalper = $sttotal ? floor($sttotal * 100 / $rscount) : 0;//包含：1-2-3-4 已经检查完的

                $stsuccess = $sttotal ? floor(($sttotal - $sterror) * 100 / $rscount) : 0;//1-核查没问题 - 符合规范

                $errorper = $sterror ? floor(($sterror - $loginfail) * 100 / $rscount) : 0;//2-核查有问题 - 不符合规范

                $loginfailper = $loginfail ? floor($loginfail * 100 / $rscount) : 0;//包含：3-登录失败 4-检查失败'  - 检查失败
            }

            if(isset($res->result)){
                $aJson ['per'] = $sttotalper;
                $aJson ['successper'] = $stsuccess;
                $aJson ['errorper'] = $errorper;
                $aJson ['loginfailper'] = $loginfailper;
                $aJson ['success'] = true;
            }else{
                $aJson ['success'] = false;
                $aJson ['per'] = 0;
                $aJson ['successper'] = 0;
                $aJson ['errorper'] = 0;
                $aJson ['loginfailper'] = 0;
            }
        }
    }else{
        $aJson ['success'] = false;
        $aJson ['per'] = 0;
        $aJson ['errorper'] = 0;
        $aJson ['loginfailper'] = 0;
    }
    echo json_encode($aJson);
    exit;
}

function devtypename(){
    $db_jx= new client_db();

    $sql = "select * from t_dev_type where is_base_os=1";
    $dData = $db_jx->fetch_all($sql,'db_jx');
    $devtypearr = array();
    foreach($dData as $d){
        $devtypearr[$d['mask']] = $d['type_name'];
    }
    return $devtypearr;
}



}
