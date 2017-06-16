<?php
namespace app\controllers;
class RoleController extends BaseController
{


    function actionIndex()
    {
        global $act;
        $data = array();
        template2($act . '/index', $data);
    }

    /**
     * @新增和编辑页
     */
    function edit()
    {
        global $act;
        template2($act . '/edit', array());
    }

    /*
     * 角色帐号设置
     */
    function user()
    {
        global $act;
        template2($act . '/user', array());
    }

    /**
     * 查看权限
     */
    function navtree()
    {
        global $act;
        template2($act . '/navtree', array());
    }

    function navflat2nested2($d, $r = 0, $p = 'iParentId', $k = 'iRoleId', $c = 'children')
    {
        $m = array();
        foreach ($d as $e) {
            isset($m[$e[$p]]) ?: $m[$e[$p]] = array();
            isset($m[$e[$k]]) ?: $m[$e[$k]] = array();
            $m["$e[$p]"][] = array_merge($e, array($c => &$m[$e[$k]]));
        }
        /*echo "<pre>";
        print_r($m);*/
        return empty($m["$r"]) ? array() : $m["$r"];
    }

    function seenavflat2nested($d, $r = 0, $p = 'pId', $k = 'id', $c = 'children')
    {
        $m = array();
        foreach ($d as $e) {
            isset($m[$e[$p]]) ?: $m[$e[$p]] = array();
            isset($m[$e[$k]]) ?: $m[$e[$k]] = array();
            $m["$e[$p]"][] = array_merge($e, array($c => &$m[$e[$k]]));
        }
        return empty($m["$r"]) ? array() : $m["$r"];
    }

    /**
     *
     * 角色列表数据
     *
     */
    function lists()
    {
        global $db, $root_user, $username, $action;
        $sPost = $_POST;
        $page = intval($sPost['page'] * ($sPost['pagesize'] - 10));
        $perpage = intval($sPost['pagesize']);
        $total = 0;
        $rows = $roles = array();
        $where = " WHERE 1=1";
        $page = $page > 1 ? $page : 1;
        $total = $db->result_first("SELECT COUNT(`iRoleId`) FROM " . getTable('role') . "$where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        $roledatas = $aItem = array();
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM " . getTable('role') . " where iRoleId !=2");
            foreach ($rows as $k => $v) {
                $aItem = array(
                    "title" => $v['iRoleName'],
                    "key" => $v['iRoleId'],
                    "isFolder" => true,
                    "iRoleId" => $v['iRoleId'],
                    "iRootRoleId" => $v['iRoleId'],
                    "id" => $v['iRoleId'],
                    "text" => $v['iRoleName'],
                    "iRoleName" => $v['iRoleName'],
                    "iParentId" => $v['iParentId'],
                    "bVisible" => $v['bVisible'],
                    "iLevel" => $v['iLevel'],
                    "leaf" => false,
                    "action" => $action
                );
                array_push($roledatas, $aItem);
            }
        }
        $dataroles = navflat2nested2($roledatas, 0);
        /*if($_SESSION['username'] != $root_user){
            unset($dataroles[0]['children'][0]);
        };*/
        $data['Rows'] = $dataroles;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;

    }

    /**
     * @新增或者编辑用户，保存到数据库
     */
    function addAndEdit()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $iRoleId = intval($sPost['iRoleId']);
        $iRoleName = filterStr($sPost['iRoleName']);
        $iLevel = intval($sPost['iLevel']);
        $arrayNav = filterStr($sPost['iNavId']);
        $arriNavId = array_filter(explode(',', $arrayNav));
        if ($sPost['addedit'] == 'edit') {//编辑
            $iTotal = $db->result_first("SELECT COUNT(`iRoleName`) FROM " . getTable('role') . " where iRoleName='" . $iRoleName . "' and iRoleId != '" . $iRoleId . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = "[" . $iRoleName . '] 已存在，请更换';
                echo json_encode($data);
                exit;
            }
            $query = "update " . getTable('role') . " set iRoleName ='" . $iRoleName . "' where iRoleId ='" . $iRoleId . "'";
            if ($db->query($query)) {
                $delRolenav = "delete from " . getTable('rolenavtree') . " where iRoleId = " . $iRoleId;
                $db->query($delRolenav);
                foreach ($arriNavId as $k => $v) {
                    $insRolenav = "insert into " . getTable('rolenavtree') . " (iId,iRoleId,iNavId) values(''," . $iRoleId . ",'" . $v . "')";
                    $db->query($insRolenav);
                }
                $success = true;
                $msg = "操作成功";
                $hdata['sDes'] = '编辑角色';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            } else {
                $success = false;
                $msg = "操作失败";
                $hdata['sDes'] = '编辑角色';
                $hdata['sRs'] = '失败';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        } else {//新增
            $iTotal = $db->result_first("SELECT COUNT(`iRoleName`) FROM " . getTable('role') . " where iRoleName='" . $iRoleName . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = "[" . $iRoleName . '] 已存在，请更换';
                echo json_encode($data);
                exit;
            }
            $query = "insert into " . getTable('role') . " (iRoleId,iParentId,iRight,iLeft,iLevel,iRoleName,bVisible)
        values('','" . $iRoleId . "','0','0','" . ($iLevel + 1) . "','" . $iRoleName . "','1')";
            if ($db->query($query)) {
                $sqlRole = "select iRoleId from " . getTable('role') . " where iRolename = '" . $iRoleName . "'";
                $sRoleId = $db->result_first($sqlRole);
                foreach ($arriNavId as $k => $v) {
                    $sqlrolnav = "insert into " . getTable('rolenavtree') . " (iId,iRoleId,iNavId) values(''," . $sRoleId . ",'" . $v . "')";
                    $db->query($sqlrolnav);
                }
                $success = true;
                $msg = "操作成功";
                $hdata['sDes'] = '新增角色';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            } else {
                $success = false;
                $msg = "操作失败";
                $hdata['sDes'] = '新增角色';
                $hdata['sRs'] = '失败';
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
     * 删除角色
     */
    function del()
    {
        global $act, $db, $show;
        $sPost = $_POST;
        $sqlRole = "select count('iParentId') from " . getTable('role') . " where iParentId = '" . $sPost['iRoleId'] . "'";
        $rRole = $db->result_first($sqlRole);
        if ($rRole) {
            $success = false;
            $msg = "该角色还有子角色存在，不能删除。";
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        } else {
            $sqluser = "select count(*) from " . getTable('user') . " where role ='" . $sPost['iRoleId'] . "'";
            if ($db->result_first($sqluser)) {
                $success = false;
                $msg = "该角色有用户存在，不能删除。请删除用户在删除该角色！";
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
            $sqlDrole = "delete from " . getTable('role') . " where iRoleId = '" . $sPost['iRoleId'] . "'";
            if ($db->query($sqlDrole)) {
                $hdata['sDes'] = '删除角色成功';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                $success = true;
                $msg = "操作成功";
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
    }

    /*
     * 角色对应的帐号
     *
     */

    function roleForUser()
    {
        global $db, $username, $root_user;
        $sPost = $_POST;
        $query = "select * from " . getTable('user') . " as u inner join " . getTable('role') . " as r on u.role =r.iRoleId where username != '" . $root_user . "'";
        $rows = $db->fetch_all($query);
        $data['Rows'] = $rows;
        echo json_encode($data);
        exit;
    }

    /*
     * 查看权限
     */
    function seeNavtree()
    {
        global $db, $root_user;
        $sPost = $_POST;
        $aData = $aItem = array();
        $rows = array();
        if ($sPost['iRoleId']) {
            $sqlseeTree = "SELECT * FROM " . getTable('navtree') . " as nt LEFT OUTER JOIN " . getTable('rolenavtree') . " as rnt
         ON nt.iNavId = rnt.iNavId WHERE rnt.iRoleId ='" . $sPost['iRoleId'] . "' order by nt.iNavId asc";
            $RseeTree = $db->fetch_all($sqlseeTree);
            foreach ($RseeTree as $k => $v) {
                $aItem = array(
                    'id' => $v['iNavId'],
                    'pId' => $v['iParentId'],
                    'name' => $v['iName'],
                    'open' => true,
                    'level' => $v['iLevel']
                );
                array_push($aData, $aItem);
            }
        } else {
            $aData['success'] = false;
            $aData['msg'] = "请求数据失败";
        }

        echo json_encode($aData);
        exit;
    }

    /*
    *查看所有的角色名称
    *
    */
    function actionGetrolename()
    {
        global $db;
        $sqlRN = "select iRoleName from " . getTable('role') . " where iRoleId != '1'";
        $resultRN = $db->fetch_all($sqlRN);
        echo json_encode($resultRN);
        exit;
    }

    function addroleuser()
    {
        global $db;
        $aPost = $_POST;
        $sqlRid = "select role_id as  role from " . getTable('user') . " where id =" . $aPost['id'];
        $rsRid = $db->fetch_first($sqlRid);
        $aJson = array();
        if (isset($aPost['iRoleId']) && isset($aPost['id'])) {
            $sqluser = "update " . getTable('user') . " set role = '" . $aPost['iRoleId'] . "' where id ='" . $aPost['id'] . "'";
            $db->query($sqluser);
            $sqlroleuser = "update " . getTable('roleuser') . " set iRoleId ='" . $aPost['iRoleId'] . "' where iRoleId = '" . $rsRid['iRoleId'] . "'";
            if ($db->query($sqlroleuser)) {
                $aJson['success'] = true;
            }
        } else {
            $aJson['success'] = false;
        }
        echo json_encode($aJson);
        exit;
    }

}

