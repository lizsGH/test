<?php
namespace app\controllers;
/**
 * 系统维护
 */
use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class SysmaintenanceController extends BaseController
{


//init 0//关机
//init 6//重启
    /**
     * @列表页
     */
    function actionIndex()
    {
        global $db, $act;
        $sql = "select * from " . getTable('sysinfo');
        $res = $db->fetch_first($sql);
        $aData = array('sysinfo' => $res);
        template2($act . '/index', $aData);
    }

    function actionRebootsys()
    {
        global $act, $show;
        $hdata['sDes'] = Yii::t('app', '重启系统');
        $hdata['sRs'] = Yii::t('app', '成功');
        $hdata['username'] = filterStr($_SESSION['username']);
        $hdata['sAct'] = $act . '/' . $show;
        saveOperationLog($hdata);
        $shell = "/sbin/shutdown -r now";
        shellResult($shell);
    }

    function actionClosesys()
    {
        global $act, $show;
        $hdata['sDes'] = Yii::t('app', '关闭系统');
        $hdata['sRs'] = Yii::t('app', '成功');
        $hdata['username'] = filterStr($_SESSION['username']);
        $hdata['sAct'] = $act . '/' . $show;
        saveOperationLog($hdata);

        $shell = "/sbin/shutdown -h now";
        shellResult($shell);
    }

    function actionUpdatesystime()
    {
        $sPost = $_POST;
        if (!empty($sPost['systemtime'])) {
            $dShell = "/bin/date -s '" . filterStr($sPost['systemtime']) . "'";
            shellResult($dShell);
            $Shell = "/sbin/clock";
            shellResult($Shell);
            $ajson['success'] = true;
            $ajson['msg'] = Yii::t('app', "操作成功");
        }
        $ajson['success'] = false;
        $ajson['msg'] = Yii::t('app', "请选择时间");
        echo json_encode($ajson);
        exit;

    }

    /**导出系统配置*/
    function actionExportsysmain()
    {
        global $db, $act, $show;
        $aData = array();
        //路由数据
        $rmShell = "cd /home/bluedon/bdscan/bdwebserver/nginx/html/config/data/bacup/; rm bacup.tar";
        shellResult($rmShell);
        $routesql = "select * from " . getTable('staticroute2');
        $routeres = $db->fetch_all($routesql);
        $aData['staticroute'] = $routeres;
        //网口数据
        $netportsql = "select * from " . getTable('netport');
        $netportres = $db->fetch_all($netportsql);
        $aData['netport'] = $netportres;
        //静态路由
        //系统配置
        $userconf = $db->fetch_first("SELECT * FROM " . getTable('userconfig') . " WHERE iId=1");
        $aData['userconf'] = $userconf;
        //snmp配置
        $snmpconf = file_get_contents(DIR_ROOT . "data/system/snmp.config");
        $aData['snmpconf'] = unserialize($snmpconf);
        //密码安全策略
        $pswstrategy = file_get_contents(DIR_ROOT . "data/system/pswstrategy.config");
        $aData['pswstrategy'] = unserialize($pswstrategy);
        //syslog配置
        $syslog = file_get_contents(DIR_ROOT . "data/system/syslog.config");
        $aData['syslog'] = unserialize($syslog);
        //stmp配置
        //$stmp = file_get_contents(DIR_ROOT . "data/system/stmp.config");
        //加密
        /*$st = unserialize($stmp);
        $st['stEmail'] = authcode($st['stEmail'],'ENCODE','mlsk');
        $st['stPassword'] = authcode($st['stPassword'],'ENCODE','mlsk');
        $aData['stmp'] = $st;*/
        //$aData['stmp'] = unserialize($stmp);
        //dns配置
        $dns = file_get_contents(DIR_ROOT . "data/system/dns.config");
        $aData['dns'] = unserialize($dns);
        //print_r($aData);
        //把数据转换成JSON格式，然后写入文件
        $res = file_put_contents(DIR_ROOT . "data/bacup/bacup.config", serialize($aData));
       // var_dump(DIR_ROOT . "data/bacup/bacup.config");die;
        if ($res > 0) {
            $ajson = array();
            //$sShell = "cd /usr/local/nginx/html/config/data/bacup; zip -q -r -j ./bacup.zip ./bacup.config";
            //shellResult($sShell);
            $ajson['success'] = true;
            $ajson['msg'] = Yii::t('app', "导出成功");
            //file_get_contents(DIR_ROOT . "../config/data/bacup/bacup.config");
            $ajson['downhtml'] = "<a href=\"/data/bacup/bacup.config\" target='_blank'>" . Yii::t('app', '下载导出文件') . "</a>&nbsp;&nbsp;";
            $hdata['sDes'] = Yii::t('app', '导出系统配置');
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($ajson);
            exit;
        } else {
            $ajson['success'] = false;
            $ajson['msg'] = Yii::t('app', "导出失败");
            $ajson['downhtml'] = "<a href=\"data/bacup/bacup.config\">" . Yii::t('app', '下载导出文件') . "</a>&nbsp;&nbsp;";
            echo json_encode($ajson);
            exit;
        }
    }

    //导入配置
    function actionUploadsysmain()
    {
        global $db, $act;
        //首先判断文件的后缀名是否符合要求。
        //判断文件是否上传成功
        $dir = DIR_ROOT . "data/bacup/uploadbac/";
        $aJson = array();
        if (is_uploaded_file($_FILES['filenamepz']['tmp_name'])) {
            $p = '/\.[a-z]+$/i';
            preg_match($p, $_FILES['filenamepz']['name'], $result);
            if ($result[0] == '.config') {
                if (move_uploaded_file($_FILES['filenamepz']['tmp_name'], $dir . $_FILES['filenamepz']['name'])) {
                    $data = file_get_contents($dir . $_FILES['filenamepz']['name']);
                    if ($data) {
                        $aData = unserialize($data);
                        //恢复系统配置数据
                        if (!empty($aData['userconf'])) {
                            $userconfs = $aData['userconf'];
                            $userconfsql = "delete from " . getTable('userconfig');
                            if ($db->query($userconfsql)) {
                                $routeinssql = "insert into " . getTable('userconfig') . "( iId ,maxError,lockTime,iSessionTimeout)
                                  values('" . $userconfs['iId'] . "','" . $userconfs['maxError'] . "','" . $userconfs['lockTime'] . "','" . $userconfs['iSessionTimeout'] . "')";
                                $db->query($routeinssql);
                            }

                        }
                        //恢复snmp配置
                        if (!empty($aData['snmpconf'])) {
                            $sFile = DIR_ROOT . "data/system/snmp.config";
                            file_put_contents($sFile, serialize($aData['snmpconf']));
                        }
                        //恢复密码安全策略
                        if (!empty($aData['pswstrategy'])) {
                            $sFile = DIR_ROOT . "data/system/pswstrategy.config";
                            file_put_contents($sFile, serialize($aData['pswstrategy']));

                        }
                        //恢复syslog配置
                        if (!empty($aData['syslog'])) {
                            $sFile = DIR_ROOT . "data/system/syslog.config";
                            file_put_contents($sFile, serialize($aData['syslog']));
                        }
                        //恢复stmp配置
                        /*if (!empty($aData['stmp'])) {
                            //解密
                            $aData['stmp']['stEmail'] = authcode($aData['stmp']['stEmail'],'DECODE','mlsk');
                            $aData['stmp']['stPassword'] = authcode($aData['stmp']['stPassword'],'DECODE','mlsk');
                            $sFile = DIR_ROOT . "data/system/stmp.config";
                            file_put_contents($sFile, serialize($aData['stmp']));
                            $sShell = "/home/bluedon/openvas/bdscan/setmsmtp  ".$aData['stmp']['stEmail']." ".$aData['stmp']['stPassword'];
                            exec($sShell);
                        }*/
                        //恢复DNS设置
                        if (!empty($aData['dns'])) {
                            $sFile = DIR_ROOT . "data/system/dns.config";
                            file_put_contents($sFile, serialize($aData['dns']));
                        }
                        //恢复网口数据
                        if (!empty($aData['netport'])) {
                            $netports = $aData['netport'];
                            $netsql = "delete from " . getTable('netport');
                            if ($db->query($netsql)) {
                                foreach ($netports as $key => $netport) {
                                    $netinssql = "insert into " . getTable('netport') . "( id ,ipaddress,subnetmask,netname,status)
                                  values('" . $netport['id'] . "','" . $netport['ipaddress'] . "','" . $netport['subnetmask'] . "','" . $netport['netname'] . "','" . $netport['status'] . "')";
                                    $db->query($netinssql);
                                    $netShell = "/sbin/ifconfig " . $netport['netname'] . " " . $netport['ipaddress'] . " netmask " . $netport['subnetmask'];
                                    shellResult($netShell);
                                    if ($netport['status'] == 'DOWN') {
                                        $stateShell = "/sbin/ifconfig " . $netport['netname'] . " down";
                                        shellResult($stateShell);
                                    }
                                }
                            }
                        }
                        //恢复静态路由数据
                        if (!empty($aData['staticroute'])) {
                            //首先要把静态路由删除
                            $routes = $aData['staticroute'];
                            $routesql = "select * from " . getTable('staticroute2');
                            $routeres = $db->fetch_all($routesql);
                            if ($routeres) {
                                foreach ($routeres as $key => $route) {
                                    //$netShell = "/sbin/ifconfig ".$route['netname']." ".$route['ipaddress']." netmask ".$route['subnetmask'];
                                    $routeShell = "/sbin/route del -net " . $route['dest'];
                                    if (!empty($route['gateway'])) {
                                        $routeShell .= " gw " . $route['gateway'];
                                    }
                                    $routeShell .= " " . $route['nic'];
                                    shellResult($routeShell);
                                }
                            }
                            $routesql = "delete from " . getTable('staticroute2');
                            if ($db->query($routesql)) {
                                foreach ($routes as $key => $route) {
                                    $routeinssql = "insert into " . getTable('staticroute2') . "( id ,nic,dest,mask,gateway)
                                  values('" . $route['id'] . "','" . $route['nic'] . "','" . $route['dest'] . "','" . $route['mask'] . "','" . $route['gateway'] . "')";
                                    $db->query($routeinssql);
                                    //$netShell = "/sbin/ifconfig ".$route['netname']." ".$route['ipaddress']." netmask ".$route['subnetmask'];
                                    $routeShell = "/sbin/route add -net " . $route['dest'];
                                    if (!empty($route['gateway'])) {
                                        $routeShell .= " gw " . $route['gateway'];
                                    }
                                    $routeShell .= " " . $route['nic'];
                                    shellResult($routeShell);
                                }
                            }
                        }

                        $aJson['success'] = true;
                        $aJson['msg'] = Yii::t('app', '恢复成功！');
                        echo json_encode($aJson);
                        exit;
                    }
                }
            }
        }
    }

//系统升级

    function actionUpgradesys()
    {
        global $act, $show;
        $dir = "/home/bluedon/bdscan/bdwebserver/nginx/html/data/upsys/";
        $aJson = array();
        if (!empty($_POST['shouquanma'])) {
            if (filterStr($_POST['shouquanma']) != 'D0UhVcnARBpox9ySf22IQ2wzGLem2kj3') {
                $aJson ['success'] = false;
                $aJson ['msg'] = Yii::t('app', '授权码错误');
                echo json_encode($aJson);
                exit;
            }
        }
        if (is_uploaded_file($_FILES['filenamesys']['tmp_name'])) {
            $p = '/\.[a-z]+$/i';
            preg_match($p, $_FILES['filenamesys']['name'], $result);
            if ($result[0] == '.bds') {
                if ($_FILES['filenamesys']['size'] < 200 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES['filenamesys']['tmp_name'], $dir . $_FILES['filenamesys']['name'])) {
                        $shell = "/home/soc/preupgrade/preupgrade -tupgrade -a" . $dir . $_FILES['filenamesys']['name'];
                        if (shellResult($shell)) {
                            $aJson ['success'] = true;
                            $aJson ['msg'] = Yii::t('app', '升级成功。');
                            //$hdata['sDes'] = '系统升级成功'.$_FILES['filenamesys']['tmp_name'];
                            $hdata['sDes'] = Yii::t('app', '系统升级成功');
                            $hdata['sRs'] = Yii::t('app', '成功');
                            $hdata['sAct'] = $act . '/' . $show;
                            saveOperationLog($hdata);
                        } else {
                            $aJson ['success'] = false;
                            $aJson ['msg'] = Yii::t('app', '升级失败，请检查/preupgrade目录是否存在');
                        }
                    } else {
                        $aJson ['success'] = false;
                        $aJson ['msg'] = Yii::t('app', '升级文件格式不对，请联系管理员。');
                        //$hdata['sDes'] = '系统升级失败'.$_FILES['filenamesys']['tmp_name'];
                        $hdata['sDes'] = Yii::t('app', '系统升级失败');
                        $hdata['sRs'] = Yii::t('app', '文件格式不对');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    }
                } else {
                    $aJson ['success'] = false;
                    $aJson ['msg'] = Yii::t('app', '升级的文件太大，请联系管理员。');
                    //$hdata['sDes'] = '系统升级失败'.$_FILES['filenamesys']['tmp_name'];
                    $hdata['sDes'] = Yii::t('app', '系统升级失败');
                    $hdata['sRs'] = Yii::t('app', '升级的文件太大');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            } else {
                $aJson ['success'] = false;
                $aJson ['msg'] = Yii::t('app', '升级文件格式不对，请联系管理员。');
                //$hdata['sDes'] = '系统升级失败'.$_FILES['filenamesys']['tmp_name'];
                $hdata['sDes'] = Yii::t('app', '系统升级失败');
                $hdata['sRs'] = Yii::t('app', '升级文件格式不对');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        } else {
            $aJson ['success'] = false;
            $aJson ['msg'] = Yii::t('app', '文件不能为空') . '!';
            $hdata['sDes'] = Yii::t('app', '系统升级失败');
            $hdata['sRs'] = Yii::t('app', '文件不能为空');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }

        echo json_encode($aJson);
        exit;

    }


    //升级规则
    function actionUpgraderule()
    {
        global $act, $show;
        $dir = "/home/bluedon/bdscan/bdwebserver/nginx/html/data/upsys/";
        $aJson = array();
        if (is_uploaded_file($_FILES['filenamerule']['tmp_name'])) {
            $p = '/\.[a-z]+$/i';
            preg_match($p, $_FILES['filenamerule']['name'], $result);
            if ($result[0] == '.bdr') {
                if ($_FILES['filenamerule']['size'] < 200 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES['filenamerule']['tmp_name'], $dir . $_FILES['filenamerule']['name'])) {
                        $shell = "/home/soc/preupgrade/preupgrade -tupgrade -a" . $dir . $_FILES['filenamerule']['name'];
                        if (shellResult($shell)) {
                            $aJson ['success'] = true;
                            $aJson ['msg'] = Yii::t('app', '升级成功。');
                            //$hdata['sDes'] = '规则升级'.$_FILES['filenamesys']['tmp_name'];
                            $hdata['sDes'] = Yii::t('app', '规则升级');
                            $hdata['sRs'] = Yii::t('app', '成功');
                            $hdata['sAct'] = $act . '/' . $show;
                            saveOperationLog($hdata);
                        }
                    } else {
                        $aJson ['success'] = false;
                        $aJson ['msg'] = Yii::t('app', '升级文件格式不对，请联系管理员。');
                        //$hdata['sDes'] = '规则升级失败'.$_FILES['filenamesys']['tmp_name'];
                        $hdata['sDes'] = Yii::t('app', '规则升级失败');
                        $hdata['sRs'] = Yii::t('app', '升级文件格式不对');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    }
                } else {
                    $aJson ['success'] = false;
                    $aJson ['msg'] = Yii::t('app', '升级的文件太大，请联系管理员。');
                    //$hdata['sDes'] = '规则升级失败'.$_FILES['filenamesys']['tmp_name'];
                    $hdata['sDes'] = Yii::t('app', '规则升级失败');
                    $hdata['sRs'] = Yii::t('app', '升级的文件太大');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            } else {
                $aJson ['success'] = false;
                $aJson ['msg'] = Yii::t('app', '升级文件格式不对，请联系管理员。');
                //$hdata['sDes'] = '规则升级失败'.$_FILES['filenamesys']['tmp_name'];
                $hdata['sDes'] = Yii::t('app', '规则升级失败');
                $hdata['sRs'] = Yii::t('app', '升级文件格式不对');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);

            }
        } else {
            $aJson ['success'] = false;
            $aJson ['msg'] = Yii::t('app', '文件不能为空') . '!';
            $hdata['sDes'] = Yii::t('app', '系统升级失败');
            $hdata['sRs'] = Yii::t('app', '文件不能为空');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);

        }
        echo json_encode($aJson);
        exit;

    }


//恢复出厂配置
    function actionBacupsys()
    {
        global $db;
        $data = file_get_contents(Yii::$app->basePath . "/data/bac/bacup.config");
        //print_r($data);
        $aData = unserialize($data);
        if (!empty($aData)) {
            //恢复网口数据
            if (!empty($aData['netport'])) {
                $netports = $aData['netport'];
                $netsql = "delete from " . getTable('netport');
                if ($db->query($netsql)) {
                    foreach ($netports as $key => $netport) {
                        $netinssql = "insert into " . getTable('netport') . "( id ,ipaddress,subnetmask,netname,status)
                                  values('" . $netport['id'] . "','" . $netport['ipaddress'] . "','" . $netport['subnetmask'] . "','" . $netport['netname'] . "','" . $netport['status'] . "')";
                        $db->query($netinssql);
                        $netShell = "/sbin/ifconfig " . $netport['netname'] . " " . $netport['ipaddress'] . " netmask " . $netport['subnetmask'];
                        shellResult($netShell);
                    }
                }
            }
            //恢复静态路由数据
            if (!empty($aData['route'])) {
                $routes = $aData['route'];
                $routesql = "delete from " . getTable('staticroute2');
                if ($db->query($routesql)) {
                    foreach ($routes as $key => $route) {
                        $routeinssql = "insert into " . getTable('staticroute2') . "( id ,nic,dest,mask,gateway)
                                  values('" . $route['id'] . "','" . $route['nic'] . "','" . $route['dest'] . "','" . $route['mask'] . "','" . $route['gateway'] . "')";
                        $db->query($routeinssql);
                        //$netShell = "/sbin/ifconfig ".$route['netname']." ".$route['ipaddress']." netmask ".$route['subnetmask'];
                        $routeShell = "/sbin/route add -net " . $route['dest'];
                        if (!empty($route['gateway'])) {
                            $routeShell .= " gw " . $route['gateway'];
                        }
                        $routeShell .= " " . $route['nic'];
                        shellResult($routeShell);
                    }
                }
            } else {
                $routesql = "delete from " . getTable('staticroute2');
                $db->query($routesql);
            }
            //恢复系统配置数据
            if (!empty($aData['userconf'])) {
                $userconfs = $aData['userconf'];
                $userconfsql = "delete from " . getTable('userconfig');
                if ($db->query($userconfsql)) {
                    $routeinssql = "insert into " . getTable('userconfig') . "( iId ,maxError,lockTime,iSessionTimeout)
                                  values('" . $userconfs['iId'] . "','" . $userconfs['maxError'] . "','" . $userconfs['lockTime'] . "','" . $userconfs['iSessionTimeout'] . "')";
                    $db->query($routeinssql);
                }

            }
            //恢复snmp配置
            if (!empty($aData['snmpconf'])) {
                $sFile = DIR_ROOT . "data/system/snmp.config";
                file_put_contents($sFile, serialize($aData['snmpconf']));
            }
            //恢复密码安全策略
            if (!empty($aData['pswstrategy'])) {
                $sFile = DIR_ROOT . "data/system/pswstrategy.config";
                file_put_contents($sFile, serialize($aData['pswstrategy']));

            }
            //恢复syslog配置
            if (!empty($aData['syslog'])) {
                $sFile = DIR_ROOT . "data/system/syslog.config";
                file_put_contents($sFile, serialize($aData['syslog']));
            }
            //恢复stmp配置
            /*if (!empty($aData['stmp'])) {
                $sFile = DIR_ROOT . "data/system/stmp.config";
                file_put_contents($sFile, serialize($aData['stmp']));
            $sShell = "/home/bluedon/openvas/bdscan/setmsmtp  ".$aData['stmp']['stEmail']." ".$aData['stmp']['stPassword'];
                exec($sShell);
            }*/
            //恢复DNS设置
            if (!empty($aData['dns'])) {
                $sFile = DIR_ROOT . "data/system/dns.config";
                file_put_contents($sFile, serialize($aData['dns']));
            }
            $aJson['success'] = true;
            $aJson['msg'] = Yii::t('app', '恢复成功！');
            echo json_encode($aJson);
            exit;
        }

    }

    function actionTime(){
       // $showtime = date('Y-m-d H:i:s',time());
        // echo $showtime;
        exec('date "+%Y-%m-%d %H:%M:%S" ',$time);
        echo $time[0];
    }
}
?>
