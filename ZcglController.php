<?php
namespace app\controllers;

class ZcglController extends BaseController
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
     * @资产新增和编辑页
     */
    function actionZc_edit()
    {
        global $db, $act;
        $aData = $TAR_M_OPEN_PORT = array();
        //新增资产时，记录部门id
        $bumenid = filterStr($_GET['bumenid']);
        if (substr($bumenid, 0, 1) == '_') {
            $zcid = intval(substr($bumenid, 1));
            $res = $db->fetch_first("SELECT * FROM bd_asset_device_info WHERE id = $zcid");
            $bumenid = intval($res['depart_id']);
            $tar_value = intval($res['value']);
        } else {
            $bumenid = intval(substr($bumenid, 1));
            $tar_value = 0;
        }
        $aData['bumenid'] = $bumenid;
        $aData['tar_value'] = $tar_value;
        $depart = $db->fetch_all("SELECT * FROM bd_asset_depart_info");
        $aData['depart'] = $depart;
        template2($act . '/zc_edit', $aData);
    }

    /**
     * @部门新增和编辑页
     */
    function actionBm_edit()
    {
        global $db, $act;
        $aData = array();

        template2($act . '/bm_edit', $aData);
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
     * @资产查看页
     */
    function actionViewzc()
    {
        global $act;
        template2($act . '/viewzc', array());
    }

    /**
     * @ 主机漏洞查看页
     */
    function actionVulhostview()
    {
        global $act;
        template2($act . '/vulhostview', array());
    }

    /**
     * @ WEB漏洞查看页
     */
    function actionVulwebview()
    {
        global $act;
        template2($act . '/vulwebview', array());
    }

    /**
     * @获取列表数据
     */
    function actionLists()
    {
        global $db;
        $sPost = $_POST;
        //var_dump($sPost);
        $page = intval($sPost['page']);
        $perpage = intval($sPost['pagesize']);
        $family =intval($sPost['family']) ;//不传值时，intval后为0
        $level = isset($sPost['level']) ? intval($sPost['level']) : 0;


        $total = 0;
        $rows = array();
        $aData = $aItem = array();
        $where = " WHERE 1=1";
        if ($level == 2) {
            $where .= " AND a.`id` = $family";
        } elseif ($family != 0 && $level < 2) {//选出1级类下所有资产
            $where .= " AND a.`depart_id` = $family";
        }
        $page = $page > 1 ? $page : 1;

        $tar_name = filterStr(isset($sPost['name']) ? $sPost['name'] :'');    //任务名称
        if (!empty($tar_name)) {
            $where .= " AND a.`name` LIKE '%{$tar_name}%'";
        }

        $total = $db->result_first("SELECT COUNT(a.`id`) FROM bd_asset_device_info as a $where");

        $maxPage = ceil($total / $perpage);//var_dump($page,$perpage,$maxPage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $where .= ' AND a.`depart_id` = b.`id`';
            //SELECT a.*,b.`DEPART_NAME` FROM `asset_target_info` as a INNER JOIN `asset_part_info` as b WHERE a.`TAR_FROM_DEPART` = b.`ID`;
            $rows = $db->fetch_all("SELECT a.*,b.`name` as depart_name FROM `bd_asset_device_info` as a INNER JOIN `bd_asset_depart_info` as b $where ORDER BY a.`id` DESC  LIMIT $start,$perpage");
            //$rows  = $db->fetch_all("SELECT * FROM asset_target_info  $where ORDER BY id DESC  LIMIT $start,$perpage");
            foreach ($rows as $k => $v) {
                $rows[$k]['open_port'] = str_replace("|", "\r\n", $v['open_port']);
            }
        }
        $depart = array();
        // echo $level;die;

        if ($level == 1) {
            $depart = $db->fetch_first("SELECT * FROM bd_asset_depart_info where id = $family");
        } else if ($level == 2) {
            $zc = $db->fetch_first("SELECT * FROM bd_asset_device_info where id = $family");
            $zc['open_port'] = str_replace("|", "<br />", $zc['open_port']);//在texteara中展示要用\r\n ，当作HTML标签时用<br />
            $zc['scan_time'] = date('Y-m-d H:i:s', $zc['scan_time']);
            if(!empty($zc['depart_id'])){
                $depart = $db->fetch_first("SELECT * FROM bd_asset_depart_info where id = {$zc['depart_id']}");
            }else{
                $depart='';
            }

            $data['zc'] = $zc;
        } else {
            $depart = 'none';
        }

        $data['part'] = $depart;
        $data['Rows'] = $rows;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }

//风险结果
    function actionRisklog2()
    {
        global $db;
        $sPost = $_POST;
        $aJson = $arr_bm = $arr_zcfx = array();
        $zclevel = intval($sPost['zclevel']);
        $zcbm = $zclevel == 2 ? filterStr($sPost['zcbm']) : intval($sPost['zcbm']);
        $zcip = filter_var($sPost['zcip'], FILTER_VALIDATE_IP) ? $sPost['zcip'] : '';
        if ($zclevel == 1) {
            //所有部门风险
            $res1 = $db->fetch_all("select MAX(end_time) as end_time,part_id,id from `bd_history_assets` group by part_id");
            foreach ($res1 as $k => $v) {
                $arr_bm[] = $db->fetch_first("select * from `bd_history_assets` where id=" . $v['id']);
            }
            //var_dump($arr_bm);exit;
            //$sql_bm = "select MAX(num) as num,part_id,h,m,l,i,t,end_time FROM `history_department` GROUP BY part_id";
            //$arr_bm = $db->fetch_all($sql_bm);

            foreach ($arr_bm as $k => $v) {
                $arr_bm[$k]['end_time'] = date("Y-m-d H:i:s",$v['end_time']);
                $arr_bm[$k]['tid'] = $v['id'];
                $arr_bm[$k]['name'] = $db->result_first("select name FROM `bd_asset_depart_info` WHERE id=" . $v['part_id']);
                $arr_bm[$k]['R'] = $v['h'] > 0 ? 'h' : ($v['m'] > 0 ? 'm' : ($v['l'] > 0 ? 'l' : 'i'));//增加风险评估列的数据
            }


            //var_dump($arr_bm);exit;
            if (!empty($arr_bm)) {

                $aJson['success'] = true;
                $aJson['bmdata'] = $arr_bm;
                //echo json_encode($aJson);
                //exit;
            } else {
                $aJson['success'] = false;
                $aJson['bmdata'] = Yii::t('app', '没有相关部门扫描');
            }
            //所有资产风险
            //$sql_zcfx = "select MAX(num) as num,ip,part_id,h,m,l,i,t,end_time,id FROM `history_all_assets` GROUP BY ip";
            //$arr_zcfx = $db->fetch_all($sql_zcfx);
            $res2 = $db->fetch_all("select MAX(end_time) as end_time,ip,id from `bd_host_history` group by ip");
            foreach ($res2 as $k => $v) {
                $arr_zcfx[] = $db->fetch_first("select * from `bd_host_history` where id=" . $v['id']);
            }
            //var_dump($arr_zcfx);exit;

            foreach ($arr_zcfx as $k => $v) {
                $arr_zcfx[$k]['end_time'] = date("Y-m-d H:i:s",$v['end_time']);
                $arr_zcfx[$k]['tid'] = $v['id'];
                $arr_zcfx[$k]['name'] = $db->result_first("select name FROM `bd_asset_depart_info` WHERE id=" . $v['part_id']);
                $arr_zcfx[$k]['R'] = $v['h'] > 0 ? 'h' : ($v['m'] > 0 ? 'm' : ($v['l'] > 0 ? 'l' : 'i'));//增加风险评估列的数据
            }


            //var_dump($arr_zcfx);exit;
            if (!empty($arr_zcfx)) {
                $aJson['success'] = true;
                $aJson['zcdata'] = $arr_zcfx;
            } else {
                $aJson['success'] = false;
                $aJson['zcdata'] = Yii::t('app', '没有相关资产扫描');
            }
            //var_dump($aJson);exit;
            echo json_encode($aJson);
            exit;
        }
        if ($zclevel == 2) {//这时传过来的$zcbm直接就是部门名称
            //$sql_f = "select ip,max(end_time) as time,max(num) as num,count(risk_factor='I' or null) as I,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H,count(risk_factor='L' or null) as L from vul_details_sum_".$a_tid['id']." group by ip";
            //某个部门风险
            $zcbmId = $db->fetch_first("select id from `bd_asset_depart_info` WHERE name='" . $zcbm . "'");
            $arr_bm = $db->fetch_all("select * from `bd_history_assets` where part_id=" . $zcbmId['id'] . " order by end_time desc limit 1");

            //var_dump($arr_bm);exit;
            //$sql_bm = "select MAX(num) as num,part_id,h,m,l,i,t,end_time FROM `history_department` GROUP BY part_id";
            //$arr_bm = $db->fetch_all($sql_bm);
            $arr_bm[0]['tid'] = $arr_bm[0]['id'];
            $arr_bm[0]['name'] = $zcbm;
            $arr_bm[0]['R'] = $arr_bm[0]['h'] > 0 ? 'h' : ($arr_bm[0]['m'] > 0 ? 'm' : ($arr_bm[0]['l'] > 0 ? 'l' : 'i'));//增加风险评估列的数据

            //var_dump($arr_bm);exit;
            if (!empty($arr_bm)) {

                $aJson['success'] = true;
                $aJson['bmdata'] = $arr_bm;
                //echo json_encode($aJson);
                //exit;
            } else {
                $aJson['success'] = false;
                $aJson['bmdata'] = Yii::t('app', '没有相关部门扫描');
            }

            //所有资产风险
            //$sql_zcfx = "select MAX(num) as num,ip,part_id,h,m,l,i,t,end_time,id FROM `history_all_assets` GROUP BY ip";
            //$arr_zcfx = $db->fetch_all($sql_zcfx);
            $res2 = $db->fetch_all("select MAX(end_time) as end_time,ip,part_id,id from `bd_history_assets` group by ip");
            foreach ($res2 as $k => $v) {
                if ($v['part_id'] == $zcbmId['id'])
                    $arr_zcfx[] = $db->fetch_first("select * from `bd_host_history` where id=" . $v['id']);
            }
            //var_dump($arr_zcfx);exit;

            foreach ($arr_zcfx as $k => $v) {
                $arr_zcfx[$k]['tid'] = $v['id'];
                $arr_zcfx[$k]['name'] = $db->result_first("select name FROM `bd_asset_depart_info` WHERE id=" . $v['part_id']);
                $arr_zcfx[$k]['R'] = $v['h'] > 0 ? 'h' : ($v['m'] > 0 ? 'm' : ($v['l'] > 0 ? 'l' : 'i'));//增加风险评估列的数据
            }


            if (!empty($arr_zcfx)) {
                $aJson['success'] = true;
                $aJson['zcdata'] = $arr_zcfx;
            } else {
                $aJson['success'] = false;
                $aJson['zcdata'] = Yii::t('app', '没有相关资产扫描');
            }
            //var_dump($aJson);exit;
            echo json_encode($aJson);
            exit;
        }
        if ($zclevel == 3) {//这时传过来的$zcbm只是部门id
            //1.风险级别区域
            $sql_f = "select end_time as end_time,total,h as H,m as M,l as L,i as I from `bd_history_assets` WHERE part_id=" . $zcbm . " and ip='" . $zcip . "'";
            $arr_f = $db->fetch_all($sql_f);
            if (!empty($arr_f)) {
                //var_dump($arr_f);
                $arr_d = array();
                foreach ($arr_f as $k => $val) {
                    $val['R'] = $val['H'] > 0 ? '4' : ($val['M'] > 0 ? '3' : ($val['L'] > 0 ? '2' : '1'));//增加风险评估列的数据
                    $val['time'] = date("Y-m-d H:i:s",$val['end_time']);
                    $val = array_reverse($val);//翻转数组
                    $arr_t[$k] = $val;
                    $f_val = array_values($val);//返回值组成的新数组
                    $arr_f[$k] = $f_val;
                }
                array_push($arr_d, $arr_f);
                $ttt = array_column($arr_t, 'R');//返回数组某一列的值
                $ddd = array_column($arr_t, 'time');
                array_push($arr_d, $ttt);
                array_push($arr_d, $ddd);
                //var_dump($arr_d);exit;
                $aJson['success'] = true;
                $aJson['zcdata'] = $arr_d;
            } else {
                $aJson['success'] = false;
                $aJson['zcdata'] = Yii::t('app', '没有相关资产扫描');
            }
            //2.风险数目区域
            //$pid = intval($sPost['part_id']);

            //$sql_f = "select ip,end_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_".$tid." where ip="."'".$zcip."'"." group by num order by num asc";
            $sql_f = "select end_time as end_time,total,h as H,m as M,l as L,i as I from `bd_history_assets` WHERE part_id=" . $zcbm . " and ip='" . $zcip . "'";
            $arr_f = $db->fetch_all($sql_f);
            if (!empty($arr_f)) {
                $arr_d = $arr_t = array();
                foreach ($arr_f as $k => $val) {
                    $val['R'] = $val['H'] > 0 ? '4' : ($val['M'] > 0 ? '3' : ($val['L'] > 0 ? '2' : '1'));//增加风险评估列的数据
                    $val['time'] = date("Y-m-d H:i:s",$val['end_time']);
                    $val = array_reverse($val);
                    $arr_t[$k] = $val;
                    $f_val = array_values($val);
                    $arr_f[$k] = $f_val;
                }
                array_push($arr_d, $arr_f);
                $ttt = array_column($arr_t, 'R');
                $ddd = array_column($arr_t, 'time');
                array_push($arr_d, $ttt);
                array_push($arr_d, $ddd);
                //var_dump($arr_d);exit;
                $aJson['success'] = true;
                $aJson['zcsmdata'] = $arr_d;
            } else {
                $aJson['success'] = false;
                $aJson['zcsmdata'] = Yii::t('app', '没有相关风险结果');
            }
            echo json_encode($aJson);
            exit;
        }
    }

//风险结果
    function actionRiskLog()
    {
        global $db;
        $sPost = $_POST;
        $aJson = array();
        $zclevel = intval($sPost['zclevel']);
        $zcbm = $zclevel == 2 ? filterStr($sPost['zcbm']) : intval($sPost['zcbm']);
        $zcip = filter_var($sPost['zcip'], FILTER_VALIDATE_IP) ? $sPost['zcip'] : '';

        if ($zclevel == 1) {
            $sql_bm = "select name from bd_asset_depart_info";
            $arr_bm = $db->fetch_all($sql_bm);
            $tt = [];
            //var_dump($arr_bm);
            foreach ($arr_bm as $v) {
                $v['name'] = Yii::t('app', '资产管理') . '-' . $v['name'];
                //$arr_bm[$k] = $v;
                array_push($tt, "'" . $v['name'] . "'");
            }
            //var_dump(implode(',',$tt));exit;
            $sql_taskid = "select id,task_name from task_manage where task_name in (" . implode(',', $tt) . ")";
            $arr_tid = $db->fetch_all($sql_taskid);//看看是否有任务
            $arr_tid_exist = array();
            if (!empty($arr_tid)) {
                $is_t = 0;
                foreach ($arr_tid as $k => $v) {
                    $tb = "'%vul_details_sum_" . $v['id'] . "%'";
                    $sql_t = "show tables from security like " . $tb;
                    $res_t = $db->result_first($sql_t);
                    if ($res_t != false) {
                        $is_t = 1;//看看是否有表
                        array_push($arr_tid_exist, $v);
                    }
                }
                //var_dump($is_t);exit;
                if ($is_t != 0) {
                    $i = 0;
                    $arr_d = array();
                    foreach ($arr_tid_exist as $v) {//每张表对应一个任务id(部门)
                        $tb = "vul_details_sum_" . $v['id'];
                        $sql_f = "select ip,max(report_time) as time,max(num) as num,count(risk_factor='I' or null) as I,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H,count(risk_factor='L' or null) as L from " . $tb . " group by ip";
                        $arr_f = $db->fetch_all($sql_f);
                        foreach ($arr_f as $k => $val) {
                            //var_dump($v['task_name']);
                            $val['tid'] = $v['id'];
                            $val['name'] = substr($v['task_name'], 13);//给每个ip添加对于的部门属性列
                            $val['R'] = $val['H'] > 0 ? 'H' : ($val['M'] > 0 ? 'M' : ($val['L'] > 0 ? 'L' : 'I'));//增加风险评估列的数据
                            $arr_f[$k] = $val;
                        }
                        $arr_d = array_merge($arr_d, $arr_f);
                        $i++;
                    }
                    if (!empty($arr_d)) {

                        $aJson['success'] = true;
                        $aJson['data'] = $arr_d;
                        echo json_encode($aJson);
                        exit;
                    } else {
                        $aJson['success'] = false;
                        $aJson['data'] = Yii::t('app', '没有相关扫描');
                        echo json_encode($aJson);
                        exit;
                    }

                } else {
                    $aJson['success'] = false;
                    $aJson['data'] = Yii::t('app', '没有相关扫描');
                    echo json_encode($aJson);
                    exit;
                }
            } else {
                $aJson['success'] = true;
                $aJson['data'] = Yii::t('app', '没有添加相关任务');
                echo json_encode($aJson);
                exit;
            }
        }
        if ($zclevel == 2) {//这时传过来的$zcbm直接就是部门名称
            $a_bm = "'" . Yii::t('app', '资产管理') . "-" . $zcbm . "'";
            $sql_taskid = "select uuid from bd_host_task_manage where name = " . $a_bm . 'order by id desc';//取最新的任务
            $a_tid = $db->fetch_first($sql_taskid);//看是否存在这个任务
            if (!empty($a_tid)) {

                $sql_f = "select ip,max(end_time) as time,max(num) as num,i,m,h,l from bd_host_history where uuid =" . $a_tid['uuid'] . " group by ip";
                $arr_f = $db->fetch_all($sql_f);
                if (!empty($arr_f)) {
                    foreach ($arr_f as $k => $val) {
                        $val['tid'] = $a_tid['uuid'];
                        $val['R'] = $val['H'] > 0 ? 'H' : ($val['M'] > 0 ? 'M' : ($val['L'] > 0 ? 'L' : 'I'));//增加风险评估列的数据
                        $arr_f[$k] = $val;
                    }
                    $aJson['success'] = true;
                    $aJson['data'] = $arr_f;
                    echo json_encode($aJson);
                    exit;
                } else {
                    $aJson['success'] = false;
                    $aJson['data'] = Yii::t('app', '没有相关扫描');
                    echo json_encode($aJson);
                    exit;
                }

            } else {
                $aJson['success'] = false;
                $aJson['data'] = Yii::t('app', '没有添加相关任务');
                echo json_encode($aJson);
                exit;
            }
        }
        if ($zclevel == 3) {//这时传过来的$zcbm只是部门id

            if (!empty(filterStr($sPost['zct']))) {//如果是在资产管理或部门的列表点击查看进来的
                $a_bm = filterStr($sPost['zct']);
            } else {
                $sql_bm = "select name from bd_asset_depart_info where id = " . $zcbm;
                $a_bm = $db->result_first($sql_bm);
                $a_bm = "'" . Yii::t('app', '资产管理') . "-" . $a_bm . "'";
            }

            $sql_taskid = "select id from task_manage where task_name = " . $a_bm;
            $a_tid = $db->result_first($sql_taskid);//看是否存在这个任务
            $tb = "'vul_details_sum_" . $a_tid . "'";

            if (!empty($a_tid)) {

                $sql_t = "show tables from security like " . $tb;
                $is_t = $db->result_first($sql_t) != false ? 1 : 0;//看是否存在这张表

                if ($is_t != 0) {
                    $sql_f = "select ip,report_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_" . $a_tid . " where ip=" . "'" . $zcip . "'" . " group by num order by num asc";

                } else {
                    $sql_f = "select ip,report_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_ip where ip=" . "'" . $zcip . "'" . " group by num order by num asc";

                }

            } else {
                $sql_f = "select ip,report_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_ip where ip=" . "'" . $zcip . "'" . " group by num order by num asc";

            }

            $arr_f = $db->fetch_all($sql_f);

            if (!empty($arr_f)) {
                $arr_d = array();
                foreach ($arr_f as $k => $val) {
                    $val['R'] = $val['H'] > 0 ? '4' : ($val['M'] > 0 ? '3' : ($val['L'] > 0 ? '2' : '1'));//增加风险评估列的数据
                    $val['time'] = $val['report_time'];
                    $val = array_reverse($val);
                    $arr_t[$k] = $val;
                    $f_val = array_values($val);
                    $arr_f[$k] = $f_val;

                }
                array_push($arr_d, $arr_f);
                $ttt = array_column($arr_t, 'R');
                $ddd = array_column($arr_t, 'report_time');

                array_push($arr_d, $ttt);
                array_push($arr_d, $ddd);

                $aJson['success'] = true;
                $aJson['data'] = $arr_d;
                echo json_encode($aJson);
                exit;
            } else {
                $aJson['success'] = false;
                $aJson['data'] = Yii::t('app', '没有相关扫描');
                echo json_encode($aJson);
                exit;
            }


        }
    }

//单个部门风险结果
    function actionShowbmlog()
    {
        global $db;
        $sPost = $_POST;
        $aJson = array();
        $pid = intval($sPost['part_id']);

        //$sql_f = "select ip,report_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_".$tid." where ip="."'".$zcip."'"." group by num order by num asc";
        $sql_f = "select report_time,num,h as H,m as M,l as L,i as I from `history_department` WHERE part_id=" . $pid;
        $arr_f = $db->fetch_all($sql_f);
        //var_dump($arr_f);exit;
        $arr_d = array();
        foreach ($arr_f as $k => $val) {
            $val['R'] = $val['H'] > 0 ? '4' : ($val['M'] > 0 ? '3' : ($val['L'] > 0 ? '2' : '1'));//增加风险评估列的数据
            $val['time'] = $val['report_time'];
            $val = array_reverse($val);
            $arr_t[$k] = $val;
            $f_val = array_values($val);
            $arr_f[$k] = $f_val;
        }
        array_push($arr_d, $arr_f);
        $ttt = array_column($arr_t, 'R');
        $ddd = array_column($arr_t, 'report_time');
        array_push($arr_d, $ttt);
        array_push($arr_d, $ddd);
        //var_dump($arr_d);exit;
        $aJson['success'] = true;
        $aJson['data'] = $arr_d;
        echo json_encode($aJson);
        exit;
    }

//单个IP风险结果
    function actionShowiplog()
    {
        global $db;
        $sPost = $_POST;
        $aJson = $arr_t = array();
        //$zcid = intval($sPost['tid']);
        $part_id = intval($sPost['part_id']);
        $zcip = filter_var($sPost['zcip'], FILTER_VALIDATE_IP);

        $sql_f = "select end_time,total,h as H,m as M,l as L,i as I from `bd_host_history` WHERE part_id=" . $part_id . " and ip='" . $zcip . "'";
        $arr_f = $db->fetch_all($sql_f);
        //var_dump($arr_f);
        $arr_d = array();
        foreach ($arr_f as $k => $val) {
            $val['R'] = $val['H'] > 0 ? '4' : ($val['M'] > 0 ? '3' : ($val['L'] > 0 ? '2' : '1'));//增加风险评估列的数据
            $val['time'] = date("Y-m-d H:i:s",$val['end_time']);
            $val = array_reverse($val);//翻转数组
            $arr_t[$k] = $val;
            $f_val = array_values($val);//返回值组成的新数组
            $arr_f[$k] = $f_val;
        }
        array_push($arr_d, $arr_f);
        $ttt = array_column($arr_t, 'R');//返回数组某一列的值
        $ddd = array_column($arr_t, 'time');
        array_push($arr_d, $ttt);
        array_push($arr_d, $ddd);
        //var_dump($arr_d);exit;
        $aJson['success'] = true;
        $aJson['zcdata'] = $arr_d;

        //2.风险数目区域
        //$pid = intval($sPost['part_id']);

        //$sql_f = "select ip,report_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_".$tid." where ip="."'".$zcip."'"." group by num order by num asc";
        $sql_f = "select end_time,total,h as H,m as M,l as L,i as I from `bd_host_history` WHERE part_id=" . $part_id . " and ip='" . $zcip . "'";
        $arr_f = $db->fetch_all($sql_f);
        //var_dump($arr_f);exit;
        $arr_d = $arr_t = array();
        foreach ($arr_f as $k => $val) {
            $val['R'] = $val['H'] > 0 ? '4' : ($val['M'] > 0 ? '3' : ($val['L'] > 0 ? '2' : '1'));//增加风险评估列的数据
            $val['time'] = date("Y-m-d H:i:s",$val['end_time']);
            $val = array_reverse($val);
            $arr_t[$k] = $val;
            $f_val = array_values($val);
            $arr_f[$k] = $f_val;
        }
        array_push($arr_d, $arr_f);
        $ttt = array_column($arr_t, 'R');
        $ddd = array_column($arr_t, 'time');
        array_push($arr_d, $ttt);
        array_push($arr_d, $ddd);
        //var_dump($arr_d);exit;
        $aJson['success'] = true;
        $aJson['zcsmdata'] = $arr_d;

        echo json_encode($aJson);
        exit;
        /*global $db;
    $sPost = $_POST;
    $aJson = array();
    $tid = intval($sPost['tid']);
    $zcip   = filter_var($sPost['zcip'],FILTER_VALIDATE_IP);

    $sql_f = "select ip,report_time,num,count(risk_factor='I' or null) as I,count(risk_factor='L' or null) as L,count(risk_factor='M' or null) as M,count(risk_factor='H' or null) as H from vul_details_sum_".$tid." where ip="."'".$zcip."'"." group by num order by num asc";

    $arr_f = $db->fetch_all($sql_f);

    $arr_d = array();
    foreach ($arr_f as $k=>$val){
        $val['R'] = $val['H']>0? '4':($val['M']>0? '3':($val['L']>0? '2':'1'));//增加风险评估列的数据
        $val['time'] = $val['report_time'];
        $val = array_reverse($val);
        $arr_t[$k] = $val;
        $f_val = array_values($val);
        $arr_f[$k] = $f_val;
    }
    array_push($arr_d,$arr_f);
    $ttt = array_column($arr_t,'R');
    $ddd = array_column($arr_t,'report_time');
    array_push($arr_d,$ttt);
    array_push($arr_d,$ddd);
    //var_dump($ttt);exit;
    $aJson['success'] = true;
    $aJson['data'] = $arr_d;
    echo json_encode($aJson);
    exit;*/

    }

    /**
     * @获取部门风险结果
     */
    function actionBumenRiskLog()
    {
        global $db;
        $sPost = $_POST;
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);

        $family = intval($sPost['family']);//不传值时，intval后为0
        $level = intval($sPost['level']);

        $total = 0;
        $rows = array();
        $aData = $aItem = array();
        $where = " WHERE 1=1";
        if ($level == 1) {
            $where .= " AND `PART_ID` = $family";
        } else if ($level == 2) {
            $name = filterStr($sPost['name']);
            //if(filter_ip($name))
            $where .= " AND `SCAN_IP` = '{$name}'";
        }
        /*elseif($family!=0 && $level<2) {//选出1级类下所有资产
        $where .= " AND `TAR_FROM_DEPART` = $family";
    }*/
        $page = $page > 1 ? $page : 1;

        /*$tar_name = filterStr($sPost['TAR_NAME']);    //任务名称
    if (!empty($tar_name)) {
        $where .= " AND `TAR_NAME` LIKE '%{$tar_name}%'";
    }*/

        $total = $db->result_first("SELECT COUNT(`id`) FROM asset_risk_log $where");

        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            //var_dump("SELECT * FROM asset_risk_log  $where ORDER BY ID DESC  LIMIT $start,$perpage");
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM asset_risk_log  $where ORDER BY ID DESC  LIMIT $start,$perpage");
            //var_dump($rows);
        }

        $data['Rows2'] = $rows;
        $data['Total2'] = $total;
        echo json_encode($data);
        exit;
    }

//查看信息/风险等级图
    function actionGetRiskView()
    {
        global $db, $act, $show;
        //var_dump($_POST);
        //$parm = $_POST;
        //filterStr();
        //$parm['ID'] = isset($parm['ID'])?intval($parm['ID']):0;

        //$rows  = $db->fetch_all("SELECT * FROM asset_target_info WHERE ID='".$parm['ID']."'");
        $where = 'WHERE 1=1';
        $where2 = '';
        $aData = $sRows = array();
        $aData = $_POST;
        //var_dump($aData);
        if ($aData['id'] == -1) {//总风险
            if (!is_null($aData['curdata']) && $aData['curdata'][1]['value'] == 1) {//部门风险
                //var_dump($aData['curdata']);
                $family = $aData['curdata'][0]['value'];
                $level = $aData['curdata'][1]['value'];
                $where .= " AND PART_ID = {$family}";
            } else if (!is_null($aData['curdata']) && $aData['curdata'][2]['value'] == 2) {//查看部门下面单个设备的风险
                $scan_ip = $aData['curdata'][0]['value'];
                $where .= " AND SCAN_IP = '{$scan_ip}'";
            }
        } else {//单个设备风险
            //var_dump($aData['curdata']);
            //$id = $aData['id'];
            //$where .= " AND ID = '{$id}'";
            $scan_ip = $aData['curdata']['SCAN_IP'];
            $where .= " AND SCAN_IP = '{$scan_ip}'";
        }
        //$data[]  = $db->fetch_first("SELECT * FROM asset_risk_log WHERE SCAN_IP=".$parm['SCAN_IP']);
        $res = $db->fetch_all("SELECT * FROM asset_risk_log {$where}");
        if ($res) {
            $time = '';
            $h = $m = $l = $i = '';
            $h2 = $m2 = $l2 = $i2 = '';
            $h3 = $m3 = $l3 = $i3 = '';
            foreach ($res as $k => $v) {
                $time .= "'" . date('d日H:i:s', $v['RESULT_TIME']) . "',";
                $h .= $v['LEAK_HIGH'] . ',';
                $m .= $v['LEAK_MIDDLE'] . ',';
                $l .= $v['LEAK_LOW'] . ',';
                $i .= $v['LEAK_MESSAGE'] . ',';
                //按扫描类型显示
                if ($v['SCAN_TYPE'] == 1) {
                    $h2 .= $v['LEAK_HIGH'] . ',';
                    $m2 .= $v['LEAK_MIDDLE'] . ',';
                    $l2 .= $v['LEAK_LOW'] . ',';
                    $i2 .= $v['LEAK_MESSAGE'] . ',';
                } else if ($v['SCAN_TYPE'] == 2) {
                    $h3 .= $v['LEAK_HIGH'] . ',';
                    $m3 .= $v['LEAK_MIDDLE'] . ',';
                    $l3 .= $v['LEAK_LOW'] . ',';
                    $i3 .= $v['LEAK_MESSAGE'] . ',';
                }
            }
            $time = rtrim($time, ',');
            $h = rtrim($h, ',');
            $m = rtrim($m, ',');
            $l = rtrim($l, ',');
            $i = rtrim($i, ',');
            $data['time'] = $time;
            $data['h'] = $h;
            $data['m'] = $m;
            $data['l'] = $l;
            $data['i'] = $i;

            $h2 = rtrim($h2, ',');
            $m2 = rtrim($m2, ',');
            $l2 = rtrim($l2, ',');
            $i2 = rtrim($i2, ',');
            $data['h2'] = $h2;
            $data['m2'] = $m2;
            $data['l2'] = $l2;
            $data['i2'] = $i2;

            $h3 = rtrim($h3, ',');
            $m3 = rtrim($m3, ',');
            $l3 = rtrim($l3, ',');
            $i3 = rtrim($i3, ',');
            $data['h3'] = $h3;
            $data['m3'] = $m3;
            $data['l3'] = $l3;
            $data['i3'] = $i3;
        }


        //var_dump($rows2);
        $success = true;
        $msg = Yii::t('app', '操作成功');
        $data['rows'] = $rows;
        //$data['rows2'] = $rows2;
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;

    }

    function actionGetbumenFamily()
    {
        global $db;
        $rows = $rows2 = array();
        $aData = $aItem = $rData = $pData = $nobumen = array();
        $where = " WHERE 1=1";
        $rows = $db->fetch_all("SELECT * FROM bd_asset_depart_info  $where ");
        $nowdj = false;
        foreach ($rows as $k => $v) {
            if ($v['id'] == 1) {//把未登记项排在最后面显示
                $bd = "resource/skin/blue/images/home.png";
                $nobumen = array(
                    "open" => false,
                    "id" => 1,
                    "name" => Yii::t('app', '未登记资产'),
                    "parent_id" => 0,
                    "pId" => 0,
                    "icon" => $bd
                );
                $rows2 = $db->fetch_all("SELECT * FROM bd_asset_device_info WHERE depart_id='1'");
                if (!empty($rows2)) {
                    foreach ($rows2 as $kk => $vv) {
                        if ($vv['isnew'] == 1) {
                            $bd = "resource/skin/blue/images/o_level.png";
                        } else {
                            $hisRisk = $db->fetch_first("SELECT * FROM history_all_assets WHERE ip='" . $vv['ipv4'] . "' ORDER BY num desc");
                            $tbxx = $hisRisk['h'] > 0 ? 'h' : ($hisRisk['m'] > 0 ? 'm' : ($hisRisk['l'] > 0 ? 'l' : 'i'));
                            $bd = "resource/skin/blue/images/" . $tbxx . "_level.png";
                        }
                        $pData = array(
                            "open" => false,
                            "id" => $vv['id'],
                            "name" => !empty($vv['name']) ? $vv['name'] : $vv['ipv4'],
                            "parent_id" => '',
                            "pId" => 1,
                            "icon" => is_null($bd) ? "" : $bd,
                        );
                        array_push($aData, $pData);
                    }
                } else {
                    $nowdj = true;
                }
                continue;
            }
            $bd = "resource/skin/blue/images/home.png";
            $aItem = array(
                "open" => false,
                "id" => $v['id'],
                "name" => $v['name'],
                "parent_id" => 0,
                "pId" => 0,
                "icon" => $bd
            );
            array_push($aData, $aItem);
            //$where = ' WHERE TAR_FROM_DEPART='.$V['ID'];
            // print_r("SELECT * FROM asset_target_info WHERE TAR_FROM_DEPART='".$v['ID']."'");
            //按ipv4排序
            //$rows2  = $db->fetch_all("SELECT *,INET_ATON(TAR_M_IPV4) as b FROM asset_target_info WHERE TAR_FROM_DEPART='".$v['ID']."' order by TAR_M_IPV4");
            $rows2 = $db->fetch_all("SELECT * FROM bd_asset_device_info WHERE depart_id='" . $v['id'] . "' order by name");
            if (!empty($rows2)) {
                foreach ($rows2 as $kk => $vv) {
                    if ($vv['isnew'] == 1) {
                        $bd = "resource/skin/blue/images/o_level.png";
                    } else {
                        $hisRisk = $db->fetch_first("SELECT * FROM history_all_assets WHERE ip='" . $vv['ipv4'] . "' ORDER BY num desc");
                        $tbxx = $hisRisk['h'] > 0 ? 'h' : ($hisRisk['m'] > 0 ? 'm' : ($hisRisk['l'] > 0 ? 'l' : 'i'));
                        $bd = "resource/skin/blue/images/" . $tbxx . "_level.png";
                    }
                    $pData = array(
                        "open" => false,
                        "id" => $vv['id'],
                        "name" => !empty($vv['name']) ? $vv['name'] : $vv['ipv4'],
                        "parent_id" => '',
                        "pId" => $v['id'],
                        "icon" => is_null($bd) ? "" : $bd,
                    );
                    array_push($aData, $pData);
                }
            }
        }
        if (!$nowdj && !empty($nobumen))
            array_push($aData, $nobumen);//如果未登记栏目里没有资产，则在树上隐藏
        $rData = array(
            "open" => true,
            "id" => 0,
            "name" => Yii::t('app', '资产树'),
            "parent_id" => '',
            "pId" => ''
        );

        array_push($aData, $rData);
        echo json_encode($aData);
        exit;
    }

    /**
     * @新增或者编辑部门，保存到数据库
     */
    function actionAddAndEditBumen()
    {
        global $db, $act, $show;

        $sPost = $_POST;
        $sRows = array();
        $id = intval($sPost['id']);     //资产id
        //基本信息
        $sRows['name'] = filterStr($sPost['name']);
        $sRows['master'] = filterStr($sPost['master']);
        $sRows['telephone'] = filterStr($sPost['telephone']);
        $sRows['email'] = filterStr($sPost['email']);
        $sRows['remark'] = filterStr($sPost['remark']);

        if ($id) {//编辑
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_asset_depart_info where name='" . $sRows['name'] . "' And id !=" . $id);
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $sRows['name'] . Yii::t('app', '已存在，请更换');
                echo json_encode($data);
                exit;
            }

            $sFieldValue = "";
            foreach ($sRows as $k => $v) {
                $sFieldValue .= $k . "= '" . $v . "',";
            }
            $sFieldValue = rtrim($sFieldValue, ",");


            $sql = "UPDATE bd_asset_depart_info SET " . $sFieldValue . " WHERE id=" . $id;
            if ($db->query($sql)) {
                $success = true;
                $msg = Yii::t('app', "修改成功");
                $hdata['sDes'] = Yii::t('app', '修改部门') . '(' . $sRows['name'] . ')';
                $hdata['sRs'] = Yii::t('app', "成功");
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            } else {
                $success = false;
                $msg = Yii::t('app', "编辑失败");
                $hdata['sDes'] = Yii::t('app', '编辑任务') . '(' . $sRows['name'] . ')';
                $hdata['sRs'] = Yii::t('app', '失败');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        } else {//新增
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_asset_depart_info where name='" . $sRows['name'] . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $sRows['name'] . Yii::t('app', '已存在，请更换');
                echo json_encode($data);
                exit;
            }
            $stField = "";
            $stValue = "";
            foreach ($sRows as $k => $v) {
                $stField .= $k . ",";
                $stValue .= "'" . $v . "',";
            }
            $stField = rtrim($stField, ",");
            $stValue = rtrim($stValue, ",");


            $sql = "INSERT INTO bd_asset_depart_info (" . $stField . ") VALUES (" . $stValue . ")";

            if ($db->query($sql)) {
                $insert_id = $db->insert_id();

                if ($insert_id) {
                    $success = true;
                    $msg = Yii::t('app', "新增成功");
                    $hdata['sDes'] = Yii::t('app', '新增部门') . '(' . $sRows['name'] . ')';
                    $hdata['sRs'] = Yii::t('app', "成功");
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                    $data['success'] = $success;
                    $data['msg'] = $msg;
                    echo json_encode($data);
                    exit;
                } else {
                    $success = false;
                    $msg = Yii::t('app', "新增失败");
                    $hdata['sDes'] = Yii::t('app', '新增部门') . '(' . $sRows['name'] . ')';
                    $hdata['sRs'] = Yii::t('app', "失败");
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                    $data['success'] = $success;
                    $data['msg'] = $msg;
                    echo json_encode($data);
                    exit;
                }

            } else {
                $success = false;
                $msg = Yii::t('app', "新增失败");
                $hdata['sDes'] = Yii::t('app', '新增部门') . '(' . $sRows['name'] . ')';
                $hdata['sRs'] = Yii::t('app', "成功");
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
    }

    /**
     * @新增或者编辑资产，保存到数据库
     */
    function actionAddAndEdit()
    {
        global $db, $act, $show;

        $sPost = $_POST;
        //var_dump($sPost);exit;
        if (!empty($sPost['TAR_M_MAC'])) {
            if (!preg_match("/[0-9a-f][0-9a-f][:-]" . "[0-9a-f][0-9a-f][:-]" . "[0-9a-f][0-9a-f][:-]" . "[0-9a-f][0-9a-f][:-]" . "[0-9a-f][0-9a-f][:-]" . "[0-9a-f][0-9a-f]/i", $sPost['TAR_M_MAC'])) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'MAC地址格式错误！');
                echo json_encode($data);
                exit;
            }
        }
        /*if(!empty($sPost['TAR_M_IPV6'])){
        if(!filter_var($sPost['TAR_M_IPV6'], FILTER_VALIDATE_IP)){
            $data['success'] = false;
            $data['msg'] = 'ipv6地址格式错误！';
            echo json_encode($data);
            exit;
        }
    }*/
        $sRows = array();
        $id = intval($sPost['id']);     //资产id
        //基本信息
        $sRows['name'] = filterStr($sPost['name']);
        $sRows['depart_id'] = intval($sPost['depart_id']);
        $sRows['type'] = intval($sPost['type']);
        $sRows['value'] = intval($sPost['value']);
        $sRows['master'] = filterStr($sPost['master']);
        $sRows['remark'] = filterStr($sPost['remark']);
        $sRows['scan_time'] = intval($sPost['scan_time']);
        $sRows['ipv4'] = filterStr($sPost['ipv4']);
        //$sRows['TAR_M_IPV6'] = filterStr($sPost['TAR_M_IPV6']);
        $sRows['mac'] = filterStr($sPost['mac']);
        $sRows['device_name'] = filterStr($sPost['device_name']);
        $sRows['os'] = filterStr($sPost['os']);
        $sRows['device_type'] = filterStr($sPost['device_type']);
        $sRows['domain'] = filterStr($sPost['domain']);
        //$sRows['TAR_M_OPEN_PORT'] = filterStr($sPost['TAR_M_OPEN_PORT']);
        $sRows['hop'] = intval($sPost['hop']);
        $sRows['device_remark'] = filterStr($sPost['device_remark']);
        //$sRows['status'] = intval($sPost['status']);
        $sRows['auto_risk'] = intval($sPost['auto_risk']);
        $sRows['ipv4_segment'] = filterStr($sPost['ipv4_segment']);
        
        //以下字段来自原1.222机器t_dev表
//    $sRows['port'] = intval($sPost['port2']);
//    $sRows['login_protocol'] = filterStr($sPost['login_protocol']);
//    $sRows['login_account'] = filterStr($sPost['login_account']);
//    $sRows['login_password'] = filterStr($sPost['login_password']);
//    $sRows['privileged_password'] = filterStr($sPost['privileged_password']);
        //计算资产类型值
        /*if(isset($sPost['install_server'])){
        foreach($sPost['install_server'] as $v){
            $sPost['dev_type'] += $v;
        }

        //拼凑software_info的值
        $sRows['software_info'] = array();
        if(in_array(32,$sPost['install_server'])){
            $aTmp = array(
                'install_server'=>32,
                'ora_port'=>$sPost['ora_port'],
                'ora_username'=>$sPost['ora_username'],
                'ora_pwd'=>$sPost['ora_pwd'],
                'ora_instance_name'=>$sPost['ora_instance_name']
            );
            array_push($sRows['software_info'],$aTmp);
        }
        if(in_array(64,$sPost['install_server'])){
            $aTmp = array(
                'install_server'=>64,
                'my_port'=>$sPost['my_port'],
                'my_username'=>$sPost['my_username'],
                'my_pwd'=>$sPost['my_pwd']
            );
            array_push($sRows['software_info'],$aTmp);
        }
        if(in_array(128,$sPost['install_server'])){
            $aTmp = array(
                'install_server'=>128,
                'webl_port'=>$sPost['webl_port'],
                'webl_username'=>$sPost['webl_username'],
                'webl_pwd'=>$sPost['webl_pwd']
            );
            array_push($sRows['software_info'],$aTmp);
        }
        if(in_array(256,$sPost['install_server'])){
            $aTmp = array(
                'install_server'=>256
            );
            array_push($sRows['software_info'],$aTmp);
        }
        if(in_array(512,$sPost['install_server'])){
            $aTmp = array(
                'install_server'=>512
            );
            array_push($sRows['software_info'],$aTmp);
        }
    }
    $sRows['software_info'] = json_encode($sRows['software_info']);*/
        //var_dump($sRows);exit;
        //$sRows['TAR_M_OPEN_PORT'] = str_replace('\r\n',"|",$sRows['TAR_M_OPEN_PORT']);//从字符串中提取时，\r\n必须用单引号包括。。解析给HTML时，必须使用双引号
        //TAR_M_OPEN_PORT 端口相关input拼成string后再存储起来
        $portLength = count($sPost['port']);
        $sRows['open_port'] = '';
        for ($i = 0; $i < $portLength; $i++) {
            $sRows['open_port'] .= $sPost['port'][$i] . ',' . $sPost['status'][$i] . ',' . $sPost['serverType'][$i] . ',' . $sPost['version'][$i] . '<br />';
        }
        $sRows['open_port'] = rtrim($sRows['open_port'], '<br />');
        if ($id) {//编辑
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_asset_device_info where name='" . $sRows['name'] . "' And id !=" . $id);
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $sRows['name'] . Yii::t('app', '已存在，请更换');
                echo json_encode($data);
                exit;
            }

            $sFieldValue = "";
            foreach ($sRows as $k => $v) {
                $sFieldValue .= $k . "= '" . $v . "',";
            }
            $sFieldValue = rtrim($sFieldValue, ",");


            $sql = "UPDATE bd_asset_device_info SET " . $sFieldValue . " WHERE id=" . $id;

            $res = $db->query($sql);

            if ($res>=0) {
                $success = true;
                $msg = Yii::t('app', "修改成功");
                $hdata['sDes'] = Yii::t('app', '修改资产') . '(' . $sRows['name'] . ')';
                $hdata['sRs'] = Yii::t('app', "成功");
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                file_put_contents('/home/bluedon/bdscan/bdasset/discovery/AddNewDev.ini', 'TRUE');
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            } else {
                $success = false;
                $msg = Yii::t('app', "编辑失败");
                $hdata['sDes'] = Yii::t('app', '编辑资产') . '(' . $sRows['name'] . ')';
                $hdata['sRs'] = Yii::t('app', '失败');
                $hdata['sAct'] = $act . '/' . $show;
                $data['success'] = $success;
                $data['msg'] = $msg;
                saveOperationLog($hdata);
                echo json_encode($data);
                exit;
            }
        } else {//新增
            $iTotal = $db->result_first("SELECT COUNT(`name`) FROM bd_asset_device_info where name='" . $sRows['name'] . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $sRows['name'] . Yii::t('app', '已存在，请更换');
                echo json_encode($data);
                exit;
            }
            $stField = "";
            $stValue = "";
            foreach ($sRows as $k => $v) {
                $stField .= $k . ",";
                $stValue .= "'" . $v . "',";
            }
            $stField = rtrim($stField, ",");
            $stValue = rtrim($stValue, ",");

            $sql = "INSERT INTO bd_asset_device_info (" . $stField . ") VALUES (" . $stValue . ")";

            if ($db->query($sql)) {
                $insert_id = $db->insert_id();

                if ($insert_id) {
                    $success = true;
                    $msg = Yii::t('app', "新增成功");
                    $hdata['sDes'] = Yii::t('app', '新增资产') . '(' . $sRows['name'] . ')';
                    $hdata['sRs'] = Yii::t('app', "成功");
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                    file_put_contents('/home/bluedon/bdscan/bdasset/discovery/AddNewDev.ini', 'TRUE');
                    $data['success'] = $success;
                    $data['msg'] = $msg;
                    echo json_encode($data);
                    exit;
                } else {
                    $success = false;
                    $msg = Yii::t('app', "新增失败");
                    $hdata['sDes'] = Yii::t('app', '新增资产') . '(' . $sRows['name'] . ')';
                    $hdata['sRs'] = Yii::t('app', "失败");
                    $hdata['sAct'] = $act . '/' . $show;
                    saveOperationLog($hdata);
                    $data['success'] = $success;
                    $data['msg'] = $msg;
                    echo json_encode($data);
                    exit;
                }

            } else {
                $success = false;
                $msg = Yii::t('app', "新增失败");
                $hdata['sDes'] = Yii::t('app', '新增资产') . '(' . $sRows['name'] . ')';
                $hdata['sRs'] = Yii::t('app', "成功");
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
    }

//登录测试
    function Logintest()
    {
        require_once DIR_ROOT . "../include/xmlrpc/BDRpc.php";
        $hPost = $_REQUEST;
        $aJson = array();
        $aJson ['success'] = false;
        $aJson ['msg'] = Yii::t('app', '连接失败');
        if (!empty($hPost)) {
            $ret = BDRpc::call("pool_device_test", array(
                "device_type" => $hPost['device_type'],
                "login_proto" => $hPost['login_proto'],
                "ipaddr" => $hPost['ipaddr'],
                "port" => $hPost['port'],
                "username" => $hPost['username'],
                "password" => $hPost['password'],
                "enable_pwd" => $hPost['enable_pwd'],
                "ora_port" => isset($hPost['ora_port']) ? $hPost['ora_port'] : '',
                "ora_username" => isset($hPost['ora_username']) ? $hPost['ora_username'] : '',
                "ora_pwd" => isset($hPost['ora_pwd']) ? $hPost['ora_pwd'] : '',
                "ora_instance_name" => isset($hPost['ora_instance_name']) ? $hPost['ora_instance_name'] : '',
                "my_port" => isset($hPost['my_port']) ? $hPost['my_port'] : '',
                "my_username" => isset($hPost['my_username']) ? $hPost['my_username'] : '',
                "my_pwd" => isset($hPost['my_pwd']) ? $hPost['my_pwd'] : '',
                "webl_port" => isset($hPost['webl_port']) ? $hPost['webl_port'] : '',
                "webl_username" => isset($hPost['webl_username']) ? $hPost['webl_username'] : '',
                "webl_pwd" => isset($hPost['webl_pwd']) ? $hPost['webl_pwd'] : '',
                "software_type" => isset($hPost['software_type']) ? $hPost['software_type'] : '',
            ));
            $ret = json_decode($ret);

            if (isset($ret['result']) && ($ret['result'] == "0")) {
                $aJson ['success'] = false;
                $aJson ['msg'] = Yii::t('app', '连接失败');
            } elseif (isset($ret['result']) && ($ret['result'] == "1")) {
                $aJson ['success'] = true;
                $aJson ['msg'] = Yii::t('app', '连接成功');
            } else {
                $aJson ['success'] = false;
                $aJson ['msg'] = Yii::t('app', '连接出错');
            }
        }
        echo json_encode($aJson);
        exit;

    }

//自发现配置的选择框改变
    function bmChange()
    {
        global $db;
        if (!empty($_POST['bm'])) {
            $bm = intval($_POST['bm']);
            $res = $db->fetch_first("select * from bd_asset_autofind_config where depart_id = $bm");
            //var_dump($res);
            echo json_encode($res);
            exit;
        }

    }

//判断同时扫描任务数
    function onTaskSum()
    {
        global $db;
        $loginuser = $db->fetch_first("SELECT * FROM " . getTable('scanset'));
        $allowtotal = 0;
        if (!empty($loginuser['smrws'])) {
            $allowtotal = $loginuser['smrws'];
        }
        if ($allowtotal > 0) {
            $hadsms = GetHadsms();//返回task_manage表中正在执行的任务个数
            if ($allowtotal - $hadsms - 1 < 0) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '同时扫描任务数不能超过') . $allowtotal . Yii::t('app', '个 ');
                return json_encode($data);
                exit;
            }
        }
        $data['success'] = true;
        $data['msg'] = Yii::t('app', '可以扫描新任务');
        return json_encode($data);
        exit;
    }

//添加资产到扫描任务
    function actionAddtask()
    {
        global $db, $act, $show;

        if (!empty($_POST['add'])) {
            //var_dump($_POST['add']);
            //var_dump(json_decode($_POST['add']));
            $ttt = explode(';', $_POST['add']);
            //var_dump($ttt);exit;
            $k_bumen = filterStr($_POST['k_bumen']);
            $ipv4 = filterStr($_POST['ipv4']);
            $ip = "'" . Yii::t('app', '资产管理') . "-" . $k_bumen . "' " . $ipv4;
        } else {
            $rows = array();
            $where = 'WHERE 1=1';
            $ids = filterStr($_POST['ids']);
            $allcheck = intval($_POST['allcheck']);
            $memory = intval($_POST['memory']);
            $tjsm = filterStr($_POST['tjsm']);
            $flag = filterStr($_POST['flag']);
            $part_name = filterStr($_POST['rwname']);
            $is_tp = intval($_POST['is_tp']);

            $isExist = $db->fetch_all("select id from bd_host_task_manage where name=" . "'" . $part_name . "'");

            if (!empty($isExist)) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '添加失败,该任务已经存在！');
                echo json_encode($data);
                exit;
            }
            if ($allcheck == 1) {
                //全选要区分是'资产管理'的还是部门的id
                if ($tjsm == Yii::t('app', '资产管理')) {
                    $res_id = $db->fetch_all("select id from bd_asset_device_info where 1=1");
                    $f_id = array_column($res_id, 'id');
                } else {
                    $res_id = $db->fetch_all("select id from bd_asset_device_info where depart_id = $tjsm");
                    $f_id = array_column($res_id, 'id');
                }
                //var_dump($ids);
                if ($memory == 1) {
                    //取除开ids里面的所有id
                    $ids = explode(',', $ids);
                    foreach ($ids as $k => $v) {
                        if ($key = array_search($v, $f_id)) {
                            unset($f_id[$key]);
                        }
                    }
                }
                //默认取全部的资产id
                $f_ids = implode(',', $f_id);

            } else {
                //取ids里面的id
                $f_ids = $ids;
            }
            if (empty($f_ids)) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '添加失败,该部门没资产！');
                echo json_encode($data);
                exit;
            }
            $where .= ' AND id IN(' . $f_ids . ')';
            //获取部门id
            //$idsArr = explode(',',$f_ids);
            //$res = $db->fetch_first("SELECT TAR_FROM_DEPART FROM asset_target_info WHERE ID = $idsArr[0]");
            //$bumenid = intval($res['TAR_FROM_DEPART']);
            //获取部门名称
            //$part_name = $db->fetch_first("SELECT DEPART_NAME FROM asset_part_info WHERE ID = $bumenid");
            if ($is_tp == 1) {
                $rows = $db->fetch_all("SELECT ipv4 FROM bd_asset_gplot_info {$where}");
            } else {
                $rows = $db->fetch_all("SELECT ipv4 FROM bd_asset_device_info {$where}");
            }

            $ip = $part_name;//有括号()的命令，在linux里执行时，需要使用引号包起来
            $zcStr = '';

            foreach ($rows as $v) {
                $zcStr .= $v['ipv4'] . ",";
            }
            $zcStr = rtrim($zcStr,',');
            $ip = $ip . " ".$flag;
            $ip = $ip . " " . filterStr($_SESSION['username']);
            $ip = $ip . " " . $act;
            $ip = $ip . " " . $show;
            $ip = $ip . " " . onlineIp();
            $ip = $ip . " " . $zcStr;
        }

        $ipStr = explode(' ',$ip);
        ///usr/local/php/bin/php -f /usr/local/nginx/html/controllers/taskinterfaceForZcgl.php '资产管理-网段10-12(测试部门)' 1 172.16.10.10 172.16.10.22 172.16.10.37 172.16.10.38 172.16.10.40 172.16.10.43 172.16.10.44 172.16.10.46 172.16.10.49 172.16.10.50 172.16.10.51 172.16.10.52 172.16.10.53 172.16.10.60 172.16.10.75 172.16.10.79 172.16.10.99 172.16.10.102 172.16.10.180 172.16.10.181 172.16.10.182 172.16.10.183 172.16.10.186 172.16.10.187 172.16.10.188 172.16.10.189 172.16.10.190 172.16.10.191 172.16.10.193 172.16.10.200 172.16.10.213 172.16.10.254 172.16.12.1 172.16.12.3 172.16.12.5 172.16.12.8 172.16.12.9 172.16.12.13 172.16.12.17 172.16.12.36 172.16.12.37 172.16.12.38 172.16.12.41 172.16.12.42 172.16.12.43 172.16.12.44 172.16.12.45 172.16.12.48 172.16.12.50 172.16.12.53 172.16.12.61 172.16.12.63 172.16.12.64 172.16.12.65 172.16.12.66 172.16.12.72 172.16.12.73 172.16.12.78 172.16.12.80 172.16.12.91 172.16.12.95 172.16.12.101 172.16.12.103 172.16.12.105 172.16.12.108 172.16.12.109 172.16.12.118 172.16.12.136 172.16.12.140 172.16.12.141 172.16.12.142 172.16.12.143 172.16.12.144 172.16.12.148 172.16.12.149 172.16.12.150 172.16.12.151 172.16.12.152 172.16.12.153 172.16.12.166 172.16.12.171 172.16.12.172 172.16.12.173 172.16.12.179 172.16.12.180 172.16.12.188 172.16.12.201 172.16.12.203 172.16.12.205 172.16.12.208 172.16.12.210 172.16.12.211 172.16.12.212 172.16.12.215 172.16.12.217 172.16.12.220 172.16.12.222 172.16.12.225 172.16.12.226 172.16.12.227 172.16.12.228 172.16.12.229 172.16.12.230 172.16.12.231 172.16.12.232 172.16.12.233 172.16.12.234 172.16.12.235 172.16.12.236 172.16.12.241 172.16.12.249 172.16.12.250 172.16.12.253 172.16.12.254 172.16.10.206 172.16.10.207 172.16.10.201 172.16.10.202 172.16.10.203 172.16.10.204 172.16.10.77 172.16.12.97 172.16.12.213 172.16.10.205 172.16.10.103 172.16.10.101
        // /usr/local/php/bin/php -f /usr/local/nginx/html/controllers/taskinterface.php 部门名称 flag 172.16.7.33-172.16.7.36
        // /usr/local/php/bin/php -f /usr/local/nginx/html/controllers/taskinterface.php 部门名称 flag username $act $show onlineIP() 172.16.7.33
        //$shell = "/home/bluedon/bdscan/bdwebserver/php/bin/php -f /home/bluedon/bdscan/bdwebserver/nginx/html/components/taskinterfaceForZcgl.php $ip";
        //shellResult($shell);
        $add_Rows['name'] = $ipStr[0];
        $add_Rows['uuid'] = uuid();
        $add_Rows['target'] = $ipStr[6];
        $add_Rows['host_policy'] = 1;
        $add_Rows['port_policy'] = 81;
        $add_Rows['max_hosts'] = 15;
        $add_Rows['max_checks'] = 5;
        $add_Rows['status'] = 2;
        $add_Rows['timeout'] = 30;
        $add_Rows['progress'] = 100;
        $add_Rows['sort'] = time();

        if(!empty($_POST['part_id']) && $_POST['part_id']!=0 ){
            $add_Rows['part_id'] = intval($_POST['part_id']);
        }

        $sField = "";
        $sValue = "";

        foreach ($add_Rows as $k => $v) {
            $sField .= $k . ",";
            $sValue .= "'" . $v . "',";

        }

        $sField = rtrim($sField, ",");
        $sValue = rtrim($sValue, ",");

        $sql = "INSERT INTO bd_host_task_manage (" . $sField . ") VALUES (" . $sValue . ")";
        //var_dump($ipStr);
        //var_dump($sql);exit;

        if ($db->query($sql)) {

            $success = true;
            $msg = Yii::t('app', "添加成功");
            $hdata['sDes'] = Yii::t('app', '新增主机扫描任务') . '(' . $ipStr[0] . ')';
            $hdata['sRs'] = Yii::t('app', "成功");
            $hdata['sAct'] = $ipStr[3] . '/' . $ipStr[4];
            $hdata['username'] = $ipStr[2];
            $hdata['onlineIP'] = $ipStr[5];
            saveOperationLog($hdata);
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;

        } else {
            $success = false;
            $msg = Yii::t('app', "添加失败");
            $hdata['sDes'] = Yii::t('app', '新增主机扫描任务') . '(' . $ipStr[0] . ')';
            $hdata['sRs'] = Yii::t('app', "失败");
            $hdata['sAct'] = $ipStr[3] . '/' . $ipStr[4];
            $hdata['username'] = $ipStr[2];
            $hdata['onlineIP'] = $ipStr[5];
            saveOperationLog($hdata);
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;

        }
        //var_dump($shell);exit;
        /*$file = popen($shell,'r');
    while(! feof($file))
    {
        $mystring .= fgets($file);
    }
    pclose($file);*/
        /*$mystring = shellResult($shell);
    $findme   = '"success":true,"msg"';//var_dump($mystring);
    $pos = strpos($mystring, $findme);
    if ($pos === false) {
        $data['success'] = false;
        $data['msg'] = '添加失败';
        echo json_encode($data);
        exit;
    } else {
        $data['success'] = true;
        $data['msg'] = '添加成功';
        echo json_encode($data);
        exit;
    }*/
        /*$data['success'] = true;
        $data['msg'] = '添加成功';

        echo json_encode($data);
        exit;*/
    }

    /**
     * @ 从数据库中删除数据
     * @ params $id
     */
    function actionDelasset()
    {
        global $db, $act, $show;
        $sPost = $_POST;

        $ids = filterStr($sPost['ids']);

        $query = "DELETE FROM bd_asset_device_info where id in (" . $ids . ") ";

        $sql = "select name from bd_asset_device_info WHERE id in(" . $ids . ")";

        $TAR_NAME = $db->fetch_all($sql);

        if ($db->query($query)) {     //返回1则删除成功
            $success = true;
            $msg = Yii::t('app', "操作成功");
            foreach ($TAR_NAME as $k => $v) {
                $hdata['sDes'] = Yii::t('app', '删除资产') . '(' . $v['name'] . ')';
                $hdata['sRs'] = Yii::t('app', '成功');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            }
        } else {
            $success = false;
            $msg = Yii::t('app', "操作失败");
            foreach ($TAR_NAME as $k => $v) {
                $hdata['sDes'] = Yii::t('app', '删除资产') . '(' . $v['name'] . ')';
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
    function actionDelbumen()
    {
        global $db, $act, $show;
        $sPost = $_POST;
        $rows = $parmrows = array();
        $curbumen = array($sPost['curbumen']);

        foreach ($curbumen[0] as $k => $v) {
            $parmrows[$v['name']] = $v['value'];
        }
        $family = $parmrows['family'];
        //$ilevel =   $parmrows['level'];
        /*if($ilevel != 1){
        $data['success'] = false;
        $data['msg'] = '请选择部门！';
        echo json_encode($data);
        exit;
    }*/
        //var_dump($sPost['flag']);
        $query = "DELETE FROM bd_asset_depart_info where id =" . $family;

        $sql = "select name from bd_asset_depart_info WHERE id = " . $family;

        $DEPART_NAME = $db->fetch_first($sql);

        if ($db->query($query)) {//返回1则删除成功
            $success = true;
            $bid = intval($sPost['bid']);
            $query_d = "DELETE FROM history_department WHERE part_id =" . $family;
            $query_a = "DELETE FROM history_all_assets WHERE part_id =" . $family;
            $db->query($query_d);
            $db->query($query_a);
            //删除部门的自发现配置
            $sql = "DELETE FROM `bd_asset_autofind_config` WHERE depart_id=" . $bid;
            $db->query($sql);
            if ($sPost['flag'] == 'true') {//js传过来的布尔值实际上是包含在引号里的
                //$bmid = $sPost['curbumen'][0]['value'];
                $query_del_zc = "delete from bd_asset_device_info where depart_id =" . $bid;
                if ($db->query($query_del_zc)) {
                    $msg = Yii::t('app', "操作成功，已删除部门，同时删除了相应资产");
                    $hdata['sDes'] = Yii::t('app', '删除部门') . '(' . $DEPART_NAME . ')' . Yii::t('app', '同时删除了相应资产');
                } else {
                    $msg = Yii::t('app', "已删除部门，但是相应资产没能成功删除");
                    $hdata['sDes'] = Yii::t('app', '删除部门') . '(' . $DEPART_NAME . ')' . Yii::t('app', '但是保留了相应资产');
                }
            } else {
                $msg = Yii::t('app', "操作成功，已删除部门，该部门资产转入未登记资产");
                $udateSql = "UPDATE bd_asset_device_info SET depart_id=1 WHERE depart_id=$bid";
                $db->query($udateSql);
                $hdata['sDes'] = Yii::t('app', '删除部门') . '(' . $DEPART_NAME . ')' . ',' . Yii::t('app', '该部门资产转入未登记资产');
            }
            $hdata['sRs'] = Yii::t('app', '成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = Yii::t('app', "操作失败");
            $hdata['sDes'] = Yii::t('app', '删除部门') . '(' . $DEPART_NAME . ')';
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }

        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }

//删除部门
    function actionBm_del()
    {
        global $act;
        template2($act . '/bm_del', array());
    }

    //添加部门扫描任务
    function actionBm_addrenwu()
    {
        global $act;
        template2($act . '/bm_addrenwu', array());
    }

    //从网络拓扑搜索添加任务
    function actionTp_addrenwu()
    {
        global $act;
        template2($act . '/tp_addrenwu', array());
    }

//自发现配置
    function actionAutosearchconf()
    {
        global $db, $act;
        $aData = array();
        if ($_POST['insertid'] == 1) {//提交配置
            $aData = $_POST;
            $target = filterStr($aData['target']);
            $target = str_replace('\r\n', ',', $target);
            $updateid = intval($aData['updateid']);//为空时表示新增，有值时表示更新
            $target_from_depart = intval($aData['depart_id']);
            $target_auto_risk = intval($aData['auto_risk']);
            $target_auto_scan = intval($aData['auto_scan']);
            $target_scan_time = filterStr($aData['scan_time']);
            //$iTotal = $db->result_first("SELECT COUNT(`target`) FROM bd_asset_autofind_config where target='".$target."' AND target_from_depart='".$target."' AND target_auto_risk=".$target_auto_risk);
            /*$iTotal = $db->result_first("SELECT COUNT(`target`) FROM bd_asset_autofind_config where target='".$target."' AND target_from_depart= "."'".$target_from_depart."'");

        if(!empty($iTotal)){
            $data['success'] = false;
            $data['msg'] = '已存在相同配置';
            echo json_encode($data);
            exit;
        }*/
            if (!empty($updateid)) {
                $m = "target='" . $target . "',auto_risk=" . $target_auto_risk . ",auto_scan=" . $target_auto_scan . ",scan_time='" . $target_scan_time . "'";
                $sql = "UPDATE bd_asset_autofind_config SET " . $m . " WHERE id = $updateid";
                //$sql = "UPDATE bd_asset_autofind_config (target,target_from_depart,target_auto_risk,target_auto_scan,target_scan_time) VALUES ('".$target."',".$target_from_depart.",".$target_auto_risk.",".$target_auto_scan.",'".$target_scan_time."')";
            } else {
                //$sql = "INSERT INTO bd_asset_autofind_config (target,target_from_depart,target_auto_risk) VALUES ('".$target."',".$target_from_depart.",".$target_auto_risk.")";
                $sql = "INSERT INTO bd_asset_autofind_config (target,depart_id,auto_risk,auto_scan,scan_time) VALUES ('" . $target . "'," . $target_from_depart . "," . $target_auto_risk . "," . $target_auto_scan . ",'" . $target_scan_time . "')";
            }

            $db->query($sql);
            $success = true;
            $msg = Yii::t('app', "操作成功");
            //$hdata['sDes'] = '新增自发现配置';
            //$hdata['sRs'] ='成功';
            //$hdata['sAct'] = $act.'/'.$show;
            //saveOperationLog($hdata);
            $data['msg'] = $msg;
            file_put_contents('/home/bluedon/bdscan/bdasset/discovery/AddNewDev.ini', 'TRUE');
            echo json_encode($data);
            exit;

        }
        $where = "WHERE 1=1";
        $bumenid = intval($_GET['bumenid']);
        if (!empty($bumenid)) {
            $where .= " AND depart_id = " . $bumenid;
        }
        $departs = $db->fetch_all("select * from bd_asset_depart_info");
        $conf = $db->fetch_first("select * from bd_asset_autofind_config $where");
        $conf['target'] = str_replace(',', "\r\n", $conf['target']);
        $aData['depart'] = $departs;
        $aData['conf'] = $conf;
        $aData['bumenid'] = $bumenid;
        template2($act . '/autoSearchConf_edit', $aData);
    }

//风险走势图
    function actionRisk_view2()
    {
        global $act;
        $lv = intval($_GET['lv']);//lv用于判断画哪种图或表
        $zct = filterStr($_GET['zct']);//zct用于作图表的标题和作为查看时的传参
        template2($act . '/risk_view2', array('lv' => $lv, 'zct' => $zct));
    }

//风险走势图
    function actionRisk_view()
    {
        global $act;
        $lv = intval($_GET['lv']);//lv用于判断画哪种图或表
        $zct = filterStr($_GET['zct']);//zct用于作图表的标题和作为查看时的传参
        template2($act . '/risk_view', array('lv' => $lv, 'zct' => $zct));
    }

//导出资产
    function actionExportzc()
    {
        global $db, $act, $show;
        require_once '../web/resource/js/PHPExcel/PHPExcel.php';
        $sPost = $_POST;
        $ids = filterStr($sPost['ids']);
        $tmp_id = explode(',', $ids);
        $f_ids = '';
        foreach ($tmp_id as $k => $v) {
            $f_ids .= intval($v) . ',';
        }
        $f_ids = trim($f_ids, ',');
        $where = 'where a.id IN(:id)';

        if ($sPost) {

            $zcname = "zcgl_" . date('Y-m-d_H-i-s', time());
            $zcxlsname = $zcname . ".xls";//文件名
            $path = DIR_ROOT . "../data/zcgl/" . $zcxlsname;
            //'资产名称','所属部门','资产类型','资产价值','负责人','备注','ipv4','ipv6','mac','上一次扫描时间','资产状态'
            //$sql = "select a.TAR_NAME,a.TAR_FROM_DEPART,a.TAR_TYPE,a.TAR_VALUE,a.TAR_CMD,a.TAR_REMARK,a.TAR_M_IPV4,a.TAR_M_IPV6,a.TAR_M_MAC,FROM_UNIXTIME(a.TAR_M_SCAN_TIME),a.TAR_M_STATE,b.DEPART_NAME from asset_target_info as a inner join asset_part_info as b ".$where." and a.TAR_FROM_DEPART = b.ID order by a.ID asc";
            $sql = "select a.device_name,a.os,a.hop,a.open_port,a.name,a.depart_id,a.device_type,a.value,a.master,a.remark,a.ipv4,a.ipv6,a.mac,FROM_UNIXTIME(a.scan_time),a.status,b.name from bd_asset_device_info as a inner join bd_asset_depart_info as b " . $where . " and a.depart_id = b.id order by a.id asc";
            //$sql = sprintf($sql, mysql_real_escape_string($f_ids));
            $list = $db->fetch_all($sql,array(':id'=>$f_ids));

            if ($list) {
                $phpexcel = new \PHPExcel();

                //设置表头
                $phpexcel->getActiveSheet()->setCellValue('A1', Yii::t('app', '资产标识'))
                    ->setCellValue('B1', Yii::t('app', '所属部门'))
                    ->setCellValue('C1', Yii::t('app', '资产类型'))
                    ->setCellValue('D1', Yii::t('app', '负责人'))
                    ->setCellValue('E1', Yii::t('app', '资产价值'))
                    ->setCellValue('F1', Yii::t('app', '备注'))
                    ->setCellValue('G1', 'ipv4')
                    ->setCellValue('H1', 'ipv6')
                    ->setCellValue('I1', Yii::t('app', 'MAC地址'))
                    ->setCellValue('J1', Yii::t('app', '设备名称'))
                    ->setCellValue('K1', Yii::t('app', '操作系统'))
                    ->setCellValue('L1', Yii::t('app', '跃点数'))
                    ->setCellValue('M1', Yii::t('app', '资产状态'))
                    ->setCellValue('N1', Yii::t('app', '开放端口'));

                $i = 2;
                foreach ($list as $v) {
                    if ($v['type'] == null || $v['type'] == 0) {
                        $zctype_tmp = Yii::t('app', '通用设备');
                    } else if ($v['type'] == 1) {
                        $zctype_tmp = Yii::t('app', '交换机');
                    } else if ($v['type'] == 2) {
                        $zctype_tmp = Yii::t('app', '路由器');
                    } else if ($v['type'] == 3) {
                        $zctype_tmp = Yii::t('app', '其它');
                    } else {
                        $zctype_tmp = Yii::t('app', '其它');
                    }
                    if ($v['value'] == null || $v['value'] == 3) {
                        $zcvalue_tmp = Yii::t('app', '高');
                    } else if ($v['value'] == 2) {
                        $zcvalue_tmp = Yii::t('app', '中');
                    } else if ($v['value'] == 1) {
                        $zcvalue_tmp = Yii::t('app', '低');
                    } else if ($v['value'] == 0) {
                        $zcvalue_tmp = Yii::t('app', '未知');
                    } else {
                        $zcvalue_tmp = Yii::t('app', '未知');
                    }
                    $phpexcel->getActiveSheet()->setCellValue('A' . $i, $v['name'])
                        ->setCellValue('B' . $i, $v['name'])
                        ->setCellValue('C' . $i, $zctype_tmp)
                        ->setCellValue('D' . $i, $v['master'])
                        ->setCellValue('E' . $i, $zcvalue_tmp)
                        ->setCellValue('F' . $i, $v['remark'])
                        ->setCellValue('G' . $i, $v['ipv4'])
                        ->setCellValue('H' . $i, $v['ipv6'])
                        ->setCellValue('I' . $i, $v['mac'])
                        ->setCellValue('J' . $i, $v['device_name'])
                        ->setCellValue('K' . $i, $v['os'])
                        ->setCellValue('L' . $i, $v['hop'])
                        ->setCellValue('M' . $i, $v['state'])
                        ->setCellValue('N' . $i, $v['open_port']);
                    //->setCellValue('N'.$i,$v['FROM_UNIXTIME(TAR_M_SCAN_TIME)']);
                    $i++;
                }

                $obj_Writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
                //设置header
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header('Content-Disposition:inline;filename="' . $zcxlsname . '"');
                header("Content-Transfer-Encoding: binary");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");

                $obj_Writer->save($path);

                $aJson['success'] = true;
                $aJson['msg'] = 'zcgl/downloadzc?izcname=' . $zcname;
                echo json_encode($aJson);
                exit;
            }
            $aJson['success'] = false;
            $aJson['msg'] = Yii::t('app', '导出失败');
            echo json_encode($aJson);
            exit;
        }
    }

    function actionDownloadzc()
    {
        $izcname = filterStr($_GET['izcname']);
        $sTitle = $izcname . ".xls";
        $sFilePath = DIR_ROOT . "../data/zcgl/" . $izcname . ".xls";
        downloadFile($sTitle, $sFilePath);
    }

    function actionUploadzc()
    {
        global $act;
        template2($act . '/uploadzc', array());
    }

    function actionUploadfilezc()
    {
        global $db;
        $aJson = array();
        $aData = array();
        $types = array('xlsx', 'xls');
        $sql = "insert into bd_asset_device_info (name,depart_id,type,master,value,remark,ipv4,ipv6,mac,device_type,os,hop,status,open_port,ipv4_segment) values ";
        if (is_uploaded_file($_FILES['filenamezc']['tmp_name'])) {
            $tmp_file = $_FILES['filenamezc']['tmp_name'];
            $file_types = explode('.', $_FILES['filenamezc']['name']);
            $file_type = $file_types[count($file_types) - 1];
            //var_dump($file_type);
            if (!in_array($file_type, $types)) {
                $aJson ['success'] = false;
                $aJson ['msg'] = Yii::t('app', '请上传Excel文件');
                echo json_encode($aJson);
                exit;
            } else {

                require_once '../web/resource/js/PHPExcel/PHPExcel.php';
                require_once '../web/resource/js/PHPExcel/PHPExcel/IOFactory.php';
                require_once '../web/resource/js/PHPExcel/PHPExcel/Reader/Excel5.php';
                require_once '../web/resource/js/PHPExcel/PHPExcel/Reader/Excel2007.php';
                $reader_type = $file_type == 'xls' ? 'Excel5' : 'Excel2007';//兼容excel版本
                //var_dump($reader_type);exit;
                $objReader = \PHPExcel_IOFactory::createReader($reader_type);//获取文件读取操作对象
                $up_time = date("y-m-d_H-i-s");//去当前上传的时间
                $file_name = $up_time . '.' . $file_type;//替换文件名
                $uploadfile = DIR_ROOT . '../cache/tmp/' . $file_name;//设置上传地址
                $res = move_uploaded_file($tmp_file, $uploadfile);
                //var_dump($res);
                if ($res) {

                    //$objPHPExcel = new PHPExcel();
                    $objPHPExcel = $objReader->load($uploadfile);//加载文件
                    //var_dump($objPHPExcel);exit;
                    foreach ($objPHPExcel->getWorksheetIterator() as $sheet) {//循环取sheet
                        $s_sum = $sheet->getHighestRow();
                        //var_dump($s_sum);
                        $s_flag = true;
                        $k_sum = $s_sum;
                        foreach ($sheet->getRowIterator() as $row) {//逐行处理
                            //var_dump($row->getRowIndex());
                            if ($row->getRowIndex() < 2) {
                                continue;
                            }
                            //$i = 0;
                            $item = array();
                            //查数据库是否有重复的，有重复的提示，并且不加入
                            //下面4个字段不能为空
                            $r_i = $row->getRowIndex();
                            $temp_a = $sheet->getCell('A' . $row->getRowIndex())->getValue();//TAR_NAME资产标识
                            $temp_b = $sheet->getCell('B' . $row->getRowIndex())->getValue();//TAR_FROM_DEPART所属部门
                            $temp_d = $sheet->getCell('D' . $row->getRowIndex())->getValue();//TAR_CMD负责人
                            $temp_g = $sheet->getCell('G' . $row->getRowIndex())->getValue();//TAR_M_IPV4
                            $temp_a = filterStr($temp_a);
                            $temp_b = filterStr($temp_b);
                            $temp_d = filterStr($temp_d);
                            $temp_g = filter_var($temp_g, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $temp_g : '';

                            if (empty($temp_b) || empty($temp_d)) {//$temp_b或$temp_d为空时
                                array_push($aData, Yii::t('app', '第 ') . $r_i . Yii::t('app', '行的所属部门、负责人和IPV4都不能为空！'));
                                $s_flag = false;
                                $k_sum--;
                                continue;
                            } else if (!empty($temp_a)) {//$temp_a不为空时
                                $temp_sql = "select name from bd_asset_device_info where name = " . "'" . $temp_a . "'";
                                if ($db->fetch_first($temp_sql)) {//已经存在时
                                    array_push($aData, Yii::t('app', '第 ') . $r_i . Yii::t('app', ' 行，资产标识 ') . $temp_a . Yii::t('app', ' 已经存在！'));
                                    $s_flag = false;
                                    $k_sum--;
                                    continue;
                                } else {//是新的
                                    if (empty($temp_g)) {//$temp_g为空时
                                        array_push($aData, Yii::t('app', '第 ') . $r_i . Yii::t('app', '行的所属部门、负责人和IPV4都不能为空！'));
                                        $s_flag = false;
                                        $k_sum--;
                                        continue;
                                    }
                                }
                            } else {//$temp_a为空时
                                if (!empty($temp_g)) {//如果资产标识为空，默认将ipv4作为资产标识
                                    $temp_sql = "select name from bd_asset_device_info where name = " . "'" . $temp_g . "'";
                                    if ($db->fetch_first($temp_sql)) {//已经存在时
                                        array_push($aData, Yii::t('app', '第 ') . $r_i . Yii::t('app', ' 行，资产标识 ') . $temp_a . Yii::t('app', ' 已经存在！'));
                                        $s_flag = false;
                                        $k_sum--;
                                        continue;
                                    } else {
                                        $temp_a = $temp_g;
                                    }
                                } else {//4个都为空时
                                    array_push($aData, Yii::t('app', '第 ') . $r_i . Yii::t('app', '行的部门、负责人和IPV4都不能为空！'));
                                    $s_flag = false;
                                    $k_sum--;
                                    continue;
                                }
                            }

                            for ($i = 0; $i < 14; $i++) {
                                switch ($i) {
                                    case 0://TAR_NAME资产标识
                                        $item[$i] = $temp_a;
                                        break;
                                    case 1://TAR_FROM_DEPART所属部门
                                        $bm_tmp = $db->fetch_first('select id from bd_asset_depart_info where name=' . "'" . $temp_b . "'");
                                        $item[$i] = $bm_tmp['id'];
                                        break;
                                    case 2://TAR_TYPE资产类型
                                        $temp = $sheet->getCell('C' . $row->getRowIndex())->getValue();
                                        $item[$i] = empty($temp) ? $temp : filterStr($temp);
                                        if ($item[$i] == Yii::t('app', '通用设备') || $item[$i] == null) {//默认
                                            $item[$i] = 0;
                                        } else if ($item[$i] == Yii::t('app', '交换机')) {
                                            $item[$i] = 1;
                                        } else if ($item[$i] == Yii::t('app', '路由器')) {
                                            $item[$i] = 2;
                                        } else {
                                            $item[$i] = 3;//其它
                                        }
                                        break;
                                    case 3://TAR_CMD负责人
                                        $item[$i] = $temp_d;
                                        break;
                                    case 4://TAR_VALUE资产价值
                                        $temp = $sheet->getCell('E' . $row->getRowIndex())->getValue();
                                        $item[$i] = empty($temp) ? $temp : filterStr($temp);
                                        if ($item[$i] == Yii::t('app', '高') || $item[$i] == null) {//默认
                                            $item[$i] = 3;
                                        } else if ($item[$i] == Yii::t('app', '中')) {
                                            $item[$i] = 2;
                                        } else if ($item[$i] == Yii::t('app', '低')) {
                                            $item[$i] = 1;
                                        } else {
                                            $item[$i] = 0;//未知
                                        }
                                        break;
                                    case 5://TAR_REMARK备注
                                        $temp = $sheet->getCell('F' . $row->getRowIndex())->getValue();
                                        $item[$i] = filterStr($temp);
                                        break;
                                    case 6://TAR_M_IPV4
                                        $item[$i] = $temp_g;
                                        //顺便算出IPV4_NET
                                        if (!empty($temp_g)) {
                                            $t_net = explode('.', $temp_g);
                                            $t_0 = dechex($t_net[0]);
                                            $t_1 = dechex($t_net[1]);
                                            $t_2 = dechex($t_net[2]);
                                            $item[14] = (strlen($t_0) < 2 ? '0' . $t_0 : $t_0) . (strlen($t_1) < 2 ? '0' . $t_1 : $t_1) . (strlen($t_2) < 2 ? '0' . $t_2 : $t_2);
                                        } else {
                                            $item[14] = null;
                                        }
                                        break;
                                    case 7://TAR_M_IPV6
                                        $temp = $sheet->getCell('H' . $row->getRowIndex())->getValue();
                                        $item[$i] = filter_var($temp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $temp : '';
                                        break;
                                    case 8://TAR_M_MAC
                                        $temp = $sheet->getCell('I' . $row->getRowIndex())->getValue();
                                        $item[$i] = filter_var($temp, FILTER_VALIDATE_MAC) ? $temp : '';
                                        break;
                                    case 9://TAR_M_TYPE设备名称
                                        $temp = $sheet->getCell('J' . $row->getRowIndex())->getValue();
                                        $item[$i] = filterStr($temp);
                                        break;
                                    case 10://TAR_M_OS操作系统
                                        $temp = $sheet->getCell('K' . $row->getRowIndex())->getValue();
                                        $item[$i] = filterStr($temp);
                                        break;
                                    case 11://TAR_M_HOP跃点数
                                        $temp = $sheet->getCell('L' . $row->getRowIndex())->getValue();
                                        $item[$i] = intval($temp);
                                        break;
                                    case 12://TAR_M_STATE资产状态
                                        $temp = $sheet->getCell('M' . $row->getRowIndex())->getValue();
                                        $item[$i] = intval($temp);
                                        break;
                                    case 13://TAR_M_OPEN_PORT开发端口
                                        $temp = $sheet->getCell('N' . $row->getRowIndex())->getValue();
                                        $item[$i] = filterStr($temp);
                                        break;
                                }
                            }
                            /* foreach($row->getCellIterator() as $cell){//逐列读取//空值已被过滤，不能读取，导致乱了

                             $temp=$cell->getValue();//获取单元格数据
                             //var_dump($i);
                             //var_dump($temp);
                             switch ($i){
                                 case 0://TAR_NAME资产标识
                                     $item[$i] = filterStr($temp);
                                     break;
                                 case 1://TAR_FROM_DEPART所属部门
                                     $bm_tmp= $db->fetch_first('select ID from asset_part_info where DEPART_NAME='."'".$temp."'");
                                     $item[$i] = $bm_tmp['ID'];
                                     break;
                                 case 2://TAR_TYPE资产类型
                                     $item[$i] = intval($temp);
                                     break;
                                 case 3://TAR_CMD负责人
                                     $item[$i] = filterStr($temp);
                                     break;
                                 case 4://TAR_VALUE资产价值
                                     $item[$i] = intval($temp);
                                     break;
                                 case 5://TAR_REMARK备注
                                     $item[$i] = filterStr($temp);
                                     break;
                                 case 6://TAR_M_IPV4
                                     $item[$i] = filter_var($temp,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)?$temp:'';
                                     break;
                                 case 7://TAR_M_IPV6
                                     $item[$i] = filter_var($temp,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)?$temp:'';
                                     break;
                                 case 8://TAR_M_MAC
                                     $item[$i] = filter_var($temp,FILTER_VALIDATE_MAC)?$temp:'';
                                     break;
                                 case 9://TAR_M_TYPE设备名称
                                     $item[$i] = filterStr($temp);
                                     break;
                                 case 10://TAR_M_OS操作系统
                                     $item[$i] = filterStr($temp);
                                     break;
                                 case 11://TAR_M_HOP跃点数
                                     $item[$i] = intval($temp);
                                     break;
                                 case 12://TAR_M_STATE资产状态
                                     $item[$i] = intval($temp);
                                     break;
                                 case 13://TAR_M_OPEN_PORT开发端口
                                     $item[$i] = filterStr($temp);
                                     break;
                                 default:
                                     echo '多余的数据列不读取';
                             }
                             $i++;
                         }*/
                            //这里拼接sql
                            $item[0] = empty($item[0]) ? 'null' : "'" . $item[0] . "'";
                            $item[1] = empty($item[1]) ? 'null' : "'" . $item[1] . "'";
                            $item[2] = empty($item[2]) ? 'null' : "'" . $item[2] . "'";
                            $item[3] = empty($item[3]) ? 'null' : "'" . $item[3] . "'";
                            $item[4] = empty($item[4]) ? 'null' : "'" . $item[4] . "'";
                            $item[5] = empty($item[5]) ? 'null' : "'" . $item[5] . "'";
                            $item[6] = empty($item[6]) ? 'null' : "'" . $item[6] . "'";
                            $item[7] = empty($item[7]) ? 'null' : "'" . $item[7] . "'";
                            $item[8] = empty($item[8]) ? 'null' : "'" . $item[8] . "'";
                            $item[9] = empty($item[9]) ? 'null' : "'" . $item[9] . "'";
                            $item[10] = empty($item[10]) ? 'null' : "'" . $item[10] . "'";
                            $item[11] = empty($item[11]) ? 'null' : "'" . $item[11] . "'";
                            $item[12] = empty($item[12]) ? 'null' : "'" . $item[12] . "'";
                            $item[13] = empty($item[13]) ? 'null' : "'" . $item[13] . "'";
                            $item[14] = empty($item[14]) ? 'null' : "'" . $item[14] . "'";
                            //var_dump($item);exit;
                            $sql .= "($item[0],$item[1],$item[2],$item[3],$item[4],$item[5],$item[6],$item[7],$item[8],$item[9],$item[10],$item[11],$item[12],$item[13],$item[14]),";

                        }

                        $sql = substr($sql, 0, strlen($sql) - 1);
                        //var_dump($sql);exit;
                        unlink($uploadfile);//入库完即将文件删了
                        if ($s_sum == 1) {
                            $aJson ['success'] = false;
                            $aJson ['msg'] = Yii::t('app', '上传失败！请严格按模板填写！');
                            echo json_encode($aJson);
                            exit;
                        } else if ($s_sum >= 2) {
                            $s_msg = '';
                            if (!$s_flag) {
                                foreach ($aData as $v) {
                                    $s_msg .= $v . '<br>';
                                }
                            }
                            if ($k_sum == 1) {
                                $aJson ['success'] = false;
                                $aJson ['msg'] = Yii::t('app', '上传失败！') . '<br>' . $s_msg;
                                $aJson ['data'] = $aData;
                                echo json_encode($aJson);
                                exit;
                            } else {
                                if ($db->query($sql)) {
                                    if ($s_msg != '') {
                                        $s_msg = Yii::t('app', '但是部分上传失败: ') . '<br>' . $s_msg;
                                    }
                                    $aJson ['success'] = true;
                                    $aJson ['msg'] = Yii::t('app', '上传成功！') . '<br>' . $s_msg;
                                    echo json_encode($aJson);
                                    exit;
                                } else {
                                    $aJson ['success'] = false;
                                    $aJson ['msg'] = Yii::t('app', '上传失败！') . '<br>' . $s_msg;
                                    $aJson ['data'] = $aData;
                                    echo json_encode($aJson);
                                    exit;
                                }
                            }
                        } else {
                            $aJson ['success'] = false;
                            $aJson ['msg'] = Yii::t('app', '上传失败！');
                            echo json_encode($aJson);
                            exit;
                        }

                    }

                }


            }

        }
    }

    /*
 * 二维数组按指定键值排须
 */
    function arr_sort($array, $key, $order = "asc")
    {//asc是升序 desc是降序

        $arr_nums = $arr = array();

        foreach ($array as $k => $v) {

            $arr_nums[$k] = $v[$key];

        }

        if ($order == 'asc') {

            asort($arr_nums);

        } else {

            arsort($arr_nums);

        }

        foreach ($arr_nums as $k => $v) {

            $arr[$k] = $array[$k];

        }

        return $arr;

    }


    function test()
    {
        global $db, $act, $show;
        $rows = array();


        echo json_encode($rows);
        exit;
    }
}
