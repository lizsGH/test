<?php

namespace  app\controllers;
use app\components\client_db;

/**
 * 子报表
 * Class BbglController
 * @package app\controllers
 * author hjf
 */
class BbglkidController extends BaseController
{
    public function actionIndex()
    {
        $data = array();
        $post = isset($_POST['post']) ? intval($_POST['post']) : 0;
        $risk_factor = array(
            'H' => Yii::t('app', '高风险'),
            'M' => Yii::t('app', '中风险'),
            'L' => Yii::t('app', '低风险'),
            'I' => Yii::t('app', '低风险'),
        );
        $risk_css = array( 'H'=>'high', 'M'=>'medium', 'L'=>'low', 'I'=>'low');
        $rcccolor = array( 'H'=>'#d2322d', 'M'=>'#d58512', 'L'=>'#3276b1', 'I'=>'#3276b1');

        $web_factor = $risk_factor;
        $web_css = array( 'H'=>'high', 'M'=>'medium', 'L'=>'low', 'I'=>'low');
        $web_color = array( 'H'=>'#d2322d', 'M'=>'#d58512', 'L'=>'#3276b1' ,'I'=>'#3276b1');
        $kcolor = array('0'=>'#EEEEEE', '1'=>'#D4E3F6');
        if($post == 1){
            $data  = array('success'=>false, 'message'=> Yii::t('app', '操作失败'), 'down'=>'');
            //$tasks  = intval($_POST['tasks']);//这里$_POST['tasks']是个数组
            $template_conf = array(
                //'name': '模板名',
                'overview' => Yii::t('app', '综述'),
                'risk' => Yii::t('app','总体风险分析'),
                'risk_lever' => Yii::t('app', '风险等级分布'),
                'risk_type' => Yii::t('app', '风险类型分布'),
                'risk_host' => Yii::t('app', '所有主机（IP）风险分布'),
                'vul_host' => Yii::t('app','主机漏洞列表'),
                'vul_host_system' => Yii::t('app', '系统漏洞'),
                'vul_host_server' => Yii::t('app', '服务漏洞'),
                'vul_host_application' => Yii::t('app', '应用漏洞'),
                'vul_host_device' => Yii::t('app', '网络设备漏洞'),
                'vul_host_database' => Yii::t('app', '数据库漏洞'),
                'vul_host_virtual' => Yii::t('app','虚拟化平台漏洞'),
                'vul_web' => Yii::t('app', 'WEB漏洞列表'),
                'vul_web_sql' => Yii::t('app', 'SQL注入'),
                'vul_web_info' => Yii::t('app', '信息泄露'),
                'vul_web_content' => Yii::t('app', '内容电子欺骗'),
                'vul_web_script' => Yii::t('app', '跨站脚本攻击'),
                'vul_web_syscmd' => Yii::t('app', '系统命令执行'),
                'vul_web_dir' => Yii::t('app', '目录遍历'),
                'vul_web_resource' => Yii::t('app', '资源位置可预测'),
                'vul_web_link' => Yii::t('app', '外链信息'),
                'vul_web_config' => Yii::t('app','配置不当'),
                'vul_web_password' => Yii::t('app', '弱密码'),
                'vul_web_access' => Yii::t('app',  '越权访问'),
                'vul_web_logical' => Yii::t('app', '逻辑错误'),
                'vul_web_deny' => Yii::t('app', '拒绝服务'),
                'risk_pwd' => Yii::t('app', '弱密码漏洞列表'),
            );

            $rt           = filterStr($_POST['rt']);
            $desc         = filterStr($_POST['desc']);
            /*$epilog       = filterStr($_POST['epilog']);*/
            $mom       = filterStr($_POST['mom']).'.zip';
            $template_report       = intval($_POST['template_report']);

            //组装需要隐藏的栏目数组
            $templateConfArr = array();
            $temRes = $db->fetch_first("SELECT * FROM template_report WHERE id=$template_report");
            foreach ($temRes as $k =>$v){
                if($k != 'id' && $k != 'name' && $v == 1 ){
                    $templateConfArr[] = $template_conf["$k"];
                }
            }
            if(empty($_POST['tasks'])){
                $data['message'] = Yii::t('app', '请选择任务.');
                echo json_encode($data);
                exit;
            }

            $theTime = '-'.date('Y-m-d_H:i:s',time());
            foreach ($_POST['tasks'] as $key=>$val) {
                $tasks = intval($val);

                $sql = "SELECT * FROM task_manage WHERE id='$tasks'";
                $t_sql = "SELECT * FROM bd_web_task WHERE task_id='$tasks'";
                $t_rows = $db->fetch_first($t_sql);
                $t_target = $t_rows['target'];
                $rows = $db->fetch_first($sql);

                //判断主机/web/弱密码哪个开启
                $s_zj = 0;
                $s_web = 0;
                $s_rmm = 0;
                $ipArr = array();
                if($rows['host_enable'] == 1 ){
                    $s_zj = 1;
                    $ip_vul = $db->fetch_all("SELECT DISTINCT ip from vul_details_$tasks");
                    foreach ($ip_vul as $v){
                        array_push($ipArr,$v['ip']);
                    }
                }
                if($rows['web_enable'] == 1){
                    $s_web = 1;
                    $ip_web = $db->fetch_all("SELECT DISTINCT ip from bd_web_result_$tasks");
                    foreach ($ip_web as $v){
                        array_push($ipArr,$v['ip']);
                    }
                }
                if($rows['weak_enable'] == 1){
                    $s_rmm = 1;
                    $ip_weak = $db->fetch_all("SELECT DISTINCT ip from weak_pwd_details_$tasks");
                    foreach ($ip_weak as $v){
                        array_push($ipArr,$v['ip']);
                    }
                }
                $ipArr = array_unique($ipArr);
                foreach ($ipArr as $ipkey => $ipvalue) {

                    $bbname = $ipvalue;
                    $tablevul = 'vul_details_' . $tasks;
                    $tablepwd = 'weak_pwd_details_' . $tasks;
                    $tablescan = 'bd_web_result_' . $tasks;
                    $exist = $db->result_first("SHOW TABLES FROM security LIKE '%{$tablevul}%'");
                    if (!$exist) {
                        $db->result_first("CREATE TABLE IF NOT EXISTS $tablevul (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(32) NOT NULL DEFAULT '',
  `vul_id` int(11) NOT NULL DEFAULT '0',
  `cve` varchar(512) DEFAULT NULL,
  `cnvd` varchar(512) DEFAULT NULL,
  `cnnvd` varchar(512) DEFAULT NULL,
  `risk_factor` varchar(32) DEFAULT NULL,
  `vul_name` varchar(512) DEFAULT NULL,
  `desc` varchar(4096) DEFAULT NULL,
  `solution` varchar(4096) DEFAULT NULL,
  `ref` varchar(4096) DEFAULT NULL,
  `output` text,
  `family` varchar(128) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `proto` varchar(32) DEFAULT NULL,
  `metasploit` varchar(256) DEFAULT NULL,
  `asset_scan_id` int(11) DEFAULT '0',
  `report` int(11) DEFAULT '0',
  `risk_factor_num` int(11) NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`,`ip`,`vul_id`,`risk_factor_num`),
  KEY `asset_scan_id` (`asset_scan_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

                    }
                    $exist = $db->result_first("SHOW TABLES FROM security LIKE '%{$tablepwd}%'");
                    if (!$exist) {
                        $db->result_first("CREATE TABLE IF NOT EXISTS $tablepwd (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) DEFAULT NULL,
  `taskname` varchar(256) DEFAULT NULL,
  `ip` varchar(32) DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `username` varchar(256) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  `asset_scan_id` int(11) DEFAULT '0',
  `port` varchar(256) DEFAULT NULL,
  `proto` varchar(256) DEFAULT NULL,
  `report` int(11) DEFAULT '0',
  `vul_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_scan_id` (`asset_scan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

                    }
                    $exist = $db->result_first("SHOW TABLES FROM security LIKE '%{$tablescan}%'");
                    if (!$exist) {
                        $db->result_first("CREATE TABLE IF NOT EXISTS $tablescan (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(8192) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `vul_type` varchar(512) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `vul_level` varchar(10) DEFAULT NULL,
  `description` varchar(1024) DEFAULT NULL,
  `vul_id` int(11) DEFAULT NULL,
  `vul_name` varchar(255) DEFAULT '',
  `solution` text,
  PRIMARY KEY (`id`),
  KEY `vul_id` (`vul_id`),
  KEY `vul_level` (`level`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

                    }

                    //CREATE TEMPORARY TABLE IF NOT EXISTS xxx SELECT * FROM vul_details_964
                    $tmp_table_sql = "CREATE TEMPORARY TABLE IF NOT EXISTS kidtmp_vul_details_$tasks SELECT * FROM vul_details_$tasks WHERE ip = '".$ipvalue."'";
                    $ok = $db->result_first($tmp_table_sql);
                    //if($ok){
                    $tablevul = 'kidtmp_vul_details_'.$tasks;
                    // }
                    $tmp_table_sql = "CREATE TEMPORARY TABLE IF NOT EXISTS kidtmp_weak_pwd_details_$tasks SELECT * FROM weak_pwd_details_$tasks WHERE ip = '".$ipvalue."'";
                    $ok2 = $db->result_first($tmp_table_sql);
                    //if($ok2){
                    $tablepwd = 'kidtmp_weak_pwd_details_'.$tasks;
                    //}
                    $tmp_table_sql = "CREATE TEMPORARY TABLE IF NOT EXISTS kidtmp_scan_result_$tasks SELECT * FROM bd_web_result_$tasks WHERE ip = '".$ipvalue."'";
                    $ok3 = $db->result_first($tmp_table_sql);
                    // if($ok3){
                    $tablescan = 'kidtmp_scan_result_'.$tasks;
                    // }
                    //die();
                    //主机风险总数
                    $hostFx = $db->fetch_all("
        select sum(case when risk_factor='H' then 1 else 0 end ) as hnum ,
            sum(case when risk_factor='M' then 1 else 0 end ) as mnum ,
            sum(case when risk_factor='L' then 1 else 0 end ) as lnum ,
            sum(case when risk_factor='I' then 1 else 0 end ) as inum
            from $tablevul
    ");
                    //web风险总数
                    $webFx = $db->fetch_all("
        select sum(case when `vul_level`='H' then 1 else 0 end ) as hnum ,
            sum(case when `vul_level`='M' then 1 else 0 end ) as mnum ,
            sum(case when `vul_level`='L' then 1 else 0 end ) as lnum ,
			sum(case when `vul_level`='I' then 1 else 0 end ) as inum ,
            from $tablescan
    ");
                    //弱密码风险总数
                    $rmmFx = $db->result_first("SELECT COUNT(*) as hnum FROM $tablepwd ");
                    //求总风险
                    $rows['h'] = intval($hostFx[0]['hnum']) + intval($webFx[0]['hnum']) + intval($rmmFx['hnum']);
                    $rows['m'] = intval($hostFx[0]['mnum']) + intval($webFx[0]['mnum']);
                    $rows['l'] = intval($hostFx[0]['lnum']) + intval($webFx[0]['lnum']);
                    $rows['i'] = intval($hostFx[0]['inum']) + intval($webFx[0]['inum']);


                    ignore_user_abort(TRUE);
                    @set_time_limit(300);
                    $date = date("Ymd");
                    $day = date('d');
                    $dym = "/usr/local/nginx/html/report";
                    $dir = "/usr/local/nginx/html/report/now";//报表存放文件夹

                    $dev = $db->result_first("SELECT model FROM " . getTable('devinfo') . " WHERE 1");

                    if (!file_exists($dir)) mkdir($dir, 0777);

                    //$file_name = array();
                    $maxid = $db->result_first("SELECT MAX(id) FROM " . getTable('reportsmanage') . " WHERE 1");
                    $maxid = (!$maxid || $maxid < 1) ? 1 : $maxid + 1;


                    exec("cd /usr/local/nginx/html/report/now;");
                    //exec("cd /usr/local/nginx/html/report/now; ln -s ../common.js common.js; ln -s ../common.css common.css; ln -s ../bluechar.js; ln -s ../jquery-1.9.1.min.js jquery-1.9.1.min.js");


                    //var_dump($s_zj,$s_web,$s_rmm);
                    $targets = explode("##", $rows['target']);
                    $content = '';
                    $content = file_get_contents($dym . '/attack-kid.html');
                    $content = str_replace('{$reportname}', $bbname, $content);
                    $content = str_replace('{$s_zj}', $s_zj, $content);
                    $content = str_replace('{$s_web}', $s_web, $content);
                    $content = str_replace('{$s_rmm}', $s_rmm, $content);
                    $content = str_replace('{$date}', date('Y-m-d H:i:s', time()), $content);
                    $content = str_replace('{$reportid}', "BD-REPORT-" . $tasks, $content);
                    $content = str_replace('{$tasksname}', $rows['task_name'], $content);
                    $content = str_replace('{$desc}', $desc, $content);
                    if ($bbname != null) {
                        $content = str_replace('{$bbname}', $bbname, $content);
                    } else {
                        $content = str_replace('{$bbname}', Yii::t('app', '蓝盾安全扫描系统'), $content);
                    }

                    //$content = str_replace('{$epilog}', $epilog, $content);

                    /*统计主机个数*/
                    $hostnum = $domainnum = 0;
                    if(!empty($targets[0])){
                        $host = explode(",", $targets[1]);
                        foreach ($host as $v) {
                            if (strstr($v, "-")) {
                                $ipd = explode("-", $v);
                                $startip = explode(".", $ipd[0]);
                                $endip = explode(".", $ipd[1]);
                                $hostnum = $hostnum + (intval($endip[3]) - intval($startip[3])) + 1;
                            } else {
                                $hostnum = $hostnum + 1;
                            }
                        }
                    }

                    $content = str_replace('{$hostip}', $ipvalue, $content);
                    //$content = str_replace('{$hostnum}', $hostnum, $content);
                    /*统计域名个数*/
                    if ($t_target) {
                        $domain_s = $db->fetch_all("SELECT domain FROM $tablescan WHERE 1=1 GROUP BY domain");
                        $domainnum = count($domain_s);
                    } else {
                        $domainnum = 0;
                    }
                    $content = str_replace('{$domainnum}', $domainnum, $content);

                    /*主机风险总数*/
                    $zj = $db->result_first("SELECT COUNT(1) AS mnum FROM $tablevul WHERE risk_factor!=''");
                    /*弱密码风险总数*/
                    $rmm = $db->result_first("SELECT COUNT(1) AS mnum FROM $tablepwd");
                    $rmmip = $db->fetch_all("SELECT ip FROM $tablepwd GROUP BY ip");
                    /*web高风险总数*/
                    $web_sum = $db->result_first("SELECT COUNT(1) AS wnum FROM $tablescan WHERE vul_level!=''");
                    $web_high_sum = $db->result_first("SELECT COUNT(1) AS mnum FROM $tablescan WHERE  `vul_level`='H'");
                    $web_domain = $db->fetch_all("SELECT domain FROM $tablescan WHERE  `vul_level`='H' GROUP BY domain");
                    /*风险总数*/
                    $resum = $rows['h'] + $rows['m'] + $rows['l'] + $rows['i'];
                    $higsum = $rows['h'];
                    $content = str_replace('{$fxsum}', $resum, $content);
                    if ($rows['h']) {
                        $jibie = Yii::t('app', '高风险');
                    } else if ($rows['m']) {
                        $jibie = Yii::t('app', '中风险');
                    } else if ($rows['l']+$rows['i']) {
                        $jibie = Yii::t('app', '低风险');
                    } else {
                        $jibie = Yii::t('app', '安全');
                    }


                    $content = str_replace('{$taskjb}', $jibie, $content);
                    $content = str_replace('{$higsum}', $higsum, $content);/*高风险以上漏洞总数*/
                    $content = str_replace('{$zjhigsum}', $higsum - $rmm - $web_high_sum, $content);/*高风险以上主机漏洞总数*/
                    $content = str_replace('{$rmmhigsum}', $rmm, $content);/*高风险以上弱密码漏洞总数*/
                    $content = str_replace('{$webhigsum}', $web_high_sum, $content);/*高风险以上WEB漏洞总数*/
                    $content = str_replace('{$web_domain}', count($web_domain), $content);/*高风险的域名数量*/
                    $content = str_replace('{$web_domain_db}', number_format(((count($web_domain) / $domainnum) * 100), 2, '.', ''), $content);/*高风险的域名占比*/
                    $content = str_replace('{$rmmip}', count($rmmip), $content);/*弱密码影响主机个数*/
                    $content = str_replace('{$zjloudb}', number_format(((($higsum - $rmm - $web_high_sum) / $higsum) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$rmmloudb}', number_format((($rmm / $higsum) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$webloudb}', number_format((($web_high_sum / $higsum) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$zjldb}', number_format(((($higsum - $rmm) / ($resum - $rmm)) * 100), 2, '.', ''), $content);
                    $content = str_replace('{$webldb}', number_format((($web_high_sum / $web_sum) * 100), 2, '.', ''), $content);

                    //$zjsum = $db->fetch_all("SELECT id FROM $tablevul WHERE risk_factor ='C' OR risk_factor ='H' group by ip");
                    //$content = str_replace('{$zjhighsum}', count($zjsum), $content);
                    //$content = str_replace('{$zjhight}', number_format(((count($zjsum) / $hostnum) * 100), 2, '.', ''), $content);

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
                    $file = "attack-" . $tasks . '.html';
                    $pdf = "attack-" . $tasks . ".pdf";

                    $Index = $fxIndex = $hostIndex = $webIndex = $weakIndex = 1;
                    //不在模板配置中的项隐藏 - doc格式暂不支持
                    if(!in_array(Yii::t('app', '综述'),$templateConfArr)){
                        $content = str_replace('{$zongshu}', 'displayNone', $content);
                        //$wordcon = str_replace('{$zongshu}', 'display:none', $wordcon);
                    }else{
                        $Index++;
                    }
                    $content = str_replace('{$zongfxtuIndex}', $Index, $content);
                    if(!in_array(Yii::t('app', '风险等级分布'),$templateConfArr)){
                        $content = str_replace('{$risk21}', 'displayNone', $content);
                        //$wordcon = str_replace('{$risk21}', 'display:none', $wordcon);
                    }else{
                        $content = str_replace('{$r21}', $fxIndex, $content);
                        $fxIndex++;
                    }
                    if(!in_array(Yii::t('app', '风险类型分布'),$templateConfArr)){
                        $content = str_replace('{$risk22}', 'displayNone', $content);
                        //$wordcon = str_replace('{$risk22}', 'display:none', $wordcon);
                    }else{
                        $content = str_replace('{$r22}', $fxIndex, $content);
                        $fxIndex++;
                    }
                    if(!in_array(Yii::t('app', '所有主机（IP）风险分布'),$templateConfArr)){
                        $content = str_replace('{$risk23}', 'displayNone', $content);
                        //$wordcon = str_replace('{$risk23}', 'display:none', $wordcon);
                    }else{
                        $content = str_replace('{$r23}', $fxIndex, $content);
                    }
                    //判断主机/web/弱密码哪个开启
                    $s_zj = 1;
                    $s_web = 1;
                    $s_rmm = 1;
                    if($rows['host_enable'] != 1 ){
                        $s_zj = 0;
                    }else{//如开启了主机扫，则主索引加1
                        $Index++;
                        $hostIndex = $Index;
                        $content = str_replace('{$hostMindex}', $Index, $content);
                    }
                    if($rows['web_enable'] != 1){
                        $s_web = 0;
                    }else{//如开启了web扫，则主索引加1
                        $Index++;
                        $webIndex = $Index;
                        $content = str_replace('{$webMindex}', $Index, $content);
                    }
                    if($rows['weak_enable'] != 1){
                        $s_rmm = 0;
                    }else{//如开启了弱密码扫，则主索引加1
                        $Index++;
                        $weakIndex = $Index;
                        $content = str_replace('{$weakMindex}', $Index, $content);
                    }
                    $content = str_replace('{$s_zj}', $s_zj, $content);
                    $content = str_replace('{$s_web}', $s_web, $content);
                    $content = str_replace('{$s_rmm}', $s_rmm, $content);

                    /*主机漏洞分布检测*/
                    $data5 = $data6 = $data8 = $data9 = $topid = $ldcnum = $ldhnum = $ldmnum = $ldlnum = $ldinum = array();
                    $cats = $db->fetch_all("SELECT * FROM host_family_list WHERE parent_id='0' ORDER BY id ASC");
                    foreach ($cats as $k => $v) {//根据类别读取
                        //$num = $db->result_first("SELECT COUNT(1) AS num FROM $tablevul vul, host_family_ref hfr WHERE vul.risk_factor !='I' AND vul.vul_id=hfr.vul_id AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id='$v[priority]')");
                        $num = $db->result_first("SELECT COUNT(1) AS num FROM $tablevul vul, host_family_ref hfr WHERE vul.vul_id=hfr.vul_id AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id='$v[id]')");
                        $num = $num > 0 ? $num : 0;
                        $data5[] = '{name:"' . $v['desc'] . '",value:' . $num . ',color:setColor[' . $k . ']}';
                    }
                    $content = str_replace('{$data5}', join(',', $data5), $content);

                    /*弱密码分布检测*/
                    $rms = $db->fetch_all("SELECT * FROM weak_vul_list ORDER BY id ASC");
                    foreach ($rms as $k => $v) {//根据类别读取
                        //$num = $db->result_first("SELECT COUNT(1) AS num FROM $tablepwd WHERE vul_id='$v[id]'");
                        $num = $db->result_first("SELECT COUNT(1) AS num FROM $tablepwd WHERE vul_id='$v[vul_id]'");
                        $num = $num > 0 ? $num : 0;
                        $data8[] = '{name:"' . $v['vul_name'] . '",value:' . $num . ',color:setColor[' . $k . ']}';
                    }
                    $content = str_replace('{$data8}', join(',', $data8), $content);

                    /*WEB漏洞分布检测*/
                    $webfb = $db->fetch_all("SELECT * FROM bd_web_family WHERE parent_id!=0 ORDER BY id ASC");
                    foreach ($webfb as $k => $v) {//根据类别读取
                        $num = $db->result_first("SELECT COUNT(1) AS num FROM $tablescan sc,bd_web_vul_lib wr WHERE sc.vul_id=wr.vul_id AND wr.family='$v[id]'");
                        $num = $num > 0 ? $num : 0;
                        $data9[] = '{name:"' . $v['name'] . '",value:' . $num . ',color:setColor[' . $k . ']}';
                    }
                    $content = str_replace('{$data9}', join(',', $data9), $content);


                    /*4.3 主机漏洞列表*/
                    $html = $whtml = '';
                    $hflist = $db->fetch_all("SELECT id,`desc` FROM host_family_list WHERE parent_id='0' ORDER BY id ASC");
                    $hosti = 0;
                    foreach ($hflist as $h => $f) {
                        if(!in_array($f['desc'],$templateConfArr)){
                            $classTemp = 'displayNone';
                            $styleTemp = 'display:none';
                        }else{
                            $classTemp = '';
                            $styleTemp = '';
                            $hosti++;
                        }
                        $html .= '<div name="hostname" id="y-section-index-root-4-3-' . ($h + 1) . '"><div class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$hostIndex.'.' . ($hosti) . ' ' . $f['desc'] . '</div>';
                        $whtml .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$hostIndex.'.' . ($hosti) . ' ' . $f['desc'] . '</p>';
                        //$html .= '<div name="hostname" id="y-section-index-root-4-3-' . ($h + 1) . '"><div class="y-report-ui-element-title-level-3">3.' . ($h + 1) . ' ' . $f['desc'] . '</div>';
                        //$whtml .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold">3.' . ($h + 1) . ' ' . $f['desc'] . '</p>';
                        $cums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND vul.risk_factor='C' AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']})");
                        $cums = $cums > 0 ? $cums : 0;
                        $hums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND vul.risk_factor='H' AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']})");
                        $hums = $hums > 0 ? $hums : 0;
                        $mums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND vul.risk_factor='M' AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']})");
                        $mums = $mums > 0 ? $mums : 0;
                        $lums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND vul.risk_factor='L' AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']})");
                        $lums = $lums > 0 ? $lums : 0;
                        $iums = $db->result_first("SELECT count(1) as cnum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND vul.risk_factor='I' AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']})");
                        $iums = $iums>0 ? $iums : 0;
                        $zfxs = $cums + $hums + $mums + $lums + $iums;
                        if ($zfxs == 0) {
                            $html .= '<p class="y-report-ui-element-content  '.$classTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                            //$html .= '<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                            $whtml .= '<p style="line-height:20px;text-indent:4em;width:100%">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                        } else {
                            //$html .= '<p class="y-report-ui-element-content">本次扫描共发现该风险<span class="y-report-ui-text-normal-b"> ' . ($cums + $hums + $mums + $lums) . ' </span>个。另有信息级风险<span class="y-report-ui-text-level-info-b"> ' . $iums . ' </span>个。</p>';
                            //$html .= '<div><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th><th width="10%" class="y-report-ui-comp-data-grid-th">影响主机数</th><th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';
                            $html .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现该风险') . '<span class="y-report-ui-text-normal-b"> ' . ($cums + $hums + $mums + $lums + $iums) . ' </span>' . Yii::t('app', '个。') . '</p>';
                            // $html .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th><th width="10%" class="y-report-ui-comp-data-grid-th">影响主机数</th><th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';
                            $html .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '风险评级') . '</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">' . Yii::t('app', '风险名称') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '影响主机数') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '更多信息') . '</th></tr>';

                            $whtml .= '<p style="line-height:20px;text-indent:4em;width:100%">' . Yii::t('app', '本次扫描没有发现该风险。') . '<span style="color:#000000;font-weight:bold"> ' . ($cums + $hums + $mums + $lums + $iums) . '</span>个。</p>';

                            $whtml .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                            $sql = "SELECT vul.solution, vul.ip,vul.vul_id,vul.risk_factor,vul.port,vul.proto,vul.output,vinfo.cve,vinfo.cnvd,vinfo.cnnvd,vinfo.vul_name_cn,vinfo.desc_cn,vinfo.ref_cn,count(1) as ipsum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.risk_factor!='I' AND vul.risk_factor!='' AND vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']}) group by vul_id order by risk_factor_num ASC";
                            $myrows = $db->fetch_all($sql);
                            foreach ($myrows as $k => $v) {
                                $jieb = $risk_factor["{$v['risk_factor']}"];
                                $rcss = $risk_css["{$v['risk_factor']}"];
                                $rcor = $rcccolor["{$v['risk_factor']}"];
                                $html .= '<tr><td colspan="4"><span id="record-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
                                $html .= '<tr class="y-report-ui-comp-data-grid-tr-' . ($k % 2) . '"><td class="y-report-ui-text-level-' . $rcss . '-b"><div id="leaklevel">' . $jieb . '</div></td><td class="y-report-ui-comp-data-grid-td-text-align-left"><div id="leakname">' . $v['vul_name_cn'] . '</div></td><td>' . $v['ipsum'] . '</td><td special="openDetail" class="y-report-ui-element-more-info-link">' . Yii::t('app', '展开详情') . '</td></tr>';
                                $whtml .= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:' . $rcor . '">' . $jieb . '</span> ] ' . $v['vul_name_cn'] . '</div><div style="clear:both"></div></div>';
                                $whtml .= '<div><div><div style="position:relative;">';
                                $whtml .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                                $whtml .= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '影响主机数') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['ipsum'] . '</td></tr>';
                                $html .= '<tr style="display:none"><td colspan="4" style="opacity:0;-ms-filter:\'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)\';filter:alpha(opacity=0);-webkit-opacity:0;-moz-opacity:0;-khtml-opacity:0"><div class="y-report-ui-object-expandable-grid-detail-panel"><div class="y-report-ui-object-expandable-grid-detail-panel-header-frame"><div class="y-report-ui-object-expandable-grid-detail-panel-header-title"> [ <span class="y-report-ui-text-level-' . $rcss . '-b">' . $jieb . '</span> ] ' . $v['vul_name_cn'] . '</div><div class="y-report-ui-object-expandable-grid-detail-panel-header-close" special="closeDetail">' . Yii::t('app', '关闭') . '</div><div style="clear:both"></div></div><div class="y-report-ui-object-expandable-grid-detail-panel-content-frame"><div class="y-report-ui-object-tab-panel-frame" special="objectType#tabPanel"><div style="position:relative;" class="y-report-ui-object-tab-panel-header-frame"><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button-toggled">' . Yii::t('app', '主机列表（共') . $v['ipsum'] . Yii::t('app', '项）') . '</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">' . Yii::t('app', '风险描述') . '</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">' . Yii::t('app', '解决方案') . '</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">' . Yii::t('app', '相关编号') . '</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">' . Yii::t('app', '参考信息') . '</div><div style="clear:both"></div></div><div class="y-report-ui-object-tab-panel-content-frame"><div style="float:left" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-accordion-list-frame" special="objectType#accordionList">';

                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '主机列表（共') . $v['ipsum'] . Yii::t('app', '项）') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">';
                                $louat = $db->fetch_all("SELECT ip,output,port,proto FROM $tablevul WHERE vul_id='{$v['vul_id']}'");
                                foreach ($louat as $i => $l) {
                                    $html .= '<div class="y-report-ui-object-accordion-list-item-frame"><div class="y-report-ui-object-accordion-list-item-header">' . $l['ip'] . ' [ ' . $l['proto'] . ' / ' . $l['port'] . ' ]</div><div class="y-report-ui-object-accordion-list-item-content"><div class="y-report-ui-object-accordion-list-item-content-text-container">' . Yii::t('app', '输出详情：') . '<br />' . htmlspecialchars($l['output']) . '</div></div></div>';
                                    $whtml .= $l['ip'] . ' [ ' . $l['proto'] . ' / ' . $l['port'] . ' ] <br />';
                                }
                                $whtml .= '</td></tr>';
                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '风险描述') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['desc_cn'] . '</td></tr>';
                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '解决方案') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['solution'] . '</td></tr>';
                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '相关编号') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">CVE：' . $v['cve'] . '<br />CNVD：' . $v['cnvd'] . '<br />CNNVD：' . $v['cnnvd'] . '</td></tr>';
                                $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '参考信息') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['ref_cn'] . '</td></tr>';
                                $whtml .= '</tbody></table>';
                                $whtml .= '</div></div></div>';
                                $whtml .= '</td></tr>';

                                $html .= '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="desc_cn">' . $v['desc_cn'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="solu_cn">' . $v['solution'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="host_xgbh"><table cellpadding="0" cellspacing="0" width="100%"><tbody><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" width="7%" style="vertical-align:top">CVE</th><td width="5px" style="vertical-align:top"> : </td><td id="cve-val">' . $v['cve'] . '</td></tr><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" style="vertical-align:top">CNVD</th><td width="5px" style="vertical-align:top"> : </td><td>' . $v['cnvd'] . '</td></tr><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" style="vertical-align:top">CNNVD</th><td width="5px" style="vertical-align:top"> : </td><td>' . $v['cnnvd'] . '</td></tr></tbody></table></div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="ref_cn">' . $v['ref_cn'] . '</div></div></div></div></div></div></td></tr>';
                                $html .= '</tbody></table></span></td></tr>';
                            }
                            $html .= '</tbody></table></div>';
                            $whtml .= '</tbody></table>';
                        }
                        $html .= '</div>';
                    }
                    if ($rt == 'html') {
                        $content = str_replace('{$webld}', $html, $content);
                    }
                    /*4.3 WEB漏洞列表*/
                    $wdhmtl = $wdwhmtl = '';
                    $webclass = $db->fetch_all("SELECT id,`name` FROM bd_web_family WHERE parent_id!='0' ORDER BY id ASC");
                    $webii = 0;
                    foreach ($webclass as $c => $w) {
                        if(!in_array($w['name'],$templateConfArr)){
                            $classTemp = 'displayNone';
                            $styleTemp = 'display:none';
                        }else{
                            $classTemp = '';
                            $styleTemp = '';
                            $webii++;
                        }
                        //$wdhmtl .= '<div name="webname" id="y-section-index-root-4-3-' . ($c + 1) . '"><div class="y-report-ui-element-title-level-3">4.' . ($c + 1) . ' ' . $w['desc'] . '</div>';
                        //$wdwhmtl .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold">4.' . ($c + 1) . ' ' . $w['desc'] . '</p>';
                        $wdhmtl .= '<div name="webname" id="y-section-index-root-4-3-' . ($c + 1) . '"><div class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$webIndex.'.' . ($webii) . ' ' . $w['name'] . '</div>';
                        $wdwhmtl .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$webIndex.'.' . ($webii) . ' ' . $w['name'] . '</p>';
                        $whnum = $db->result_first("SELECT count(1) as cnum FROM $tablescan sc, bd_web_vul_lib wlist WHERE sc.vul_id=wlist.vul_id AND wlist.family={$w['id']} AND sc.`vul_level`!=''");
                        $whnum = $whnum > 0 ? $whnum : 0;
                        if ($whnum == 0) {
                            //$wdhmtl .= '<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                            $wdhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                            $wdwhmtl .= '<p style="line-height:20px;text-indent:4em;width:100%">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                        } else {
                            //$wdhmtl .= '<p class="y-report-ui-element-content">本次扫描共发现该风险<span class="y-report-ui-text-normal-b"> ' . $whnum . ' </span>个。</p>';
                            //$wdhmtl .= '<div><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th><th width="10%" class="y-report-ui-comp-data-grid-th">影响URL数</th><th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';
                            $wdhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现该风险') . '<span class="y-report-ui-text-normal-b"> ' . $whnum . ' </span>' . Yii::t('app', '个。') . '</p>';
                            $wdhmtl .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '风险评级') . '</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">' . Yii::t('app', '风险名称') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '影响URL数') . '</th><th width="10%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '更多信息') . '</th></tr>';

                            $wdwhmtl .= '<p style="line-height:20px;text-indent:4em;width:100%">' . Yii::t('app', '本次扫描共发现该风险') . '<span style="color:#000000;font-weight:bold"> ' . $whnum . ' </span>' . Yii::t('app', '个。') . '</p>';
                            $wdwhmtl .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                            $sql = "SELECT sc.url,sc.vul_type,sc.vul_id,sc.vul_level,wlist.vul_name,wlist.description,wlist.solution,count(1) as urlsum FROM $tablescan sc, bd_web_vul_lib wlist WHERE sc.vul_id=wlist.vul_id AND wlist.family_id={$w['id']}  GROUP BY sc.vul_type ORDER BY sc.vul_level DESC";
                            $wmyrows = $db->fetch_all($sql);
                            foreach ($wmyrows as $k => $v) {
                                $jieb = $web_factor["{$v['vul_level']}"];
                                $rcss = $web_css["{$v['vul_level']}"];
                                $rcor = $web_color["{$v['vul_level']}"];
                                $wdhmtl .= '<tr><td colspan="4"><span id="recordweb-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
                                $wdhmtl .= '<tr class="y-report-ui-comp-data-grid-tr-' . ($k % 2) . '"><td class="y-report-ui-text-level-' . $rcss . '-b" id="webfxjb">' . $jieb . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="webfxname">' . $v['vul_name'] . '</td><td id="weburlnum">' . $v['urlsum'] . '</td><td special="openDetail" class="y-report-ui-element-more-info-link">' . Yii::t('app', '展开详情') . '</td></tr>';
                                $wdwhmtl .= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:' . $rcor . '">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div style="clear:both"></div></div>';
                                $wdwhmtl .= '<div><div><div style="position:relative;">';
                                $wdwhmtl .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                                $wdwhmtl .= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . Yii::t('app', '本次扫描共发现该风险') . '</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['urlsum'] . '</td></tr>';

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

                    /*4.3 弱密码列表*/
                    $rmhmtl = $rmwhmtl = '';
                    $mmlist = $db->fetch_all("SELECT `ip`,`username`,`password`,`type` FROM $tablepwd ORDER BY id ASC");
                    $mmjl = count($mmlist) ? count($mmlist) : 0;
                    if(!in_array(Yii::t('app', '弱密码漏洞列表'),$templateConfArr)){
                        $classTemp = 'displayNone';
                        $styleTemp = 'display:none';
                    }else{
                        $classTemp = '';
                        $styleTemp = '';
                    }
                    if ($mmjl == 0) {
                        $rmhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                        //$rmhmtl .= '<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                        $rmwhmtl .= '<p style="line-height:20px;text-indent:4em;width:100%">' . Yii::t('app', '本次扫描没有发现该风险。') . '</p>';
                    } else {
                        //$rmhmtl .= '<p class="y-report-ui-element-content">本次扫描共发现弱密码<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>个。影响主机<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>个。</p>';
                        //$rmhmtl .= '<div><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="20%" class="y-report-ui-comp-data-grid-th">IP</th><th width="30%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">用户名</th><th width="30%" class="y-report-ui-comp-data-grid-th">密码</th><th width="20%" class="y-report-ui-comp-data-grid-th">弱密码类型</th></tr>';
                        $rmhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现弱密码') . '<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>' . Yii::t('app', '个。') . Yii::t('app', '影响主机') . '<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>' . Yii::t('app', '个。') . '</p>';
                        $rmhmtl .= '<div class="'.$classTemp.'"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="20%" class="y-report-ui-comp-data-grid-th">IP</th><th width="30%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">' . Yii::t('app', '用户名') . '</th><th width="30%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '密码') . '</th><th width="20%" class="y-report-ui-comp-data-grid-th">' . Yii::t('app', '弱密码类型') . '</th></tr>';

                        $rmwhmtl .= '<p class="y-report-ui-element-content '.$classTemp.'">' . Yii::t('app', '本次扫描共发现弱密码') . '<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>' . Yii::t('app', '个。') . Yii::t('app', '影响主机') . '<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>' . Yii::t('app', '个。') . '</p>';
                        $rmwhmtl .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody><tr><td width="20%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">IP</td><td width="30%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">' . Yii::t('app', '用户名') . '</td><td width="30%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">' . Yii::t('app', '密码') . '</td><td width="20%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">' . Yii::t('app', '弱密码类型') . '</td></tr>';
                        foreach ($mmlist as $m => $n) {
                            $rmhmtl .= '<span id="record-show"><tr class="y-report-ui-comp-data-grid-tr-' . ($m % 2) . '"><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-ip">' . $n['ip'] . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-user">' . $n['username'] . '</td><td id="rmm-password">' . $n['password'] . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-type">' . $n['type'] . '</td></tr>';
                            $rmwhmtl .= '<tr style="height:25px;text-align:center;background-color:' . $kcolor[($m % 2)] . '"><td>' . $n['ip'] . '</td><td style="padding-right:1em !important;padding-left:1em !important;text-align:left">' . $n['username'] . '</td><td>' . $n['password'] . '</td><td>' . $n['type'] . '</td></tr></span>';
                        }
                        $rmhmtl .= '</tbody></table></div>';
                        $rmwhmtl .= '</tbody></table>';
                    }
                    $content = str_replace('{$rmmlist}', $rmhmtl, $content);

                    //if (!file_exists($dir."/$tasks/$ipvalue")) mkdir($dir."/$tasks/$ipvalue", 0777);
                    //file_put_contents($dir . "/attack-" . $tasks . ".html", $content, LOCK_EX);
                    //file_put_contents($dir . "/$tasks/$ipvalue/" . $ipvalue . ".html", $content, LOCK_EX);
                    file_put_contents($dir . "/" . $ipvalue . ".html", $content, LOCK_EX);

                    $name = $bbname . Yii::t('app', "的安全评估报告");

                    /*if ($rt == 'html') {
                        $data['down'] = "<a href=\"./report/now/" . $bbname .$theTime. "." . ($rt == 'html' ? 'zip' : 'pdf') . "\" target=\"_self\"" . " ><span id=\"downAll\">下载{$name}</span></a>";
                    }*/
                    if ($rt == 'html') {
                        //$path = "now/" . $bbname . $theTime . ".zip";
                        //$exec = "cd /usr/local/nginx/html/report/now; zip -q -r -j ./" . $mom . " ./" . $ipvalue . ".html ../common.js ../common.css ../jquery-1.9.1.min.js ../bluechar.js";
                        $exec = "cd /usr/local/nginx/html/report/now; zip -q -r -j ./" . $mom . " ./" . $ipvalue . ".html";
                        exec($exec);
                        //删掉html
                        unlink(REPORT_DIR . "./now/".$ipvalue . ".html");
//                unlink(REPORT_DIR . "./now/common.css");
//                unlink(REPORT_DIR . "./now/common.js");
//                unlink(REPORT_DIR . "./now/jquery-1.9.1.min.js");
//                unlink(REPORT_DIR . "./now/bluechar.js");
                    }
                    //$file_name[] = $file;


                    $sql = "DROP TABLE IF EXISTS ".$tablevul;
                    //$sql = "truncate table ".$tablevul;
                    $db->query($sql);
                    //$sql = "truncate table ".$tablepwd;
                    $sql = "DROP TABLE IF EXISTS ".$tablepwd;
                    $db->query($sql);
                    //$sql = "truncate table ".$tablescan;
                    $sql = "DROP TABLE IF EXISTS ".$tablescan;
                    $db->query($sql);

                }

            }
            //$db->query("INSERT INTO " . getTable('reportsmanage') . " (`name`,`type`,`desc`,`time`,`path`,`timetype`,`format`) VALUES ('$name','1','$desc','$timestamp','$path','1','$rt')");

            $data['success'] = true;
            $data['message'] = Yii::t('app', '操作成功');
            $hdata['sDes'] = Yii::t('app', '导出报表');
            $hdata['sRs'] = Yii::t('app', '导出成功');
            $hdata['sAct'] = $act.'/'.$show;
            saveOperationLog($hdata);
            echo json_encode($data);
            exit;
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



}

?>
