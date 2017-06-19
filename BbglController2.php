<?php
namespace  app\controllers;

use app\components\client_db;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;

class BbglController extends BaseController {
    public function actionIndex(){
        $db=new client_db();
        $data = array();
        $post = isset($_POST['post']) ? intval($_POST['post']) : 0;
        $vul_level = array( 
			'H' => Yii::t('app', '高风险'), 
			'M' => Yii::t('app', '中风险'), 
			'L' => Yii::t('app', '低风险'), 
			'I' => Yii::t('app', '低风险'),
		);
        $risk_css = array( 'H'=>'high', 'M'=>'medium', 'L'=>'low' ,'I'=>'low');
        $rcccolor = array( 'H'=>'#d2322d', 'M'=>'#d58512', 'L'=>'#3276b1', 'I'=>'#3276b1');

        // $web_factor = array ('H'=>'高风险', 'M'=>'中风险', 'L'=>'低风险' ,'I'=>'低风险');
		$web_factor = $vul_level;
        $web_css = array( 'H'=>'high', 'M'=>'medium', 'L'=>'low' ,'I'=>'low');
        $web_color = array( 'H'=>'#d2322d', 'M'=>'#d58512', 'L'=>'#3276b1' ,'I'=>'#3276b1');
        $kcolor = array('0'=>'#EEEEEE', '1'=>'#D4E3F6');

        $if_host_list = false;
        $if_web_list  = false;
        $new_content=$new_image_content=$new_word_content='';
        if($post == 1){
            $data  = array('success'=>false, 'message'=>'操作失败', 'down'=>'');
            $template_conf = array(
                //'name': '模板名',
                'overview' => Yii::t('app', '综述'),
                'risk' => Yii::t('app', '总体风险分析'),
                'risk_lever' => Yii::t('app', '风险等级分布'),
                'risk_type' => Yii::t('app', '风险类型分布'),
                'risk_host' => Yii::t('app', '所有主机（IP）风险分布'),
                'vul_host' => Yii::t('app', '主机漏洞列表'),
                'vul_host_system' => Yii::t('app', '系统漏洞'),
                'vul_host_server' => Yii::t('app', '服务漏洞'),
                'vul_host_application' => Yii::t('app', '应用漏洞'),
                'vul_host_device' => Yii::t('app', '网络设备漏洞'),
                'vul_host_database' => Yii::t('app', '数据库漏洞'),
                'vul_host_virtual' => Yii::t('app', '虚拟化平台漏洞'),
                'risk_web' => Yii::t('app', 'WEB漏洞列表'),
                'vul_web_syscmd' => Yii::t('app', '系统命令执行'),
                'vul_web_sql' => Yii::t('app', 'SQL注入'),
                'vul_web_code' => Yii::t('app', '代码远程执行'),
                'vul_web_file' => Yii::t('app', '远程文件包含'),
                'vul_web_http' => Yii::t('app', 'HTTP参数污染'),
                'vul_web_ldap'  => Yii::t('app', 'LDAP注入'),
                'vul_web_script' => Yii::t('app', '跨站脚本攻击'),
                'vul_web_content' => Yii::t('app', '内容欺骗'),
                'vul_web_upload' => Yii::t('app', '文件上传'),
                'vul_web_deny' => Yii::t('app', '拒绝服务'),
                'vul_web_info' => Yii::t('app', '信息泄露'),
                'vul_web_dir' => Yii::t('app', '目录遍历'),
                'vul_web_log' => Yii::t('app', '日志文件扫描'),
                'vul_web_server' => Yii::t('app', '软件服务检测'),
                'vul_web_read' => Yii::t('app', '任意文件读取'),
                'vul_web_database' => Yii::t('app', '数据库发现'),
                'vul_web_backdoor' => Yii::t('app', '后门发现'),
                'vul_web_auth' => Yii::t('app', '验证绕过'),
                'vul_web_config' => Yii::t('app', '配置不当'),
                'vul_web_other' => Yii::t('app', '其它'),
                'risk_pwd' => Yii::t('app', '弱密码漏洞列表'),
            );           //$tasks  = intval($_POST['tasks']);//这里$_POST['tasks']是个数组

            $t_host_list = array(
                'vul_host'=>'vul_host',
                'vul_host_system'=> 'vul_host_system',
                'vul_host_server'=> 'vul_host_server',
                'vul_host_application'=> 'vul_host_application',
                'vul_host_device'=> 'vul_host_device',
                'vul_host_database'=> 'vul_host_database',
                'vul_host_virtual'=>'vul_host_virtual'
            );

            $t_web_list = array(
                'risk_web'=> 'risk_web',
                'vul_web_syscmd'=> 'vul_web_syscmd',
                'vul_web_sql'=> 'vul_web_sql',
                'vul_web_code'=> 'vul_web_code',
                'vul_web_file'=> 'vul_web_file',
                'vul_web_http'=> 'vul_web_http',
                'vul_web_ldap'=> 'vul_web_ldap',
                'vul_web_script'=> 'vul_web_script',
                'vul_web_content'=> 'vul_web_content',
                'vul_web_upload'=>'vul_web_upload',
                'vul_web_deny'=> 'vul_web_deny',
                'vul_web_info'=>  'vul_web_info',
                'vul_web_dir'=> 'vul_web_dir',
                'vul_web_log'=> 'vul_web_log',
                'vul_web_server'=> 'vul_web_server',
                'vul_web_read'=> 'vul_web_read',
                'vul_web_database'=> 'vul_web_database',
                'vul_web_backdoor'=> 'vul_web_backdoor',
                'vul_web_auth'=> 'vul_web_auth',
                'vul_web_config'=> 'vul_web_config',
                'vul_web_other'=> 'vul_web_other'
            );

            $rt           = filterStr($_POST['rt']);
            $bbtitle      = filterStr($_POST['bbtitle']);
            $bbname       = filterStr($_POST['bbname']);
            $desc         = filterStr($_POST['desc']);
            $epilog       = filterStr($_POST['epilog']);
            $kidbb       = intval($_POST['kidbb']);
            $template_report       = intval($_POST['template_report']);
            //组装需要隐藏的栏目数组
            $templateConfArr = array();
            $temRes = $db->fetch_first("SELECT * FROM template_report WHERE id=$template_report");
            foreach ($temRes as $k =>$v){
                if($k != 'id' && $k != 'name' && $v == 1 ){
                    if(in_array($k,$t_host_list)){
                        $if_host_list = true;
                    }
                    if(in_array($k,$t_web_list)){
                        $if_web_list = true;
                    }
                    $templateConfArr[] = $template_conf["$k"];
                }
            }

            if(empty($_POST['tasks'])){
                $data['message'] = Yii::t('app', '请选择任务.');
                echo json_encode($data);
                exit;
            }

            if($bbname == ''){
                $data['message'] = Yii::t('app', '请填写报表名称.');
                echo json_encode($data);
                exit;
            }
            $theTime = '-'.date('Y-m-d_H:i:s',time());

            foreach ($_POST['tasks'] as $key=>$val) {
                $tasks=intval($val);
                $tablevul = 'bd_host_result_' . $tasks;
                $tablepwd = 'bd_weakpwd_result_' . $tasks;
                $tablescan = 'bd_web_result_' . $tasks;
                ignore_user_abort(TRUE);
                @set_time_limit(300);
                $date = date("Ymd");
                $day = date('d');
                $dym = "/basic/web/report";
                $dir = "/basic/web/report/now";//报表存放文件夹

                if (!file_exists($dir)) mkdir($dir, 0777);

                $file_name = array();
                $maxid = $db->result_first("SELECT MAX(id) FROM " . getTable('reportsmanage') . " WHERE 1");
                $maxid = (!$maxid || $maxid < 1) ? 1 : $maxid + 1;

                exec("cd /basic/web/report/now; ln -s ../common.js common.js; ln -s ../common.css common.css; ln -s ../bluechar.js; ln -s ../jquery-1.9.1.min.js jquery-1.9.1.min.js");

                $content = $wordcon = $imageCon = '';
                if($_POST['type']=='weakpwd'){
                    $content = file_get_contents($dym . '/attack_weak.html');

                    $total=\Yii::$app->db->createCommand("select count(1) as num from $tablepwd")->queryColumn()[0];
                    $rows=\Yii::$app->db->createCommand("select * from $tablepwd")->queryAll();
                    $main_tasks=\Yii::$app->db->createCommand("SELECT * FROM bd_weakpwd_task_manage WHERE id='$tasks'")->queryOne();
                    $targets = $rows['target'];
                   // var_dump($num);die;
                    $content = str_replace('{$weak_num}', $total, $content);
                    $content = str_replace('{$type}', Yii::t('app', '弱密码'), $content);
                    $h_sum=$m_sum=$l_sum=0;
                    foreach ($rows as $v){
                        if($v['level']=='H'){
                            $level = Yii::t('app', '高危');
                            $h_sum++;
                        }
                        if($v['level']=='M'){
                            $level = Yii::t('app', '中危');
                            $m_sum++;
                        }
                        if($v['level']=='L'){
                            $level = Yii::t('app', '低危');
                            $l_sum++;
                        }
                    }
                    //var_dump($h_sum,$m_sum);die;
                    $content = str_replace('{$level}', $level, $content);
                    $content = str_replace('{$h_sum}', $h_sum, $content);
                    $content = str_replace('{$m_sum}', $m_sum, $content);
                    $content = str_replace('{$l_sum}', $l_sum, $content);

                    /*弱密码风险总数*/
                    $rmm = $db->result_first("SELECT COUNT(1) AS mnum FROM $tablepwd");
                    $rmmip = $db->fetch_all("SELECT ip FROM $tablepwd GROUP BY ip");
                    /*弱密码分布检测*/
                    $rms = $db->fetch_all("SELECT * FROM bd_weakpwd_vul_lib ORDER BY id ASC");
                    foreach ($rms as $k => $v) {//根据类别读取
                        //$num = $db->result_first("SELECT COUNT(1) AS num FROM $tablepwd WHERE vul_id='$v[id]'");
                        $num = $db->result_first("SELECT COUNT(1) AS num FROM $tablepwd WHERE vul_id='$v[vul_id]'");
                        $num = $num > 0 ? $num : 0;
                        $data8[] = '{name:"' . $v['vul_name'] . '",value:' . $num . ',color:setColor[' . $k . ']}';
                    }
                    $content = str_replace('{$data8}', join(',', $data8), $content);
                    $imageCon = str_replace('{$data8}', join(',', $data8), $imageCon);
                    //高中低风险数量
                    $content = str_replace('{$h_fx}', number_format((($h_sum / $total) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$m_fx}', number_format((($m_sum / $total) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$l_fx}', number_format((($l_sum / $total) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$h_sum}', $h_sum ? $h_sum : 0, $content);
                    $content = str_replace('{$m_sum}', $m_sum ? $m_sum : 0, $content);
                    $content = str_replace('{$l_sum}', $l_sum ? $l_sum : 0, $content);

                    /*4.3 弱密码列表*/
                    $rmhmtl = $rmwhmtl = '';
                    $mmlist = $db->fetch_all("SELECT `ip`,`username`,`password`,`vul_name` FROM $tablepwd ORDER BY id ASC");
                    $mmjl = count($mmlist) ? count($mmlist) : 0;

                    $classTemp = '';
                    $styleTemp = '';

                    if ($mmjl == 0) {
                        $rmhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                        $rmwhmtl .= '<p style="line-height:20px;text-indent:4em;width:100%;'.$styleTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                    } else {
                        $rmhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现弱密码') . '<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>' . Yii::t('app', '个。') . Yii::t('app', '影响主机') . '<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>' . Yii::t('app', '个。') . '</p>';
                        $rmhmtl .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="20%" class="y-report-ui-comp-data-grid-th">IP</th><th width="30%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">' . Yii::t('app', '用户名') . '</th><th width="30%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '密码') . '</th><th width="20%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '弱密码类型') . '</th></tr>';

                        $rmwhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现弱密码') . '<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>' . Yii::t('app', '个。') . Yii::t('app', '影响主机') . '<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>' . Yii::t('app', '个。') . '</p>';
                        $rmwhmtl .= '<table cellpadding="0" style="'.$styleTemp.';font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody><tr><td width="20%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">IP</td><td width="30%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">' . Yii::t('app', '用户名') . '</td><td width="30%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">' . Yii::t('app', '密码') . '</td><td width="20%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">' . Yii::t('app', '弱密码类型') . '</td></tr>';
                        foreach ($mmlist as $m => $n) {
                            $rmhmtl .= '<span id="record-show"><tr class="y-report-ui-comp-data-grid-tr-' . ($m % 2) . '"><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-ip">' .'<a href="javascript:" >'. $n['ip'] . '</a>' . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-user">' . $n['username'] . '</td><td id="rmm-password">' . $n['password'] . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-type">' . $n['vul_name'] . '</td></tr>';
                            $rmwhmtl .= '<tr style="height:25px;text-align:center;background-color:' . $kcolor[($m % 2)] . '"><td>' . $n['ip'] . '</td><td style="padding-right:1em !important;padding-left:1em !important;text-align:left">' . $n['username'] . '</td><td>' . $n['password'] . '</td><td>' . $n['vul_name'] . '</td></tr></span>';
                        }
                        $rmhmtl .= '</tbody></table></div>';
                        $rmwhmtl .= '</tbody></table>';
                    }
                    $content = str_replace('{$rmmlist}', $rmhmtl, $content);
                    if ($rt == 'doc') {
                        $wordcon = str_replace('{$rmmlist}', $rmwhmtl, $wordcon);
                    }
                    $new_content.=$content."\r\n";

                    if($rt=='doc') {
                        $new_image_content.=$imageCon."\r\n";
                        $new_word_content .=$wordcon."\r\n";
                    }
                    //echo $content;die;
                }elseif($_POST['type']=='web'){
                    $sql = "SELECT * FROM task_manage WHERE id='$tasks'";
                    $t_sql = "SELECT * FROM bd_web_task WHERE task_id='$tasks'";
                    $t_rows = $db->fetch_first($t_sql);
                    $t_target = $t_rows['target'];
                    /*统计域名个数*/
                    if ($t_target) {
                        $domain_s = $db->fetch_all("SELECT domain FROM $tablescan WHERE 1=1 GROUP BY domain");
                        $domainnum = count($domain_s);
                    } else {
                        $domainnum = 0;
                    }
                    if($domainnum == 0){
                        $content = str_replace('{$domainnum}', '', $content);
                    }else{
                        $content = str_replace('{$domainnum}', Yii::t('app', '域名') .$domainnum. Yii::t('app', '个，'), $content);
                    }
                    /*web高风险总数*/
                    $web_sum = $db->result_first("SELECT COUNT(1) AS wnum FROM $tablescan WHERE vul_level!=''");
                    $web_high_sum = $db->result_first("SELECT COUNT(1) AS mnum FROM $tablescan WHERE `vul_level`='H'");
                    $web_domain = $db->fetch_all("SELECT domain FROM $tablescan WHERE `vul_level`='H' GROUP BY domain");

                    /*4.3 WEB漏洞列表*/
                    $wdhmtl = $wdwhmtl = '';
                    $webclass = $db->fetch_all("SELECT id,`name` FROM bd_web_family WHERE parent_id!='0' ORDER BY id ASC");
                    $webii = 0;
                    foreach ($webclass as $c => $w) {
                        //$classTemp = !in_array($w['desc'],$templateConfArr)?'displayNone':'';
                        //$styleTemp = !in_array($w['desc'],$templateConfArr)?'display:none':'';
                        if(!in_array($w['name'],$templateConfArr)){
                            $classTemp = 'displayNone';
                            $styleTemp = 'display:none';
                        }else{
                            $classTemp = '';
                            $styleTemp = '';
                            $webii++;
                        }
                        $wdhmtl .= '<div name="webname" id="y-section-index-root-4-3-' . ($c + 1) . '"><div class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$webIndex.'.' . ($webii) . ' ' . $w['name'] . '</div>';
                        $wdwhmtl .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$webIndex.'.' . ($webii) . ' ' . $w['name'] . '</p>';
                        $whnum = $db->result_first("SELECT count(1) as cnum FROM $tablescan sc, bd_web_vul_lib wlist WHERE sc.vul_id=wlist.vul_id AND wlist.family_id={$w['id']} AND sc.`vul_level`!=''");
                        $whnum = $whnum > 0 ? $whnum : 0;
                        //var_dump($whnum);
                        if ($whnum == 0) {
                            $wdhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                            $wdwhmtl .= '<p style="line-height:20px;text-indent:4em;width:100%;'.$styleTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                        } else {
                            $wdhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现该风险') . '<span class="y-report-ui-text-normal-b"> ' . $whnum . ' </span>' . Yii::t('app', '个。') . '</p>';
                            $wdhmtl .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '风险评级') . '</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">' . Yii::t('app', '风险名称') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '影响URL数') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '更多信息') . '</th></tr>';

                            $wdwhmtl .= '<p style="line-height:20px;text-indent:4em;width:100%;'.$styleTemp.'">' . Yii::t('app', '本次扫描共发现该风险') . '<span style="color:#000000;font-weight:bold"> ' . $whnum . ' </span>' . Yii::t('app', '个。') . '</p>';
                            $wdwhmtl .= '<table cellpadding="0" style="'.$styleTemp.';font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                            $sql = "SELECT sc.url,sc.vul_type,sc.vul_id,sc.vul_level,sc.vul_name,sc.description,sc.solution,count(1) as urlsum FROM $tablescan sc, bd_web_vul_lib wlist WHERE sc.vul_id=wlist.vul_id AND wlist.family_id={$w['id']} GROUP BY sc.vul_id ORDER BY sc.vul_level DESC";
                            $wmyrows = $db->fetch_all($sql);
                            //var_dump($wmyrows);
                            foreach ($wmyrows as $k => $v) {
                                $jieb = $web_factor["{$v['vul_level']}"];
                                $rcss = $web_css["{$v['vul_level']}"];
                                $rcor = $web_color["{$v['vul_level']}"];
                                $wdhmtl .= '<tr><td colspan="4"><span id="recordweb-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
                                $wdhmtl .= '<tr class="y-report-ui-comp-data-grid-tr-' . ($k % 2) . '"><td class="y-report-ui-text-level-' . $rcss . '-b" id="webfxjb">' . $jieb . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="webfxname">' . $v['vul_name'] . '</td><td id="weburlnum">' . $v['urlsum'] . '</td><td special="openDetail" class="y-report-ui-element-more-info-link">' . Yii::t('app', '展开详情') . '</td></tr>';
                                $wdwhmtl .= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:' . $rcor . '">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div style="clear:both"></div></div>';
                                $wdwhmtl .= '<div><div><div style="position:relative;">';
                                $wdwhmtl .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                                $wdwhmtl .= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '影响URL数') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['urlsum'] . '</td></tr>';

                                $wdhmtl .= '<tr style="display:none"><td colspan="4" style="opacity:0;-ms-filter:\'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)\';filter:alpha(opacity=0);-webkit-opacity:0;-moz-opacity:0;-khtml-opacity:0"><div class="y-report-ui-object-expandable-grid-detail-panel"><div class="y-report-ui-object-expandable-grid-detail-panel-header-frame"><div class="y-report-ui-object-expandable-grid-detail-panel-header-title"> [ <span class="y-report-ui-text-level-' . $rcss . '-b">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div class="y-report-ui-object-expandable-grid-detail-panel-header-close" special="closeDetail">' . Yii::t('app', '关闭') . '</div><div style="clear:both"></div></div><div class="y-report-ui-object-expandable-grid-detail-panel-content-frame"><div class="y-report-ui-object-tab-panel-frame" special="objectType#tabPanel"><div style="position:relative;" class="y-report-ui-object-tab-panel-header-frame"><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button-toggled">' . Yii::t('app', 'URL列表（共') . $v['urlsum'] . Yii::t('app', '项）') . '</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">' . Yii::t('app', '风险描述') . '</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">' . Yii::t('app', '解决方案') . '</div><div style="clear:both"></div></div><div class="y-report-ui-object-tab-panel-content-frame"><div style="float:left" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-accordion-list-frame" special="objectType#accordionList">';
                                $wdwhmtl .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', 'URL列表（共') . $v['urlsum'] . Yii::t('app', '项）') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">';
                                $urllist = $db->fetch_all("SELECT url FROM $tablescan WHERE vul_id='{$v['vul_id']}'");
                                foreach ($urllist as $l => $u) {
                                    $wdhmtl .= '<div class="y-report-ui-object-accordion-list-item-frame" id="web_urllist"><div class="y-report-ui-object-accordion-list-item-header">' . $u['url'] . '</div></div>';
                                    $wdwhmtl .= $u['url'] . '<br />';
                                }
                                $wdwhmtl .= '</td></tr>';
                                $wdwhmtl .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '风险描述') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['description'] . '</td></tr>';
                                $wdwhmtl .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '解决方案') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['solution'] . '</td></tr>';
                                $wdwhmtl .= '</tbody></table>';
                                $wdwhmtl .= '</div></div></div>';
                                $wdwhmtl .= '</td></tr>';

                                $wdhmtl .= '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="web_fxms">' . $v['description'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="web_jjfa">' . $v['solution'] . '</div></div></div></div></div></div></td></tr>';
                                $wdhmtl .= '</tbody></table></span></td></tr>';
                            }
                            $wdhmtl .= '</tbody></table></div>';
                            $wdwhmtl .= '</tbody></table>';
                        }
                        $wdhmtl .= '</div>';
                    }

                    if ($rt == 'html') {
                        $content = str_replace('{$weblist}', $wdhmtl, $content);
                    } elseif ($rt == 'pdf') {
                        $content = str_replace('{$weblist}', $wdwhmtl, $content);
                    }
                    if ($rt == 'doc') {
                        $wordcon = str_replace('{$weblist}', $wdwhmtl, $wordcon);
                    }
                }elseif($_POST['type']=='host'){
                    $content = file_get_contents($dym . '/attack_host.html');
                    $hostnum = $domainnum = 0;
                    /*统计主机个数*/
                    $hostnum=\Yii::$app->db->createCommand("select count(DISTINCT ip) from bd_host_result_$tasks")->queryColumn()[0];
                    
                    $content = str_replace('{$hostnum}', Yii::t('app', '主机') . $hostnum. Yii::t('app', '个，'), $content);

                    /*主机风险总数*/
                    $zj = $db->result_first("SELECT COUNT(1) AS mnum FROM $tablevul WHERE vul_level!=''");
                    $zjsum = $db->fetch_all("SELECT id FROM $tablevul WHERE vul_level ='C' OR vul_level ='H' group by ip");
                    if(count($zjsum) == 0){
                        $content = str_replace('{$zjhighsum}', '', $content);
                    }else{
                        $content = str_replace('{$zjhighsum}', '所有主机中包含有高风险漏洞的主机数量为 '.count($zjsum).' 个，', $content);
                    }
                    if(number_format(((count($zjsum) / $hostnum) * 100), 2, '.', '') == 0){
                        $content = str_replace('{$zjhight}','', $content);
                    }else{
                        $content = str_replace('{$zjhight}', '占主机总数 '.number_format(((count($zjsum) / $hostnum) * 100), 2, '.', '').'%。', $content);
                    }

                    /*主机漏洞分布检测*/
                    $data5 = $data6 = $data8 = $data9 = $topid = $ldcnum = $ldhnum = $ldmnum = $ldlnum = $ldinum = array();
                    $cats = $db->fetch_all("SELECT * FROM bd_host_family_list WHERE parent_id='0' ORDER BY id ASC");
                    foreach ($cats as $k => $v) {//根据类别读取
                       // $num = $db->result_first("SELECT COUNT(1) AS num FROM $tablevul vul, host_family_ref hfr WHERE vul.vul_id=hfr.vul_id AND vinfo.family_id IN (SELECT id FROM host_family_list WHERE parent_id='$v[id]')");
                        $num = $db->result_first("SELECT COUNT(1) AS num FROM $tablevul vul ");

                        $num = $num > 0 ? $num : 0;
                        $data5[] = '{name:"' . $v['desc'] . '",value:' . $num . ',color:setColor[' . $k . ']}';
                    }
                    $content = str_replace('{$data5}', join(',', $data5), $content);
                    $imageCon = str_replace('{$data5}', join(',', $data5), $imageCon);
        //var_dump($content,$imageCon);die;
                    /*TOP10危险IP所有漏洞统计图*/
                    //$ipld = $db->fetch_all("SELECT ip FROM $tablevul GROUP BY ip DESC LIMIT 10");
                    $ipld = $db->fetch_all("
        select ip from
            (select ip ,sum(case when vul_level='H' then 1 else 0 end ) as hnum ,
            sum(case when vul_level='M' then 1 else 0 end ) as mnum ,
            sum(case when vul_level='L' then 1 else 0 end ) as lnum ,
            sum(case when vul_level='I' then 1 else 0 end ) as inum
            from $tablevul
            group by ip) as iptem
            ORDER BY hnum DESC, mnum desc,lnum DESC limit 10
    ");
                    foreach ($ipld as $k => $v) {
                        array_push($topid, "'" . $v['ip'] . "'");
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计紧急
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='C' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldcnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计高风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='H' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldhnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计中风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='M' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldmnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计低风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='L' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $tum_i = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum_i = $tum_i > 0 ? $tum_i : 0;
                        $ldlnum[] = $tum+$tum_i;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计信息
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldinum[] = $tum;
                    }
                    // $data7 = '{name:"高风险",value:[' . join(',', $ldhnum) . '],color:"#ffa500"},{name:"中风险",value:[' . join(',', $ldmnum) . '],color:"#f737ec"},{name:"低风险",value:[' . join(',', $ldlnum) . '],color:"#6060fe"}';
                    $data7 = '{name:"' . Yii::t('app', '高风险') . '",value:[' . join(',', $ldhnum) . '],color:"#ffa500"},{name:"' . Yii::t('app', '中风险') . '",value:[' . join(',', $ldmnum) . '],color:"#f737ec"},{name:"' . Yii::t('app', '低风险') . '",value:[' . join(',', $ldlnum) . '],color:"#6060fe"}';
                    $content = str_replace('{$dataip}', join(',', $topid), $content);
                    $content = str_replace('{$data7}', $data7, $content);
                    $imageCon = str_replace('{$dataip}', join(',', $topid), $imageCon);
                    $imageCon = str_replace('{$data7}', $data7, $imageCon);
                    /*4.3 主机漏洞列表*/
                    $html = $whtml = '';
                    $hflist = $db->fetch_all("SELECT id,`description` FROM bd_host_family_list WHERE parent_id='0' ORDER BY id ASC");
                    $hosti = 0;
                    foreach ($hflist as $h => $f) {
                        //$classTemp = !in_array($f['desc'],$templateConfArr)?'displayNone':'';
                        //$styleTemp = !in_array($f['desc'],$templateConfArr)?'display:none':'';
                        if(!in_array($f['description'],$templateConfArr)){
                            $classTemp = 'displayNone';
                            $styleTemp = 'display:none';
                        }else{
                            $classTemp = '';
                            $styleTemp = '';
                            $hosti++;
                        }
                        $html .= '<div name="hostname" id="y-section-index-root-4-3-' . ($h + 1) . '"><div class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$hostIndex.'.' . ($hosti) . ' ' . $f['desc'] . '</div>';
                        $whtml .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$hostIndex.'.' . ($hosti) . ' ' . $f['desc'] . '</p>';
                        $cums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul,  bd_host_vul_lib vinfo WHERE  vul.vul_id=vinfo.vul_id AND vul.vul_level='C' AND vinfo.family_id IN (SELECT id FROM bd_host_family_list WHERE parent_id={$f['id']})");
                        $cums = $cums > 0 ? $cums : 0;
                        $hums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul,  bd_host_vul_lib vinfo WHERE  vul.vul_id=vinfo.vul_id AND vul.vul_level='H' AND vinfo.family_id IN (SELECT id FROM bd_host_family_list WHERE parent_id={$f['id']})");
                        $hums = $hums > 0 ? $hums : 0;
                        $mums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul,  bd_host_vul_lib vinfo WHERE  vul.vul_id=vinfo.vul_id AND vul.vul_level='M' AND vinfo.family_id IN (SELECT id FROM bd_host_family_list WHERE parent_id={$f['id']})");
                        $mums = $mums > 0 ? $mums : 0;
                        $lums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul,  bd_host_vul_lib vinfo WHERE  vul.vul_id=vinfo.vul_id AND vul.vul_level='L' AND vinfo.family_id IN (SELECT id FROM bd_host_family_list WHERE parent_id={$f['id']})");
                        $lums = $lums > 0 ? $lums : 0;
                        $iums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul,  bd_host_vul_lib vinfo WHERE  vul.vul_id=vinfo.vul_id AND vul.vul_level='I' AND vinfo.family_id IN (SELECT id FROM bd_host_family_list WHERE parent_id={$f['id']})");
                        $iums = $iums > 0 ? $iums : 0;
                        $zfxs = $cums + $hums + $mums + $lums + $iums;
                        if ($zfxs == 0) {
                            $html .= '<p class="y-report-ui-element-content  '.$classTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                            $whtml .= '<p style="line-height:20px;text-indent:4em;width:100%;'.$styleTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                        } else {
                            $html .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现该风险') . '<span class="y-report-ui-text-normal-b"> ' . ($cums + $hums + $mums + $lums + $iums) . ' </span>' . Yii::t('app', '个。') . '</p>';
                            // $html .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th><th width="10%" class="y-report-ui-comp-data-grid-th">影响主机数</th><th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';
                            $html .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '风险评级') . '</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">' . Yii::t('app', '风险名称') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '影响主机数') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '更多信息') . '</th></tr>';

                            $whtml .= '<p style="line-height:20px;text-indent:4em;width:100%;'.$styleTemp.'">' . Yii::t('app', '本次扫描共发现该风险') . '<span style="color:#000000;font-weight:bold"> ' . ($cums + $hums + $mums + $lums + $iums) . ' </span>' . Yii::t('app', '个。') . '</p>';

//                            $whtml .= '<table cellpadding="0" style="'.$styleTemp.';font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
//                            $sql = "SELECT vul.solution, vul.ip,vul.vul_id,vul.vul_level,vul.port_proto,vinfo.cve,vinfo.cnvd,vinfo.cnnvd,vinfo.vul_name_cn,vinfo.desc_cn,vinfo.ref_cn,count(1) as ipsum FROM $tablevul vul,  bd_host_vul_lib vinfo WHERE vul.vul_level!='' AND vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND vinfo.family_id IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']}) group by vul_id order by vul_level_num ASC";
//                            $myrows = $db->fetch_all($sql);
//                            foreach ($myrows as $k => $v) {
//                                $jieb = $vul_level["{$v['vul_level']}"];
//                                $rcss = $risk_css["{$v['vul_level']}"];
//                                $rcor = $rcccolor["{$v['vul_level']}"];
//                                $html .= '<tr><td colspan="4"><span id="record-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
//                                $html .= '<tr class="y-report-ui-comp-data-grid-tr-' . ($k % 2) . '"><td class="y-report-ui-text-level-' . $rcss . '-b"><div id="leaklevel">' . $jieb . '</div></td><td class="y-report-ui-comp-data-grid-td-text-align-left"><div id="leakname">' . $v['vul_name_cn'] . '</div></td><td>' . $v['ipsum'] . '</td><td special="openDetail" class="y-report-ui-element-more-info-link">展开详情</td></tr>';
//                                $whtml .= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:' . $rcor . '">' . $jieb . '</span> ] ' . $v['vul_name_cn'] . '</div><div style="clear:both"></div></div>';
//                                $whtml .= '<div><div><div style="position:relative;">';
//                                $whtml .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
//                                $whtml .= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">影响主机数</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['ipsum'] . '</td></tr>';
//                                $html .= '<tr style="display:none"><td colspan="4" style="opacity:0;-ms-filter:\'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)\';filter:alpha(opacity=0);-webkit-opacity:0;-moz-opacity:0;-khtml-opacity:0"><div class="y-report-ui-object-expandable-grid-detail-panel"><div class="y-report-ui-object-expandable-grid-detail-panel-header-frame"><div class="y-report-ui-object-expandable-grid-detail-panel-header-title"> [ <span class="y-report-ui-text-level-' . $rcss . '-b">' . $jieb . '</span> ] ' . $v['vul_name_cn'] . '</div><div class="y-report-ui-object-expandable-grid-detail-panel-header-close" special="closeDetail">关闭</div><div style="clear:both"></div></div><div class="y-report-ui-object-expandable-grid-detail-panel-content-frame"><div class="y-report-ui-object-tab-panel-frame" special="objectType#tabPanel"><div style="position:relative;" class="y-report-ui-object-tab-panel-header-frame"><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button-toggled">主机列表（共' . $v['ipsum'] . '项）</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">风险描述</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">解决方案</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">相关编号</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">参考信息</div><div style="clear:both"></div></div><div class="y-report-ui-object-tab-panel-content-frame"><div style="float:left" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-accordion-list-frame" special="objectType#accordionList">';
//
//                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">主机列表（共' . $v['ipsum'] . '项）</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">';
//                                $louat = $db->fetch_all("SELECT ip,output,port,proto FROM $tablevul WHERE vul_id='{$v['vul_id']}'");
//                                foreach ($louat as $i => $l) {
//                                    if($kidbb == 1 && $hostnum > 1){//选择了生成子报表，并且扫描IP大于1个
//                                        $html .= '<div class="y-report-ui-object-accordion-list-item-frame"><div class="y-report-ui-object-accordion-list-item-header">' ."<a href='./".$l['ip'].".html' target='_blank'>". $l['ip'] . ' [ ' . $l['proto'] . ' / ' . $l['port'] . ' ]</a></div><div class="y-report-ui-object-accordion-list-item-content"><div class="y-report-ui-object-accordion-list-item-content-text-container">输出详情：<br />' . htmlspecialchars($l['output']) . '</div></div></div>';
//                                    }else{
//                                        $html .= '<div class="y-report-ui-object-accordion-list-item-frame"><div class="y-report-ui-object-accordion-list-item-header">' . $l['ip'] . ' [ ' . $l['proto'] . ' / ' . $l['port'] . ' ]</div><div class="y-report-ui-object-accordion-list-item-content"><div class="y-report-ui-object-accordion-list-item-content-text-container">输出详情：<br />' . htmlspecialchars($l['output']) . '</div></div></div>';
//                                    }
//                                    $whtml .= $l['ip'] . ' [ ' . $l['proto'] . ' / ' . $l['port'] . ' ] <br />';
//                                }
//                                $whtml .= '</td></tr>';
//                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">风险描述</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['desc_cn'] . '</td></tr>';
//                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">解决方案</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['solution'] . '</td></tr>';
//                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">相关编号</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">CVE：' . $v['cve'] . '<br />CNVD：' . $v['cnvd'] . '<br />CNNVD：' . $v['cnnvd'] . '</td></tr>';
//                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">参考信息</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['ref_cn'] . '</td></tr>';
//                                $whtml .= '</tbody></table>';
//                                $whtml .= '</div></div></div>';
//                                $whtml .= '</td></tr>';
//
//                                $html .= '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="desc_cn">' . $v['desc_cn'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="solu_cn">' . $v['solution'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="host_xgbh"><table cellpadding="0" cellspacing="0" width="100%"><tbody><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" width="7%" style="vertical-align:top">CVE</th><td width="5px" style="vertical-align:top"> : </td><td id="cve-val">' . $v['cve'] . '</td></tr><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" style="vertical-align:top">CNVD</th><td width="5px" style="vertical-align:top"> : </td><td>' . $v['cnvd'] . '</td></tr><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" style="vertical-align:top">CNNVD</th><td width="5px" style="vertical-align:top"> : </td><td>' . $v['cnnvd'] . '</td></tr></tbody></table></div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="ref_cn">' . $v['ref_cn'] . '</div></div></div></div></div></div></td></tr>';
//                                $html .= '</tbody></table></span></td></tr>';
//                            }
//                            $html .= '</tbody></table></div>';
//                            $whtml .= '</tbody></table>';
                        }
                        $html .= '</div>';
                    }
                    if ($rt == 'html') {
                        $content = str_replace('{$webld}', $html, $content);
                    } elseif ($rt == 'pdf') {
                        $content = str_replace('{$webld}', $whtml, $content);
                    }
                    if ($rt == 'doc') {
                        $wordcon = str_replace('{$webld}', $whtml, $wordcon);
                    }
                }


                $content = str_replace('{$bbtitle}', $bbtitle, $content);
                $content = str_replace('{$reportname}', $bbname, $content);
                $content = str_replace('{$date}', date('Y-m-d H:i:s', time()), $content);
                $content = str_replace('{$reportid}', "BD-REPORT-" . $tasks, $content);
                $content = str_replace('{$tasksname}', $main_tasks['name'], $content);
                $content = str_replace('{$task_id}', $val, $content); //修改任务id
                $content = str_replace('{$bbname}', Yii::t('app', '蓝盾安全扫描系统'), $content);

                $content = str_replace('{$piezjld}', $zj, $content);
                $content = str_replace('{$webldsum}', $web_sum, $content);
                $content = str_replace('{$rumsum}', $rmm, $content);
                $content = str_replace('{$alertbf}', number_format((($rows['h'] / $resum) * 100), 2, '.', ''), $content);
                $content = str_replace('{$critbf}', number_format((($rows['m'] / $resum) * 100), 2, '.', ''), $content);
                $content = str_replace('{$errorbf}', number_format(((($rows['l']+$rows['i']) / $resum) * 100), 2, '.', ''), $content);
                $content = str_replace('{$alert}', $rows['h'] ? $rows['h'] : 0, $content);
                $content = str_replace('{$crit}', $rows['m'] ? $rows['m'] : 0, $content);
                $content = str_replace('{$error}', ($rows['l']+$rows['i']) ? ($rows['l']+$rows['i']) : 0, $content);

                $content = str_replace('{$zhujitb}', number_format((($zj / $resum) * 100), 2, '.', ''), $content);
                $content = str_replace('{$webtb}', number_format((($web_sum / $resum) * 100), 2, '.', ''), $content);
                $content = str_replace('{$rmmtb}', number_format((($rmm / $resum) * 100), 2, '.', ''), $content);

            }
//            var_dump($content);die;
            $name=$bbname. Yii::t('app', '的安全评估');
            $now=date('YmdHis',time());
            if($rt=='html') {
                file_put_contents($dir . '/'.$bbname.'-'.$now . ".html", $content, LOCK_EX);

                $mom = $bbname . $theTime;
                $path = "now/" . $bbname .$now. ".zip";
                $exec = "cd /basic/web/report/now; zip -q -r -j ./" . $bbname .'-'. $now.'-html'.".zip ./$bbname-$now.html ../common.js ../common.css ../jquery-1.9.1.min.js ../bluechar.js";
//echo $exec;die;
                exec($exec);
                unlink(REPORT_DIR . '/now/'.$bbname.'-'.$now . ".html");
                unlink(REPORT_DIR . "./now/common.css");
                unlink(REPORT_DIR . "./now/common.js");
                unlink(REPORT_DIR . "./now/jquery-1.9.1.min.js");
                unlink(REPORT_DIR . "./now/bluechar.js");
                $data['down'] = "<a href=\"/report/now/" . $bbname.'-'.$now.'-html' . ".zip\" id=\"downAll\" target=\"_self\"" . "><span>" . Yii::t('app', '下载') . $name . "</span></a>";
            }elseif($rt=='pdf'){
                file_put_contents($dir . '/'.$bbname.'-'.$now . ".html", $new_content, LOCK_EX);
                $html_filename=$bbname.'-'.$now.'.html';
                //html转换成pdf
                $pdf=$bbname.'-'.$now.'.pdf';
               // echo "cd /basic/web/report/now; /nginx/wkhtmltox/bin/wkhtmltopdf ./$html_filename ./$pdf";die;
                exec("cd /basic/web/report/now; chmod 777 /nginx/wkhtmltox -R; /nginx/wkhtmltox/bin/wkhtmltopdf ./$html_filename ./$pdf");
                $exec = "cd /basic/web/report/now; zip -q -r -j ./" . $bbname .'-'.$now.'-pdf'. ".zip ./$pdf ";
                exec($exec);
                //删掉pdf
                unlink(REPORT_DIR . "./now/{$pdf}");
                unlink(REPORT_DIR . "./now/attack-" . $tasks . ".html");
                unlink(REPORT_DIR . "./now/common.css");
                unlink(REPORT_DIR . "./now/common.js");
                unlink(REPORT_DIR . "./now/jquery-1.9.1.min.js");
                unlink(REPORT_DIR . "./now/bluechar.js");
                $data['down'] = "<a href=\"/report/now/" . $bbname.'-'.$now .'-pdf'. ".zip\" id=\"downAll\" target=\"_self\"" . "><span>" . Yii::t('app', '下载') . $name . "</span></a>";

            }

            $db->query("INSERT INTO " . getTable('reportsmanage') . " (`name`,`type`,`desc`,`time`,`path`,`timetype`,`format`) VALUES ('$name','1','$desc','$timestamp','$path','1','$rt')");

            $data['success'] = true;
            $data['message'] = Yii::t('app', '操作成功');
            if($kidbb == 1 && $hostnum >1){
                $data['mom'] = $mom;
            }
            $hdata['sDes'] = Yii::t('app', '导出报表');
            $hdata['sRs'] = Yii::t('app', '导出成功');
            $hdata['sAct'] = $act.'/'.$show;
            saveOperationLog($hdata);
            echo json_encode($data);
            exit;
        }else{
            $total=0;
            $report = $db->fetch_all("SELECT `id`,`name` FROM template_report");
            $data['modules'] = $report;
            //
            $where = " WHERE 1=1";
            if($_GET['s']=='weakpwd'){
               $table='bd_weakpwd_task_manage';
            }elseif($_GET['s']=='web'){
                $table='bd_web_task_manage';
            }elseif($_GET['s']=='host'){
                $table='bd_host_task_manage';
            }
            $rows  = $db->fetch_all("SELECT id,name as task_name FROM $table $where order by id desc");
           // var_dump($rows);die;
            $data['rows'] = $rows;
            $data['Total'] = $total;

            //userLog('system',3,6,'查看');
           // return $this->render('bbgl',['data'=>$data]);
            template('bbgl/bbgl', $data);
        }
    }

    //获取数组某一列的值的总和
    function getSum($arr=array(), $col=''){
        $count = 0;
        if(empty($arr) || $col=='') return $count;

        foreach($arr as $v){
            $count += $v[$col];
        }
        return $count;
    }

    function actionTest(){
        $mpdf=new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_CORE,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => 'fsdfs',
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:18px}',
             // set mPDF properties on the fly
            'options' => ['title' => 'Krajee Report Title'],
             // call mPDF methods on the fly
            'methods' => [
                'SetHeader'=>['Krajee Report Header'],
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        $mpdf->render('fsdf');

        $mpdf->Output();
        exit;
    }

}

?>
