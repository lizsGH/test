<?php
namespace  app\controllers;
/**
 * 报表报告
 * author hjf
 */
class BbglreportController extends BaseController
{
    private $post;

    public function init(){
        parent::init();

        $this->post = isset($_POST['post']) ? intval($_POST['post']) : 0;

    }
    function actionIndex()
    {
        global $act, $db;
        $data = array();
        if ($this->post == 2) {
            echo 31;
        } else {
            //模板部分
            $sql = "SELECT * FROM template_report";
            $report = $db->fetch_all($sql);
            //{"1" : {"name" : "系统默认模板", "modules" : "net-standard,suggestion"}, "2" : {"name" : "技术主管模板", "modules" : "summary,suggestion"}};
            $res = array();
            foreach ($report as $k => $v) {
                $res[$v['id']]['id'] = $v['id'];
                $res[$v['id']]['name'] = $v['name'];
                foreach ($v as $kk => $vv) {
                    if ($vv == 1 && !in_array($kk, array('id', 'name', 'risk', 'vul_host', 'vul_web'))) {
                        $res[$v['id']]['modules'] .= $kk . ',';
                    }
                }
                $res[$v['id']]['modules'] = rtrim($res[$v['id']]['modules'], ',');
            }
            $data['modules'] = $res;
            $data['res'] = json_encode($res);
            //var_dump($data['res']);exit;
            //生成部分
            $where = " WHERE 1=1";
            $userid = intval($_SESSION['userid']);
            $userrow = $db->fetch_first("select role_id as  role from bd_sys_user WHERE id=$userid ");
            if ($userrow['role'] != 16) { //不是系统管理员
                $where .= " AND user_id=$userid";
            }
            $rows = $db->fetch_all("SELECT id,task_name FROM task_manage $where order by id desc");
            $data['rows'] = $rows;
            $data['Total'] = $total;

            template($act, $data);
        }
    }

    function edit()
    {
        $html = '<script src="resource/js/jquery-1.9.1.js"></script>';
        $html .= '<form action="index.php?act=bbgl_report&show=addTamplate" method="post" class="form_id"><div>模板名称： <input name="template_name" type="text" value="" maxlength="20" /></div>';
        $html .= '<script>var dialog = top.getDialog();dialog.DOM.wrap.on("ok", function (e) {e.preventDefault();$("form.form_id").submit();window.localStorage["bbmb"] = "1";});</script>';
        echo $html;
    }

    function addTamplate()
    {
        global $act, $db, $show;
        $data = array();
        $res = false;
        $name = filterStr($_POST['template_name']);
        if ($name == '')
            $name = 0;
        $iTotal = $db->result_first("SELECT COUNT(`name`) FROM template_report WHERE name='" . $name . "'");
        if (!empty($iTotal)) {
            $data['success'] = false;
            $data['msg'] = $name . '已存在，请更换';
            echo json_encode($data);
            exit;
        }
        $sql = "INSERT INTO template_report ( name ) values ( '" . $name . "' )";
        if ($db->query($sql)) {
            $hdata['sDes'] = '增加报表模板';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            $res = true;
        } else {
            $hdata['sDes'] = '增加报表模板';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        }
        $html = '<script>var dialog = top.getDialog();dialog.hide();</script>';
        echo $html;
    }

    function saveTpl()
    {
        global $act, $db, $show;
        $data = $sPost = array();
        $tplID = intval($_POST['tplID']);
        $s_risk = intval($_POST['s_risk']) ? ',risk' : '';
        $s_host = intval($_POST['s_host']) ? ',vul_host' : '';
        $s_web = intval($_POST['s_web']) ? ',risk_web' : '';
        //先置为0
        $m = "overview=0,risk=0,risk_lever=0,risk_type=0,risk_host=0,vul_host=0,vul_host_system=0,vul_host_server=0,vul_host_application=0,vul_host_device=0,vul_host_database=0,vul_host_virtual=0,risk_web=0,vul_web_syscmd=0,vul_web_sql=0,vul_web_code=0,vul_web_file=0,vul_web_http=0,vul_web_ldap=0,vul_web_script=0,vul_web_content=0,vul_web_upload=0,vul_web_deny=0,vul_web_info=0,vul_web_dir=0,vul_web_log=0,vul_web_server=0,vul_web_read=0,vul_web_database=0,vul_web_backdoor=0,vul_web_auth=0,vul_web_config=0,vul_web_other=0,risk_pwd=0";
        $db->query("UPDATE template_report SET " . $m . " WHERE id = $tplID");
        $modules = filterStr($_POST['modules']);
        $modules = $modules . $s_risk . $s_host . $s_web;
        $modules = str_replace(',', '=1,', $modules);
        if ($modules == '') {
            $data['success'] = true;
            $data['msg'] = '保存成功！';
            $hdata['sDes'] = '编辑报表模板';
            $hdata['sRs'] = '成功';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($data);
            exit;
        } else {
            $res = $db->query("UPDATE template_report SET " . $modules . "=1 WHERE id = $tplID");
            if ($res) {
                $data['success'] = true;
                $data['msg'] = '保存成功！';
                $hdata['sDes'] = '编辑报表模板';
                $hdata['sRs'] = '成功';
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
                echo json_encode($data);
                exit;
            }
            $data['success'] = false;
            $data['msg'] = '保存失败！';
            $hdata['sDes'] = '编辑报表模板';
            $hdata['sRs'] = '失败';
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($data);
            exit;
        }

    }

    function delTpl()
    {
        global $db, $act, $show;
        $data = array();
        $tplID = intval($_POST['tplID']);
        $query = "DELETE FROM template_report where id =$tplID";
        $db->result_first($query);

        $success = true;
        $msg = "删除成功";
        $data['success'] = $success;
        $data['msg'] = $msg;
        $hdata['sDes'] = '删除报表模板';
        $hdata['sRs'] = '成功';
        $hdata['sAct'] = $act . '/' . $show;
        saveOperationLog($hdata);
        echo json_encode($data);
        exit;
    }
}
?>