<?php
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class ServerconfigController extends BaseController
{

    /**
     * @列表页
     */
    function actionIndex()
    {
        $sFlg = 'ppp';
//    $sFlg = 'bnet';
        exec("/sbin/ifconfig -a | grep " . $sFlg, $aData);
        $aJson = array();
        foreach ($aData as $k => $v) {
            $aJson[$sFlg . $k] = getInterForceData($sFlg . $k);
        }
       # var_dump($aJson);die;
        global $db, $act;
        $aData = array();
        $userconf = $db->fetch_first("SELECT * FROM " . getTable('userconfig') . " WHERE iId=1");
        $ftpconf = file_get_contents(DIR_ROOT . "../config/data/system/ftp.config");
        $pptp = file_get_contents(DIR_ROOT . "../config/data/system/pptp.config");
        $syslog = file_get_contents(DIR_ROOT . "../config/data/system/syslog.config");
        $stmp = file_get_contents(DIR_ROOT . "../config/data/system/stmp.config");
        $aData['userconf'] = $userconf;
        $aData['ftpconf'] = unserialize($ftpconf);//var_dump($pptp);
        $aData['pptp'] = unserialize($pptp);//var_dump($aData['pptp']);
        $aData['syslog'] = unserialize($syslog);
        $aData['stmp'] = unserialize($stmp);
        $sql = "select * from bd_sys_scanset where iId =1";
        $scanset = $db->fetch_first($sql);
        $aData['scanset'] = $scanset;
        $aData['pp'] = $aJson['ppp0'];
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
     * @ ftp配置
     */
    function actionFtpsetting()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        $aData['lService'] = filterStr($sPost['lService']);
        $aData['lPath'] = filterStr($sPost['lPath']);
        $aData['lUser'] = filterStr($sPost['lUser']);
        $aData['lPassword'] = filterStr($sPost['lPassword']);
        //$aData['lCode'] = filterStr($sPost['lCode']);
        $aData['lCode'] = 'GB2312';

        $sFile = DIR_ROOT . "../config/data/system/ftp.config";
        if (file_put_contents($sFile, serialize($aData))) {
            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', 'ftp配置');
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            $hdata['sDes'] = Yii::t('app', 'ftp配置');
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    //pptp配置
    function actionPptpconf()
    {
        $aData = array();
        $pptp = file_get_contents(DIR_ROOT . "../config/data/system/pptp.config");
        $aData = unserialize($pptp);
        //var_dump($aData);die;
        $aJson ['success'] = true;
        $aJson ['lAction'] = $aData['lAction'];
        //$aJson ['lAction'] = 'pppp';
        $aJson ['lStatus'] = $aData['lStatus'];
        $aJson ['lIp'] = $aData['lIp'];
        echo json_encode($aJson);
        exit;
    }

    /**
     * @ pptp配置
     */
    function actionPptpsetting()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        //var_dump($sPost);exit;
        $aData['lService'] = filterStr($sPost['lService']);
        $aData['lUser'] = filterStr($sPost['lUser']);
        $aData['lPassword'] = filterStr($sPost['lPassword']);
        $aData['lReconnect'] = intval($sPost['lReconnect']);
        $aData['lRetimer'] = intval($sPost['lRetimer']);
        $aData['lKeepconnect'] = intval($sPost['lKeepconnect']);
        $aData['lStatus'] = intval($sPost['lStatus']);
        $aData['lIp'] = filterStr($sPost['lIp']);
        $aData['lAction'] = filterStr($sPost['lAction']);
//var_dump(serialize($aData));exit;
        $sFile = DIR_ROOT . "../config/data/system/pptp.config";
        if (file_put_contents($sFile, serialize($aData))) {
            /*if($aData['lAction'] == 'start'){
                shellResult("kill -9 vpn.sh");
                shellResult("/home/bluedon/openvas/bdscan/netserver/vpn.sh &");
            }*/
            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', 'pptp配置');
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            $hdata['sDes'] = Yii::t('app', 'pptp配置');
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }
    /**
     * @pptp路由设置
     */
    function actionPptprouteSetting(){

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

        $sFile = DIR_ROOT . "../config/data/system/syslog.config";
        if (file_put_contents($sFile, serialize($aData))) {
            //exec("/home/bluedon/openvas/bdscan/netserver/syslogctl");
            exec("/home/bluedon/bdscan/bdnetserver/bdsyslog/bdsyslog");

            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', 'SYSLOG配置');
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            $hdata['sDes'] = Yii::t('app', 'SYSLOG配置');
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ 邮件服务器设置
     */
    function actionStmpsetting()
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

        $sFile = DIR_ROOT . "../config/data/system/stmp.config";
        if (file_put_contents($sFile, serialize($aData))) {
            exec("/home/bluedon/openvas/bdscan/setmsmtp  $email $password");
            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', '邮件服务器设置');
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            $hdata['sDes'] = Yii::t('app', '邮件服务器设置');
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ 看是否有ftp设置
     */
    function actionIsftp()
    {
        $ftpconf = file_get_contents(DIR_ROOT . "../config/data/system/ftp.config");
        if (!empty(unserialize($ftpconf)['lService'])) {
            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ 看是否有pptp设置
     */
    function actionIspptp()
    {
        $pptp = file_get_contents(DIR_ROOT . "../config/data/system/pptp.config");
        if (!empty(unserialize($pptp)['lService'])) {
            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }
    }
    /**
     * pptpstatus
     */
    function actionPptpstatus(){
        header("cache-control:no-cache,must-revalidate");
        header("Content-Type:text/html;charset=utf-8");
        $aData = array();
        $pptp  = file_get_contents("/home/bluedon/bdscan/bdwebserver/nginx/html/config/data/system/pptp.config");
        $pptp = unserialize($pptp);
        $vpnStatus = isset($pptp['lStatus']) ? $pptp['lStatus']:0;
        if($vpnStatus == 0){
            $status = Yii::t('app', '已断开');
        }else if($vpnStatus == 1){
            $status = Yii::t('app', '已连接') . '! &nbsp;&nbsp; ' . Yii::t('app', 'ip地址') . '：'.$pptp['lIp'];
            $sFlg = 'ppp';
            exec("/sbin/ifconfig -a | grep " . $sFlg, $aData);
            $aJson = array();
            foreach ($aData as $k => $v) {
                $aJson[$sFlg . $k] = getInterForceData($sFlg . $k);
            }
            $status = '已连接!　　ip地址：'.$pptp['lIp'];
        }
        $json = json_encode(['status'=>$status,'pptp_route'=>$aJson]);
        echo $json;
    }

    function actionUpdatenetport()
    {
        global $db, $act, $show;
        $aPost = $_POST;

        if (!filter_ip($aPost['ipaddress'])) {
            $success = false;
            $msg = "ipv4地址格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }
        if (!filter_ip($aPost['mask'])) {
            $success = false;
            $msg = "掩码格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }
        $sFlg = 'ppp';
        exec("/sbin/ifconfig -a | grep " . $sFlg, $aData);
        $aJson = array();
        foreach ($aData as $k => $v) {
            $aJson[$sFlg . $k] = getInterForceData($sFlg . $k);
        }
        $aPost['oldnetname'] = filterStr($aJson['ppp0']['ipaddress']);
        $aPost['oldipv6address'] = filterStr($aJson['oldipv6address']);
        $aPost['oldipv6prefix'] = filterStr($aJson['oldipv6prefix']);


        $aJson = array();
        if (1) {
            //$updatesql = "update ".getTable('netport')." set ipaddress ='" . $aPost['ipaddress'] . "', subnetmask = '" . $aPost['subnetmask'] . "', ipv6address = '" . $aPost['ipv6address'] . "', ipv6prefix = '" . $aPost['ipv6prefix'] . "', status ='" . $aPost['status'] . "' where netname = '" . $aPost['netname'] . "'";
            if (1) {
                $this->setifcigeth($aPost['netname'],$aPost['ipaddress'],$aPost['subnetmask']);
                $shell = "/sbin/ifconfig " . $aPost['netname'] . " " . $aPost['ipaddress'] . " netmask " . $aPost['subnetmask'];
                shellResult($shell);


                if ($aPost['status'] == 'UP') {
                    $stShell = "/sbin/ifconfig " . $aPost['netname'] . " up";
                    shellResult($stShell);
                } else {
                    $stShell = "/sbin/ifconfig " . $aPost['netname'] . " down";
                    shellResult($stShell);
                }

                $aJson['success'] = true;
                // $aJson['msg'] = '更新成功！';
                $hdata['sDes'] = '网口更新';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        }
        echo json_encode($aJson);
        exit;
    }
}
