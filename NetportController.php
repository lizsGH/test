<?php
namespace app\controllers;
/**
 * 网络端口
 */
use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class NetportController extends BaseController
{


    /**
     * @列表页
     */
    function actionIndex()
    {
        global $act;
        $aData = array();
        template2($act . '/index', $aData);
    }

    function actionEdit()
    {
        global $act;
        $aData = array();
        template2($act . '/edit', $aData);
    }

    function actionLists()
    {
        global $db;
        $ifconfigmsg = $this->getIfconfigmsg();//var_dump($ifconfigmsg);
        $aData = array();
        //从数据库获取网卡信息(mac地址除外)
        /*$ifconfigmsgfromdb  = $db->fetch_all("SELECT * FROM ".getTable('netport'));
        foreach ($ifconfigmsgfromdb as $k => $v) {
            foreach ($ifconfigmsg as $kk => $vv){
                if($v['netname'] == $kk)
                    $v['macaddress'] = $vv['macaddress'];
            }
            $aItem = array(
                "status" => $v['status'],
                "netname" => $v['netname'],
                "ipaddress" => $v['ipaddress'],
                "subnetmask" => $v['subnetmask'],
                "ipv6address" => $v['ipv6address']."/".$v['ipv6prefix'],
                "ipv6prefix" => $v['ipv6prefix'],
                "ipv6weichuli" => $v['ipv6address'],
                "macaddress" => $v['macaddress']
            );
            array_push($aData, $aItem);
        }*/
        //原来是使用linux命令获取网卡信息
        foreach ($ifconfigmsg as $k => $v) {
            $aItem = array(
                "status" => $v['status'],
                "netname" => $k,
                "ipaddress" => $v['ipaddress'],
                "subnetmask" => $v['mask'],
                //"ipv6address" => $v['ipv6address'],
                "ipv6address" => $v['ipv6prefix'] == '' ? $v['ipv6address'] : $v['ipv6address'] . "/" . $v['ipv6prefix'],
                "ipv6prefix" => $v['ipv6prefix'],
                "ipv6weichuli" => $v['ipv6address'],
                "macaddress" => $v['macaddress'],
                "mtu" => $v['mtu'],
            );
            array_push($aData, $aItem);
        }
        $iTotal = count($ifconfigmsg);
        $aJson = array();
        $aJson['Rows'] = $aData;
        $aJson['Total'] = $iTotal;
        echo json_encode($aJson);
        exit;

    }

    /**
     * @return array
     * 获取ifconfig
     */
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

    function liststes()
    {
        $hData = $data = array();
        $hData[0]['netname'] = 'bnet1';
        $hData[1]['netname'] = 'bnet2';
        $hData[2]['netname'] = 'bnet3';

        $hData[0]['ipaddress'] = '172.3.3.2';
        $hData[1]['ipaddress'] = '175.3.2.1';
        $hData[2]['ipaddress'] = '174.1.3.5';

        $hData[0]['subnetmask'] = '255.255.255.0';
        $hData[1]['subnetmask'] = '255.255.255.0';
        $hData[2]['subnetmask'] = '255.255.255.0';

        $hData[0]['istatus'] = 'UP';
        $hData[1]['istatus'] = 'DOWN';
        $hData[2]['istatus'] = 'UP';

        $data['Rows'] = $hData;
        $data['Total'] = 3;
        echo json_encode($data);
        exit;
    }

    function actionUpdatenetport()
    {
        global $db, $act, $show;
        $aPost = $_POST;
        if (!empty($aPost['ipv6address']) && !$this->filter_ip($aPost['ipv6address'])) {
            $success = false;
            $msg = "ipv6地址格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }
        if (!filter_ip($aPost['ipaddress'])) {
            $success = false;
            $msg = "ipv4地址格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }
        if (!filter_ip($aPost['subnetmask'])) {
            $success = false;
            $msg = "子网掩码格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }
        if (!empty($aPost['ipv6prefix'])) {
            $aPost['ipv6prefix'] = intval($aPost['ipv6prefix']);
            if ($aPost['ipv6prefix'] > 127 || $aPost['ipv6prefix'] < 1) {
                $success = false;
                $msg = "ipv6前缀必须在1~127的范围内";
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
        $aPost['oldnetname'] = filterStr($aPost['oldnetname']);
        $aPost['oldipv6address'] = filterStr($aPost['oldipv6address']);
        $aPost['oldipv6prefix'] = filterStr($aPost['oldipv6prefix']);

        $sql = "select count(*) as num from " . getTable('netport') . " where netname ='" . $aPost['netname'] . "'";
        $rs = $db->fetch_first($sql);
        $aJson = array();
        if ($rs['num'] > 0) {
            $updatesql = "update ".getTable('netport')." set ipaddress ='" . $aPost['ipaddress'] . "', subnetmask = '" . $aPost['subnetmask'] . "', ipv6address = '" . $aPost['ipv6address'] . "', ipv6prefix = '" . $aPost['ipv6prefix'] . "', status ='" . $aPost['status'] . "' where netname = '" . $aPost['netname'] . "'";
            if ($db->query($updatesql)) {
                 $this->setifcigeth($aPost['netname'],$aPost['ipaddress'],$aPost['subnetmask']);
                $shell = "/sbin/ifconfig " . $aPost['netname'] . " " . $aPost['ipaddress'] . " netmask " . $aPost['subnetmask'];
                shellResult($shell);
                if (!empty($aPost['ipv6address'])) {
                    //删除ipv6地址 :ifconfig 接口 inet6 del ipv6地址/前缀
                    $oldipv6shell = "/sbin/ifconfig " . $aPost['oldnetname'] . " inet6 del " . $aPost['oldipv6address'] . "/" . $aPost['oldipv6prefix'];
                    shellResult($oldipv6shell);
                    //添加ipv6地址 :ifconfig 接口 inet6 add ipv6地址/前缀
                    $ipv6shell = "/sbin/ifconfig " . $aPost['netname'] . " inet6 add " . $aPost['ipv6address'] . "/" . $aPost['ipv6prefix'];
                    ///sbin/ifconfig eth3 inet6 add 1234:1234:1234::ee/64
                    shellResult($ipv6shell);
                }

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
        } else {
            $insertsql = "insert into  " . getTable('netport') . " (id,ipaddress,subnetmask,netname,ipv6address,ipv6prefix,status) values('','" . $aPost['ipaddress'] . "','" . $aPost['subnetmask'] . "','" . $aPost['netname'] . "','" . $aPost['ipv6address'] . "','" . $aPost['ipv6prefix'] . "','" . $aPost['status'] . "')";
            if ($db->query($insertsql)) {
                $this->setifcigeth($aPost['netname'],$aPost['ipaddress'],$aPost['subnetmask']);
                $shell = "/sbin/ifconfig " . $aPost['netname'] . " " . $aPost['ipaddress'] . " netmask " . $aPost['subnetmask'];
                shellResult($shell);
                $stShell = "/sbin/ifconfig " . $aPost['netname'] . " " . $aPost['status'];
                shellResult($stShell);
                if (!empty($aPost['ipv6address'])) {
                    //添加ipv6地址 :ifconfig 接口 inet6 add ipv6地址/前缀
                    $ipv6shell = "/sbin/ifconfig " . $aPost['netname'] . " inet6 add " . $aPost['ipv6address'] . "/" . $aPost['ipv6prefix'];
                    shellResult($ipv6shell);
                }

                $aJson['success'] = true;
                $aJson['msg'] = '创建成功！';
                $hdata['sDes'] = '网口创建';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        }
        echo json_encode($aJson);
        exit;
    }

    function setifcigeth($eth, $ipadd, $mask)
    {
        $ethfile = "/etc/sysconfig/network-scripts/ifcfg-" . $eth;
        $dns = file_get_contents(DIR_ROOT . "config/data/system/dns.config");
        $aData['dns'] = unserialize($dns);
        $streth = "DEVICE=" . $eth . "\n";
        $streth .= "BOOTPROTO=static\n";
        $streth .= "IPADDR=" . $ipadd . "\n";
        $streth .= "NETMASK=" . $mask . "\n";
        $streth .= "NM_CONTROLLED=no\n";
        $streth .= "ONBOOT=yes\n";
        $streth .= "TYPE=Ethernet\n";
        if ($aData['dns']['firstDnsIp']) {
            $streth .= "DNS1=" . $aData['dns']['firstDnsIp'] . "\n";
        }
        if ($aData['dns']['firstDnsIp']) {
            $streth .= "DNS2=" . $aData['dns']['alertDnsIp'] . "\n";
        }
        $ethShell = "echo '" . $streth . "' >" . $ethfile;
        //echo $ethShell;die;
        shellResult($ethShell);
    }

//若IP格式不对，则返回false。否则返回true
    function filter_ip($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP))
            return false;
        return true;
    }
}
?>
