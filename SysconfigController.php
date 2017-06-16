<?php
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class SysconfigController extends BaseController
{


    /**
     * @列表页
     */
    function actionIndex()
    {
        global $db, $act;
        $aData = array();
        $userconf = $db->fetch_first("SELECT * FROM " . getTable('userconfig') . " WHERE iId=1");
        $snmpconf = file_get_contents(DIR_ROOT . "data/system/snmp.config");
        $pswstrategy = file_get_contents(DIR_ROOT . "data/system/pswstrategy.config");
        $syslog = file_get_contents(DIR_ROOT . "data/system/syslog.config");
        $stmp = file_get_contents(DIR_ROOT . "data/system/stmp.config");
        $lognum = file_get_contents(DIR_ROOT . "data/system/lognum.config");
        $aData['userconf'] = $userconf;
        $aData['snmpconf'] = unserialize($snmpconf);
        $aData['pswstrategy'] = unserialize($pswstrategy);
        $aData['syslog'] = unserialize($syslog);
        $aData['stmp'] = unserialize($stmp);
        $aData['lognum'] = unserialize($lognum);
        //var_dump($aData);die;
        $sql = "select * from bd_sys_scanset where iId =1";
        $scanset = $db->fetch_first($sql);
        $scanset['allowIPs'] = str_replace(",", "\r\n", $scanset['allowIPs']);
        $scanset['allow_login_ips'] = str_replace(",", "\r\n", $scanset['allow_login_ips']);
        /* $login_ips = explode(",",$scanset['allow_login_ips']);
         $login_ips = array_unique($login_ips);
         foreach($login_ips as $k=>$v){
             $log_ips .= $v."\r\n";//172.16.22.22-66\r\n56465
         }
         $log_ips = rtrim($log_ips,"\r\n");
         $scanset['allow_login_ips'] = str_replace("\\r\\n","",$log_ips);

         $cl_ips = explode(",",$scanset['allowIPs']);
         $cl_ips = array_unique($cl_ips);
         foreach($cl_ips as $k=>$v){
             $post_ips .= $v."\r\n";
         }
         $post_ips = rtrim($post_ips,"\r\n");
         $scanset['allowIPs'] = str_replace("\\r\\n","",$post_ips);*/

        $aData['scanset'] = $scanset;
        template2($act . '/index', $aData);
    }

    function getscanset()
    {
        global $db;
        $sql = "select * from bd_sys_scanset where iId =1";
        $scanset = $db->fetch_first($sql);
        $data['data'] = $scanset;
        $data['success'] = true;
        echo json_encode($data);
        exit;
    }

    /**
     * @ 登录失败设置
     */
    function actionLoginsetting()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $maxError = intval($sPost['maxError']);
        $lockTime = intval($sPost['lockTime']);
        $query = "update " . getTable('userconfig') . " set maxError=$maxError,lockTime=$lockTime WHERE iId=1";
        if ($db->query($query)) {
            $success = true;
            $msg = "操作成功";
            $hdata['sDes'] = '登录失败设置';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = "操作失败";
            $hdata['sDes'] = '登录失败设置';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /**
     * @ 自动退出设置
     */
    function actionAuto_logout_set()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $iSessionTimeout = intval($sPost['iSessionTimeout']);
        $query = "update " . getTable('userconfig') . " set iSessionTimeout=$iSessionTimeout WHERE iId=1";
        if ($db->query($query)) {
            $success = true;
            $msg = "操作成功";
            $hdata['sDes'] = '自动退出设置';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = "操作失败";
            $hdata['sDes'] = '自动退出设置';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /**
     * @ SNMP设置
     */
    function snmpSet()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        $aData['iStatus'] = intval($sPost['iStatus']);
        $aData['iVersion'] = filterStr($sPost['iVersion']);
        $aData['iIp'] = filterStr($sPost['iIp']);
        $aData['iPort'] = filterStr($sPost['iPort']);
        $aData['iTimespan'] = intval($sPost['iTimespan']);

        $sFile = DIR_ROOT . "data/system/snmp.config";
        if (file_put_contents($sFile, serialize($aData))) {
            $aJson['msg'] = "操作成功";
            $aJson ['success'] = true;
            $hdata['sDes'] = 'SNMP设置';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = "操作失败";
            $aJson ['success'] = false;
            $hdata['sDes'] = 'SNMP设置';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ 密码安全策略
     */
    function actionPwd_safe_policy()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        $aData['pLength'] = intval($sPost['pLength']);
        $aData['pStrength'] = intval($sPost['pStrength']);
        $aData['pPeriod'] = intval($sPost['pPeriod']);

        $sFile = DIR_ROOT . "data/system/pswstrategy.config";
        if (file_put_contents($sFile, serialize($aData))) {
            $aJson['msg'] = "操作成功";
            $aJson ['success'] = true;
            $hdata['sDes'] = '密码安全策略';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = "操作失败";
            $aJson ['success'] = false;
            $hdata['sDes'] = '密码安全策略';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ SYSLOG配置
     */
    function actionSyslogsetting()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        $aData['lService'] = filterStr($sPost['lService']);
        $aData['lPort'] = intval($sPost['lPort']);

        $sFile = DIR_ROOT . "data/system/syslog.config";
        if (file_put_contents($sFile, serialize($aData))) {
            $aJson['msg'] = "操作成功";
            $aJson ['success'] = true;
            $hdata['sDes'] = 'SYSLOG配置';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = "操作失败";
            $aJson ['success'] = false;
            $hdata['sDes'] = 'SYSLOG配置';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ 邮件服务器设置
     */
    function stmpsetting()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        if (!empty($sPost['default_set'])) { //恢复默认设置
            //$aData['stAuthType'] = intval($sPost['stAuthType']);
            $aData['stService'] = DEFAULT_SERVER_IP;
            $aData['stPort'] = DEFAULT_SERVER_PORT;
            $aData['stEmail'] = DEFAULT_EMAIL;
            $aData['stPassword'] = DEFAULT_EMAIL_PASSWORD;
        } else {
            //$aData['stAuthType'] = intval($sPost['stAuthType']);
            $aData['stService'] = filterStr($sPost['stService']);
            $aData['stPort'] = intval($sPost['stPort']);
            $aData['stEmail'] = filterStr($sPost['stEmail']);
            $aData['stPassword'] = filterStr($sPost['stPassword']);
        }

        $email = $aData['stEmail'];
        $password = $aData['stPassword'];

        $sFile = DIR_ROOT . "data/system/stmp.config";
        if (file_put_contents($sFile, serialize($aData))) {
            exec("/home/bluedon/openvas/bdscan/setmsmtp  $email $password");
            $aJson['msg'] = "操作成功";
            $aJson ['success'] = true;
            $hdata['sDes'] = '邮件服务器设置';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = "操作失败";
            $aJson ['success'] = false;
            $hdata['sDes'] = '邮件服务器设置';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /*
     * 日志设置
     */

    function actionSetlogmax()
    {
        global $act, $show;
        $sPost = $_POST;
        $aData = array();
        $aData['iLognumwarn'] = intval($sPost['iLognumwarn']);
        $aData['iLogrecnum'] = intval($sPost['iLogrecnum']);
        $aData['iLogautobackup'] = intval($sPost['iLogautobackup']);
        $aData['iLogsettime'] = time();

        $sFile = DIR_ROOT . "data/system/lognum.config";
        if (file_put_contents($sFile, serialize($aData))) {
            $aJson['msg'] = "操作成功";
            $aJson ['success'] = true;
            $hdata['sDes'] = '日志阀值设置';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = "操作失败";
            $aJson ['success'] = false;
            $hdata['sDes'] = '日志阀值设置';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /*
     * 判断日志是否已经超过阀值
     */
    function actionCheckLogset()
    {
        global $db, $act, $show;
        $files = $sFile = DIR_ROOT . "data/system/lognum.config";
        if (file_exists($files)) {
            $lognum = file_get_contents($files);
            $lognumdata = unserialize($lognum);

            $user_role = $db->fetch_first("select role_id from bd_sys_user where username='" . $_SESSION['username'] . "'");
            $where = " ";
            if ($user_role['role'] == 4) {
                $where .= " where sOperateRoleId = 3 OR sOperateRoleId = 0";
            }
            if ($user_role['role'] == 3) {
                $where .= " where sOperateRoleId = 4 OR sOperateRoleId = 16";
            }
            $logcount = $db->fetch_first("select count(*) as logcount from " . 'bd_sys_operatelog' . $where);
            $maxlognum = ceil($lognumdata['iLogrecnum'] * $lognumdata['iLognumwarn'] / 100) - 1;
            /*var_dump($logcount['logcount']);
            var_dump($maxlognum);*/

            if ($logcount['logcount'] >= $maxlognum && $user_role['role'] != 16) {
                $aJson['success'] = true;
                $aJson['msg'] = "日志数据存储量已经达到阀值,请导出备份数据或者清除部分数据！";
                echo json_encode($aJson);
            } else {
                $aJson['success'] = false;
                echo json_encode($aJson);
            }

        } else {
            $aJson['success'] = false;
            echo json_encode($aJson);
        }
    }

    /*
     * 扫描设置
     */

    function actionSetscanset()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        if ($sPost) {
            $smrws = intval($sPost['smrws']);
            //allowIPs
            $allowIPs = trim($sPost['allowIPs']);
            //allow_login_ips
            $allow_login_ips = trim($sPost['allow_login_ips']);

            //allowIPs
            if (!empty($allowIPs)) {
                $allowIPs = nl2br($allowIPs);  //将分行符"\r\n"转义成HTML的换行符"<br />"
                $allowIPs = str_replace("<br />", ",", $allowIPs);
                $allowIPs = str_replace("\r\n", "", $allowIPs);

                $a_allowIps = explode(",", $allowIPs);
                $a_allowIps_arr = array();
                foreach ($a_allowIps as $v) {
                    if (strrpos($v, '-')) {
                        $a_target_str = explode("-", trim($v));
                        $sl = $a_target_str[0];
                        $sr = $a_target_str[1];

                        if (strpos($sl, '.') && !strpos($sr, '.')) {
                            $sr = substr($sl, 0, (strrpos($sl, '.') + 1)) . $sr;
                        } else if (strpos($sl, ':') && !strpos($sr, ':')) {
                            $sr = substr($sl, 0, (strrpos($sl, ':') + 1)) . $sr;
                        }
                        $a_allowIps_arr[] = $sl . '-' . $sr;
                    } else {
                        $a_allowIps_arr[] = $v;
                    }
                }
                //验证ip格式
                foreach ($a_allowIps_arr as $k => $v) {
                    $s_r = $k + 1;
                    if (strrpos($v, '-')) {
                        $ips_yanzhen = explode("-", trim($v));
                        $leftIP = $ips_yanzhen[0];
                        $rightIP = $ips_yanzhen[1];
                        if (!filter_var($leftIP, FILTER_VALIDATE_IP) || !filter_var($rightIP, FILTER_VALIDATE_IP)) {
                            $data['success'] = false;
                            $data['msg'] = '第' . $s_r . '行ip格式错误';
                            echo json_encode($data);
                            exit;
                        }
                    } else {
                        if (!filter_var($v, FILTER_VALIDATE_IP)) {
                            $data['success'] = false;
                            $data['msg'] = '第' . $s_r . '行ip格式错误';
                            echo json_encode($data);
                            exit;
                        }
                    }
                }
                $allowIPs = implode(',', $a_allowIps_arr);
            }


            //allow_login_ips
            if (!empty($allow_login_ips)) {
                $allow_login_ips = nl2br($allow_login_ips);  //将分行符"\r\n"转义成HTML的换行符"<br />"
                $allow_login_ips = str_replace("<br />", ",", $allow_login_ips);
                $allow_login_ips = str_replace("\r\n", "", $allow_login_ips);

                $a_allow_login_Ips = explode(",", $allow_login_ips);
                $a_allowloginIps_arr = array();
                foreach ($a_allow_login_Ips as $v) {
                    if (strrpos($v, '-')) {
                        $a_target_str = explode("-", trim($v));
                        $sl = $a_target_str[0];
                        $sr = $a_target_str[1];

                        if (strpos($sl, '.') && !strpos($sr, '.')) {
                            $sr = substr($sl, 0, (strrpos($sl, '.') + 1)) . $sr;
                        } else if (strpos($sl, ':') && !strpos($sr, ':')) {
                            $sr = substr($sl, 0, (strrpos($sl, ':') + 1)) . $sr;
                        }
                        $a_allowloginIps_arr[] = $sl . '-' . $sr;
                    } else {
                        $a_allowloginIps_arr[] = $v;
                    }
                }
                //验证ip格式
                foreach ($a_allowloginIps_arr as $k => $v) {
                    $s_r = $k + 1;
                    if (strrpos($v, '-')) {
                        $ips_yanzhen_login = explode("-", trim($v));
                        $lIP = $ips_yanzhen_login[0];
                        $rIP = $ips_yanzhen_login[1];
                        if (!filter_var($lIP, FILTER_VALIDATE_IP) || !filter_var($rIP, FILTER_VALIDATE_IP)) {
                            $data['success'] = false;
                            $data['msg'] = '第' . $s_r . '行ip格式错误';
                            echo json_encode($data);
                            exit;
                        }
                    } else {
                        if (!filter_var($v, FILTER_VALIDATE_IP)) {
                            $data['success'] = false;
                            $data['msg'] = '第' . $s_r . '行ip格式错误';
                            echo json_encode($data);
                            exit;
                        }
                    }

                }
                $allow_login_ips = implode(',', $a_allowloginIps_arr);
            }


            $sql = "update bd_sys_scanset set smrws = " . $smrws . ",allowIPs ='" . $allowIPs . "' , allow_login_ips ='" . $allow_login_ips . "' where iId =1";
            if ($db->query($sql)) {
                $aJson['success'] = true;
                $aJson['msg'] = "保存成功";
                echo json_encode($aJson);
                exit;
            } else {
                $aJson['success'] = false;
                $aJson['msg'] = "保存失败";
                echo json_encode($aJson);
                exit;
            }

        }
    }
}
?>