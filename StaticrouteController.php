<?php
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class StaticrouteController extends BaseController
{


    /**
     * @列表页
     */
    function actionIndex()
    {
        global $act;
        template2($act . '/index', array());
    }

    /**
     * @ 编辑页
     */
    function actionEdit()
    {
        global $act;
        template2($act . '/edit', array());
    }

    /**
     * @获取列表数据
     */
    function actionLists()
    {
        global $db;
        $sPost = $_POST;
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $total = 0;
        $rows = $aData = array();
        $where = "WHERE 1=1";
        $page = $page > 1 ? $page : 1;

        $total = $db->result_first("SELECT COUNT(`nic`) FROM " . getTable('staticroute2') . " $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM " . getTable('staticroute2') . "  $where LIMIT $start,$perpage");
        }
        //$rows =system("/sbin/route -n 2>/dev/stdout");
       // var_dump(str_replace('Kernel IP routing table',$rows));die;
        foreach ($rows as $k => $v) {
            $aItem = array(
                'id' => $v['id'],
                "nic" => $v['nic'],
                "dest" => $v['dest'],
                "mask" => $v['mask'],
                "gateway" => $v['gateway'],
                "ipv6dest" => $v['ipv6dest'],
                "ipv6prefix" => $v['ipv6prefix'],
                "ipv6gateway" => $v['ipv6gateway']

            );
            array_push($aData, $aItem);
        }
        $data['Rows'] = $aData;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }

    /**
     * @新增或者编辑静态路由，保存到数据库
     */
    function actionAddandedit()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        if (!empty($sPost['ipv6dest']) && !filter_ip($sPost['ipv6dest'])) {
            $success = false;
            $msg = "ipv6地址格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }
        if (!empty($sPost['ipv6prefix'])) {
            $sPost['ipv6prefix'] = intval($sPost['ipv6prefix']);
            if ($sPost['ipv6prefix'] > 127 || $sPost['ipv6prefix'] < 1) {
                $success = false;
                $msg = "前缀（ipv6）必须在1~127的范围内";
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
        if (!empty($sPost['ipv6gateway']) && !filter_ip($sPost['ipv6gateway'])) {
            $success = false;
            $msg = "网关（ipv6）格式错误";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }

        $ipv6dest = filterStr($sPost['ipv6dest']);
        $ipv6prefix = filterStr($sPost['ipv6prefix']);
        $ipv6gateway = filterStr($sPost['ipv6gateway']);

        $id = $sPost['id'];
        $oldnic = filterStr($sPost['oldnic']);
        $olddest = filterStr($sPost['olddest']);
        $oldmask = filterStr($sPost['oldmask']);
        $oldgateway = filterStr($sPost['oldgateway']);

        $oldipv6dest = filterStr($sPost['oldipv6dest']);
        $oldipv6prefix = filterStr($sPost['oldipv6prefix']);
        $oldipv6gateway = filterStr($sPost['oldipv6gateway']);

        $nic = filterStr($sPost['nic']);
        //$destarr = explode('.', filterStr($sPost['dest']));
        //var_dump($destarr);die;
        //$dest = $destarr[0] . "." . $destarr[1] . "." . $destarr[2] . '.0';
        $dest = $sPost['dest'];
        //var_dump($dest);die;
        $mask = filterStr($sPost['mask']);
        $gateway = filterStr($sPost['gateway']);

        /*if（网络地址==0.0.0.0）
                {
                   if（网关地址设置）
                   {
                       if（route -n |  awk -F "255" '{print $1}' | grep UG | cut -c 1-7 | grep "0.0.0.0"）
                       {
                        返回提示信息（已存在默认路由）；
                       }
                       else
                       {
                        添加这条路由，返回提示信息（成功或者失败）；
                       }
                   }
                   else
                   {
                        添加这条路由，返回提示信息（成功或者失败）；
                   }
                }
                else
                {
                    添加这条路由，返回提示信息（成功或者失败）；
                }*/
        if ($id) {//编辑
            $nic = $oldnic;//编辑时在页面禁止改变接口
            $tSql = "select count(*) from " . getTable('staticroute2') . " where nic = '" . $nic . "' and dest ='" . $dest . "' and mask = '" . $mask . "' and gateway = '" . $gateway . "' and ipv6dest = '" . $ipv6dest . "' and ipv6prefix = '" . $ipv6prefix . "' and ipv6gateway = '" . $ipv6gateway . "' and id !=" . $id;
            //$tSql = "select count(*) from ".getTable('staticroute2')." where nic = '".$nic."' and dest ='".$dest."' and mask = '".$mask."' and gateway = '".$gateway."' and id !=".$id;
          // echo $tSql;die;
            $num = $db->result_first($tSql);
           // var_dump($num);die;
            if ($num > 0) {
                $success = false;
                $msg = "已经有相同的静态路由";
                $hdata['sDes'] = '编辑静态路由';
                $hdata['sRs'] = '失败';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
            /*$query = "update ".getTable('staticroute2')." set nic='".$nic."',dest='".$dest."',mask='".$mask."',gateway='".$gateway."',ipv6dest='".$ipv6dest."',ipv6prefix='".$ipv6prefix."',ipv6gateway='".$ipv6gateway."' where id='".$id."'";
            if($db->query($query)){
                $dShell = "/sbin/route del -net ".$oldipaddress;
                if(!empty($oldgateway)){
                    $dShell .=" gw ".$oldgateway;
                }
                $dShell .= " ".$oldnic;
                shellResult($dShell);
                $routeShell = "/sbin/route add -net ".$ipaddress;
                if(!empty($gateway)){
                    $routeShell .=" gw ".$gateway;
                }
                $routeShell .= " ".$nic;
                shellResult($routeShell);
                //writeRoute();

                if(!empty($ipv6gateway)){
                    //删除ipv6默认网关:route -A inet6 del default gw ipv6网关地址
                    $oldipv6routeShell = "/sbin/route -A inet6 del default gw ".$oldipv6gateway;
                    $msg = $oldipv6routeShell;
                    shellResult($oldipv6routeShell);
                    //添加ipv6默认网关:route -A inet6 add default gw ipv6网关地址
                    $ipv6routeShell = "/sbin/route -A inet6 add default gw ".$ipv6gateway;
                    $msg = $ipv6routeShell;
                    shellResult($ipv6routeShell);
                }else{
                    //删除ipv6静态路由：route -A inet6 del ipv6地址/前缀 dev 接口
                    $oldipv6routeShell = "/sbin/route -A inet6 del ".$oldipv6dest."/".$oldipv6prefix." dev ".$oldnic;
                    $msg = $oldipv6routeShell;
                    shellResult($oldipv6routeShell);
                    //route -A inet6 add ipv6地址/前缀 dev 接口
                    $ipv6routeShell = "/sbin/route -A inet6 add ".$ipv6dest."/".$ipv6prefix." dev ".$nic;
                    $msg = $ipv6routeShell;
                    shellResult($ipv6routeShell);
                }

                $success = true;
                //$msg = "操作成功";
                $hdata['sDes'] = '编辑静态路由';
                $hdata['sRs'] ='成功';
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }else{
                $success = false;
                $msg = "操作失败";
            }*/
            //删除旧的路由
            $dShell = "/sbin/route del -net " . $olddest . " netmask " . $oldmask;
            if (!empty($oldgateway)) {
                $dShell .= " gw " . $oldgateway;
            }
            $dShell .= " " . $oldnic . " 2>/dev/stdout";
            $handle = popen($dShell, "r");
            $buffer = fgets($handle);
            //var_dump($buffer);die;
            pclose($handle);
            //if (!$buffer) {
                if ($dest == '0.0.0.0') {
                    if (!empty($gateway))//网关地址设置
                    {
                        $handle = popen("/sbin/route -n | awk -F '255' '{print $1}' | grep UG | cut -c 1-7 | grep '0.0.0.0'", "r");
                        $buffer = fgets($handle);
                        pclose($handle);
                        if (!empty($buffer))//返回提示信息（已存在默认路由）；
                        {
                            $dShell = "/sbin/route add -net " . $olddest . " netmask " . $oldmask;
                            if (!empty($oldgateway)) {
                                $dShell .= " gw " . $oldgateway;
                            }
                            $dShell .= " " . $oldnic;
                            shellResult($dShell);

                            $success = false;
                            $msg = "已存在默认路由";
                            $data['success'] = $success;
                            $data['msg'] = $msg;
                            echo json_encode($data);
                            exit;
                        } else {
                            $routeShell = "/sbin/route add -net " . $dest . " netmask " . $mask . " gw " . $gateway . " " . $nic . " 2>/dev/stdout";
                            $handle = popen($routeShell, "r");
                            $buffer = fgets($handle);
                            pclose($handle);
                            if (!$buffer) {//如果添加编辑后的ipv4路由成功，则开始添加ipv6的路由
                                if (!empty($ipv6dest)) {
                                    if (!empty($ipv6gateway)) {
                                        $oldipv6routeShell = "/sbin/route -A inet6 del default gw " . $oldipv6gateway . " 2>/dev/stdout";
                                        $handle = popen($oldipv6routeShell, "r");
                                        $buffer1 = fgets($handle);
                                        pclose($handle);

                                        $ipv6routeShell = "/sbin/route -A inet6 add default gw " . $ipv6gateway . " 2>/dev/stdout";
                                        $handle = popen($ipv6routeShell, "r");
                                        $buffer2 = fgets($handle);
                                        pclose($handle);
                                    } else {
                                        $oldipv6routeShell = "/sbin/route -A inet6 del " . $oldipv6dest . "/" . $oldipv6prefix . " dev " . $oldnic . " 2>/dev/stdout";
                                        $handle = popen($oldipv6routeShell, "r");
                                        $buffer1 = fgets($handle);
                                        pclose($handle);

                                        $ipv6routeShell = "/sbin/route -A inet6 add " . $ipv6dest . "/" . $ipv6prefix . " dev " . $nic . " 2>/dev/stdout";
                                        $handle = popen($ipv6routeShell, "r");
                                        $buffer2 = fgets($handle);
                                        pclose($handle);
                                    }
                                }
                                //添加这条路由，返回提示信息（成功或者失败）；
                                //$query = "update ".getTable('staticroute2')." set nic='".$nic."',dest='".$dest."',mask='".$mask."',gateway='".$gateway."' where id='".$id."'";
                                $query = "update " . getTable('staticroute2') . " set nic='" . $nic . "',dest='" . $dest . "',mask='" . $mask . "',gateway='" . $gateway . "',ipv6dest='" . $ipv6dest . "',ipv6prefix='" . $ipv6prefix . "',ipv6gateway='" . $ipv6gateway . "' where id='" . $id . "'";
                                if ($db->query($query)) {
                                    $this->writeRoute();
                                    $success = true;
                                    $msg = "操作成功";
                                    $data['success'] = $success;
                                    $data['msg'] = $msg;
                                    echo json_encode($data);
                                    exit;
                                }
                            } else {//如果添加编辑后的ipv4路由失败，则把一开始删除掉的旧ipv4路由还原回来。
                                $dShell = "/sbin/route add -net " . $olddest . " netmask " . $oldmask;
                                if (!empty($oldgateway)) {
                                    $dShell .= " gw " . $oldgateway;
                                }
                                $dShell .= " " . $oldnic;
                                shellResult($dShell);

                                $success = false;
                                $msg = "添加失败";
                                $data['success'] = $success;
                                $data['msg'] = $msg;
                                echo json_encode($data);
                                exit;
                            }
                        }
                    } else {
                        //添加这条路由，返回提示信息（成功或者失败）；
                        $routeShell = "/sbin/route add -net " . $dest . " netmask " . $mask . " " . $nic . " 2>/dev/stdout";
                        $handle = popen($routeShell, "r");
                        $buffer = fgets($handle);
                        pclose($handle);
                        if (!$buffer) {//如果添加编辑后的ipv4路由成功，则开始添加ipv6的路由
                            if (!empty($ipv6dest)) {
                                if (!empty($ipv6gateway)) {
                                    $oldipv6routeShell = "/sbin/route -A inet6 del default gw " . $oldipv6gateway . " 2>/dev/stdout";
                                    $handle = popen($oldipv6routeShell, "r");
                                    $buffer1 = fgets($handle);
                                    pclose($handle);

                                    $ipv6routeShell = "/sbin/route -A inet6 add default gw " . $ipv6gateway . " 2>/dev/stdout";
                                    $handle = popen($ipv6routeShell, "r");
                                    $buffer2 = fgets($handle);
                                    pclose($handle);
                                } else {
                                    $oldipv6routeShell = "/sbin/route -A inet6 del " . $oldipv6dest . "/" . $oldipv6prefix . " dev " . $oldnic . " 2>/dev/stdout";
                                    $handle = popen($oldipv6routeShell, "r");
                                    $buffer1 = fgets($handle);
                                    pclose($handle);

                                    $ipv6routeShell = "/sbin/route -A inet6 add " . $ipv6dest . "/" . $ipv6prefix . " dev " . $nic . " 2>/dev/stdout";
                                    $handle = popen($ipv6routeShell, "r");
                                    $buffer2 = fgets($handle);
                                    pclose($handle);
                                }
                            }
                            //添加这条路由，返回提示信息（成功或者失败）；
                            //$query = "update ".getTable('staticroute2')." set nic='".$nic."',dest='".$dest."',mask='".$mask."',gateway='".$gateway."' where id='".$id."'";

                            $query = "update " . getTable('staticroute2') . " set nic='" . $nic . "',dest='" . $dest . "',mask='" . $mask . "',gateway='" . $gateway . "',ipv6dest='" . $ipv6dest . "',ipv6prefix='" . $ipv6prefix . "',ipv6gateway='" . $ipv6gateway . "' where id='" . $id . "'";
                            if ($db->query($query)) {
                                $this->writeRoute();
                                $success = true;
                                $msg = "操作成功";
                                $data['success'] = $success;
                                $data['msg'] = $msg;
                                echo json_encode($data);
                                exit;
                            }
                        } else {//如果添加编辑后的ipv4路由失败，则把一开始删除掉的旧ipv4路由还原回来。
                            $dShell = "/sbin/route add -net " . $olddest . " netmask " . $oldmask;
                            if (!empty($oldgateway)) {
                                $dShell .= " gw " . $oldgateway;
                            }
                            $dShell .= " " . $oldnic;
                            shellResult($dShell);

                            $success = false;
                            $msg = "添加失败";
                            $data['success'] = $success;
                            $data['msg'] = $msg;
                            echo json_encode($data);
                            exit;
                        }
                    }
                } else {
                    //添加这条路由，返回提示信息（成功或者失败）；
                    $routeShell = "/sbin/route add -net " . $dest . " netmask " . $mask;
                    if (!empty($gateway)) {
                        $routeShell .= " gw " . $gateway;
                    }
                    $routeShell .= " " . $nic . " 2>/dev/stdout";
                    //echo $routeShell;die;
                    $handle = popen($routeShell, "r");
                    $buffer = fgets($handle);
                    pclose($handle);
                    if (!$buffer) {
                        if (!empty($ipv6dest)) {
                            if (!empty($ipv6gateway)) {
                                $oldipv6routeShell = "/sbin/route -A inet6 del default gw " . $oldipv6gateway . " 2>/dev/stdout";
                                $handle = popen($oldipv6routeShell, "r");
                                $buffer1 = fgets($handle);
                                pclose($handle);

                                $ipv6routeShell = "/sbin/route -A inet6 add default gw " . $ipv6gateway . " 2>/dev/stdout";
                                $handle = popen($ipv6routeShell, "r");
                                $buffer2 = fgets($handle);
                                pclose($handle);
                            } else {
                                $oldipv6routeShell = "/sbin/route -A inet6 del " . $oldipv6dest . "/" . $oldipv6prefix . " dev " . $oldnic . " 2>/dev/stdout";
                                $handle = popen($oldipv6routeShell, "r");
                                $buffer1 = fgets($handle);
                                pclose($handle);

                                $ipv6routeShell = "/sbin/route -A inet6 add " . $ipv6dest . "/" . $ipv6prefix . " dev " . $nic . " 2>/dev/stdout";
                                $handle = popen($ipv6routeShell, "r");
                                $buffer2 = fgets($handle);
                                pclose($handle);
                            }
                        }
                        //添加这条路由，返回提示信息（成功或者失败）；
                        //$query = "update ".getTable('staticroute2')." set nic='".$nic."',dest='".$dest."',mask='".$mask."',gateway='".$gateway."' where id='".$id."'";
                        $query = "update " . getTable('staticroute2') . " set nic='" . $nic . "',dest='" . $dest . "',mask='" . $mask . "',gateway='" . $gateway . "',ipv6dest='" . $ipv6dest . "',ipv6prefix='" . $ipv6prefix . "',ipv6gateway='" . $ipv6gateway . "' where id='" . $id . "'";
                        if ($db->query($query)) {
                            $this->writeRoute();
                            $success = true;
                            $msg = "操作成功";
                            $data['success'] = $success;
                            $data['msg'] = $msg;
                            echo json_encode($data);
                            exit;
                        }
                    } else {//如果添加编辑后的ipv4路由失败，则把一开始删除掉的旧ipv4路由还原回来。
                        $dShell = "/sbin/route add -net " . $olddest . " netmask " . $oldmask;
                        if (!empty($oldgateway)) {
                            $dShell .= " gw " . $oldgateway;
                        }
                        $dShell .= " " . $oldnic;
                        shellResult($dShell);

                        $success = false;
                        $msg = "编辑路由失败";
                        $data['success'] = $success;
                        $data['msg'] = $msg;
                        echo json_encode($data);
                        exit;
                    }
                }
//            } else {
//                $success = false;
//                $msg = "操作失败";
//                $hdata['sDes'] = '编辑静态路由';
//                $hdata['sRs'] = '失败';
//                $hdata['sAct'] = $act . '/' . $show;
//                saveOperationLog($hdata);
//            }
        } else {//新增
            $tSql = "select count(*) from " . getTable('staticroute2') . " where nic = '" . $nic . "' and dest ='" . $dest . "' and mask = '" . $mask . "' and gateway = '" . $gateway . "' and ipv6dest = '" . $ipv6dest . "' and ipv6prefix = '" . $ipv6prefix . "' and ipv6gateway = '" . $ipv6gateway . "'";
            //$tSql = "select count(*) from ".getTable('staticroute2')." where nic = '".$nic."' and dest ='".$dest."' and mask = '".$mask."' and gateway = '".$gateway."'";
            $num = $db->result_first($tSql);
            if ($num > 0) {
                $success = false;
                $msg = "已经有相同的静态路由";
                $hdata['sDes'] = '新增静态路由失败';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
            if ($dest == '0.0.0.0') {
                if (!empty($gateway))//网关地址设置
                {
                    $handle = popen("/sbin/route -n | awk -F '255' '{print $1}' | grep UG | cut -c 1-7 | grep '0.0.0.0' ", "r");
                    $buffer = fgets($handle);//var_dump($handle);var_dump($buffer);exit;
                    pclose($handle);

                    if (!empty($buffer))//返回提示信息（已存在默认路由）；
                    {
                        $success = false;
                        $msg = "已存在默认路由";
                        $data['success'] = $success;
                        $data['msg'] = $msg;
                        echo json_encode($data);
                        exit;
                    } else {
                        $routeShell = "/sbin/route add -net " . $dest . " netmask " . $mask . " gw " . $gateway . " " . $nic . " 2>/dev/stdout";
                        $handle = popen($routeShell, "r");
                        $buffer = fgets($handle);
                        pclose($handle);
                        //var_dump($buffer);die;
                        if (!$buffer) {
                            if (!empty($ipv6dest)) {
                                if (!empty($ipv6gateway)) {
                                    //route -A inet6 add default gw ipv6网关地址
                                    $ipv6routeShell = "/sbin/route -A inet6 add default gw " . $ipv6gateway . " 2>/dev/stdout";
                                    //shellResult($ipv6routeShell);
                                    $handle = popen($ipv6routeShell, "r");
                                    $buffer2 = fgets($handle);
                                    pclose($handle);
                                } else {
                                    //route -A inet6 add ipv6地址/前缀 dev 接口
                                    $ipv6routeShell = "/sbin/route -A inet6 add " . $ipv6dest . "/" . $ipv6prefix . " dev " . $nic . " 2>/dev/stdout";
                                    //shellResult($ipv6routeShell);
                                    $handle = popen($ipv6routeShell, "r");
                                    $buffer2 = fgets($handle);
                                    pclose($handle);
                                }
                            }
                            //添加这条路由，返回提示信息（成功或者失败）；
                            //$query = "insert into ".getTable('staticroute2')." (id ,nic,dest,mask,gateway) values('','".$nic."','".$dest."','".$mask."','".$gateway."')";
                            $this->writeRoute();
                            $query = "insert into " . getTable('staticroute2') . " (id ,nic,dest,mask,gateway,ipv6dest,ipv6prefix,ipv6gateway) values('','" . $nic . "','" . $dest . "','" . $mask . "','" . $gateway . "','" . $ipv6dest . "','" . $ipv6prefix . "','" . $ipv6gateway . "')";
                            if ($db->query($query)) {
                                $success = true;
                                $msg = "操作成功";
                                $data['success'] = $success;
                                $data['msg'] = $msg;
                                echo json_encode($data);
                                exit;
                            }
                        } else {
                            $success = false;
                            $msg = "添加默认路由失败";
                            $data['success'] = $success;
                            $data['msg'] = $msg;
                            echo json_encode($data);
                            exit;
                        }

                    }
                } else {
                    //添加这条路由，返回提示信息（成功或者失败）；
                    $routeShell = "/sbin/route add -net " . $dest . " netmask " . $mask . " " . $nic . " 2>/dev/stdout";//var_dump($routeShell);
                    $handle = popen($routeShell, "r");//var_dump($handle);
                    $buffer = fgets($handle);//var_dump($buffer);exit;
                    pclose($handle);
                    if (!$buffer) {
                        if (!empty($ipv6dest)) {
                            if (!empty($ipv6gateway)) {
                                //route -A inet6 add default gw ipv6网关地址
                                $ipv6routeShell = "/sbin/route -A inet6 add default gw " . $ipv6gateway . " 2>/dev/stdout";
                                //shellResult($ipv6routeShell);
                                $handle = popen($ipv6routeShell, "r");
                                $buffer2 = fgets($handle);
                                pclose($handle);
                            } else {
                                //route -A inet6 add ipv6地址/前缀 dev 接口
                                $ipv6routeShell = "/sbin/route -A inet6 add " . $ipv6dest . "/" . $ipv6prefix . " dev " . $nic . " 2>/dev/stdout";
                                //shellResult($ipv6routeShell);
                                $handle = popen($ipv6routeShell, "r");
                                $buffer2 = fgets($handle);
                                pclose($handle);
                            }
                        }
                        //添加这条路由，返回提示信息（成功或者失败）；
                        //$query = "insert into ".getTable('staticroute2')." (id ,nic,dest,mask,gateway) values('','".$nic."','".$dest."','".$mask."','".$gateway."')";
                        $this->writeRoute();
                        $query = "insert into " . getTable('staticroute2') . " (id ,nic,dest,mask,gateway,ipv6dest,ipv6prefix,ipv6gateway) values('','" . $nic . "','" . $dest . "','" . $mask . "','" . $gateway . "','" . $ipv6dest . "','" . $ipv6prefix . "','" . $ipv6gateway . "')";
                        if ($db->query($query)) {
                            $success = true;
                            $msg = "操作成功";
                            $data['success'] = $success;
                            $data['msg'] = $msg;
                            echo json_encode($data);
                            exit;
                        }
                    } else {
                        $success = false;
                        $msg = "新增默认路由失败";
                        $data['success'] = $success;
                        $data['msg'] = $msg;
                        echo json_encode($data);
                        exit;
                    }

                }
            } else {
                //添加这条路由，返回提示信息（成功或者失败）；
                #route add -net 192.168.0.0 netmask 255.255.0.0 gw 192.168.0.1 eth0
                $routeShell = "/sbin/route add -net " . $dest . " netmask " . $mask;
                if (!empty($gateway)) {
                    $routeShell .= " gw " . $gateway;
                }
                $routeShell .= " " . $nic . " 2>/dev/stdout";
                $handle = popen($routeShell, "r");
                $buffer = fgets($handle);
               // var_dump($buffer);die;
                pclose($handle);
                if (!$buffer) {
                    if (!empty($ipv6dest)) {
                        if (!empty($ipv6gateway)) {
                            //route -A inet6 add default gw ipv6网关地址
                            $ipv6routeShell = "/sbin/route -A inet6 add default gw " . $ipv6gateway . " 2>/dev/stdout";
                            //shellResult($ipv6routeShell);
                            $handle = popen($ipv6routeShell, "r");
                            $buffer2 = fgets($handle);
                            pclose($handle);
                        } else {
                            //route -A inet6 add ipv6地址/前缀 dev 接口
                            $ipv6routeShell = "/sbin/route -A inet6 add " . $ipv6dest . "/" . $ipv6prefix . " dev " . $nic . " 2>/dev/stdout";
                            //shellResult($ipv6routeShell);
                            $handle = popen($ipv6routeShell, "r");
                            $buffer2 = fgets($handle);
                            pclose($handle);
                        }
                    }
                    //添加这条路由，返回提示信息（成功或者失败）；
                    //$query = "insert into ".getTable('staticroute2')." (id ,nic,dest,mask,gateway) values('','".$nic."','".$dest."','".$mask."','".$gateway."')";
                    $this->writeRoute();
                    $query = "insert into " . getTable('staticroute2') . " (id ,nic,dest,mask,gateway,ipv6dest,ipv6prefix,ipv6gateway) values('','" . $nic . "','" . $dest . "','" . $mask . "','" . $gateway . "','" . $ipv6dest . "','" . $ipv6prefix . "','" . $ipv6gateway . "')";
                    if ($db->query($query)) {

                        $success = true;
                        $msg = "操作成功";
                        $data['success'] = $success;
                        $data['msg'] = $msg;
                        echo json_encode($data);
                        exit;
                    }
                } else {
                    $success = false;
                    $msg = "新增静态路由失败";
                    $data['success'] = $success;
                    $data['msg'] = $msg;
                    echo json_encode($data);
                    exit;
                }
            }
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /**
     * @ 从数据库中删除数据
     * @ params sId
     */
    function actionDel()
    {
        global $db, $act, $show;
        $sPost = $_POST;
//        $destarr = explode('.', filterStr($sPost['data']['dest']));
//        $dest = $destarr[0] . "." . $destarr[1] . "." . $destarr[2] . '.0';
//        $slash_notation = strlen(preg_replace("/0/", "", decbin(ip2long(filterStr($sPost['data']['mask'])))));
//        $ipaddress = $dest . "/" . $slash_notation;
        $ipaddress =$sPost['data']['dest'];
        $netmask =$sPost['data']['mask'];
        $dShell = "/sbin/route del -net " . $ipaddress . " netmask $netmask 2>/dev/stdout";
        if (!empty($gateway)) {
            $dShell .= " gw " . $sPost['data']['gateway'];
        }
        $dShell .= " " . $sPost['data']['nic'];
       // echo $dShell;die;
        $handler= popen($dShell,"r");
        $buffer=fgets($handler);
        pclose($handler);
        if(!$buffer){
            if (!empty($sPost['data']['ipv6gateway'])) {
                //route -A inet6 del default gw ipv6网关地址
                $ipv6routeShell = "/sbin/route -A inet6 del default gw " . $sPost['data']['ipv6gateway'];
                shellResult($ipv6routeShell);
            } else {
                //route -A inet6 del ipv6地址/前缀 dev 接口
                $ipv6routeShell = "/sbin/route -A inet6 del " . $sPost['data']['ipv6dest'] . "/" . $sPost['data']['ipv6prefix'] . " dev " . $sPost['data']['nic'];
                shellResult($ipv6routeShell);
            }
            $query = "DELETE FROM " . getTable('staticroute2') . " where id='" . $sPost['data']['id'] . "'";
            if($db->execute($query) > 0){
                $this->writeRoute();
                $success = true;
                $msg = "操作成功";
                $hdata['sDes'] = '删除静态路由';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }else{
                $success = false;
                $msg = "操作失败";
                $hdata['sDes'] = '删除静态路由';
                $hdata['sRs'] = '失败';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }

        }else{
            $success = false;
            $msg = "操作失败";
            $hdata['sDes'] = '删除静态路由';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }

        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

//把所有路由写到文件里面

    function writeRoute()
    {
        global $db;
        $count = $db->result_first("select count(*) from " . getTable('staticroute2'));
        //var_dump($count);die;
        if ($count > 0) {
            $res = $db->fetch_all("select * from " . getTable('staticroute2'));
            if ($res) {
                $routeShell = "#!/bin/sh" . "\n";
                foreach ($res as $k => $v) {
                    $routeShell .= "/sbin/route add -net " . $v['dest'] . " netmask " . $v['mask'];
                    if (!empty($v['gateway'])) {
                        $routeShell .= " gw " . $v['gateway'];
                    }
                    $routeShell .= " " . $v['nic'] . "\n";

                }

                file_put_contents(DIR_ROOT . "../data/route/route.sh", $routeShell);
            }
        } else {
            $routeShell = "#!/bin/sh" . "\n";
           // echo $routeShell;die;
            file_put_contents(DIR_ROOT . "../data/route/route.sh", $routeShell);
        }
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
