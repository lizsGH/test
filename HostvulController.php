<?php
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class HostvulController extends BaseController
{
    /**
     * @ 获取主机策略所有的漏洞
     */
//vul_name=&family=64&ilevel=3&start=1&length=30&sortname=id&sortorder=asc
    function actionLists()
    {
        global $db;
        $sPost = $_POST;
        $existids = array();
        $aData = $aItem = $rows = array();
        $refids = array();
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $total = 0;
        $where = " WHERE 1=1 ";
        $page = $page > 1 ? $page : 1;

        $risk = filterStr($sPost['risk']);
        if (!empty($risk)) {
            $riskFactor = '';
            $hr = filterStr($sPost['hr']);
            $mr = filterStr($sPost['mr']);
            $lr = filterStr($sPost['lr']);
            $ir = filterStr($sPost['ir']);
            if (!empty($hr) && $hr == 'H') {
                $riskFactor = "'H'," . $riskFactor;
            }
            if (!empty($mr) && $mr == 'M') {
                $riskFactor = "'M'," . $riskFactor;
            }
            if (!empty($lr) && $lr == 'L') {
                $riskFactor = "'L'," . $riskFactor;
            }
            if (!empty($ir) && $ir == 'I') {
                $riskFactor = "'I'," . $riskFactor;
            }
            $riskFactor = trim($riskFactor, ',');
            $where .= " AND vi.vul_level in(" . $riskFactor . ")";
            //var_dump($where);
        }

        $treeType = intval($sPost['tree_type']);
        if (!empty($treeType) && $treeType == 3) {
            $CVEyear = filterStr($sPost['CVEyear']);
            if ($CVEyear != 'CVE年份') {
                $where .= " AND vi.CVEyear = '" . $CVEyear . "'";
            }
        } else if (!empty($treeType) && $treeType == 2) {
            $published_time = filterStr($sPost['published_time']);
            if ($published_time != '时间' && $published_time != 'OtherTime') {
                //$where .= " AND vi.published_time = '".$published_time."'";
                $where .= " AND vi.published_time LIKE '{$published_time}%'";
            } else if ($published_time == 'OtherTime') {
                $where .= " AND vi.published_time is null";
            }
        }

        $vul_name = filterStr($sPost['vul_name']);  //查询名称
        $family = intval($sPost['family']);     //点击左边的分类 id
        $ilevel = intval($sPost['ilevel']);     //点击左边的分类级别
        $refarr = $desc = '';
        $ismodule = false;
        if (!empty($family)) {
            if ($ilevel == 3) {     //三级分类
                $where1 = " WHERE family = $family";
                $refrows = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib  $where1 ");
                if (!empty($refrows)) {
                    foreach ($refrows as $k => $v) {
                        $refids[] = $v['vul_id'];
                    }
                    $refarr = implode(",", $refids);
                } else {
                    $data = array();
                    echo json_encode($data);
                    exit;
                }
            } else if ($ilevel == 2) {       //二级分类
                $where1 = " WHERE module_id = $family";
                $refrows = $db->fetch_all("SELECT vul_id FROM bd_host_vul_lib  $where1 ");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                $refarr = implode(",", $refids);
                $getname = $db->fetch_all("SELECT description as `desc` FROM bd_host_family_list WHERE id=$family");
                $desc = $getname[0]['desc'];
                $ismodule = true;
            } else {      //一级分类
                $refarr = '';
            }
        }

        if (!empty($refarr)) {
            $where .= " AND vi.vul_id in (" . $refarr . ")";
        }
        if (!empty($vul_name)) {        //查询名称
            $where .= " AND vi.vul_name LIKE '%{$vul_name}%'";
        }
//echo "SELECT COUNT(`id`) FROM bd_host_vul_lib AS vi $where";die;
        $total = $db->result_first("SELECT COUNT(`id`) FROM bd_host_vul_lib AS vi $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($sPost['sortname'] == 'vul_level') {
            $sortname = trim($sPost['sortname']);
            $sortorder = trim($sPost['sortorder']);
            $where .= " ORDER BY FIELD(vi." . $sortname . ",'H', 'M', 'L','I') " . $sortorder;
        } else if ($sPost['sortname'] == 'vul_name_sub') {
            $where .= " ORDER BY vi.vul_name " . $sPost['sortorder'];
            //$where .= " ORDER BY FIELD(vi.vul_level, 'H', 'M', 'L','I') ".$sPost['sortorder'];
        } else if ($sPost['sortname'] == 'family') {
            $where .= " ORDER BY vi.family " . $sPost['sortorder'];
        } else {
            $where .= " ORDER BY FIELD(vi.vul_level, 'H', 'M', 'L','I') " . $sPost['sortorder'];
        }
        if ($total) {
            $start = ($page - 1) * $perpage;
            //$querys = "SELECT * FROM bd_host_vul_lib AS vi  INNER JOIN bd_host_family_list AS fl ON fr.family=fl.id  $where   LIMIT $start,$perpage";
            $querys = "SELECT * FROM bd_host_vul_lib AS vi $where LIMIT $start,$perpage";
           // echo ($querys);die;
            $rows = $db->fetch_all($querys);
        }
        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "vul_id" => $v['vul_id'],
                "vul_name" => $v['vul_name'],
                "risk_factor" => $v['vul_level'],
                //"family" => $ismodule ? $desc : $v['desc'],
                //"family" => $ismodule ? $desc : $v['family'],
                "family" => $v['family'],
                "cve" => $v['cve'],
                "cnvd" => $v['cnvd'],
                "cnnvd" => $v['cnnvd'],
                "published_time" => $v['published_time'],
                "bid" => $v['bid'],
                "desc_cn" => $v['description'],
                "solu_cn" => $v['solution'],
                "ref_cn" => $v['ref_cn'],
                "vul_name_sub" => mb_substr($v['vul_name'], 0, 16, 'utf-8'),

            );
            array_push($aData, $aItem);
        }
        $data['Rows'] = $aData;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }

    /**
     * 获取已经选择的vul_id
     * @ params: policy_id
     * 用在策略的编辑页面
     */
    function actionGetexistvulid()
    {
        global $db;
        $sPost = $_POST;
        $existids = array();
        $rows = array();
        $policy_id = intval(($sPost['policy_id']));     //编辑
        if (!empty($policy_id)) {     //编辑,获取已选择的数据项
            $existrows = $db->fetch_all("SELECT * FROM bd_host_policy_selectors  WHERE policy_id=" . $policy_id);
            //默认使用‘服务检测’
            /*$vul_id_query_arr = array();
            $vul_id_query = "select `vul_id` FROM host_family_ref where family=47";
            $vul_id_query_arr = $db->fetch_all($vul_id_query);
            $vul_ids = '';
            foreach ($vul_id_query_arr as $v){
                $vul_ids = $v['vul_id'].','.$vul_ids;
            }
            foreach ($existrows as $k => $v){
                if(strpos($vul_ids,$v['vul_id']) !== false){
                    unset($existrows[$k]);
                }
            }*/
            $data['data'] = $existrows;
            $data['leng'] = count($existrows);
            $data['success'] = true;
            echo json_encode($data);
            exit;

        } else {
            $data['success'] = false;
            echo json_encode($data);
            exit;
        }
    }

    /**
     * 全选时候，获取当前相关的漏洞
     * @ params ：curparm
     */
    function actionGetcurrentall()
    {
        global $db;
        $sPost = $_POST;
        $rows = $parmrows = array();
        $curparm = array($sPost['curparm']);
        $curdata = array();
        if (!empty($sPost['curdata']))
            $curdata = explode(',', $sPost['curdata']);
        foreach ($curparm[0] as $k => $v) {
            $parmrows[$v['name']] = $v['value'];
        }
        $vul_name = $parmrows['vul_name'];
        $treeType = $parmrows['tree_type'];
        //按CVEyear的全选条件
        $CVEyear = $parmrows['CVEyear'];
        //按出现时间的全选条件
        $published_time = $parmrows['published_time'];

        $treeType = $parmrows['tree_type'];
        if (!empty($CVEyear)) {
            $refids = array();
            $leng = 0;
            if (!empty($treeType) && $treeType == 3) {
                if ($CVEyear != 'CVE年份') {
                    $where = "WHERE vi.oid!='' AND vi.CVEyear = '" . $CVEyear . "'";
                    if (!empty($vul_name)) {
                        $where .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                    }
                    if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                        if (empty($parmrows['hr'])) {
                            $where .= " AND vi.vul_level !='H' ";
                        }
                        if (empty($parmrows['mr'])) {
                            $where .= " AND vi.vul_level !='M' ";
                        }
                        if (empty($parmrows['lr'])) {
                            $where .= " AND vi.vul_level !='L' ";
                        }
                        if (empty($parmrows['ir'])) {
                            $where .= " AND vi.vul_level !='I' ";
                        }
                    }

                    $refrows = $db->fetch_all("SELECT id FROM bd_host_vul_lib AS vi $where ");
                    foreach ($refrows as $k => $v) {
                        $refids[] = $v['vul_id'];
                    }
                }
            }
            $aaa = array();
            if (!empty($curdata)) {
                $aaa = array_merge($curdata, $refids);
                $refids = array_unique($aaa, SORT_NUMERIC);
            }
            $leng = count($refids);
            $data['data'] = $refids;
            $data['leng'] = $leng;
            $data['success'] = true;
            echo json_encode($data);
            exit;
        }
        if (!empty($published_time)) {
            $refids = array();
            $leng = 0;
            if (!empty($treeType) && $treeType == 2) {
                if ($published_time != '时间' && $published_time != 'OtherTime') {
                    $where = "WHERE vi.oid!='' AND vi.published_time LIKE '{$published_time}%'";
                } else if ($published_time == 'OtherTime') {
                    $where = "WHERE vi.oid!='' AND vi.published_time is null";
                }
                if (!empty($vul_name)) {
                    $where .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi $where");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
            }
            $aaa = array();
            if (!empty($curdata)) {
                $aaa = array_merge($curdata, $refids);
                $refids = array_unique($aaa, SORT_NUMERIC);
            }
            $leng = count($refids);
            $data['data'] = $refids;
            $data['leng'] = $leng;
            $data['success'] = true;
            echo json_encode($data);
            exit;
        }

        //漏洞分类的全选条件
        $family = $parmrows['family'];
        $ilevel = $parmrows['ilevel'];
        if (is_null($ilevel)) {
            $ilevel = 1;
        }
        //漏洞分类
        if (!empty($ilevel)) {
            $refids = array();
            $leng = 0;
            if ($ilevel == 3) {     //三级分类
                $where1 = " WHERE vi.oid!='' AND fr.family = $family";
                if (!empty($vul_name)) {
                    $where1 .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where1 .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where1 .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where1 .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where1 .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi   $where1 ");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                //$leng = count($refids);
            } else if ($ilevel == 2) {       //二级分类
                $where1 = " WHERE  fr.module_id = $family";
                if (!empty($vul_name)) {
                    $where1 .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where1 .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where1 .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where1 .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where1 .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi  $where1 ");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                //$leng = count($refids);
            } else {      //一级分类
                $where1 = "WHERE 1=1 AND vi.oid!='' ";
                if (!empty($vul_name)) {
                    $where1 .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where1 .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where1 .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where1 .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where1 .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi   $where1");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                //$leng = count($refids);
            }
            $aaa = array();
            if (!empty($curdata)) {
                //var_dump($curdata);
                $aaa = array_merge($curdata, $refids);
                //var_dump($aaa);
                $refids = array_unique($aaa, SORT_NUMERIC);
                //var_dump($refids);
                //$leng = count($refids);
            }
            $leng = count($refids);
            $refids = array_merge($refids, array());//全选时，上一步操作可能会出现键名断裂的数组，致使json_encode($data)的时候，会把键值同时压入
            $data['data'] = $refids;
            $data['leng'] = $leng;
            $data['success'] = true;
            echo json_encode($data);
            exit;
        }
        $data['data'] = 'fail';
        $data['success'] = false;
        echo json_encode($data);
        exit;
    }
    /*
     *
     * 合并 漏洞
     */

    /**
     * 获取主机策略的漏洞分类
     * @ params table ：nvts_type
     * @ strategy : 1
     */
    function actionGetHostFamily()
    {
        global $db;
        $rows = array();
        $aData = $aItem = $rData = array();
        $where = " WHERE 1=1";
        $rows = $db->fetch_all("SELECT * FROM bd_host_family_list  $where ");

        foreach ($rows as $key => $value) {
            $id = $value['id'];
//            $sql = "select vul_id from host_family_ref where family= $id";
//            $r_arry = $db->fetch_all($sql);
//            if (empty($r_arry) && $value['parent_id'] != 0) {
            if ( $value['parent_id'] != 0) {
                unset($rows[$key]);
            }
        }

        foreach ($rows as $k => $v) {

            $aItem = array(
                "open" => false,
                "id" => $v['id'],
                "tree_type" => "1",
                "name" => $v['description'],
                "parent_id" => $v['parent_id'],
                "pId" => $v['parent_id']
            );
            array_push($aData, $aItem);

        }
        $rData = array(
            "open" => true,
            "id" => 0,
            "tree_type" => "1",
            "name" => '所有漏洞',
            "parent_id" => '',
            "pId" => ''
        );

        array_push($aData, $rData);
        echo json_encode($aData);
        exit;
    }

    /**
     * 获取主机策略的漏洞分类(按CVE年份获取)
     * @ params table ：nvts_type
     * @ strategy : 1
     */
    function actionGetHostFamilyByCve()
    {
        global $db;
        $rows = array();
        $aData = $aItem = $rData = array();
        $where = " WHERE 1=1";
        //$where .= "GROUP BY CVEyear"
        $rows = $db->fetch_all("SELECT DISTINCT CVEyear FROM bd_host_vul_lib  $where ");
        //对结果进行倒序，并且NOCVE放在最后
        sort($rows);
        $y = array_reverse($rows);
        if ($y[0]['CVEyear'] == 'NOCVE') {
            array_push($y, array_shift($y));
        }
        foreach ($y as $k => $v) {
            $aItem = array(
                "open" => false,
                "id" => 1,
                "tree_type" => "3",
                "name" => $v['CVEyear'],
                "parent_id" => 0,
                "pId" => 0
            );
            array_push($aData, $aItem);
        }
        $rData = array(
            "open" => true,
            "id" => 0,
            "tree_type" => "3",
            "name" => 'CVE年份',
            "parent_id" => '',
            "pId" => ''
        );

        array_push($aData, $rData);
        echo json_encode($aData);
        exit;
    }

    /**
     * 获取主机策略的漏洞分类(按发现时间获取)
     * @ params table ：nvts_type
     * @ strategy : 1
     */
    function actionGetHostFamilyByTime()
    {
        global $db;
        $rows = array();
        $aData = $aItem = $rData = array();
        $where = " WHERE 1=1";
        $rows = $db->fetch_all("SELECT DISTINCT published_time FROM bd_host_vul_lib  $where ");

        $y = array();
        foreach ($rows as $k => $v) {
            $year = substr($v['published_time'], 0, 4);
            if ($year === false) {
                continue;
            }
            $y[$year] = $year;
        }
        sort($y, SORT_NUMERIC);
        $y = array_reverse($y);
        foreach ($y as $v) {
            $aItem = array(
                "open" => false,
                "id" => 1,
                "tree_type" => "2",
                "name" => $v,
                "parent_id" => 0,
                "pId" => 0
            );
            array_push($aData, $aItem);
        }
        array_push($aData, array(
            "open" => false,
            "id" => 1,
            "tree_type" => "2",
            "name" => 'OtherTime',
            "parent_id" => 0,
            "pId" => 0
        ));
        $rData = array(
            "open" => true,
            "id" => 0,
            "tree_type" => "2",
            "name" => '时间',
            "parent_id" => '',
            "pId" => ''
        );

        array_push($aData, $rData);
        echo json_encode($aData);
        exit;
    }

    /**
     * 查看策略
     * 根据policy id 获取相关的漏洞
     * @ params ： policy_id
     */
    function actionGetvulbypolicyid()
    {
        global $db;
        $sPost = $_POST;
        $existids = array();
        $aData = $aItem = $rows = array();
        $page = intval($sPost['start']);
        //$page=1;
        $perpage = intval($sPost['length']);
        $total = 0;
        $where = " WHERE 1=1 and enable=1";
        $page = $page > 1 ? $page : 1;
        $policy_id = intval($sPost['policy_id']);      //查询名称
        $family = intval($sPost['family']);     //点击左边的分类 id
        $ilevel = intval($sPost['ilevel']);     //点击左边的分类级别
        $desc = '';
        $ismodule = false;
        if (!empty($family)) {
            if ($ilevel == 3) {     //三级分类
                $where = " AND fl.id = $family";
            } else if ($ilevel == 2) {       //二级分类
                $where = " AND fl.parent_id = $family";
                $getname = $db->fetch_all("SELECT `description` FROM bd_host_family_list WHERE id=$family");
                $desc = $getname[0]['desc'];
                $ismodule = true;
            }
        }
        $where .= " AND fr.policy_id=" . $policy_id;
        $where.= " and vi.category not in(0,1,2)";
        $total = $db->result_first("SELECT count(vi.vul_id) FROM bd_host_vul_lib AS vi INNER JOIN bd_host_policy_selectors as fr ON vi.vul_id= fr.vul_id INNER JOIN bd_host_family_list AS fl ON fr.family_id=fl.id  $where ");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $where.= " and vi.category not in(0,1,2)";
            $querys = "SELECT * FROM bd_host_vul_lib AS vi INNER JOIN bd_host_policy_selectors as fr ON vi.vul_id= fr.vul_id INNER JOIN bd_host_family_list AS fl ON fr.family_id=fl.id  $where  LIMIT $start,$perpage";
            //echo $querys;die;
            $rows = $db->fetch_all($querys);
        }
        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "vul_id" => $v['vul_id'],
                "vul_name" => $v['vul_name'],
                "risk_factor" => $v['vul_level'],
                "family" => $ismodule ? $desc : $v['description'],
                "desc" => $v['desc'],
                //"family" => $ismodule ? $desc : $v['family'],
                //"desc" => $v['family'],
                "cve" => $v['cve'],
                "cnvd" => $v['cnvd'],
                "cnnvd" => $v['cnnvd'],
                "bid" => $v['bid'],
                "desc_cn" => $v['description'],
                "solu_cn" => $v['solution'],
                "ref_cn" => $v['ref_cn'],
                "vul_name_sub" => mb_substr($v['vul_name'], 0, 16, 'utf-8'),
            );
            array_push($aData, $aItem);
        }
        $data['Rows'] = $aData;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }


//二维数组去掉重复值,并保留键值

    function array_unique_fb($array2D)
    {
        foreach ($array2D as $k => $v) {
            $v = join(',', $v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k] = $v;
        }
        $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v) {
            $array = explode(',', $v); //再将拆开的数组重新组装
            //下面的索引根据自己的情况进行修改即可
            //  $temp2[$k]['open'] =$array[0];
            $temp2[$k]['id'] = $array[1];
            $temp2[$k]['desc'] = $array[2];
            $temp2[$k]['parent_id'] = $array[3];
            //  $temp2[$k]['pId'] =$array[4];
        }
        return $temp2;
    }
    /**
     * 获取漏洞库分类
     * 根据policy id 获取相关的漏洞分类
     * @ params ： policy_id
     */
    function actionGetHostFamilyByPolicyid()
    {
        global $db;
        $sPost = $_POST;
        $policy_id = intval($sPost['policy_id']);
        $rows = array();
        $a_parentid = array();
        $s_parentid = "";
        $aData = $aItem = $rData = array();

        $sql="SELECT distinct fl.id,fl.description as `desc`, fl.parent_id FROM bd_host_family_list as fl  INNER JOIN bd_host_policy_selectors as fr ON fl.id= fr.family_id  WHERE fr.policy_id='$policy_id'";
     //  echo $sql;die;
        $rows = $db->fetch_all($sql);
    //echo "SELECT distinct fl.id,fl.desc, fl.parent_id FROM bd_host_family_list as fl  INNER JOIN bd_host_policy_selectors as fr ON fl.id= fr.family_id  $where ";die;
     // var_dump($rows);die;
        foreach ($rows as $k => $v) {
            $parentids[] = $v['parent_id'];
            $aItem = array(
                "open" => false,
                "id" => $v['id'],
                "name" => $v['desc'],
                "parent_id" => $v['parent_id'],
                "pId" => $v['parent_id']
            );
            array_push($aData, $aItem);
        }
        $s_parentid = implode(",", $parentids);
        $rowparent = $db->fetch_all("SELECT * FROM bd_host_family_list WHERE id in (" . $s_parentid . ")");
        foreach ($rowparent as $ks => $vs) {
            $aItem = array(
                "open" => false,
                "id" => $vs['id'],
                "name" => $vs['description'],
                "parent_id" => $vs['parent_id'],
                "pId" => $vs['parent_id']
            );
            array_push($aData, $aItem);
        }

        $rData = array(
            "open" => true,
            "id" => 0,
            "name" => '所有漏洞',
            "parent_id" => '',
            "pId" => ''
        );
        $aData = $this->array_unique_fb($aData);  //二维数组去除重复数组
        $cData = array();
        foreach ($aData as $ks => $vs) {
            $aItem = array(
                "open" => false,
                "id" => $vs['id'],
                "name" => $vs['desc'],
                "parent_id" => $vs['parent_id'],
                "pId" => $vs['parent_id']
            );
            array_push($cData, $aItem);
        }
        array_push($cData, $rData);
        echo json_encode($cData);
        exit;
    }

    /**
     * 反选时候，获取当前相关的相反漏洞
     * @ params ：curparm，curdata
     */
    function actionGetcurrentrev()
    {
        global $db;
        $sPost = $_POST;
        $rows = $parmrows = array();
        $curparm = array($sPost['curparm']);
        //$curdata = array($sPost['curdata']);
        //$curdata = $curdata[0];
        //$curlen = count($curdata);
        $curdata = array();
        if (!empty($sPost['curdata']))
            $curdata = explode(',', $sPost['curdata']);
        foreach ($curparm[0] as $k => $v) {
            $parmrows[$v['name']] = $v['value'];
        }
        $vul_name = $parmrows['vul_name'];

        //保存当前类之前选中的id
        $curtabid = array();

        $treeType = $parmrows['tree_type'];
        //按CVEyear的全选条件
        $CVEyear = $parmrows['CVEyear'];
        //按出现时间的全选条件
        $published_time = $parmrows['published_time'];

        $treeType = $parmrows['tree_type'];
        if (!empty($CVEyear)) {
            $refids = array();
            $leng = 0;
            if (!empty($treeType) && $treeType == 3) {
                if ($CVEyear != 'CVE年份') {
                    $where = "WHERE vi.oid!='' AND vi.CVEyear = '" . $CVEyear . "'";
                    if (!empty($vul_name)) {
                        $where .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                    }
                    if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                        if (empty($parmrows['hr'])) {
                            $where .= " AND vi.vul_level !='H' ";
                        }
                        if (empty($parmrows['mr'])) {
                            $where .= " AND vi.vul_level !='M' ";
                        }
                        if (empty($parmrows['lr'])) {
                            $where .= " AND vi.vul_level !='L' ";
                        }
                        if (empty($parmrows['ir'])) {
                            $where .= " AND vi.vul_level !='I' ";
                        }
                    }

                    $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi $where ");
                    foreach ($refrows as $k => $v) {
                        if (!in_array($v['vul_id'], $curdata)) {
                            $refids[] = $v['vul_id'];
                        } else {
                            $curtabid[] = $v['vul_id'];
                        }
                    }
                }
            }
            $refids = array_merge(array_diff($curdata, $curtabid), $refids);
            $leng = count($refids);
            $data['data'] = $refids;
            $data['leng'] = $leng;
            $data['success'] = true;
            echo json_encode($data);
            exit;
        }
        if (!empty($published_time)) {
            $refids = array();
            $leng = 0;
            if (!empty($treeType) && $treeType == 2) {
                if ($published_time != '时间' && $published_time != 'OtherTime') {
                    $where = "WHERE vi.oid!='' AND vi.published_time LIKE '{$published_time}%'";
                } else if ($published_time == 'OtherTime') {
                    $where = "WHERE vi.oid!='' AND vi.published_time is null";
                }
                if (!empty($vul_name)) {
                    $where .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi $where");
                foreach ($refrows as $k => $v) {
                    if (!in_array($v['vul_id'], $curdata)) {
                        $refids[] = $v['vul_id'];
                    } else {
                        $curtabid[] = $v['vul_id'];
                    }
                }
            }
            $refids = array_merge(array_diff($curdata, $curtabid), $refids);
            $leng = count($refids);
            $data['data'] = $refids;
            $data['leng'] = $leng;
            $data['success'] = true;
            echo json_encode($data);
            exit;
        }

        $family = $parmrows['family'];
        $ilevel = $parmrows['ilevel'];
        if (is_null($ilevel)) {
            $ilevel = 1;
        }
        if (!empty($ilevel)) {
            $refids = array();
            $leng = 0;
            if ($ilevel == 3) {     //三级分类
                $where1 = " WHERE vi.oid!='' AND fr.family = $family";
                if (!empty($vul_name)) {
                    $where1 .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where1 .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where1 .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where1 .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where1 .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT vi.vul_id FROM bd_host_vul_lib AS vi   $where1 ORDER BY vi.vul_id ASC");
                foreach ($refrows as $k => $v) {
                    if (!in_array($v['vul_id'], $curdata)) {
                        $refids[] = $v['vul_id'];
                    } else {
                        $curtabid[] = $v['vul_id'];
                    }
                }
                //$leng = count($refids);
            } else if ($ilevel == 2) {       //二级分类
                $where1 = " WHERE  fr.module_id = $family";
                if (!empty($vul_name)) {
                    $where1 .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                //$stttr = "SELECT vi.vul_id FROM bd_host_vul_lib AS vi   $where1 ORDER BY vi.vul_id ASC";
                //print_r($stttr);exit;
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where1 .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where1 .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where1 .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where1 .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT id FROM bd_host_vul_lib AS vi   $where1 ORDER BY vi.vul_id ASC");
                foreach ($refrows as $k => $v) {
                    if (!in_array($v['vul_id'], $curdata)) {
                        $refids[] = $v['vul_id'];
                    } else {
                        $curtabid[] = $v['vul_id'];
                    }
                }
                //$leng = count($refids);
            } else {      //一级分类
                $where1 = "WHERE 1=1 AND vi.oid!='' ";
                if (!empty($vul_name)) {
                    $where1 .= " AND vi.vul_name LIKE '%{$vul_name}%'";
                }
                if (!empty($parmrows['hr']) || !empty($parmrows['mr']) || !empty($parmrows['lr']) || !empty($parmrows['ir'])) {
                    if (empty($parmrows['hr'])) {
                        $where1 .= " AND vi.vul_level !='H' ";
                    }
                    if (empty($parmrows['mr'])) {
                        $where1 .= " AND vi.vul_level !='M' ";
                    }
                    if (empty($parmrows['lr'])) {
                        $where1 .= " AND vi.vul_level !='L' ";
                    }
                    if (empty($parmrows['ir'])) {
                        $where1 .= " AND vi.vul_level !='I' ";
                    }
                }

                $refrows = $db->fetch_all("SELECT id FROM bd_host_vul_lib AS vi   $where1 ORDER BY vi.vul_id ASC");
                foreach ($refrows as $k => $v) {
                    if (!in_array($v['vul_id'], $curdata)) {
                        $refids[] = $v['vul_id'];
                    } else {
                        $curtabid[] = $v['vul_id'];
                    }
                }
                //$leng = count($refids);
            }
            $refids = array_merge(array_diff($curdata, $curtabid), $refids);
            $leng = count($refids);
            //$data['curtabid'] = $curtabid;
            $data['data'] = $refids;
            $data['leng'] = $leng;
            $data['success'] = true;
            echo json_encode($data);
            exit;
        }

        $data['data'] = 'fail';
        $data['success'] = false;
        echo json_encode($data);
        exit;
    }


}
?>
