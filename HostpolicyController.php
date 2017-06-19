<?php
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class HostpolicyController extends BaseController
{
    /**
     * @列表页
     */
    function actionIndex()
    {
        global $db, $act;
        $aData = array();
        template2($act . '/index', $aData);
    }

    /**
     * @新增和编辑页
     */
    function actionEdit()
    {
        global $act;
        template2($act . '/edit', array());
    }

    /**
     * @查看页
     */
    function actionView()
    {
        global $act;
        template2($act . '/view', array());
    }

    /**
     * @ 查看主机策略
     */
    function actionPolicyview()
    {
        global $act;
        template2($act . '/policyview', array());
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
        $userid = intval($_SESSION['userid']);
        $total = 0;
        $rows = array();
        $taskrow = array();
        $where = " WHERE 1=1 AND (preset=0 ";
        $page = $page > 1 ? $page : 1;
        $userrow = $db->fetch_first("select role_id as  role from bd_sys_user WHERE id=$userid ");
        if ($userrow['role'] != 16) { //不是系统管理员
            $where .= " AND user_id=$userid";
        }
        $where .= " OR preset = 2 OR preset = 1)";
        //隐藏 自动匹配策略
        $where .= " AND id > 1";
        $total = $db->result_first("SELECT COUNT(`id`) FROM bd_host_policy $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM bd_host_policy  $where ORDER BY id DESC  LIMIT $start,$perpage");
        }

        //*任务列表中的策略、*/
        $taskrows = $db->fetch_all("select distinct host_policy from bd_host_task_manage");
       // var_dump($taskrows);die;
        foreach ($taskrows as $k => $v) {
            $taskrow[] = $v['host_policy'];
        }
        foreach ($rows as $k => $v) {
            $rows[$k]['intask'] = in_array($v['id'], $taskrow) ? true : false;   //判断是否已经在任务列表中使用
            $accrsmb = explode('|', $v['accrsmb']);
            $accrkerberos = explode('|', $v['accrkerberos']);
            $rows[$k]['smbuser'] = $accrsmb[0];
            $rows[$k]['smbpassword'] = $accrsmb[1];
            $rows[$k]['kerberosip'] = $accrkerberos[0];
            $rows[$k]['kerberosuser'] = $accrkerberos[1];
            $rows[$k]['kerberospassword'] = $accrkerberos[2];
            $rows[$k]['kerberosrealm'] = $accrkerberos[3];
            $rows[$k]['kerberosport'] = $accrkerberos[4];
        }
        $data['Rows'] = $rows;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }

    /**
     * @新增或者编辑策略，保存到数据库
     */
    function actionAddandedit()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $vrows = array();
        $userid = intval($_SESSION['userid']);
        $accrsmb = "";
        $accrkerberos = "";
        $id = intval($sPost['id']);
        $name = filterStr($sPost['name']);
        if (empty($name)) { //filterStr函数会把0置为空'';
            $name = '0';
        }
        //2015-6-7 新添加
        $accrtype = intval($sPost['accrtype']);
        $smbuser = filterStr($sPost['smbuser']);
        $smbpassword = filterStr($sPost['smbpassword']);
        $kerberosuser = filterStr($sPost['kerberosuser']);
        $kerberospassword = filterStr($sPost['kerberospassword']);
        $kerberosip = filterStr($sPost['kerberosip']);
        $kerberosport = filterStr($sPost['kerberosport']);
        $kerberosrealm = filterStr($sPost['kerberosrealm']);

        $vul_ids = trim($sPost['vul_ids']);     // for bd_host_policy_selectors
        $vul_length = intval($sPost['vul_length']);     // for bd_host_policy_selectors

        if ($accrtype > 0) {
            if (empty($smbuser) && empty($smbpassword)) {
                $accrtype = 0;
                $accrsmb = "";
            } else {
                $accrsmb = $smbuser . "|" . $smbpassword;
            }
        }
        $accrkerberos = "";
        if (empty($vul_ids)) {
            $data['success'] = false;
            $data['msg'] = Yii::t('app','请选择漏洞');
            echo json_encode($data);
            exit;
        }

        $aboutoids = $aboutoids2 = array();
        $vquery = "select `family_id`,`vul_id` FROM bd_host_vul_lib where vul_id in (" . $vul_ids . ") ";//找出具洞对应分类表中父洞id
        $vrows = $db->fetch_all($vquery);
        //var_dump($vrows);die;
        $aboutoidrows = $db->fetch_all("select `oid` FROM bd_host_vul_lib where category > 2 AND vul_id in (" . $vul_ids . ") ");//找出具洞的oid
        $aboutoidrows2 = $db->fetch_all("select `oid` FROM bd_host_vul_lib where category in (0,1,2)");
        //$aboutoidrows = $db->fetch_all("select `oid` FROM bd_host_vul_lib where vul_id in (".$vul_ids.") ");//找出具洞的oid
        foreach ($aboutoidrows as $k => $v) {
            $aboutoids[] = $v['oid'];
        }
        foreach ($aboutoidrows2 as $k => $v) {
            $aboutoids[] = $v['oid'];
        }
        $aboutoids = array_filter($aboutoids, create_function('$v', 'return !empty($v);'));

        if ($id) {//编辑
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_host_policy where name='" . $name . "' And id <>" . $id);
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $name . Yii::t('app', '已存在，请更换');
                $hdata['sDes'] = Yii::t('app', '编辑主机扫描策略') . '(' . $name . ')';
                $hdata['sRs'] = Yii::t('app', '名称已存在');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                echo json_encode($data);
                exit;
            }

            $uuid = $db->result_first("SELECT uuid FROM bd_host_policy where id =" . $id);
            $all = 0;
            $vulTotal = $db->result_first("SELECT COUNT(vi.`id`) FROM bd_host_vul_lib AS vi ");
            if ($vul_length == $vulTotal) {
                $all = 1;
            }
            $nvts = implode(",", $aboutoids);//var_dump($uuid,$nvts,$all);exit;

            $uData['uuids'] = $uuid;
            $uData['nvts'] = $nvts;
            $uData['all'] = $all;
            $name=$db->result_first("select name from bd_host_policy WHERE id=$id");
            if($_POST['name'] != $name){
                if(!$db->execute("update bd_host_policy set name='" . $name . "' where id=$id")){
                    $success = false;
                    $msg = Yii::t('app', "操作失败");
                    $hdata['sDes'] = Yii::t('app', '编辑主机扫描策略') . '（' . $name . '）';
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            }
            $dquery = "DELETE FROM bd_host_policy_selectors where policy_id=" . $id;
            $db->query($dquery);
            if (!empty($vrows)) {
                $default_vul_ids = $db->fetch_all("select vul_id from bd_host_vul_lib WHERE category in(0,1,2)"); //主机默认必选漏洞
                foreach ($default_vul_ids as $v){
                    $newvul_ids[]=$v['vul_id'];
                }
                $vul_ids_str = $vul_ids.','.implode(',',$newvul_ids);
                $query1 = "insert into bd_host_policy_selectors (policy_id,family_id,vul_id) select " . $id . ", `family_id`,`vul_id` FROM bd_host_vul_lib where vul_id in (" . $vul_ids_str . " )";
                if($db->query($query1)){
                    $success = true;
                    $msg = Yii::t('app', "操作成功");
                    $hdata['sDes'] = Yii::t('app', '编辑主机扫描策略') . '（' . $name . '）';
                    $hdata['sRs'] = Yii::t('app', '成功');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                    $sFile = DIR_ROOT . "data/hostpolicy/hostpolicy_edit.config";
                    if (file_put_contents($sFile, serialize($uData)) > 0) {
                        $editShell = "/bin/touch /tmp/edit_bd_host_policy";
                        shellResult($editShell);
                    }
                }else{
                    $success = false;
                    $msg = Yii::t('app', "操作失败");
                    $hdata['sDes'] = Yii::t('app', '编辑主机扫描策略'). '（' . $name . '）';
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            }

        } else {//新增
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_host_policy where name='" . $name . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $name . Yii::t('app', '已存在，请更换');
                $hdata['sDes'] = Yii::t('app', '添加主机扫描策略') . '（' . $name . '）';
                $hdata['sRs'] = Yii::t('app', '失败，名称（') . $name . '）' . Yii::t('app', '已存在');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                echo json_encode($data);
                exit;
            }
            $uuid = uuid();//31随机生成
            $all = 0;
            $vulTotal = $db->result_first("SELECT COUNT(vi.`id`) FROM bd_host_vul_lib AS vi ");
            if ($vul_length == $vulTotal) {//31 ???
                $all = 1;
            }
            $nvts = implode(",", $aboutoids);//31把存储oid的数组变成字串
            //file_put_contents(DIR_ROOT ."/data/oid/oid.txt",$nvts);
            $uData['uuids'] = $uuid;
            $uData['nvts'] = $nvts;
            $uData['all'] = $all;
            $query = "insert into bd_host_policy (name,user_id,uuid) values('$name',$userid,'$uuid')";
            if (1) {
                if ($db->query($query)) {
                    $policy_id = $db->insert_id();
                    if (!empty($vrows)) {
                        $default_vul_ids = $db->fetch_all("select vul_id from bd_host_vul_lib WHERE category in(0,1,2)"); //主机默认必选漏洞
                       foreach ($default_vul_ids as $v){
                          $newvul_ids[]=$v['vul_id'];
                       }

                        $vul_ids_str = $vul_ids.','.implode(',',$newvul_ids);
                        $query1 = "insert into bd_host_policy_selectors (policy_id,family_id,vul_id) select " . $policy_id . ", `family_id`,`vul_id` FROM bd_host_vul_lib where vul_id in (" . $vul_ids_str . " )";
                       //echo $query1;die;
                        $db->query($query1);
                    }
                    $sFile = DIR_ROOT . "data/hostpolicy/hostpolicy_add.config";
                    if (file_put_contents($sFile, serialize($uData)) > 0) {
                        $addShell = "/bin/touch /tmp/add_bd_host_policy"; //31  创建或修改文件
                        shellResult($addShell);
                    }
                    //vas_bd_configauth($accrsmb,$accrkerberos,$accrtype);
                    $success = true;
                    $msg = Yii::t('app', "操作成功");
                    $hdata['sDes'] = Yii::t('app', '新增主机扫描策略'). '（' . $name . '）';
                    $hdata['sRs'] = Yii::t('app', '成功');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                } else {
                    $success = false;
                    $msg = Yii::t('app', "操作失败");
                    $hdata['sDes'] = Yii::t('app', '添加主机扫描策略') . '（' . $name . '）';
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            } else {
                $success = false;
                $msg = Yii::t('app', "后台创建主机扫描策略失败");
                $hdata['sDes'] = Yii::t('app', '添加主机扫描策略');
                $hdata['sRs'] = Yii::t('app', '失败');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }

        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /**
     * @ 从数据库中删除数据
     * @ params $id
     */
    function actionDel()
    {
        global $db, $act, $show;
        $sPost = $_POST;

        $ids = filterStr($sPost['ids']);
        $uuidrows = $db->fetch_all("SELECT uuid ,name FROM bd_host_policy WHERE id in (" . $ids . ") ");
        $names = array();
        foreach ($uuidrows as $k => $v) {
            $uuidrow[] = $v['uuid'];
            $nItem = array('name' => $v['name']);
            array_push($names, $nItem);
        }

        array_filter($uuidrow);
        if (!empty($uuidrow)) {
            $uuids = implode(",", $uuidrow);
        }

        $uData['uuids'] = $uuids;
        $query = "DELETE FROM bd_host_policy where id in (" . $ids . ") and preset !=2";

        if (1) { //删除成功
            if ($db->query($query)) {
                // if(1){
                $dquery = "DELETE FROM bd_host_policy_selectors where policy_id in (" . $ids . ") ";
                $db->query($dquery);
                $sFile = DIR_ROOT . "data/hostpolicy/hostpolicy.config";
                file_put_contents($sFile, serialize($uData));
                $delShell = "/bin/touch /tmp/del_bd_host_policy";
                shellResult($delShell);
                $success = true;
                $msg = Yii::t('app', "操作成功");
                foreach ($names as $key => $val) {
                    $hdata['sDes'] = Yii::t('app', '删除主机扫描策略') . '(' . $val['name'] . ')';
                    $hdata['sRs'] = Yii::t('app', '成功');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            } else {
                $success = false;
                $msg = Yii::t('app', "操作失败");
                $hdata['sDes'] = Yii::t('app', '删除主机扫描策略');
                $hdata['sRs'] = Yii::t('app', '失败');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        } else {
            $success = false;
            $msg = Yii::t('app', "后台删除主机扫描策略失败");

        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }


    /*
     * 删除后台
     */

    function delht()
    {
        global $db;
        $sPost = $_POST;
        if ($sPost['uuids']) {
            dl("openvas.so");
            vas_bd_initialize(INTERFACE_ROOT, 9390);
            vas_bd_deleteconfigs($sPost['uuids']);
        }

    }

    /**
     * 获取主机策略的漏洞分类
     * @ params table ：nvts_type
     * @ strategy : 1
     */
    function getFamily()
    {
        global $db;
        $rows = array();
        $aData = $aItem = array();
        $where = " WHERE 1=1";
        $rows = $db->fetch_all("SELECT * FROM host_family_list  $where ");

        foreach ($rows as $k => $v) {
            $aData[$v['desc']] = $v['id'];
        }
        return $aData;
    }


    /**
     * @预设主机快扫策略
     * bd_host_vul_lib筛选条件 : vul_type=1
     * 没有OID的过滤掉
     */
    function setFastPolicy()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        if (isset($_POST['st-all'])) {
            $userid = intval($_SESSION['userid']);
            $name = Yii::t('app', "快扫主机策略");
            $all = 0;
            $a_aboutoids = $a_aboutvulids = array();

            //2015-6-7 新添加
            $accrsmb = "";
            $accrkerberos = "";

            $accrtype = intval($sPost['accrtype']);

            $smbuser = filterStr($sPost['smbuser']);
            $smbpassword = filterStr($sPost['smbpassword']);
//        $smbip = filterStr($sPost['smbip']);

            $kerberosuser = filterStr($sPost['kerberosuser']);
            $kerberospassword = filterStr($sPost['kerberospassword']);
            $kerberosip = filterStr($sPost['kerberosip']);
            $kerberosport = filterStr($sPost['kerberosport']);
            $kerberosrealm = filterStr($sPost['kerberosrealm']);

            if ($accrtype > 0) {
                if (empty($smbuser) && empty($smbpassword)) {
                    $accrtype = 0;
                    $accrsmb = "";
                } else {
                    $accrsmb = $smbuser . "|" . $smbpassword;
                }
            }
            $accrkerberos = "";
//    $accrkerberos = $kerberosip."|".$kerberosuser."|".$kerberospassword."|".$kerberosrealm."|".$kerberosport;

            $a_getvulids = $db->fetch_all("select `vul_id`,`oid`  FROM bd_host_vul_lib where vul_type=1 AND oid <> '' GROUP BY oid ");
            foreach ($a_getvulids as $k => $v) {
                $a_aboutoids[] = $v['oid'];
                $a_aboutvulids[] = $v['vul_id'];
            }
            $vul_ids = implode(",", $a_aboutvulids);
            $vrows = $db->fetch_all("select `family_id`,`vul_id` FROM bd_host_vul_lib where vul_id in (" . $vul_ids . ") ");

            $id = $db->result_first("SELECT `id` FROM bd_host_policy where preset=1 limit 1 ");
            if ($id) {    //编辑
                $uuid = $db->result_first("SELECT uuid FROM bd_host_policy where id =" . $id);
                $nvts = implode(",", $a_aboutoids);//var_dump($uuid,$nvts,$all);exit;
                dl("openvas.so");
                vas_bd_initialize(INTERFACE_ROOT, 9390);
                $backcreateport = vas_bd_editconfig($uuid, $nvts, $all);      //返回1则编辑成功
                $query = "update bd_host_policy set name='" . $name . "' where id=$id";

                if ($backcreateport == 1) { //1:编辑成功
                    if ($db->query($query)) {
                        $dquery = "DELETE FROM bd_host_policy_selectors where policy_id=" . $id;
                        $db->query($dquery);
                        if (!empty($vrows)) {
                            foreach ($vrows as $k1 => $v1) {
                                $family_id = $v1['family'];
                                $vul_id = $v1['vul_id'];
                                $query1 = "insert into bd_host_policy_selectors (policy_id,family_id,vul_id) values('" . $id . "', '" . $family_id . "','" . $vul_id . "')";
                                $db->query($query1);
                            }
                        }
                        vas_bd_configauth($accrsmb, $accrkerberos, $accrtype);
                        $success = true;
                        $msg = Yii::t('app', "预设快扫主机策略成功");
                        $hdata['sDes'] = Yii::t('app', '编辑快扫主机策略');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    } else {
                        $success = false;
                        $msg = Yii::t('app', "预设快扫主机策略失败");
                        $hdata['sDes'] = Yii::t('app', '编辑快扫主机策略');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    }
                } else {
                    $success = false;
                    $msg = Yii::t('app', "后台编辑快扫主机策略失败");
                    $hdata['sDes'] = Yii::t('app', '后台编辑快扫主机策略');
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            } else {  //新增
                $uuid = uuid();
                $preset = 1;
                $nvts = implode(",", $a_aboutoids);
                dl("openvas.so");
                vas_bd_initialize(INTERFACE_ROOT, 9390);
                $backcreateport = vas_bd_addconfig($uuid, $nvts, $all);      //返回1则创建成功
                $query = "insert into bd_host_policy (name,user_id,uuid,host_all,preset,accrtype,accrsmb,accrkerberos) values('" . $name . "',$userid,'" . $uuid . "',$all,$preset, $accrtype,'" . $accrsmb . "','" . $accrkerberos . "' )";
                if ($backcreateport == 1) { //1:创建成功
                    if ($db->query($query)) {
                        $policy_id = $db->insert_id();
                        if (!empty($vrows)) {
                            foreach ($vrows as $k1 => $v1) {
                                $family_id = $v1['family'];
                                $vul_id = $v1['vul_id'];
                                $query1 = "insert into bd_host_policy_selectors (policy_id,family_id,vul_id) values('" . $policy_id . "', '" . $family_id . "','" . $vul_id . "')";
                                $db->query($query1);
                            }
                        }
                        vas_bd_configauth($accrsmb, $accrkerberos, $accrtype);
                        $success = true;
                        $msg = Yii::t('app', "预设快扫主机策略成功");
                        $hdata['sDes'] = Yii::t('app', '新增快扫主机策略');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    } else {
                        $success = false;
                        $msg = Yii::t('app', "预设快扫主机策略失败");
                        $hdata['sDes'] = Yii::t('app', '新增快扫主机策略');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    }
                } else {
                    $success = false;
                    $msg = Yii::t('app', "后台创建快扫主机策略失败");
                    $hdata['sDes'] = Yii::t('app', '后台创建快扫主机策略');
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            }

            echo $msg;
            exit;
        } else {
            $aData['title'] = Yii::t('app', "设置快扫主机策略");
            template2($act . '/defaultAllPolicy', $aData);
        }


    }


    /**
     * @预设主机全扫策略
     * 没有OID的过滤掉
     */
    function setDefaultAllPolicy()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        if (isset($_POST['st-all'])) {
            $userid = intval($_SESSION['userid']);
            $name = Yii::t('app', "全部主机策略");
            $all = 1;
            $a_aboutoids = $a_aboutvulids = array();
            //2015-6-7 新添加
            $accrsmb = "";
            $accrkerberos = "";

            $accrtype = intval($sPost['accrtype']);

            $smbuser = filterStr($sPost['smbuser']);
            $smbpassword = filterStr($sPost['smbpassword']);
//        $smbip = filterStr($sPost['smbip']);

            $kerberosuser = filterStr($sPost['kerberosuser']);
            $kerberospassword = filterStr($sPost['kerberospassword']);
            $kerberosip = filterStr($sPost['kerberosip']);
            $kerberosport = filterStr($sPost['kerberosport']);
            $kerberosrealm = filterStr($sPost['kerberosrealm']);

            if ($accrtype > 0) {
                if (empty($smbuser) && empty($smbpassword)) {
                    $accrtype = 0;
                    $accrsmb = "";
                } else {
                    $accrsmb = $smbuser . "|" . $smbpassword;
                }
            }
            $accrkerberos = "";
//    $accrkerberos = $kerberosip."|".$kerberosuser."|".$kerberospassword."|".$kerberosrealm."|".$kerberosport;

            $a_getvulids = $db->fetch_all("select `vul_id`,`oid`  FROM bd_host_vul_lib where vul_id>=1000000 AND oid <> '' ");
            foreach ($a_getvulids as $k => $v) {
                $a_aboutoids[] = $v['oid'];
                $a_aboutvulids[] = $v['vul_id'];
            }
            $vul_ids = implode(",", $a_aboutvulids);
            $vrows = $db->fetch_all("select `family`,`vul_id` FROM bd_host_vul_lib where vul_id in (" . $vul_ids . ") ");

            $id = $db->result_first("SELECT `id` FROM bd_host_policy where preset=2 limit 1 ");
            if ($id) {    //编辑
                $uuid = $db->result_first("SELECT uuid FROM bd_host_policy where id =" . $id);
//        array_walk($a_aboutoids , 'changeArrCell', 'nvt:');
                $nvts = implode(",", $a_aboutoids);
                dl("openvas.so");
                vas_bd_initialize(INTERFACE_ROOT, 9390);
                $backcreateport = vas_bd_editconfig($uuid, $nvts, $all);      //返回1则编辑成功
                $query = "update bd_host_policy set name='" . $name . "',host_all=$all, accrtype=$accrtype, accrsmb='" . $accrsmb . "', accrkerberos='" . $accrkerberos . "' where id=$id";
                if ($backcreateport == 1) { //1:编辑成功
                    if ($db->query($query)) {
                        $dquery = "DELETE FROM bd_host_policy_selectors where policy_id=" . $id;
                        $db->query($dquery);
                        if (!empty($vrows)) {
                            foreach ($vrows as $k1 => $v1) {
                                $family_id = $v1['family'];
                                $vul_id = $v1['vul_id'];
                                $query1 = "insert into bd_host_policy_selectors (policy_id,family_id,vul_id) values('" . $id . "', '" . $family_id . "','" . $vul_id . "')";
                                $db->query($query1);
                            }
                        }
                        vas_bd_configauth($accrsmb, $accrkerberos, $accrtype);
                        $success = true;
                        $msg = Yii::t('app', "预设全部主机策略成功");
                        $hdata['sDes'] = Yii::t('app', '编辑全部主机策略');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    } else {
                        $success = false;
                        $msg = Yii::t('app', "预设全部主机策略失败");
                        $hdata['sDes'] = Yii::t('app', '编辑全部主机策略');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    }
                } else {
                    $success = false;
                    $msg = Yii::t('app', "后台编辑全部主机策略失败");
                    $hdata['sDes'] = Yii::t('app', '后台编辑全部主机策略');
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            } else {  //新增

                $uuid = uuid();
                $preset = 2;
                $nvts = 'oid';
                $nvt_count = count($a_aboutoids);
//        array_walk($a_aboutoids , 'changeArrCell', 'nvt:')1则创建成功
                $backcreateport = vas_bd_configauth($accrsmb, $accrkerberos, $accrtype);
                $query = "insert into bd_host_policy (name,user_id,uuid,host_all,preset,accrtype,accrsmb,accrkerberos) values('" . $name . "',$userid,'" . $uuid . "',$all,$preset, $accrtype,'" . $accrsmb . "','" . $accrkerberos . "' )";
                if ($backcreateport == 1) { //1:创建成功
                    if ($db->query($query)) {
                        $policy_id = $db->insert_id();
                        if (!empty($vrows)) {
                            foreach ($vrows as $k1 => $v1) {
                                $family_id = $v1['family'];
                                $vul_id = $v1['vul_id'];
                                $query1 = "insert into bd_host_policy_selectors (policy_id,family_id,vul_id) values('" . $policy_id . "', '" . $family_id . "','" . $vul_id . "')";
                                $db->query($query1);
                            }
                        }

                        $success = true;
                        $msg = Yii::t('app', "预设全部主机策略成功");
                        $hdata['sDes'] = Yii::t('app', '新增全部主机策略');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    } else {
                        $success = false;
                        $msg = Yii::t('app', "预设全部主机策略失败");
                        $hdata['sDes'] = Yii::t('app', '新增全部主机策略');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                    }
                } else {
                    $success = false;
                    $msg = Yii::t('app', "后台创建全部主机策略失败");
                    $hdata['sDes'] = Yii::t('app', '后台新增全部主机策略');
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                }
            }

            echo $msg;
            exit;
        } else {
            $aData['title'] = Yii::t('app', "设置全部主机策略");
            template2($act . '/defaultAllPolicy', $aData);
        }

    }

    function test()
    {
        global $db, $act, $show;
        $rows = array();
//    $row1 = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib limit 0,10000 ");
//    sleep(2);
//    $row2 = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib limit 10000,10000 ");
//    sleep(2);
//    $row3 = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib limit 20000,10000 ");
//    $row4 = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib limit 30000,10000 ");
//    $row5 = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib limit 40000,10000 ");
//    $rows = array_merge($row1,$row2,$row3,$row4,$row5);
        $rows = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib");
        print_r($rows);
//    echo count($rows);

    }


}




?>
