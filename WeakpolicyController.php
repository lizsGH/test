<?php
/**
 * 弱密码扫描策略
 */
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class WeakpolicyController extends BaseController
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
        //$where = " WHERE 1=1";
        $where = " WHERE preset=0 ";
        $page = $page > 1 ? $page : 1;
//        $userrow = $db->fetch_first("select role_id as  role from bd_sys_user WHERE id=$userid ");
//        if ($userrow['role'] != 16) { //不是系统管理员
//            $where .= " AND user_id=$userid";
//        }
        $where .= " OR preset = 2 OR preset = 1";
        $total = $db->result_first("SELECT COUNT(`id`) as num FROM bd_weakpwd_policy $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM bd_weakpwd_policy  $where ORDER BY id DESC  LIMIT $start,$perpage");
        }
        foreach ($rows as $val){
            $ports[]=explode('|',$val['portlist']);
        }
//var_dump($ports);die;

        //*任务列表中的策略、*/
        $taskrows = $db->fetch_all("select distinct policy from bd_weakpwd_task_manage");
        foreach ($taskrows as $k => $v) {
            $taskrow[] = $v['policy'];
        }
        foreach ($rows as $k => $v) {
            $rows[$k]['intask'] = in_array($v['id'], $taskrow) ? true : false;   //判断是否已经在任务列表中使用
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
        $id = intval($sPost['id']);
        $name = filterStr($sPost['name']);
        $vuls = trim($sPost['vuls']);
        $ports = trim($sPost['ports']);
        $all = 0;
        if (empty($vuls)) {
            $data['success'] = false;
            $data['msg'] = '请选择漏洞';
            echo json_encode($data);
            exit;
        }

       // $vul_ids = str_replace("|", ",", $vuls);
       // var_dump($ports);die;
        $ports = str_replace("弱密码", "", strtolower($ports));
        $ports = str_replace("windows远程协助", "rdp", ($ports));
        //var_dump($ports);die;
        if ($id) {//编辑

//            $uuid = $db->result_first("SELECT weak_uuid FROM bd_weakpwd_policy where id =" . $id);
//            array_walk($aboutoids, 'changeArrCell', 'nvt:');
//            var_dump($aboutoids);die;
//            $nvts = implode(",", $aboutoids);
//            dl("openvas.so");
//            vas_bd_initialize(INTERFACE_ROOT, 9390);
//            $backcreateport = vas_bd_editconfig($uuid, $nvts, 0);      //返回1则编辑成功
//            if ($backcreateport == 1) { //1:编辑成功
           // if ( 1) { //1:编辑成功
            $iTotal = $db->result_first("SELECT COUNT(`name`) num FROM bd_weakpwd_policy where name='$name' and id!= $id");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $name . '已存在，请更换';
                echo json_encode($data);
                exit;
            }
                $query = "update bd_weakpwd_policy set name='" . $name . "',vuls='" . $vuls . "',portlist='" . $ports . "' where id=$id";
                if ($db->query($query)) {
                    $success = true;
                    $msg = "操作成功";
                    $hdata['sDes'] = '编辑弱密码策略(' . $name . ')';
                    $hdata['sRs'] = '成功';
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                } else {
                    $success = false;
                    $msg = "操作失败";
                }
//            } else {
//                $success = false;
//                $msg = "后台编辑弱密码策略失败";
//            }
        } else {//新增

            $iTotal = $db->result_first("SELECT COUNT(`name`) num FROM bd_weakpwd_policy where name='$name'"  );
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $name . '已存在，请更换';
                $hdata['sDes'] = '新增弱密码策略(' . $name . ')';
                $hdata['sRs'] = '失败，名称(' . $name . ')已存在';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                echo json_encode($data);
                exit;
            }
             if ( 1) {//1:创建成功
                 $query = "insert into bd_weakpwd_policy (name,vuls,user_id,portlist) values('$name','$vuls','{$_SESSION['userid']}','$ports' )";
                 if ($db->query($query)) {
                    $success = true;
                    $msg = "操作成功";
                    $hdata['sDes'] = '新增弱密码策略(' . $name . ')';
                    $hdata['sRs'] = '成功';
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                } else {
                    $success = false;
                    $msg = "操作失败";
                }
            } else {
                $success = false;
                $msg = "后台创建弱密码策略失败";
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

        $query = "DELETE FROM bd_weakpwd_policy where id in (" . $ids . ") and preset !=2 ";

        if ($db->query($query)) {
            $success = true;
            $msg = "操作成功";
        } else {
            $success = false;
            $msg = "操作失败";
        }

        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

}



?>