<?php
/**
 * 导航
 */
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class NavtreeController extends BaseController
{


    /**
     * @新增和编辑页
     */
    function edit()
    {
        global $act;
        template2($act . '/edit', array());
    }

    /**
     * @查看页
     */
    function view()
    {
        global $act;
        template2($act . '/view', array());
    }

    /**
     *
     *
     */
    function navflat2nested($d, $r = 0, $p = 'parent_id', $k = 'id', $c = 'children')
    {
        $m = array();
        foreach ($d as $e) {
            $e['name']=Yii::t('app',$e['name']);
            isset($m[$e[$p]]) ?: $m[$e[$p]] = array();
            isset($m[$e[$k]]) ?: $m[$e[$k]] = array();
            $m["$e[$p]"][] = array_merge($e, array($c => &$m[$e[$k]]));
        }

       //var_dump($m);die;

        return empty($m["$r"]) ? array() : $m["$r"];
    }

    /**
     * @获取列表数据 用于导航菜单
     * 菜单： 跟权限相关
     *
     */
    function actionListIndexGetData()
    {
        global $db, $root_user;
        $sPost = $_POST;
        $aData = $aItem = array();
        $rows = array();
        $aJson = array();
        if (isset($_SESSION['username'])) {
            $uIdSql = "select role_id from " . getTable('user') . " where username = '" . filterStr($_SESSION['username']) . "'";
            $RuId = $db->fetch_first($uIdSql);

//            $rows = $db->fetch_all("SELECT * FROM s_navtree as nt LEFT OUTER JOIN s_rolenavtree as rnt
//         ON nt.iNavId = rnt.iNavId WHERE rnt.iRoleId =  :roleid  order by nt.iSort,nt.iNavId asc",[':roleid'=>$RuId['role']]);
//            echo $RuId['role_id'];die;
//            echo "SELECT * FROM bd_sys_navtree as nt LEFT OUTER JOIN bd_sys_rolenavtree as rnt
//          ON nt.id = rnt.nav_id WHERE rnt.role_id =  :roleid  order by nt.sort,nt.id asc";die;
            $rows = $db->fetch_all("SELECT * FROM bd_sys_navtree as nt LEFT OUTER JOIN bd_sys_rolenavtree as rnt
          ON nt.id = rnt.nav_id WHERE rnt.role_id = '{$RuId['role_id']}'  order by nt.sort,nt.id asc");
                //var_dump($rows);die;
            $aJson = $this->navflat2nested($rows, 0);
        } else {

        }
        //print_r($aJson);die;
        echo json_encode($aJson);
        exit;
    }


    /**
     * @获取列表数据 用于角色列表
     * 菜单： 跟权限相关
     *
     */
    function navtreeForRole()
    {
        global $db, $root_user;
        $sPost = $_POST;
        $aData = $aItem = array();
        //$aJson =array();
        $rows = array();

        if (filterStr($_SESSION['username']) == $root_user) {
            $uIdSql = "select * from " . getTable('user') . " where username = '" . filterStr($_SESSION['username']) . "'";
            $RuId = $db->fetch_first($uIdSql);
            $rows = $db->fetch_all("SELECT * FROM " . getTable('navtree') . " as nt LEFT OUTER JOIN " . getTable('rolenavtree') . " as rnt
         ON nt.iNavId = rnt.iNavId WHERE rnt.iRoleId = '" . $RuId['role'] . "' order by nt.iNavId asc");
            //$aJson =flat2nested($rows, 0);
            //print_r($aJson);
            foreach ($rows as $k => $v) {
                $aItem = array(
                    'id' => $v['iNavId'],
                    'pId' => $v['iParentId'],
                    'name' => $v['iName'],
                    'open' => false,
                    'level' => $v['iLevel']
                );
                array_push($aData, $aItem);
            }

        } else {
            $rows = $db->fetch_all("SELECT * FROM " . getTable('navtree') . " as nt LEFT OUTER JOIN " . getTable('rolenavtree') . " as rnt
         ON nt.iNavId = rnt.iNavId WHERE rnt.iRoleId = '" . $sPost['roleId'] . "' order by nt.iNavId asc");
            foreach ($rows as $k => $v) {
                $aItem = array(
                    'id' => $v['iNavId'],
                    'pId' => $v['iParentId'],
                    'name' => $v['iName'],
                    'open' => false,
                    'level' => $v['iLevel']
                );
                array_push($aData, $aItem);
            }

        }

        echo json_encode($aData);
        exit;

    }

    /**
     * 与角色有关
     * 选框的树
     * @remotable
     */
    function listCheckTreeData()
    {
        global $db, $root_user;
        $sPost = $_POST;
        $aData = $aItem = array();
        $where = " WHERE 1=1";

        if (filterStr($_SESSION['username']) == $root_user) {
            $iRoot = 0;
            $rows = $db->fetch_all("SELECT * FROM " . getTable('navtree') . "  $where ");
        }
        foreach ($rows as $k => $v) {
            $aItem = array(
                "title" => $v['iName'],
                "key" => $v['iNavId'],
                "isFolder" => $v['iLevel'] == 3 ? false : true,
                "iNavId" => $v['iNavId'],
                "id" => $v['iNavId'],
                "sNodeName" => $v['iName'],
                "text" => $v['iName'],
                "iParentId" => $v['iParentId'],
                "iSort" => $v['iSort'],
                "sNodeCss" => $v['iIcon'],
                "iconCls" => $v['iIcon'],
                "sUrl" => $v['iUrl'],
                "leaf" => $v['iLevel'] == 3 ? true : false

            );

//        if ($v->isRoot()) {
//            $aItem = CMap::mergeArray($aItem, array(
//                'expanded' => true
//            ));
//        }
            array_push($aData, $aItem);
        }


        $aJson = flat2nested($aData, $iRoot);
        die();

//    $data['Total'] = $_SESSION['role'];
        echo json_encode($aJson);
        exit;
    }

}
?>