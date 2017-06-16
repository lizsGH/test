<?php
namespace  app\controllers;

use app\components\client_db;

class OperatelogController extends BaseController
{

    /**
     * @列表页
     */
    function actionIndex()
    {
        global $act;
        template2($act . '/index', array());
    }

    function actionLoglists()
    {
        global $act;
        template2($act . '/loglist', array());
    }

    /**
     * @获取列表数据
     */
    function actionLists()
    {
        global $db;
        $page = \Yii::$app->request->post('start',0);
        $perpage = \Yii::$app->request->post('length',0);
        $content = \Yii::$app->request->post('content','');
        $status = \Yii::$app->request->post('status','');
        $user_name = \Yii::$app->request->post('user_name','');
        $action = \Yii::$app->request->post('action','');
        $icStartDate = strtotime(filterStr(\Yii::$app->request->post('iStartDate')));;
        $icEndDate = strtotime(filterStr(\Yii::$app->request->post('iEndDate','')));
        $iStartDate = empty($icStartDate) ? $icStartDate : $icStartDate - 8 * 60 * 60;
        $iEndDate = empty($icEndDate) ? $icEndDate : $icEndDate - 8 * 60 * 60;

        $total = 0;
        $rows = $aData = array();
        $where = "WHERE 1=1";
        $page = $page > 1 ? $page : 1;


        if (strtotime($iStartDate) > strtotime($iEndDate)) {
            $data['success'] = false;
            $data['msg'] = "开始时间不能大于结束时间";
            echo json_encode($data);
            exit;
        }
        if (!empty($content)) {
            $where .= " AND content LIKE '%{$content}%'";
        }
        if (!empty($status)) {
            $where .= " AND status LIKE '%{$status}%'";
        }
        if (!empty($user_name)) {
            $where .= " AND user_name LIKE '%{$user_name}%'";
        }
        if (!empty($action)) {
            $where .= " AND action LIKE '%{$action}%'";
        }
        if (!empty($iStartDate)) {
            $where .= " AND datetime >= $iStartDate";
        }
        if (!empty($iEndDate)) {
            $where .= " AND datetime <= $iEndDate";
        }

        $user_role = $db->fetch_first("select role_id as  role from bd_sys_user where username='" . filterStr($_SESSION['username']) . "'");


        if ($user_role['role'] == 4) {
            $where .= " AND (user_name = 'sec_audit' OR user_name ='System')";
        }
        if ($user_role['role'] == 3) {
            $where .= " AND (user_name = 'sec_admin' OR user_name ='sys_admin')";
        }
        $where .= " and content != ''";

        $total = $db->result_first("SELECT COUNT(`id`) FROM " . "bd_sys_operatelog" . " $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM " . "bd_sys_operatelog" . "  $where ORDER BY id DESC LIMIT $start,$perpage");
        }

        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "content" => $v['content'],
                "ip" => $v['ip'],
                "datetime" => date('Y-m-d H:i:s', $v['datetime']),
                "user_name" => $v['user_name'],
                "status" => $v['status'],
                "action" => $v['action'],
            );
            array_push($aData, $aItem);
        }
        $data['Rows'] = $aData;
        $data['Total'] = $total;
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
        $id = filterStr($sPost['id']);
        $query = "DELETE FROM " . "bd_sys_operatelog" . " where id in (" . $id . ") ";
        if ($db->query($query)) {
            $success = true;
            $msg = "操作成功";
            $hdata['sDes'] = '删除日志';
            $hdata['status'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = "操作失败";
            $hdata['sDes'] = '删除日志';
            $hdata['status'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /*
     * 删除所有日志
     */
    function actionDelall()
    {
        global $db, $act, $show;
        $user_role = $db->fetch_first("select role_id as  role from bd_sys_user where username='" . filterStr($_SESSION['username']) . "'");
        $where = " ";
        if ($user_role['role'] == 4) {
            $where .= " where sOperateRoleId = 3 OR sOperateRoleId = 0";
        }
        if ($user_role['role'] == 3) {
            $where .= " where sOperateRoleId = 4 OR sOperateRoleId = 16";
        }

        $query = "DELETE FROM " . "bd_sys_operatelog" . $where;

        if ($db->query($query)) {
            $success = true;
            $msg = "操作成功";
            $hdata['sDes'] = '清空所有日志';
            $hdata['status'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = "操作失败";
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /*
     * 根据条件导出日志
     */
    function actionExportlog()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $where = " WHERE content !='' ";
        $content = filterStr($sPost['content']);
        $status = filterStr($sPost['status']);
        $user_name = filterStr($sPost['user_name']);
        $action = filterStr($sPost['action']);
        $icStartDate = strtotime(filterStr($sPost['iStartDate']));
        $icEndDate = strtotime(filterStr($sPost['iEndDate']));
        $iStartDate = empty($icStartDate) ? $icStartDate : $icStartDate - 8 * 60 * 60;
        $iEndDate = empty($icEndDate) ? $icEndDate : $icEndDate - 8 * 60 * 60;
        if (strtotime($iStartDate) > strtotime($iEndDate)) {
            $data['success'] = false;
            $data['msg'] = "开始时间不能大于结束时间";
            echo json_encode($data);
            exit;
        }
        if (!empty($content)) {
            $where .= " AND content LIKE '%{$content}%'";
        }
        if (!empty($status)) {
            $where .= " AND status LIKE '%{$status}%'";
        }
        if (!empty($user_name)) {
            $where .= " AND user_name LIKE '%{$user_name}%'";
        }
        if (!empty($action)) {
            $where .= " AND action LIKE '%{$action}%'";
        }
        if (!empty($iStartDate)) {
            $where .= " AND datetime >= $iStartDate";
        }
        if (!empty($iEndDate)) {
            $where .= " AND datetime <= $iEndDate";
        }
        $where .= ' and content !=""';
        if ($sPost) {
            $nowtime = date("Y-m-d H:i:s", time());
            $logname = "log_" . date('Y_m_d H:i:s', time());
            $logcsvname = $logname . ".csv";
            $path = DIR_ROOT . "../config/data/log/" . $logcsvname;//对应目录需要有写权限
            $backupsql = "select FROM_UNIXTIME(datetime),ip,user_name,status,content from " . "bd_sys_operatelog" . $where . " order by id  asc ";
            $sql = $backupsql . " into outfile '$path'
            CHARACTER SET gbk
            fields terminated by ','
            optionally enclosed by '\"' escaped by '\"'
            lines terminated by '\r\n';";
           // echo $sql;die;
            //var_dump($db->query($sql));die;
            if ($db->query($sql) >=0) {
                $aJson['success'] = true;
                $aJson['msg'] = '/operatelog/downloadlog?ilogname=' . $logname;
                echo json_encode($aJson);
                exit;
            }
        }
    }

    function actionDownloadlog()
    {
        $iLogname = filterStr($_GET['ilogname']);
        $sTitle = $iLogname . ".csv";
        $sFilePath = DIR_ROOT . "../config/data/log/" . $iLogname . ".csv";
        downloadFile($sTitle, $sFilePath);
    }

    /*
     * 打包导出备份日志文件
     */

    function actionExportlogfile()
    {
        global $db, $act, $show;
        $aPost = $_POST;
        $logname = "log_" . date('Y-m-d-H-i-s', time());
        $exportname = $logname . ".zip";
        $iId = filterStr($aPost['iId']);
        $sql = "select * from " . getTable('loglist') . " where iId in (" . $iId . ")";
        $logfiles = $db->fetch_all($sql);
        $filenames = " ";
        foreach ($logfiles as $k => $v) {
            $filenames .= $v['logname'] . " ";
        }
        $shell = "cd " . DIR_ROOT . "../config/data/log/;  /usr/bin/zip -r " . $exportname . " " . $filenames;
       //echo $shell;die;
        shellResult($shell);
        $aJson['msg'] = \Yii::$app->request->getHostInfo()."/operatelog/downloadlogfile?ilogname=" . $logname;
        echo json_encode($aJson);
        exit;
    }

    /*
     * 下载
     */
    function actionDownloadlogfile()
    {
        $iLogname = filterStr($_GET['ilogname']);
        $sTitle = $iLogname . ".zip";
        $sFilePath = DIR_ROOT . "../config/data/log/" . $iLogname . ".zip";
        downloadFile($sTitle, $sFilePath);
    }


    function actionLogcsvlist()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $total = 0;
        $rows = $aData = array();

        $user_role = $db->fetch_first("select role_id as  role from bd_sys_user where username='" . filterStr($_SESSION['username']) . "'");

        $where = " ";
        $page = $page > 1 ? $page : 1;

        if (!empty($user_role)) {
            if ($user_role['role'] == 4) {
                $where .= " where roleId = 3 OR roleId = 0";
            }
            if ($user_role['role'] == 3) {
                $where .= " where roleId = 4 OR roleId = 16";
            }

        }

        $total = $db->result_first("SELECT COUNT(`iId`) FROM " . getTable('loglist') . " $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM " . getTable('loglist') . "  $where ORDER BY iId DESC LIMIT $start,$perpage");
        }
        foreach ($rows as $k => $v) {
            $aItem = array(
                "iId" => $v['iId'],
                "logdx" => $v['logdx'],
                "logname" => $v['logname'],
                "logtime" => date("Y-m-d H:i:s", ($v['logtime']))
            );
            array_push($aData, $aItem);
        }
        $data['Rows'] = $aData;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;

    }

    /*
     * 删除日志列表
     */
    function actionDelloglist()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $iId = filterStr($sPost['iId']);
        $sql = "select * from " . getTable('loglist') . " where iId in (" . $iId . ") ";
        $res = $db->fetch_all($sql);
        $query = "DELETE FROM " . getTable('loglist') . " where iId in (" . $iId . ") ";
        if ($db->query($query)) {
            $sShell = "";
            foreach ($res as $k => $v) {
                $sShell = "/bin/rm -rf " . DIR_ROOT . "../config/data/log/" . $v['logname'];
                shellResult($sShell);
            }
            $success = true;
            $msg = "操作成功";
            $hdata['sDes'] = '删除备份日志';
            $hdata['status'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = "操作失败";
            $hdata['sDes'] = '删除备份日志';
            $hdata['status'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /*
     * 删除日志列表所有数据
     */
    function delallloglist()
    {
        global $db, $act, $show;
        $sql = "select * from " . getTable('loglist');
        $res = $db->fetch_all($sql);
        $user_role = $db->fetch_first("select role_id as  role from bd_sys_user where username='" . filterStr($_SESSION['username']) . "'");
        $where = " ";
        if ($user_role['role'] == 4) {
            $where .= " where roleId = 3 OR roleId = 0";
        }
        if ($user_role['role'] == 3) {
            $where .= " where roleId = 4 OR roleId = 16";
        }

        $query = "DELETE FROM " . getTable('loglist') . $where;
        if ($db->query($query)) {
            /*$sShell = "/bin/rm -rf ".DIR_ROOT."config/data/log/log*";
            shellResult($sShell);*/
            $sShell = "";
            foreach ($res as $k => $v) {
                $sShell = "/bin/rm -rf " . DIR_ROOT . "../config/data/log/" . $v['logname'];
                shellResult($sShell);
            }
            $success = true;
            $msg = "操作成功";
            $hdata['sDes'] = '清空所有日志';
            $hdata['status'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
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
