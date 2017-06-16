<?php
/**
 * 弱密码漏洞
 */
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class WeakvulController extends BaseController
{
    /**
     * @获取所有的弱密码漏洞
     */
    function actionLists()
    {
        global $db;
        $sPost = $_POST;
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $policy_id = intval(($sPost['policy_id']));      //编辑
        $total = 0;
        $rows = array();
        $aData = $aItem = array();
        $where = " WHERE 1=1";
        $page = $page > 1 ? $page : 1;
        if (!empty($policy_id)) {     //编辑,获取已选择的数据项
            $existrows = $db->fetch_first("SELECT vuls FROM bd_weakpwd_policy  WHERE id=" . $policy_id);
            $existids = explode("|", $existrows['vuls']);
        }


        $total = $db->result_first("SELECT COUNT(`id`) num FROM bd_weakpwd_vul_lib $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;

        if (!empty($sPost['sortname']) && !empty($sPost['sortorder'])) {
            $sortname = trim($sPost['sortname']);
            $sortorder = trim($sPost['sortorder']);
            $where .= " ORDER BY $sortname $sortorder";
        } else {
            $where .= " ORDER BY id DESC ";
        }

        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM bd_weakpwd_vul_lib  $where  LIMIT $start,$perpage");
        }
        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "vul_id" => $v['vul_id'],
                "vul_name" => $v['vul_name'],
                "level" => $v['level'],
                "ischecked" => in_array($v['vul_id'], $existids) ? true : false,
                "desc" => $v['desc'],
                "solu" => $v['solu'],
                'port'=>$v['port']
            );
            array_push($aData, $aItem);
        }

        $data['Rows'] = $aData;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }

    //根据扫描id获取漏洞
    function actionGetvulbypolicyid()
    {
        global $db;
        $sPost = $_POST;
        $aData = $aItem = $rows = array();
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $total = 0;
        $where = " WHERE 1=1";
        $page = $page > 1 ? $page : 1;
        $policy_id = intval($sPost['policy_id']);      //查询名称

        $policyrows = $db->fetch_first("SELECT vuls FROM bd_weakpwd_policy  WHERE id=" . $policy_id);
        $a_vulids = str_replace("|", ",", $policyrows['vuls']);
        $where .= " AND vul_id in (" . $a_vulids . ")";

        $total = $db->result_first("SELECT COUNT(`id`) num FROM bd_weakpwd_vul_lib $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM bd_weakpwd_vul_lib  $where ORDER BY id DESC  LIMIT $start,$perpage");
        }
        foreach ($rows as $k => $v) {
            $aItem = array(
                "id" => $v['id'],
                "vul_id" => $v['vul_id'],
                "vul_name" => $v['vul_name'],
                "level" => $v['level'],
                "desc" => $v['desc'],
                "solu" => $v['solu'],
                'port' => $v['port']
            );
            array_push($aData, $aItem);
        }

        $data['Rows'] = $aData;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;


    }

}

?>