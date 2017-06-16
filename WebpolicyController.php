<?php
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;


class WebpolicyController extends BaseController
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
     * @ 查看web策略
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
        //$where = " WHERE 1=1";
        $where = " WHERE preset=0 ";
        $page = $page > 1 ? $page : 1;
        $userrow = $db->fetch_first("select role_id as  role from bd_sys_user WHERE id=$userid ");
        if ($userrow['role'] != 16) { //不是系统管理员
            $where .= " AND user_id=$userid";
        }
        $where .= " OR preset = 2 OR preset = 1";
        $total = $db->result_first("SELECT COUNT(`id`) FROM bd_web_policy $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM bd_web_policy  $where ORDER BY id DESC  LIMIT $start,$perpage");
        }
        /*任务列表中的策略*/
        $taskrows = $db->fetch_all("select distinct policy_id from bd_web_task_manage");
        foreach ($taskrows as $k => $v) {
            $taskrow[] = $v['policy_id'];
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
        $userid = intval($_SESSION['userid']);
        $id = intval($sPost['id']);
        $name = filterStr($sPost['name']);
        $vul_ids = filterStr($sPost['vul_ids']);     // for host_policy_ref
        if (empty($vul_ids)) {
            $data['success'] = false;
            $data['msg'] = '请选择漏洞';
            echo json_encode($data);
            exit;
        }

        $vquery = "select `family_id`,`vul_id` FROM bd_web_vul_lib where vul_id in (" . $vul_ids . ") ";
        $vrows = $db->fetch_all($vquery);
//var_dump($vrows);die;
        $s_scripts = "";
        $a_scripts = array();
        if (!empty($vul_ids)) {
            $a_script = $db->fetch_all("SELECT `description` FROM bd_web_vul_lib where vul_id in (" . $vul_ids . ") ");
            //var_dump($a_script);
            foreach ($a_script as $sc => $vc) {
                $a_scripts[] = $vc['description'];
            }
            $s_scripts = implode(',', $a_scripts);
        }
        $bData = array();
        $bind_data = array();
        $bind_sql = "select vul_id,vul_id_bind from bd_web_vul_lib where vul_id_bind != NULL ";
        $bData = $db->fetch_all($bind_sql);
        if (!empty($bData)) {
            foreach ($bData as $k => $v) {
                array_push($bind_data[$v['vul_id_bind']], $v['vul_id']);
            }
        }

        if ($id) {//编辑
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_web_policy where name='" . $name . "' And id !=" . $id);
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $name . '已存在，请更换';
                echo json_encode($data);
                exit;
            }

            // $web_id = $db->result_first("SELECT web_id FROM bd_web_policy where id =".$id);
//        dl("openvas.so");
//        vas_bd_initialize(INTERFACE_ROOT,9390);
//        $backcreateport = web_bd_editconfig($web_id,$s_scripts);      //返回1则编辑成功
            $query = "update bd_web_policy set name='" . $name . "' where id=$id";
            $w_res = $db->query($query);
            if ($w_res >=0) {
                $dquery = "DELETE FROM bd_web_policy_selectors where policy_id=" . $id;
                $db->query($dquery);
                if (!empty($vrows)) {
                    $f_sql = '';
                    foreach ($vrows as $k1 => $v1) {
                        $family_id = $v1['family_id'];
                        $vul_id = $v1['vul_id'];
                        $vul_id_bind = $bind_data[$v1['vul_id']];
                        $f_sql .= "('" . $id . "','" . $family_id . "','" . $vul_id . "'),";
                        if (!empty($vul_id_bind)) {
                            foreach ($vul_id_bind as $key => $value) {
                                $f_sql .= "('" . $id . "','" . $family_id . "','" . $value . "'),";
                            }
                        }
                    }
                    $f_sql = substr($f_sql, 0, strlen($f_sql) - 1);
                    $query1 = "insert into bd_web_policy_selectors (policy_id,family_id,vul_id) values " . $f_sql;
                    $db->query($query1);

                }

                $sss = "select pl_detail,pl_file,type from bd_web_vul_lib,bd_web_policy_selectors where bd_web_vul_lib.vul_id = bd_web_policy_selectors.vul_id and bd_web_policy_selectors.policy_id = " . $id;
                //$ddd = "select script,file from web_vul_list,web_policy_ref where web_vul_list.vul_id_bind = web_policy_ref.vul_id and web_policy_ref.policy_id = ".$id;
                $sData = $db->fetch_all($sss);
                //$dData = $db->fetch_all($ddd);
                //$rData = array_merge($sData,$dData);
                $x_str = "/home/bluedon/bdscan/bdwebscan/bdwebpl/databases/";
                $y_str = "/home/bluedon/bdscan/bdwebscan/bdwebpy/";
                $str_c = '"webscan_id","osvdb","matchstring","message"';
                $str_h = '"webscan_id","method","osvdb","message"';
                $str_s = '"webscan_id","server","osvdb","message"';
                $i = 0;
                $fData = array();
                foreach ($sData as $k => $v) {
                    if (!empty($v['pl_file'])) {
                        $fData[$v['pl_file']] = $id;
                    }
                }
                foreach ($sData as $k => $v) {
                    if (!empty($v['pl_file'])) {
                        if (substr($v['type'],0,1) == '1') {
                            $s_str = $x_str;
                        } else {
                            $s_str = $y_str;
                        }
                        $s_tmp = $s_str . $v['pl_file'] . '_' . $id;
                        if ($i == 0) {
                            foreach ($fData as $key => $value) {
                                $f_tmp = $s_str . $key . '_' . $value;
                                //var_dump($f_tmp);
                                switch ($v['pl_file']) {
                                    case 'db_content_search':
                                        file_put_contents($f_tmp, $str_c . PHP_EOL);
                                        break;
                                    case 'db_httpoptions':
                                        file_put_contents($f_tmp, $str_h . PHP_EOL);
                                        break;
                                    case 'db_server_msgs':
                                        file_put_contents($f_tmp, $str_s . PHP_EOL);
                                        break;
                                    case 'db_tests':
                                        file_put_contents($f_tmp, '');
                                        break;
                                }

                            }

                        }
                        file_put_contents($s_tmp, $v['pl_detail'] . PHP_EOL, FILE_APPEND);
                    }
                    $i++;
                }

                $success = true;
                $msg = "操作成功";
                $hdata['sDes'] = '编辑WEB应用扫描策略[' . $name . ']';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            } else {
                $success = false;
                $msg = "操作失败";
            }
        } else {//新增
            $existTotal = $db->result_first("SELECT COUNT(`id`) FROM bd_web_policy ");
            if ($existTotal >= 1024) {
                $data['success'] = false;
                $data['msg'] = 'web配置已经达到或者超过1024条，不能继续添加';
                echo json_encode($data);
                exit;
            }
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_web_policy where name='" . $name . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $name . '已存在，请更换';
                echo json_encode($data);
                exit;
            }


//        dl("openvas.so");
//        vas_bd_initialize(INTERFACE_ROOT,9390);
//        $backcreateport = web_bd_addconfig($web_id,$s_scripts);      //返回1则创建成功
            $query = "insert into bd_web_policy (name,user_id) values('" . $name . "','" . $userid . "')";
            if ($db->query($query)) {
                $policy_id = $db->insert_id();
                if (!empty($vrows)) {
                    $t_s = '';
//                    foreach ($vrows as $k1 => $v1) {
//                        $vul_id = $v1['vul_id'];
//                        $tsql = "select $policy_id,vul_id,family_id from bd_web_vul_lib where vul_id = $vul_id";
//                        $tData = $db->fetch_first($tsql);
//                        //var_dump($tData);die;
//                        $t_s .= '(' . $tData[$policy_id] . ',' . $tData['vul_id'] . ',' . $tData['family_id'] . '),';
//                    }
//                    $t_s = rtrim($t_s, ',');
//                    $query1 = "insert into bd_web_policy_selectors (policy_id,vul_id,family_id) values " . $t_s;
//                    $db->query($query1);

                    foreach ($vrows as $k1=>$v1){
                        $t_s .= '('.$policy_id.','.$v1['vul_id'].','.$v1['family_id'].')'.',';
                    }
                    $t_s = rtrim($t_s,',');
                    $query1 = "insert into bd_web_policy_selectors (policy_id,vul_id,family_id) values $t_s" ;
                    $db->query($query1);
                }

                $sss = "select pl_detail,pl_file,type from bd_web_vul_lib,bd_web_policy_selectors where bd_web_vul_lib.vul_id = bd_web_policy_selectors.vul_id and bd_web_policy_selectors.policy_id = " . $policy_id;
                //$ddd = "select script,file from web_vul_list,web_policy_ref where web_vul_list.vul_id_bind = web_policy_ref.vul_id and web_policy_ref.policy_id = ".$id;
                $sData = $db->fetch_all($sss);
                //var_dump($sData);die;
                //$dData = $db->fetch_all($ddd);
                //$rData = array_merge($sData,$dData);
                $x_str = "/home/bluedon/bdscan/bdwebscan/bdwebpl/databases/";
//                $y_str = "/home/bluedon/bdscan/bdwebscan/bdwebpy/";
                $str_c = '"webscan_id","osvdb","matchstring","message"';
                $str_h = '"webscan_id","method","osvdb","message"';
                $str_s = '"webscan_id","server","osvdb","message"';
                file_put_contents($x_str . 'db_content_search_' . $policy_id, $str_c . PHP_EOL);
                file_put_contents($x_str . 'db_httpoptions_' . $policy_id, $str_h . PHP_EOL);
                file_put_contents($x_str . 'db_server_msgs_' . $policy_id, $str_s . PHP_EOL);
                file_put_contents($x_str . 'db_tests_' . $policy_id, '');
//                file_put_contents($y_str . 'db_content_search_' . $policy_id, $str_c . PHP_EOL);
//                file_put_contents($y_str . 'db_httpoptions_' . $policy_id, $str_h . PHP_EOL);
//                file_put_contents($y_str . 'db_server_msgs_' . $policy_id, $str_s . PHP_EOL);
//                file_put_contents($y_str . 'db_tests_' . $policy_id, '');
                $i = 0;
                foreach ($sData as $k => $v) {
                    if (!empty($v['pl_file'])) {

                        if (  substr($v['type'],0,1) == '1') {
                            $s_str = $x_str;
                        } else {
                           // $s_str = $y_str;
                        }
                        $s_tmp = $s_str . $v['pl_file'] . '_' . $policy_id;
                        if ($i == 0) {
                            switch ($v['pl_file']) {
                                case 'db_content_search':
                                    file_put_contents($x_str . 'db_content_search_' . $policy_id, $str_c . PHP_EOL);
                                   // file_put_contents($y_str . 'db_content_search_' . $policy_id, $str_c . PHP_EOL);
                                    break;
                                case 'db_httpoptions':
                                    file_put_contents($x_str . 'db_httpoptions_' . $policy_id, $str_h . PHP_EOL);
                                  //  file_put_contents($y_str . 'db_httpoptions_' . $policy_id, $str_h . PHP_EOL);
                                    break;
                                case 'db_server_msgs':
                                    file_put_contents($x_str . 'db_server_msgs_' . $policy_id, $str_s . PHP_EOL);
                                  //  file_put_contents($y_str . 'db_server_msgs_' . $policy_id, $str_s . PHP_EOL);
                                    break;
                                case 'db_tests':
                                    file_put_contents($x_str . 'db_tests_' . $policy_id, '');
                                  //  file_put_contents($y_str . 'db_tests_' . $policy_id, '');
                                    break;
                            }
                        }
                        file_put_contents($s_tmp, $v['pl_detail'] . PHP_EOL, FILE_APPEND);
                    }
                    $i++;
                }

                $success = true;
                $msg = "操作成功";
                $hdata['sDes'] = '新增WEB应用扫描策略[' . $name . ']';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            } else {
                $success = false;
                $msg = "操作失败";
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
        $webidrow = array();
        $d_f = array();
        $wids = "";
        $ids = $sPost['ids'];
        $d_f = explode(',', $ids);

        $x_str = "/home/bluedon/bdscan/bdwebscan/bdwebpl/databases/";
        //$y_str = "/home/bluedon/bdscan/bdwebscan/bdwebpy/";
        foreach ($d_f as $k => $v) {
            $d_a = $x_str . 'db_content_search_' . intval($v);
            $d_b = $x_str . 'db_httpoptions_' . intval($v);
            $d_c = $x_str . 'db_server_msgs_' . intval($v);
            $d_d = $x_str . 'db_tests_' . intval($v);
            //var_dump($d_d);die;
//            $y_a = $y_str . 'db_content_search_' . intval($v);
//            $y_b = $y_str . 'db_httpoptions_' . intval($v);
//            $y_c = $y_str . 'db_server_msgs_' . intval($v);
//            $y_d = $y_str . 'db_tests_' . intval($v);
            //同时删除文件
            unlink($d_a);
            unlink($d_b);
            unlink($d_c);
            unlink($d_d);
//            unlink($y_a);
//            unlink($y_b);
//            unlink($y_c);
//            unlink($y_d);
        }
        //$webids = $db->fetch_all("SELECT web_id,name FROM web_policy WHERE id in (".$ids.") ");
        $names = array();
        foreach ($ids as $k => $v) {
            $webidrow[] = $v['id'];
            $nItem = array('name' => $v['name']);
            array_push($names, $nItem);
        }
        array_filter($webidrow);
        if (!empty($webidrow)) {
            $wids = implode(",", $webidrow);
        }
//    dl("openvas.so");
//    vas_bd_initialize(INTERFACE_ROOT,9390);
//    $backcreateport = web_bd_deleteconfigs($wids);      //返回1则删除成功
        $query = "DELETE FROM bd_web_policy where id in (" . $ids . ") ";
        if ($db->query($query)) {
            $dquery = "DELETE FROM bd_web_policy_selectors where policy_id in (" . $ids . ") ";
            $db->query($dquery);
            $success = true;
            $msg = "操作成功";
            foreach ($names as $key => $val) {
                $hdata['sDes'] = '删除WEB扫描策略(' . $val['name'] . ')';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }

        } else {
            foreach ($names as $key => $val) {
                $hdata['sDes'] = '删除WEB扫描策略(' . $val['name'] . ')';
                $hdata['sRs'] = '失败';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
            $success = false;
            $msg = "操作失败";
        }

        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

    /**
     * 获取主机策略的漏洞分类
     * @ params table ：nvts_type
     * @ strategy : 1
     */
    function actionGetFamily()
    {
        global $db;
        $rows = array();
        $aData = $aItem = array();
        $where = " WHERE 1=1";
        $rows = $db->fetch_all("SELECT * FROM bd_web_family  $where ");

        foreach ($rows as $k => $v) {
            $aData[$v['name']] = $v['id'];
        }
        return $aData;
    }


}

?>