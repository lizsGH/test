<?php
namespace app\controllers;
//web漏洞
use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class WebvulController extends BaseController
{
    /**
     * @ 获取web策略所有的漏洞
     */
    function actionLists()
    {
        global $db;
        $sPost = $_POST;
        $existids = array();
        $aData = $aItem = $rows = array();
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $total = 0;
        $where = " WHERE 1=1";
        $where .= " AND vi.enable=1";
        $page = $page > 1 ? $page : 1;
        //  $page=1;
        $vul_name = filterStr($sPost['vul_name']);      //查询名称
        $family = intval($sPost['family']);     //点击左边的分类 id
        $ilevel = intval($sPost['ilevel']);     //点击左边的分类级别
        $refarr = $desc = '';
        $ismodule = false;

        if (!empty($family)) {
            if ($ilevel == 3) {     //三级分类
                $where1 = " WHERE family_id = $family";
                $refrows = $db->fetch_all("SELECT vul_id FROM bd_web_vul_lib  $where1 ");
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
                $refrows = $db->fetch_all("SELECT vul_id FROM bd_web_vul_lib  $where1 ");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                $refarr = implode(",", $refids);
                $getname = $db->fetch_all("SELECT `name` FROM bd_web_family WHERE id=$family");
                $desc = $getname[0]['name'];
                $ismodule = true;
            }
        }

        if (!empty($refarr)) {
            $where .= " AND vi.vul_id in (" . $refarr . ")";
        }

        if (!empty($vul_name)) {        //查询名称
            $where .= " AND vi.vul_name LIKE '%{$vul_name}%'";
        }

        $total = $db->result_first("SELECT COUNT(`id`) FROM bd_web_vul_lib AS vi $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;

        if (!empty($sPost['sortname']) && !empty($sPost['sortorder']) && $sPost['sortname'] != 'id') {
            $sortname = trim($sPost['sortname']);
            $sortorder = trim($sPost['sortorder']);
            $where .= " ORDER BY FIELD(vi." . $sortname . ",'H', 'M', 'L','I') " . $sortorder;
        } else {
            $where .= " ORDER BY FIELD(vi.level, 'H', 'M', 'L','I') " . $sPost['sortorder'];
        }

        if ($total) {
            $start = ($page - 1) * $perpage;
            $querys = "SELECT vi.id,vi.description,vi.vul_id,vi.vul_name,vi.level,vi.family,vi.solution FROM bd_web_vul_lib as vi INNER JOIN bd_web_family on bd_web_family.id = vi.family_id $where  LIMIT $start,$perpage";
            $rows = $db->fetch_all($querys);
        }

        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "vul_id" => $v['vul_id'],
                "vul_name" => $v['vul_name'],
                "level" => $v['level'],
                "family" => $ismodule ? $desc : $v['family'],
                "desc" => $v['description'],
                "solu" => $v['solution'],
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
     * 获取主机策略的漏洞分类
     * @ params table ：nvts_type
     * @ strategy : 1
     */
    function actionGetwebfamily()
    {
        global $db;
        $rows = array();
        $aData = $aItem = $rData = array();
        $where = " WHERE 1=1";
        $rows = $db->fetch_all("SELECT * FROM bd_web_family $where ");

        foreach ($rows as $k => $v) {
            $aItem = array(
                "open" => false,
                "id" => $v['id'],
                "name" => $v['description'],
                "parent_id" => $v['parent_id'],
                "pId" => $v['parent_id']
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
        array_push($aData, $rData);
        echo json_encode($aData);
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
            $existrows = $db->fetch_all("SELECT * FROM bd_web_policy_selectors  WHERE policy_id=" . $policy_id);
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
            $family = $parmrows['family'];
            $ilevel = $parmrows['ilevel'];
            $vul_name = $parmrows['vul_name'];
        if (is_null($ilevel)) {
            $ilevel = 1;
        }
//var_dump($parmrows);die;
        if (!empty($ilevel)) {
            $refids = array();
            $leng = 0;
            if ($ilevel == 3) {     //三级分类
                $where1 = " WHERE family_id = $family  AND enable=1";
                if (!empty($vul_name)) {
                    $where1 .= " AND vul_name LIKE '%{$vul_name}%'";
                }

                $refrows = $db->fetch_all("SELECT * FROM bd_web_vul_lib $where1 ");
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                //$leng = count($refids);
            } else if ($ilevel == 2) {       //二级分类
                $where1 = "WHERE 1=1";
                $where1 .= " AND module_id=$family AND enable=1";
                if (!empty($vul_name)) {
                    $where1 .= " AND vul_name LIKE '%{$vul_name}%'";
                }
                $refrows = $db->fetch_all("SELECT * FROM bd_web_vul_lib " . $where1);
                foreach ($refrows as $k => $v) {
                    $refids[] = $v['vul_id'];
                }
                //$leng = count($refids);
            } else {
                $where1 = "WHERE 1=1 AND enable=1";
                $refrows = $db->fetch_all("SELECT * FROM bd_web_vul_lib " . $where1);
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


    /**
     *  反选时候，获取当前相关的相反漏洞
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
        $family = $parmrows['family'];
        $ilevel = $parmrows['ilevel'];
        $vul_name = $parmrows['vul_name'];
        if (is_null($ilevel)) {
            $ilevel = 1;
        }
        //保存当前类之前选中的id
        $curtabid = array();
        if (!empty($ilevel)) {
            $refids = array();
            $leng = 0;
            if ($ilevel == 3) {     //三级分类
                $where1 = " WHERE family_id = $family AND enable=1";
                if (!empty($vul_name)) {
                    $where1 .= " AND vul_name LIKE '%{$vul_name}%'";
                }
                $refrows = $db->fetch_all("SELECT * FROM bd_web_vul_lib $where1 ORDER BY id ASC");
                foreach ($refrows as $k => $v) {
                    if (!in_array($v['vul_id'], $curdata)) {
                        $refids[] = $v['vul_id'];
                    } else {
                        $curtabid[] = $v['vul_id'];
                    }
                }
                //$leng = count($refids);
            } else if ($ilevel == 2) {       //二级分类
                $where1 = "WHERE 1=1";
                $where1 .= " AND module_id=$family  AND enable=1";
                if (!empty($vul_name)) {
                    $where1 .= " AND vul_name LIKE '%{$vul_name}%'";
                }
                $refrows = $db->fetch_all("SELECT * FROM bd_web_vul_lib $where1 ORDER BY id ASC");
                foreach ($refrows as $k => $v) {
                    if (!in_array($v['vul_id'], $curdata)) {
                        $refids[] = $v['vul_id'];
                    } else {
                        $curtabid[] = $v['vul_id'];
                    }
                }
                //$leng = count($refids);
            } else {
                $where1 = "WHERE 1=1 AND enable=1";
                $refrows = $db->fetch_all("SELECT * FROM bd_web_vul_lib " . $where1);
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
        $perpage = intval($sPost['length']);
        $total = 0;
        $where = " WHERE 1=1";
        //  $page  = $page > 1 ? $page : 1;
        $page = 1;
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
                $getname = $db->fetch_all("SELECT `description` FROM bd_web_family WHERE id=$family");
                $desc = $getname[0]['description'];
                $ismodule = true;
            }
        }
        $where .= " AND fr.policy_id=" . $policy_id;
        $total = $db->result_first("SELECT count(vi.id) FROM bd_web_vul_lib AS vi INNER JOIN bd_web_policy_selectors as fr ON vi.vul_id= fr.vul_id INNER JOIN bd_web_family AS fl ON fr.family_id=fl.id  $where ");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $querys = "SELECT vi.id,vi.vul_id,vi.vul_name,vi.level,vi.family,vi.description,vi.solution FROM bd_web_vul_lib AS vi INNER JOIN bd_web_policy_selectors as fr ON vi.vul_id= fr.vul_id INNER JOIN bd_web_family AS fl ON fr.family_id=fl.id  $where LIMIT $start,$perpage";
            $rows = $db->fetch_all($querys);
        }

        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "vul_id" => $v['vul_id'],
                "vul_name" => $v['vul_name'],
                "level" => $v['level'],
                "family" => $ismodule ? $desc : $v['family'],
                "desc" => $v['description'],
                "solu" => $v['solution'],
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
     * 获取漏洞库分类
     * 根据policy id 获取相关的漏洞分类
     * @ params ： policy_id
     */
    function actionGetwebfamilybypolicyid()
    {
        global $db;
        $sPost = $_POST;
        $policy_id = intval($sPost['policy_id']);
        $rows = array();
        $aData = $aItem = $rData = array();
        $where = " AND fr.policy_id=" . $policy_id;
        $rows = $db->fetch_all("SELECT distinct fl.id,fl.description, fl.parent_id FROM bd_web_family as fl  INNER JOIN bd_web_policy_selectors as fr ON fl.id= fr.family_id  $where ");

        $pIdArray = array();
        foreach ($rows as $k => $v) {
            $aItem = array(
                "open" => false,
                "id" => $v['id'],
                "name" => $v['description'],
                "parent_id" => $v['parent_id'],
                "pId" => $v['parent_id']
            );
            array_push($pIdArray, $v['parent_id']);
            array_push($aData, $aItem);
        }
        $rowparent = $db->fetch_all("SELECT * FROM bd_web_family WHERE parent_id=0");
        foreach ($rowparent as $ks => $vs) {
            if (in_array($vs['id'], $pIdArray)) {
                $aItem = array(
                    "open" => false,
                    "id" => $vs['id'],
                    "name" => $vs['description'],
                    "parent_id" => $vs['parent_id'],
                    "pId" => $vs['parent_id']
                );
                array_push($aData, $aItem);
            }
        }
        $rData = array(
            "open" => true,
            "id" => 0,
            "name" => '所有漏洞',
            "parent_id" => '',
            "pId" => ''
        );
        array_push($aData, $rData);
        echo json_encode($aData);
        exit;
    }


}

?>