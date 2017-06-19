<?php
namespace app\controllers;

//网络配置
use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class NetconfigController extends BaseController
{
    /**
     * @列表页
     */
    function actionIndex()
    {
        global $db, $act;
        $aData = array();
        $dns = file_get_contents(DIR_ROOT . "../config/data/system/dns.config");
        $aData['dns'] = unserialize($dns);
        template2($act . '/index', $aData);
    }

//若IP格式不对，则返回false。否则返回true
    function filter_ip($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP))
            return false;
        return true;
    }

    /**
     * @ DNS服务器设置
     */
    function actionDnssetting()
    {
        global $act, $show, $db;
        $aData = array();
        $sPost = $_POST;
        foreach ($sPost as $v) {
            if ($v != '') {
                if (!$this->filter_ip($v) || '0.0.0.0' == $v) {
                    $success = false;
                    $msg = Yii::t('app', 'dns地址') . $v . Yii::t('app', '不合法');
                    //$msg = "dns地址 $v 格式错误";
                    $data['success'] = $success;
                    $data['msg'] = $msg;
                    echo json_encode($data);
                    exit;
                }
            }
        }
        $aData['firstDnsIp'] = $sPost['firstDnsIp'];
        $aData['alertDnsIp'] = $sPost['alertDnsIp'];
        $aData['firstDnsIpv6'] = $sPost['firstDnsIpv6'];
        $aData['alertDnsIpv6'] = $sPost['alertDnsIpv6'];

        $tSql = "select id from " . getTable('dns') . " where 1=1";
        $num = $db->result_first($tSql);
        //var_dump($num);die;
        if (!empty($num)) {
            $query = "update " . getTable('dns') . " set ipv4_first = '" . $aData['firstDnsIp'] . "', ipv4_second ='" . $aData['alertDnsIp'] . "', ipv6_first = '" . $aData['firstDnsIpv6'] . "', ipv6_second ='" . $aData['alertDnsIpv6'] . "' where id=" . $num;
            $db->query($query);
        } else {

            $query = "insert into " . getTable('dns') . " (id ,ipv4firstdns,ipv4alertdns,ipv6firstdns,ipv6alertdns) values('','" . $aData['firstDnsIp'] . "','" . $aData['alertDnsIp'] . "','" . $aData['firstDnsIpv6'] . "','" . $aData['alertDnsIpv6'] . "')";
            if (!$db->query($query)) {
                $success = false;
                $msg = Yii::t('app', "操作失败");
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
        $dnsseting = "nameserver " . $aData['firstDnsIp'] . "\n";
        $dnsseting .= "nameserver " . $aData['alertDnsIp'] . "\n";
        $dnsseting .= "nameserver " . $aData['firstDnsIpv6'] . "\n";
        $dnsseting .= "nameserver " . $aData['alertDnsIpv6'] . "\n";
        $sFile = DIR_ROOT . "../config/data/system/dns.config";
        //echo $sFile;die;
        //var_dump(file_put_contents($sFile, serialize($aData)));die;
        //var_dump($aData);die;
        if (file_put_contents($sFile, serialize($aData))) {
            $dnsfile = "/etc/resolv.conf";
           // var_dump($dnsseting);die;
            file_put_contents($dnsfile, $dnsseting);
          //  var_dump(file_put_contents($dnsfile, $dnsseting));die;
            //exec("cp $sFile /etc/resolv1.conf ");
           // $this->setdnsconfig($aData['firstDnsIp'],$aData['alertDnsIp']);
            $aJson['msg'] = Yii::t('app', '操作成功');
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', 'DNS服务器设置');
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            $hdata['sDes'] = Yii::t('app', 'DNS服务器设置');
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        }
    }


    function getIfconfigmsg()
    {
        $aJson = array();
        $sFlg = 'eth';
//    $sFlg = 'bnet';
        exec("/sbin/ifconfig -a | grep " . $sFlg, $aData);
        $aJson = array();
        foreach ($aData as $k => $v) {
            $aJson[$sFlg . $k] = getInterForceData($sFlg . $k);
        }
        return $aJson;
    }

    function setdnsconfig($v4dns1, $v4dns2)
    {
        $eth = $this->getIfconfigmsg();//var_dump($eth);die;
        $streth = "";
        $dShell = "";
        foreach ($eth as $k => $v) {
            if (!strrpos($v['ipaddress'], ':')) {//如果不是ipv6
                $streth = "DEVICE=" . $k . "\n";
                $streth .= "BOOTPROTO=static\n";
                $streth .= "IPADDR=" . $v['ipaddress'] . "\n";
                $streth .= "NETMASK=" . $v['mask'] . "\n";
                $streth .= "ONBOOT=yes\n";
                $streth .= "TYPE=Ethernet\n";
                $streth .= "DNS1=" . $dns1 . "\n";
                $streth .= "DNS2=" . $dns2 . "\n";
                $dShell = "echo '" . $streth . "' > /etc/sysconfig/network-scripts/ifcfg-" . $k;
               // echo $dShell;die;
                shellResult($dShell);
            } else {//ipv6保存格式等待决定，择日完成
                $streth = "DEVICE=" . $k . "\n";
                $streth .= "BOOTPROTO=static\n";
                $streth .= "IPADDR=" . $v['ipaddress'] . "\n";
                $streth .= "NETMASK=" . $v['mask'] . "\n";
                $streth .= "ONBOOT=yes\n";
                $streth .= "TYPE=Ethernet\n";
                $streth .= "DNS1=" . $dns1 . "\n";
                $streth .= "DNS2=" . $dns2 . "\n";
                $dShell = "echo '" . $streth . "' > /etc/sysconfig/network-scripts/ifcfg-" . $k;
                shellResult($dShell);
            }
        }

    }


}
?>
