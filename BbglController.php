<?php
namespace  app\controllers;

use app\components\client_db;
use app\components\MhtFileMaker;
use app\models\BdHostTaskManage;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

class BbglController extends BaseController {
    public function actionIndex(){
        $db=new client_db();
        $data = array();
        $post = isset( $_REQUEST['post']) ? intval($_REQUEST['post']) : 0;
        if($post == 1){
            $data  = array('success'=>false, 'message'=>'操作失败', 'down'=>'');
            //$tasks  = intval($_REQUEST['tasks']);//这里$_REQUEST['tasks']是个数组
            $template_conf = array(
                //'name': '模板名',
                'overview'=> '综述',
                'risk'=>'总体风险分析',
                'risk_lever'=> '风险等级分布',
                'risk_type'=> '风险类型分布',
                'risk_host'=> '所有主机（IP）风险分布',
                'vul_host'=>'主机漏洞列表',
                'vul_host_system'=> '系统漏洞',
                'vul_host_server'=> '服务漏洞',
                'vul_host_application'=> '应用漏洞',
                'vul_host_device'=> '网络设备漏洞',
                'vul_host_database'=> '数据库漏洞',
                'vul_host_virtual'=>'虚拟化平台漏洞',
                'risk_web'=> 'WEB漏洞列表',
                'vul_web_syscmd'=> '系统命令执行',
                'vul_web_sql'=> 'SQL注入',
                'vul_web_code'=> '代码远程执行',
                'vul_web_file'=> '远程文件包含',
                'vul_web_http'=> 'HTTP参数污染',
                'vul_web_ldap'=> 'LDAP注入',
                'vul_web_script'=> '跨站脚本攻击',
                'vul_web_content'=> '内容欺骗',
                'vul_web_upload'=>'文件上传',
                'vul_web_deny'=> '拒绝服务',
                'vul_web_info'=>  '信息泄露',
                'vul_web_dir'=> '目录遍历',
                'vul_web_log'=> '日志文件扫描',
                'vul_web_server'=> '软件服务检测',
                'vul_web_read'=> '任意文件读取',
                'vul_web_database'=> '数据库发现',
                'vul_web_backdoor'=> '后门发现',
                'vul_web_auth'=> '验证绕过',
                'vul_web_config'=> '配置不当',
                'vul_web_other'=> '其它',
                'risk_pwd'=> '弱密码漏洞列表'
            );
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

            $rt           = filterStr($_REQUEST['rt']);
            $bbtitle      = filterStr($_REQUEST['bbtitle']);
            $bbname       = filterStr($_REQUEST['bbname']);
            $desc         = filterStr($_REQUEST['desc']);
            $epilog       = filterStr($_REQUEST['epilog']);
            $kidbb       = intval($_REQUEST['kidbb']);
            $template_report       = intval($_REQUEST['template_report']);
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
            if(empty($_REQUEST['tasks'])){
                $data['message'] = '请选择任务.';
                echo json_encode($data);
                exit;
            }

            if($bbname == ''){
                $data['message'] = '请填写报表名称.';
                echo json_encode($data);
                exit;
            }
            $theTime = '-'.date('Y-m-d_H:i:s',time());

            if($_POST['tasks']){
                $taskss=$_POST['tasks'];
            }elseif($_GET['tasks']){
                $taskss=explode(',',$_REQUEST['tasks']);
            }
            //var_dump($taskss);die;
            foreach ($taskss as $key=>$val) {
                $tasks=intval($val);
                $tablevul = 'bd_host_result_' . $tasks;
                $tablepwd = 'bd_weakpwd_result_' . $tasks;
                $tablescan = 'bd_web_result_' . $tasks;
                ignore_user_abort(TRUE);
                @set_time_limit(300);
                $date = date("Ymd");
                $day = date('d');
                $dym = "/home/bluedon/bdscan/bdwebserver/nginx/html/web/report";
                $dir = "/home/bluedon/bdscan/bdwebserver/nginx/html/web/report/now";//报表存放文件夹

                if (!file_exists($dir)) mkdir($dir, 0777);

                $file_name = array();
                $maxid = $db->result_first("SELECT MAX(id) FROM " . getTable('reportsmanage') . " WHERE 1");
                $maxid = (!$maxid || $maxid < 1) ? 1 : $maxid + 1;

                exec("cd /home/bluedon/bdscan/bdwebserver/nginx/html/web/report/now; ln -s ../common.js common.js; ln -s ../common.css common.css; ln -s ../bluechar.js; ln -s ../jquery-1.9.1.min.js jquery-1.9.1.min.js");

                $content = $wordcon = $imageCon = '';
                global $act,$show;
                if($_REQUEST['type']=='weakpwd'){
                    if(!in_array($tablepwd,$this->getAllTables())){
                        $db->execute("CREATE TABLE $tablepwd (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` varchar(256) DEFAULT NULL COMMENT 'task id',
  `task_name` varchar(256) DEFAULT NULL COMMENT '任务名称',
  `ip` varchar(32) DEFAULT NULL COMMENT '目标ip',
  `vul_name` varchar(255) DEFAULT NULL COMMENT '字典名称',
  `username` varchar(256) DEFAULT NULL COMMENT '用户名',
  `password` varchar(256) DEFAULT NULL COMMENT '密码',
  `port` varchar(256) DEFAULT NULL COMMENT '服务端口号',
  `proto` varchar(256) DEFAULT NULL COMMENT '协议：TCP/UDP',
  `report` int(11) DEFAULT '0' COMMENT '报表id',
  `vul_id` int(11) DEFAULT '0' COMMENT '弱口令参数对应vul_id,在weak_vul_list表中',
  `level` varchar(10) DEFAULT 'H' COMMENT '危险等级',
  `description` text COMMENT '描述',
  `solution` text COMMENT '解决方案',
  `dbname` varchar(256) DEFAULT NULL COMMENT '结果属于的库名',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
");
                    }
                    $content = file_get_contents($dym . '/attack_host.html');

                    $total=\Yii::$app->db->createCommand("select count(1) as num from $tablepwd")->queryColumn()[0];
                    $rows=\Yii::$app->db->createCommand("select * from $tablepwd")->queryAll();
                    $main_tasks=\Yii::$app->db->createCommand("SELECT * FROM bd_weakpwd_task_manage WHERE id='$tasks'")->queryOne();
                    $targets = $rows['target'];
                   // var_dump($num);die;
                    $content = str_replace('{$weak_num}', $total, $content);
                    $content = str_replace('{$type}', '弱密码', $content);
                    $level=$this->getLevel($rows);
                    $h_sum=$m_sum=$l_sum=0;
                    foreach ($rows as $v){
                        if($v['level']=='H'){

                            $h_sum++;
                        }
                        if($v['level']=='M'){

                            $m_sum++;
                        }
                        if($v['level']=='L'){

                            $l_sum++;
                        }
                    }
                    //var_dump($h_sum,$m_sum);die;
                    $content = str_replace('{$level}', $level, $content);
                    $content = str_replace('{$h_sum}', $h_sum, $content);
                    $content = str_replace('{$m_sum}', $m_sum, $content);
                    $content = str_replace('{$l_sum}', $l_sum, $content);

                    //风险分布图
                    $h_fx = number_format((($h_sum / $total) * 100), 2, '.', '');
                    $m_fx = number_format((($m_sum / $total) * 100), 2, '.', '');
                    $l_fx = number_format((($l_sum / $total) * 100), 2, '.', '');
                    $data1 = '{name:"高风险('.$h_sum.')个",value:[' . $h_fx. '],color:"#ffa500"},{name:"中风险('.$m_sum.')个",value:[' .  $m_fx . '],color:"#f737ec"},{name:"低风险('.$l_sum.')个",value:[' .  $l_fx . '],color:"#6060fe"}';
                    $content = str_replace('{$data_level}', $data1, $content);

                    /*漏洞类型分布图*/
                    $category = $db->fetch_all("select id,vul_name from bd_weakpwd_vul_lib ");
                    $category = ArrayHelper::map($category,'id','vul_name');
                    $str=$type_list='';
                    foreach ($category as $c_i=>$v){
                        $str.= '<li><a class="sub" href="#t3_'.$c_i.'" >3.'.$c_i.'、'.$v.'</a></li>';
                        $type_list.='<div  class="y-report-ui-comp-section weakSS">
                                        <div class="y-report-ui-element-title-level-2" id="t3_'.$c_i.'">3.'.$c_i.'、'.$v.'</div>
                                        <div >'.$this->gridview($tablepwd,$c_i).'</div>
                                    </div>';
                    }
                    $content= str_replace('{$type_li}',$str,$content);
                    $content= str_replace('{$type_list}',$type_list,$content);
                    //var_dump($category);die;
                    $vuls_type = $db->fetch_all("SELECT COUNT(1) as num,t.id as category from(
select a.vul_name,a.vul_id,b.id from $tablepwd a LEFT JOIN bd_weakpwd_vul_lib b on a.vul_id = b.vul_id
) as t  GROUP BY vul_id
");
                    foreach ($category as $i=> $v){
                        $arr[$i]['name']= $v;
                        $arr[$i]['value']=number_format(0 / $total * 100,2);
                        $arr[$i]['color'] = $this->randrgb();
                    }
                    // var_dump($arr);die;
                    $total = array_sum(ArrayHelper::getColumn($vuls_type,'num'));
                    //var_dump(array_diff(ArrayHelper::getColumn($vuls_type,'category'),$category));die;
                    foreach ($vuls_type as $i=>$v){
                        $arr[$v['category']]['name'] = $category[$v['category']];
                        $arr[$v['category']]['value'] = number_format($v['num'] / $total * 100,2);
                        // $arr[$v['category']]['color'] = $this->randrgb();
                    }
                    sort($arr);
                    // echo json_encode($arr);die;
                    //var_dump($arr);die;
                    $content = str_replace('{$data_type}', json_encode($arr), $content);
                    /*TOP10危险IP所有漏洞统计图*/
                    //$ipld = $db->fetch_all("SELECT ip FROM $tablevul GROUP BY ip DESC LIMIT 10");
                    $topid=[];
                    $ipld = $db->fetch_all("
        select ip from
            (select ip ,sum(case when level='H' then 1 else 0 end ) as hnum ,
            sum(case when level='M' then 1 else 0 end ) as mnum ,
            sum(case when level='L' then 1 else 0 end ) as lnum ,
            sum(case when level='L' then 1 else 0 end ) as inum
            from $tablepwd
            group by ip) as iptem
            ORDER BY hnum DESC, mnum desc,lnum DESC limit 10
    ");
                    //var_dump($ipld);die;
                    foreach ($ipld as $k => $v) {
                        array_push($topid, "'" . $v['ip'] . "'");
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计紧急
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablepwd WHERE level ='C' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldcnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计高风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablepwd WHERE level ='H' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldhnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计中风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablepwd WHERE level ='M' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldmnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计低风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablepwd WHERE level ='L' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $tum_i = $db->result_first("SELECT COUNT(1) AS tum FROM $tablepwd WHERE level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum_i = $tum_i > 0 ? $tum_i : 0;
                        $ldlnum[] = $tum+$tum_i;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取安全信息
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablepwd WHERE level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldinum[] = $tum;
                    }
                    $data7 = '{name:"高风险",value:[' . join(',', $ldhnum) . '],color:"#ffa500"},{name:"中风险",value:[' . join(',', $ldmnum) . '],color:"#f737ec"},{name:"低风险",value:[' . join(',', $ldlnum) . '],color:"#6060fe"}';
                    $content = str_replace('{$dataip}', join(',', $topid), $content);
                    $content = str_replace('{$data7}', $data7, $content);


                    /*3.按漏洞类型列表*/
                    $str='';
                    foreach ($category as $c_i=>$v){
                        $str.= '<li><a class="sub" href="#t3_'.$c_i.'" >3.'.$c_i.'、'.$v.'</a></li>';
                    }
                    $content= str_replace('{$type_li}',$str,$content);
                    $content = str_replace('{$vuls_sys_list}',$this->gridview($tablepwd,'type',$rt),$content);

                    /* 4.安全等级详细信息*/
                    $content = str_replace('{$vuls_level_list}',$this->gridview($tablepwd,'level',$rt),$content);

                    /* 5.ip详细信息*/
                    $content = str_replace('{$vuls_ip_list}',$this->gridview($tablepwd,'ip',$rt),$content);

                    $sql="select * from bd_weakpwd_task_manage WHERE id=$val";
                    $row = $db->fetch_row($sql);
                    // var_dump($row);die;
                    $content = str_replace('{$target}', $row['target'], $content);
                    if($row['start_time']==0){
                        $start='未开始';
                    }else{
                        $start=date('Y-m-d H:i:s',$row['start_time']);
                    }
                    if($row['end_time']==0){
                        $end='未结束';
                    }else{
                        $end=date('Y-m-d H:i:s',$row['end_time']);
                    }
                    $content = str_replace('{$starttime}', $start, $content);
                    $content = str_replace('{$endtime}',$end, $content);


                }elseif($_REQUEST['type']=='web'){
                    if(!in_array($tablescan,$this->getAllTables())){
                        $db->execute("CREATE TABLE $tablescan (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '结果ID',
  `vul_id` int(11) DEFAULT '0' COMMENT '漏洞id',
  `type` varchar(16) DEFAULT NULL COMMENT '漏洞归属: 1 开头表示 Perl 的漏洞: 1, 原本就已经存在; 1-0, 不显示; 1-1, 新增;...。 2 开头表示 Python 的漏洞: 2, waf 原本就存在; 2-0, 不显示; 2-1, 新增; ...',
  `task_name` varchar(255) DEFAULT NULL COMMENT '任务名字',
  `vul_name` varchar(255) DEFAULT NULL COMMENT '漏洞名字',
  `family` varchar(255) DEFAULT NULL COMMENT '漏洞分类',
  `level` varchar(10) DEFAULT 'H' COMMENT '危险等级：H, 高; M, 中; L, 低; I, info',
  `url` varchar(1024) DEFAULT NULL COMMENT 'URL',
  `ip` varchar(32) DEFAULT NULL COMMENT '目标ip',
  `domain` varchar(255) DEFAULT NULL COMMENT '域名',
  `description` text COMMENT '描述',
  `solution` text COMMENT '解决方案',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
");
                    }
                    $content = file_get_contents($dym . '/attack_host.html');
                    $total=\Yii::$app->db->createCommand("select count(1) as num from $tablescan")->queryColumn()[0];
                    $rows=\Yii::$app->db->createCommand("select  `level` from $tablescan")->queryAll();
                    $main_tasks=\Yii::$app->db->createCommand("SELECT * FROM bd_web_task_manage WHERE id='$tasks'")->queryOne();
                    $content = str_replace('{$weak_num}', $total, $content);
                    $content = str_replace('{$type}', 'WEB', $content);

                    $h_sum=$m_sum=$l_sum=0;
                    $level=$this->getLevel($rows);
                    foreach ($rows as $v){
                        if($v['level']=='H'){
                            $h_sum++;
                        }
                        if($v['level']=='M'){
                            $m_sum++;
                        }
                        if($v['level']=='L'){
                            $l_sum++;
                        }
                    }
                    //var_dump($h_sum,$m_sum);die;
                    $content = str_replace('{$level}', $level, $content);
                    $content = str_replace('{$h_sum}', $h_sum, $content);
                    $content = str_replace('{$m_sum}', $m_sum, $content);
                    $content = str_replace('{$l_sum}', $l_sum, $content);

                    //风险分布图
                    $h_fx = number_format((($h_sum / $total) * 100), 2, '.', '');
                    $m_fx = number_format((($m_sum / $total) * 100), 2, '.', '');
                    $l_fx = number_format((($l_sum / $total) * 100), 2, '.', '');
                    $data1 = '{name:"高风险('.$h_sum.')个",value:[' . $h_fx. '],color:"#ffa500"},{name:"中风险('.$m_sum.')个",value:[' .  $m_fx . '],color:"#f737ec"},{name:"低风险('.$l_sum.')个",value:[' .  $l_fx . '],color:"#6060fe"}';
                    $content = str_replace('{$data_level}', $data1, $content);

                    /*漏洞类型分布图*/
                    $category = $db->fetch_all("select id,description from bd_web_family WHERE parent_id=0");
                    $category = ArrayHelper::map($category,'id','description');
                    //var_dump($category);die;
                    $vuls_type = $db->fetch_all("SELECT COUNT(1) as num,t.module_id as category from(
select a.vul_name,a.vul_id,b.module_id from $tablescan a LEFT JOIN bd_web_vul_lib b on a.vul_id = b.vul_id
) as t where module_id in (1,2,3,4,5,6) GROUP BY module_id
");
                    foreach ($category as $i=> $v){
                        $arr[$i]['name']= $v;
                        $arr[$i]['value']=number_format(0 / $total * 100,2);
                        if($i==1){
                            $arr[$i]['color'] = '#ffa500';
                        }
                        if($i==2){
                            $arr[$i]['color'] = '#f737ec';
                        }
                        if($i==3){
                            $arr[$i]['color'] = '#6060fe';
                        }
                        if($i==4){
                            $arr[$i]['color'] = 'green';
                        }
                        if($i==5){
                            $arr[$i]['color'] = 'red';
                        }
                        if($i==6){
                            $arr[$i]['color'] = 'blue';
                        }

                        //$arr[$i]['color'] = $this->randrgb();
                    }
                    // var_dump($arr);die;
                    $total = array_sum(ArrayHelper::getColumn($vuls_type,'num'));
                    //var_dump(array_diff(ArrayHelper::getColumn($vuls_type,'category'),$category));die;
                    foreach ($vuls_type as $i=>$v){
                        $arr[$v['category']]['name'] = $category[$v['category']];
                        $arr[$v['category']]['value'] = number_format($v['num'] / $total * 100,2);
                        // $arr[$v['category']]['color'] = $this->randrgb();
                    }
                    sort($arr);
                    // echo json_encode($arr);die;
                    //var_dump($arr);die;
                    $content = str_replace('{$data_type}', json_encode($arr), $content);
                    /*TOP10危险IP所有漏洞统计图*/
                    //$ipld = $db->fetch_all("SELECT ip FROM $tablevul GROUP BY ip DESC LIMIT 10");
                    $topid=[];
                    $ipld = $db->fetch_all("
        select ip from
            (select ip ,sum(case when level='H' then 1 else 0 end ) as hnum ,
            sum(case when level='M' then 1 else 0 end ) as mnum ,
            sum(case when level='L' then 1 else 0 end ) as lnum ,
            sum(case when level='L' then 1 else 0 end ) as inum
            from $tablescan
            group by ip) as iptem
            ORDER BY hnum DESC, mnum desc,lnum DESC limit 10
    ");
                    //var_dump($ipld);die;
                    foreach ($ipld as $k => $v) {
                        array_push($topid, "'" . $v['ip'] . "'");
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计紧急
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablescan WHERE level ='C' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldcnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计高风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablescan WHERE level ='H' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldhnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计中风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablescan WHERE level ='M' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldmnum[] = $tum;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取统计低风险
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablescan WHERE level ='L' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $tum_i = $db->result_first("SELECT COUNT(1) AS tum FROM $tablescan WHERE level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum_i = $tum_i > 0 ? $tum_i : 0;
                        $ldlnum[] = $tum+$tum_i;
                    }
                    foreach ($ipld as $k => $v) {//根据IP读取安全信息
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablescan WHERE level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldinum[] = $tum;
                    }
                    $data7 = '{name:"高风险",value:[' . join(',', $ldhnum) . '],color:"#ffa500"},{name:"中风险",value:[' . join(',', $ldmnum) . '],color:"#f737ec"},{name:"低风险",value:[' . join(',', $ldlnum) . '],color:"#6060fe"}';
                    $content = str_replace('{$dataip}', join(',', $topid), $content);
                    $content = str_replace('{$data7}', $data7, $content);

                    /*3.按漏洞类型列表*/
                    $str='';
                    foreach ($category as $c_i=>$v){
                        $str.= '<li><a class="sub" href="#t3_'.$c_i.'" >3.'.$c_i.'、'.$v.'</a></li>';
                    }
                    $content= str_replace('{$type_li}',$str,$content);
                    $content = str_replace('{$vuls_sys_list}',$this->gridview($tablescan,'type',$rt),$content);

                    /* 4.安全等级详细信息*/
                    $content = str_replace('{$vuls_level_list}',$this->gridview($tablescan,'level',$rt),$content);

                    /* 5.ip详细信息*/
                    $content = str_replace('{$vuls_ip_list}',$this->gridview($tablescan,'ip',$rt),$content);

                    $sql="select * from bd_web_task_manage WHERE id=$val";
                    $row = $db->fetch_row($sql);
                    // var_dump($row);die;
                    $content = str_replace('{$target}', $row['target'], $content);
                    if($row['start_time']==0){
                        $start='未开始';
                    }else{
                        $start=date('Y-m-d H:i:s',$row['start_time']);
                    }
                    if($row['end_time']==0){
                        $end='未结束';
                    }else{
                        $end=date('Y-m-d H:i:s',$row['end_time']);
                    }
                    $content = str_replace('{$starttime}', $start, $content);
                    $content = str_replace('{$endtime}',$end, $content);

                    // var_dump($content);die;
                }elseif($_REQUEST['type']=='host'){
                    if(!in_array($tablevul,$this->getAllTables())){
                        $db->execute("CREATE TABLE `bd_host_result_$val` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_name` varchar(256) DEFAULT NULL COMMENT '任务名称',
  `vul_id` int(11) NOT NULL DEFAULT '0' COMMENT '策略id',
  `oid` varchar(64) DEFAULT NULL COMMENT '漏洞编号',
  `vul_name` varchar(512) DEFAULT NULL COMMENT '策略名称',
  `vul_level` varchar(32) DEFAULT NULL COMMENT '风险等级',
  `ip` varchar(32) NOT NULL DEFAULT '' COMMENT '目标IP',
  `port_proto` varchar(32) DEFAULT NULL COMMENT '端口协议：TCP,icmp...',
  `description` varchar(4096) DEFAULT NULL COMMENT '描述',
  `solution` varchar(4096) DEFAULT NULL COMMENT '解决方案',
  `ref` varchar(4096) DEFAULT NULL,
  `family` varchar(128) DEFAULT NULL COMMENT '所属分类',
  `cve` varchar(512) DEFAULT NULL COMMENT 'CVE号',
  `cnvd` varchar(512) DEFAULT NULL,
  `cnnvd` varchar(512) DEFAULT NULL,
  `cvss` varchar(32) DEFAULT '0.0' COMMENT '风险等级的具体数值，eg:1.0-10.0',
  PRIMARY KEY (`id`,`vul_id`,`ip`)
) ENGINE=MyISAM AUTO_INCREMENT=398 DEFAULT CHARSET=utf8;

");
                    }
                    $content = file_get_contents($dym . '/attack_host.html');

                    $total=\Yii::$app->db->createCommand("select count(1) as num from $tablevul")->queryColumn()[0];
                    $rows=\Yii::$app->db->createCommand("select vul_level as `level` from $tablevul")->queryAll();
                    $main_tasks=\Yii::$app->db->createCommand("SELECT * FROM bd_host_task_manage WHERE id='$tasks'")->queryOne();
                    $content = str_replace('{$weak_num}', $total, $content);
                    $content = str_replace('{$type}', '主机', $content);
                    $h_sum=$m_sum=$l_sum=0;
                    $level=$this->getLevel($rows);
                    foreach ($rows as $v){
                        if($v['level']=='H'){
                            $h_sum++;
                        }
                        if($v['level']=='M'){
                            $m_sum++;
                        }
                        if($v['level']=='L'){
                            $l_sum++;
                        }
                    }
                    //var_dump($h_sum,$m_sum);die;
                    $content = str_replace('{$level}', $level, $content);
                    $content = str_replace('{$h_sum}', $h_sum, $content);
                    $content = str_replace('{$m_sum}', $m_sum, $content);
                    $content = str_replace('{$l_sum}', $l_sum, $content);

                    /*风险分布图*/
                    $h_fx = number_format((($h_sum / $total) * 100), 2, '.', '');
                    $m_fx = number_format((($m_sum / $total) * 100), 2, '.', '');
                    $l_fx = number_format((($l_sum / $total) * 100), 2, '.', '');
                    $data1 = '{name:"高风险('.$h_sum.')个",value:[' . $h_fx. '],color:"#ffa500"},{name:"中风险('.$m_sum.')个",value:[' .  $m_fx . '],color:"#f737ec"},{name:"低风险('.$l_sum.')个",value:[' .  $l_fx . '],color:"#6060fe"}';
                    $content = str_replace('{$data_level}', $data1, $content);

                    /*漏洞类型分布图*/
                    $category = $db->fetch_all("select id,description from bd_host_family_list WHERE parent_id=0");
                    $category = ArrayHelper::map($category,'id','description');
                    //var_dump($category);die;
                    $vuls_type = $db->fetch_all("SELECT COUNT(1) as num,t.category from(
select a.vul_name,a.vul_id,b.category from $tablevul a LEFT JOIN bd_host_vul_lib b on a.vul_id = b.vul_id
) as t where category in (1,2,3,4,5,6) GROUP BY category
");
                    foreach ($category as $i=> $v){
                        $arr[$i]['name']= $v;
                        $arr[$i]['value']=number_format(0 / $total * 100,2);
                        if($i==1){
                            $arr[$i]['color'] = '#ffa500';
                        }
                        if($i==2){
                            $arr[$i]['color'] = '#f737ec';
                        }
                        if($i==3){
                            $arr[$i]['color'] = '#6060fe';
                        }
                        if($i==4){
                            $arr[$i]['color'] = 'green';
                        }
                        if($i==5){
                            $arr[$i]['color'] = 'red';
                        }
                        if($i==6){
                            $arr[$i]['color'] = 'blue';
                        }

                        //$arr[$i]['color'] = $this->randrgb();
                    }
                   // var_dump($arr);die;
                    $total = array_sum(ArrayHelper::getColumn($vuls_type,'num'));
                    //var_dump(array_diff(ArrayHelper::getColumn($vuls_type,'category'),$category));die;
                    foreach ($vuls_type as $i=>$v){
                        $arr[$v['category']]['name'] = $category[$v['category']];
                        $arr[$v['category']]['value'] = number_format($v['num'] / $total * 100,2);
                       // $arr[$v['category']]['color'] = $this->randrgb();
                    }
                    sort($arr);

                    $content = str_replace('{$data_type}', json_encode($arr), $content);

                    /*TOP10危险IP所有漏洞统计图*/
                    //$ipld = $db->fetch_all("SELECT ip FROM $tablevul GROUP BY ip DESC LIMIT 10");
                    $topid=[];
                    $ipld = $db->fetch_all("
        select ip from
            (select ip ,sum(case when vul_level='H' then 1 else 0 end ) as hnum ,
            sum(case when vul_level='M' then 1 else 0 end ) as mnum ,
            sum(case when vul_level='L' then 1 else 0 end ) as lnum ,
            sum(case when vul_level='L' then 1 else 0 end ) as inum
            from $tablevul
            group by ip) as iptem
            ORDER BY hnum DESC, mnum desc,lnum DESC limit 10
    ");
                    //var_dump($ipld);die;
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
                    foreach ($ipld as $k => $v) {//根据IP读取安全信息
                        $tum = $db->result_first("SELECT COUNT(1) AS tum FROM $tablevul WHERE vul_level ='I' AND ip = '" . $v['ip'] . "'");
                        $tum = $tum > 0 ? $tum : 0;
                        $ldinum[] = $tum;
                    }
                    $data7 = '{name:"高风险",value:[' . join(',', $ldhnum) . '],color:"#ffa500"},{name:"中风险",value:[' . join(',', $ldmnum) . '],color:"#f737ec"},{name:"低风险",value:[' . join(',', $ldlnum) . '],color:"#6060fe"}';
                    $content = str_replace('{$dataip}', join(',', $topid), $content);
                    $content = str_replace('{$data7}', $data7, $content);
//                    $imageCon = str_replace('{$dataip}', join(',', $topid), $imageCon);
//                    $imageCon = str_replace('{$data7}', $data7, $imageCon);

                    /*3.按漏洞类型列表*/
                    $content = str_replace('{$vuls_sys_list}',$this->gridview($tablevul,'type',$rt),$content);

                    /* 4.安全等级详细信息*/
                    $content = str_replace('{$vuls_level_list}',$this->gridview($tablevul,'level',$rt),$content);

                    /* 5.ip详细信息*/
                    $content = str_replace('{$vuls_ip_list}',$this->gridview($tablevul,'ip',$rt),$content);

                    $sql="select * from bd_host_task_manage WHERE id=$val";
                    $row = $db->fetch_row($sql);
                   // var_dump($row);die;
                    $content = str_replace('{$target}', $row['target'], $content);
                    if($row['start_time']==0){
                        $start='未开始';
                    }else{
                        $start=date('Y-m-d H:i:s',$row['start_time']);
                    }
                    if($row['end_time']==0){
                        $end='未结束';
                    }else{
                        $end=date('Y-m-d H:i:s',$row['end_time']);
                    }
                    $content = str_replace('{$starttime}', $start, $content);
                    $content = str_replace('{$endtime}',$end, $content);
//                    $content = str_replace('{$target}', $row['host_policy'], $content);
                }

                $content = str_replace('{$bbtitle}', $bbtitle, $content);
                $content = str_replace('{$reportname}', $bbname, $content);
                $content = str_replace('{$date}', date('Y-m-d H:i:s', time()), $content);
                $content = str_replace('{$reportid}', "BD-REPORT-" . $tasks, $content);
                $content = str_replace('{$tasksname}', $main_tasks['name'], $content);
                $content = str_replace('{$task_id}', $tasks, $content); //修改任务id
                $content = str_replace('{$bbname}', '蓝盾安全扫描系统', $content);

                //$new_content.=$content."\r\n";
                $name=$bbname.'的安全评估';

                if($rt=='html') {
                    file_put_contents($dir . '/attack-'.$tasks. ".html", $content, LOCK_EX);
                }elseif($rt=='pdf'){

                    $new_content = str_replace('{$download_type}','pdf',$content);
                    $new_content = str_replace('{$cover}','<img src="'.\Yii::$app->request->getHostInfo().'/img/cover.png" style="">',$new_content);
//var_dump($new_content);die;
                    $preg ='/<style>.*?<\/style>/si';
                    $new_content = preg_replace($preg,'',$new_content);
                    $preg ='/<div id="sidebar" class="opened">.*?<\/div>/si';
                    //preg_match($preg,$new_content,$match);
                    $new_content =preg_replace($preg,'',$new_content);
                    $html='attack-'.$tasks.'.html';
                    file_put_contents($dir . '/'.$html, $new_content, LOCK_EX);
                    //html转换成pdf
                    $pdf='attack-'.$tasks.'.pdf';
                    $exec="cd /home/bluedon/bdscan/bdwebserver/nginx/html/web/report/now; /home/bluedon/bdscan/bdwebserver/nginx/wkhtmltox/bin/wkhtmltopdf ./$html ./$pdf";
                    system($exec);

                }else{
                    $exec = "cd /home/bluedon/bdscan/bdwebserver/nginx/html/web/report/now; /home/bluedon/bdscan/bdwebserver/nginx/wkhtmltox/bin/wkhtmltoimage --crop-x 50 --crop-y 40 --crop-w 900 --crop-h 300 attack-host-{$tasks}.html {$tasks}-{$date}-1.jpg";
                   echo $exec;die;
                    exec($exec);
                    $new_content = str_replace('{$download_type}','pdf',$content);
                    $preg ='/<style>.*?<\/style>/si';
                    $new_content = preg_replace($preg,'',$new_content);
                    $preg ='/<div id="sidebar" class="opened">.*?<\/div>/si';
                    //preg_match($preg,$new_content,$match);
                    $new_content =preg_replace($preg,'',$new_content);

                    file_put_contents($dir . '/attack-'.$tasks. ".doc", $new_content, LOCK_EX);
                }
                $db->query("INSERT INTO " . getTable('reportsmanage') . " (`name`,`type`,`desc`,`time`,`path`,`timetype`,`format`) VALUES ('$name','1','$desc','$timestamp','$path','1','$rt')");
            }
            //打包
            $bbname =str_replace('://','_',$bbname);
            $bbname =str_replace('/','_',$bbname);
            if($rt=='html'){
                $exec = "cd /home/bluedon/bdscan/bdwebserver/nginx/html/web/report/now; zip -q -r -j ./" ."$bbname-html.zip ./attack*.html ../common.js ../common.css ../jquery-1.9.1.min.js ../bluechar.js";
                //echo $exec;die;
                exec($exec);
                $mom = $bbname . $theTime;
                foreach ($taskss as $v){
                    unlink($dir . '/attack-'.$v . ".html");
                }
                unlink(REPORT_DIR . "./now/common.css");
                unlink(REPORT_DIR . "./now/common.js");
                unlink(REPORT_DIR . "./now/jquery-1.9.1.min.js");
                unlink(REPORT_DIR . "./now/bluechar.js");

                $data['down'] = "<a href=\"/report/now/" . "$bbname-html.zip\" id=\"downAll\" target=\"_self\"" . "><span>下载{$name}</span></a>";
            }elseif($rt=='pdf'){
                $exec = "cd /home/bluedon/bdscan/bdwebserver/nginx/html/web/report/now; zip -q -r -j ./$bbname-pdf.zip" . " ./attack*.pdf ";
                exec($exec);

                //删掉html,pdf
                foreach ($taskss as $v){
                    unlink($dir . '/attack-'.$v . ".html");
                    unlink($dir . '/attack-'.$v . ".pdf");
                }
                unlink(REPORT_DIR . "./now/common.css");
                unlink(REPORT_DIR . "./now/common.js");
                unlink(REPORT_DIR . "./now/jquery-1.9.1.min.js");
                unlink(REPORT_DIR . "./now/bluechar.js");

                $data['down'] = "<a href=\"/report/now/" . $bbname .'-pdf'. ".zip\" id=\"downAll\" target=\"_self\"" . "><span>下载{$name}</span></a>";
            }else{

            }
            $data['success'] = true;
            $data['message'] = '操作成功';
//            if($kidbb == 1 && $hostnum >1){
//                $data['mom'] = $mom;
//            }
            $hdata['sDes'] = '导出报表';
            $hdata['sRs'] ='导出成功';
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

    function getLevel($levelarray)
    {
        $array = array();
        foreach ($levelarray as $k => $v) {
            $array[] = $v['level'];
        }
        if (in_array('H', $array)) {
            return '高危';
        }
        if (in_array('M', $array)) {
            return '中危';
        }
        if (in_array('L', $array)) {
            return '低危';
        }
        if (in_array('I', $array)) {
            return '信息';
        }
        return '安全';
    }

    function random_color(){
        mt_srand((double)microtime()*1000000);
        $c = '';
        while(strlen($c)<6){
            var_dump($c);
            $c .= sprintf("%02X", mt_rand(0, 255));
        }
        return '#'.$c;
    }
    function random_color2(){
        $color_arr =['#ffa500','#ffff00','#6060fe','green','blue','yellow'];

    }
    function randrgb()
    {
        $color=["#008e8f","#afd8f8","#8e468f","#9d080c","#b2aa00","#8cba00","#FF550D","#ebd72e","#3fa0fa","#f737ec"];

        $str='0123456789ABCDEF';
        $estr='#';
        $len=strlen($str);
        for($i=1;$i<=6;$i++)
        {
            $num=rand(0,$len-1);
            $estr=$estr.$str[$num];
        }
        return $estr;
    }

    function listByLevel($tablevul,$category,$type='html'){
        global $db;
        if(stripos($tablevul,'web')>0 || stripos($tablevul,'weakpwd')>0){

        }else{

        }
    }


    function gridview_web($table,$category,$type='html'){
        global $db;
        $vul_level = array( 'H'=>'高风险', 'M'=>'中风险', 'L'=>'低风险' ,'I'=>'信息');
        $risk_css = array( 'H'=>'high', 'M'=>'medium', 'L'=>'low' ,'I'=>'low');
        $rcccolor = array( 'H'=>'#d2322d', 'M'=>'#d58512', 'L'=>'#3276b1', 'I'=>'#3276b1');
        $html = $whtml = '';
        if($category=='level'){ //按等级
            $index=4;
            $hflist=[
                ['id'=>1,'desc'=>'高危','level'=>'H'],
                ['id'=>2,'desc'=>'中危','level'=>'M'],
                ['id'=>3,'desc'=>'低危','level'=>'L'],
            ];
        }elseif($category=='ip'){ //按ip
            $index=5;
            $hflist=[
                ['id'=>1,'desc'=>'按ip'],
            ];
        }else{  //按类型
            $hflist = $db->fetch_all("SELECT id,description as `desc` FROM bd_web_family WHERE parent_id='0' ORDER BY id ASC");
            $index=3;
        }
        //    var_dump($hflist);die;
        foreach($hflist as $h=>$f){
            $html.='<div name="hostname" class="y-report-ui-comp-section"  ><div id="t'.$index.'_'.($h+1).'" class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$index.'.'.($h+1).' '.$f['desc'].'</div>';
            $whtml.='<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$hostIndex.'.'.($h+1).' '.$f['desc'].'</p>';
            if($category=='level'){
                $sql ="select a.*,count(1) as urlsum from $table a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id WHERE a.level='{$f['level']}' GROUP BY a.vul_id";
                $myrows = $db->fetch_all($sql);
            }elseif($category=='ip'){
                $sql ="select a.*,count(1) as urlsum from $table a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id  GROUP BY a.vul_id";
                $myrows = $db->fetch_all($sql);
            }else{  //类型
                $sql ="select a.*,b.module_id as category,count(1) as urlsum from $table a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id WHERE b.module_id = {$f['id']} GROUP BY a.vul_id";
                //echo $sql;die;
                $myrows = '';
            }

            if(empty($myrows)){
                $html.='<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                $whtml.='<p style="line-height:20px;text-indent:4em;width:100%">本次扫描没有发现该风险。</p>';
            }else{
                //$html.='<p class="y-report-ui-element-content">本次扫描共发现该风险<span class="y-report-ui-text-normal-b"> '.$v['ipnum'].' </span>个。</p>';
                $html.='<div><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th><th width="10%" class="y-report-ui-comp-data-grid-th">影响主机数</th><th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';

                //$whtml.='<p style="line-height:20px;text-indent:4em;width:100%">本次扫描共发现该风险<span style="color:#000000;font-weight:bold"> '.($cums + $hums + $mums + $lums + $iums).' </span>个。</p>';

                $whtml.= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';

                foreach($myrows as $k=>$v){
                    $jieb = $vul_level["{$v['level']}"];
                    $rcss = $risk_css["{$v['level']}"];
                    $rcor = $rcccolor["{$v['level']}"];
                    $html .= '<tr><td colspan="4"><span id="recordweb-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
                    $html .= '<tr class="y-report-ui-comp-data-grid-tr-' . ($k % 2) . '"><td class="y-report-ui-text-level-' . $rcss . '-b" id="webfxjb">' . $jieb . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="webfxname">' . $v['vul_name'] . '</td><td id="weburlnum">' . $v['urlsum'] . '</td><td special="openDetail" class="y-report-ui-element-more-info-link">展开详情</td></tr>';
                    $whtml .= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:' . $rcor . '">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div style="clear:both"></div></div>';
                    $whtml .= '<div><div><div style="position:relative;">';
                    $whtml .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                    $whtml .= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">影响URL数</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['urlsum'] . '</td></tr>';

                    $html .= '<tr style="display:none"><td colspan="4" style="opacity:0;-ms-filter:\'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)\';filter:alpha(opacity=0);-webkit-opacity:0;-moz-opacity:0;-khtml-opacity:0"><div class="y-report-ui-object-expandable-grid-detail-panel"><div class="y-report-ui-object-expandable-grid-detail-panel-header-frame"><div class="y-report-ui-object-expandable-grid-detail-panel-header-title"> [ <span class="y-report-ui-text-level-' . $rcss . '-b">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div class="y-report-ui-object-expandable-grid-detail-panel-header-close" special="closeDetail">关闭</div><div style="clear:both"></div></div><div class="y-report-ui-object-expandable-grid-detail-panel-content-frame"><div class="y-report-ui-object-tab-panel-frame" special="objectType#tabPanel"><div style="position:relative;" class="y-report-ui-object-tab-panel-header-frame"><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button-toggled">URL列表（共' . $v['urlsum'] . '项）</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">风险描述</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">解决方案</div><div style="clear:both"></div></div><div class="y-report-ui-object-tab-panel-content-frame"><div style="float:left" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-accordion-list-frame" special="objectType#accordionList">';
                    $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">URL列表（共' . $v['urlsum'] . '项）</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">';
                    $urllist = $db->fetch_all("SELECT url FROM $table WHERE vul_id='{$v['vul_id']}'");
                    foreach ($urllist as $l => $u) {
                        $html .= '<div class="y-report-ui-object-accordion-list-item-frame" id="web_urllist"><div class="y-report-ui-object-accordion-list-item-header">' . $u['url'] . '</div></div>';
                        $whtml .= $u['url'] . '<br />';
                    }
                    $whtml .= '</td></tr>';
                    $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">风险描述</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['description'] . '</td></tr>';
                    $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">解决方案</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['solution'] . '</td></tr>';
                    $whtml .= '</tbody></table>';
                    $whtml .= '</div></div></div>';
                    $whtml .= '</td></tr>';

                    $html .= '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="web_fxms">' . $v['description'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="web_jjfa">' . $v['solution'] . '</div></div></div></div></div></div></td></tr>';
                    $html .= '</tbody></table></span></td></tr>';
                }
                $html.='</tbody></table></div>';
                $whtml.='</tbody></table>';
            }

            $html.='</div>';
        }
        # echo $html;die;
        if($type=='pdf' || $type=='word'){
            return $whtml;
        }else{
            return $html;
        }
    }

    //生成列表
    function gridview($tablevul,$category=1,$type='html'){
        global $db;
        $vul_level = array( 'H'=>'高风险', 'M'=>'中风险', 'L'=>'低风险' ,'I'=>'信息');
        $risk_css = array( 'H'=>'high', 'M'=>'medium', 'L'=>'low' ,'I'=>'low');
        $rcccolor = array( 'H'=>'#d2322d', 'M'=>'#d58512', 'L'=>'#3276b1', 'I'=>'#3276b1');
        $query = new Query();

        if($category==''){

        }else {
            //弱密码列表
           if (stripos($tablevul, 'weakpwd') > 0) {
               $html = $whtml = '';
               if($category=='level'){ //按等级
                   $index=4;
                   $hflist=[
                       ['id'=>1,'desc'=>'高危','level'=>'H'],
//                       ['id'=>2,'desc'=>'中危','level'=>'M'],
//                       ['id'=>3,'desc'=>'低危','level'=>'L'],
                   ];
               }elseif($category=='ip'){ //按ip
                   $index=5;
                   $hflist=[
                       ['id'=>1,'desc'=>'按ip'],
                   ];
               }else{  //按类型
                   $hflist = $db->fetch_all("SELECT id,vul_name as `desc` FROM bd_weakpwd_vul_lib  ORDER BY id ASC");
                   $index=3;
               }
               foreach($hflist as $h=>$f) {
                   $html .= '<div name="hostname" class="y-report-ui-comp-section"  ><div id="t' . $index . '_' . ($h + 1) . '" class="y-report-ui-element-title-level-3 ' . $classTemp . '">' . $index . '.' . ($h + 1) . ' ' . $f['desc'] . '</div>';
                   $whtml .= '<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;' . $styleTemp . '">' . $hostIndex . '.' . ($h + 1) . ' ' . $f['desc'] . '</p>';
                   if ($category == 'level') {
                       $sql = "select a.*,count(1) as urlsum from $tablevul a LEFT JOIN bd_weakpwd_vul_lib b  on a.vul_id = b.vul_id WHERE a.level='{$f['level']}' GROUP BY a.vul_id";
                       $myrows = $db->fetch_all($sql);
                   } elseif ($category == 'ip') {
                       $sql = "select a.*,count(1) as urlsum from $tablevul a LEFT JOIN bd_weakpwd_vul_lib b  on a.vul_id = b.vul_id  GROUP BY a.vul_id";
                       $myrows = $db->fetch_all($sql);
                       //var_dump($myrows);continue;
                   } else {  //类型
                       $sql = "select a.*,b.vul_id as category,count(1) as urlsum from $tablevul a LEFT JOIN bd_weakpwd_vul_lib b  on a.vul_id = b.vul_id WHERE b.vul_id = {$f['id']} GROUP BY a.vul_id";
                       //echo $sql;die;
                       $myrows = $db->fetch_all($sql);
                   }

                   if (empty($myrows)) {
                       $html .= '<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                       $whtml .= '<p style="line-height:20px;text-indent:4em;width:100%">本次扫描没有发现该风险。</p>';
                   } else {
//                       $html = '<p class="y-report-ui-element-content ' . $classTemp . '">本次扫描共发现弱密码<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>个。影响主机<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>个。</p>';
                       $html .= '<div class="' . $classTemp . '"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="20%" class="y-report-ui-comp-data-grid-th">IP</th><th width="30%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">用户名</th><th width="30%" class="y-report-ui-comp-data-grid-th">密码</th><th width="20%" class="y-report-ui-comp-data-grid-th">弱密码类型</th></tr>';

//                       $whtml = '<p class="y-report-ui-element-content ' . $classTemp . '">本次扫描共发现弱密码<span class="y-report-ui-text-normal-b"> ' . $mmjl . ' </span>个。影响主机<span class="y-report-ui-text-level-info-b"> ' . count($rmmip) . ' </span>个。</p>';
                       $whtml .= '<table cellpadding="0" style="' . $styleTemp . ';font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody><tr><td width="20%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">IP</td><td width="30%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">用户名</td><td width="30%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">密码</td><td width="20%" style="height:25px;color:#FFF;background-color:#6296D3;font-weight:bold;padding:2px">弱密码类型</td></tr>';
                       $data = $db->fetch_all("select * from $tablevul");
                       foreach ($data as $m => $n) {
                           $html .= '<span id="record-show"><tr class="y-report-ui-comp-data-grid-tr-' . ($m % 2) . '"><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-ip">' . '<a href="' . $n['ip'] . '.html" target="_blank">' . $n['ip'] . '</a>' . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-user">' . $n['username'] . '</td><td id="rmm-password">' . $n['password'] . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="rmm-type">' . $n['type'] . '</td></tr>';
                           $whtml .= '<tr style="height:25px;text-align:center;background-color:' . $kcolor[($m % 2)] . '"><td>' . $n['ip'] . '</td><td style="padding-right:1em !important;padding-left:1em !important;text-align:left">' . $n['username'] . '</td><td>' . $n['password'] . '</td><td>' . $n['type'] . '</td></tr></span>';
                       }
                       $html .= '</tbody></table></div>';
                       $whtml .= '</tbody></table>';
                   }
               }
               if($type=='pdf' || $type=='word'){
                   return $whtml;
               }else{
                   return $html;
               }
            }

            //web列表
            elseif (stripos($tablevul, 'web') > 0){
               $html = $whtml = '';
               if($category=='level'){ //按等级
                   $index=4;
                   $hflist=[
                       ['id'=>1,'desc'=>'高危','level'=>'H'],
                       ['id'=>2,'desc'=>'中危','level'=>'M'],
                       ['id'=>3,'desc'=>'低危','level'=>'L'],
                   ];
               }elseif($category=='ip'){ //按ip
                   $index=5;
                   $hflist=[
                       ['id'=>1,'desc'=>'按ip'],
                   ];
               }else{  //按类型
                   $hflist = $db->fetch_all("SELECT id,description as `desc` FROM bd_web_family WHERE parent_id='0' ORDER BY id ASC");
                   $index=3;
               }
           //    var_dump($hflist);die;
               foreach($hflist as $h=>$f){
                   $html.='<div name="hostname" class="y-report-ui-comp-section"  ><div id="t'.$index.'_'.($h+1).'" class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$index.'.'.($h+1).' '.$f['desc'].'</div>';
                   $whtml.='<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$hostIndex.'.'.($h+1).' '.$f['desc'].'</p>';
                   if($category=='level'){
                       $sql ="select a.*,count(1) as urlsum from $tablevul a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id WHERE a.level='{$f['level']}' GROUP BY a.vul_id";
                       $myrows = $db->fetch_all($sql);
                   }elseif($category=='ip'){
                       $sql ="select a.*,count(1) as urlsum from $tablevul a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id  GROUP BY a.vul_id";
                       $myrows = $db->fetch_all($sql);
                       //var_dump($myrows);continue;
                   }else{  //类型
                       $sql ="select a.*,b.module_id as category,count(1) as urlsum from $tablevul a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id WHERE b.module_id = {$f['id']} GROUP BY a.vul_id";
                       //echo $sql;die;
                       $myrows = $db->fetch_all($sql);
                   }

                   if(empty($myrows)){
                       $html.='<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                       $whtml.='<p style="line-height:20px;text-indent:4em;width:100%">本次扫描没有发现该风险。</p>';
                   }else{

                       //$html.='<p class="y-report-ui-element-content">本次扫描共发现该风险<span class="y-report-ui-text-normal-b"> '.$v['ipnum'].' </span>个。</p>';
                       $html.='<div><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr>
<th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th>
<th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th>
<th width="10%" class="y-report-ui-comp-data-grid-th">影响主机数</th>
<th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';

                       //$whtml.='<p style="line-height:20px;text-indent:4em;width:100%">本次扫描共发现该风险<span style="color:#000000;font-weight:bold"> '.($cums + $hums + $mums + $lums + $iums).' </span>个。</p>';

                       $whtml.= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';

                       foreach($myrows as $k=>$v){

                           $v['solution']=preg_replace('<script>','&lt;script&gt;',$v['solution']);
                           $v['description']=preg_replace('<script>','&lt;script&gt;',$v['description']);
                           $v['solution']=htmlentities(($v['solution']));
                           $v['description']=htmlentities(($v['description']));

                           $jieb = $vul_level["{$v['level']}"];
                           $rcss = $risk_css["{$v['level']}"];
                           $rcor = $rcccolor["{$v['level']}"];
                           $html .= '<tr><td colspan="4"><span id="recordweb-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
                           $html .= '<tr class="y-report-ui-comp-data-grid-tr-' . ($k % 2) . '"><td class="y-report-ui-text-level-' . $rcss . '-b" id="webfxjb">' . $jieb . '</td><td class="y-report-ui-comp-data-grid-td-text-align-left" id="webfxname">' . $v['vul_name'] . '</td><td id="weburlnum">' . $v['urlsum'] . '</td><td special="openDetail" class="y-report-ui-element-more-info-link">展开详情</td></tr>';
                           $whtml .= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:' . $rcor . '">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div style="clear:both"></div></div>';
                           $whtml .= '<div><div><div style="position:relative;">';
                           $whtml .= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                           $whtml .= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">影响URL数</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['urlsum'] . '</td></tr>';

                           $html .= '<tr style="display:none"><td colspan="4" style="opacity:0;-ms-filter:\'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)\';filter:alpha(opacity=0);-webkit-opacity:0;-moz-opacity:0;-khtml-opacity:0"><div class="y-report-ui-object-expandable-grid-detail-panel"><div class="y-report-ui-object-expandable-grid-detail-panel-header-frame"><div class="y-report-ui-object-expandable-grid-detail-panel-header-title"> [ <span class="y-report-ui-text-level-' . $rcss . '-b">' . $jieb . '</span> ] ' . $v['vul_name'] . '</div><div class="y-report-ui-object-expandable-grid-detail-panel-header-close" special="closeDetail">关闭</div><div style="clear:both"></div></div><div class="y-report-ui-object-expandable-grid-detail-panel-content-frame"><div class="y-report-ui-object-tab-panel-frame" special="objectType#tabPanel"><div style="position:relative;" class="y-report-ui-object-tab-panel-header-frame"><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button-toggled">URL列表（共' . $v['urlsum'] . '项）</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">风险描述</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">解决方案</div><div style="clear:both"></div></div><div class="y-report-ui-object-tab-panel-content-frame"><div style="float:left" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-accordion-list-frame" special="objectType#accordionList">';
                           $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">URL列表（共' . $v['urlsum'] . '项）</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">';
                           $urllist = $db->fetch_all("SELECT url FROM $tablevul WHERE vul_id='{$v['vul_id']}'");
                           foreach ($urllist as $l => $u) {
                               $html .= '<div class="y-report-ui-object-accordion-list-item-frame" id="web_urllist"><div class="y-report-ui-object-accordion-list-item-header">' . $u['url'] . '</div></div>';
                               $whtml .= $u['url'] . '<br />';
                           }
                           $whtml .= '</td></tr>';
                           $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">风险描述</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['description'] . '</td></tr>';
                           $whtml .= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">解决方案</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">' . $v['solution'] . '</td></tr>';
                           $whtml .= '</tbody></table>';
                           $whtml .= '</div></div></div>';
                           $whtml .= '</td></tr>';

                           $html .= '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="web_fxms">' . $v['description'] . '</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="web_jjfa">' . $v['solution'] . '</div></div></div></div></div></div></td></tr>';
                           $html .= '</tbody></table></span></td></tr>';
                       }
                       $html.='</tbody></table></div>';
                       $whtml.='</tbody></table>';
                   }

                   $html.='</div>';
               }
              # echo $html;die;
               if($type=='pdf' || $type=='word'){
                   return $whtml;
               }else{
                   return $html;
               }
           }

         //主机、
         elseif (stripos($tablevul, 'host') > 0){
                $html = $whtml = '';
                if($category=='level'){ //按等级
                    $index=4;
                    $hflist=[
                        ['id'=>1,'desc'=>'高危','level'=>'H'],
                        ['id'=>2,'desc'=>'中危','level'=>'M'],
                        ['id'=>3,'desc'=>'低危','level'=>'L'],
                    ];

                }elseif($category=='ip'){ //按ip
                    $index=5;
                    $hflist=[
                        ['id'=>1,'desc'=>'按ip'],
                    ];

                }else{  //按类型
                    if(stripos($tablevul, 'web') > 0){
                        $hflist = $db->fetch_all("SELECT id,description as `desc` FROM bd_web_family WHERE parent_id='0' ORDER BY id ASC");
                    }elseif (stripos($tablevul, 'host') > 0){
                        $hflist = $db->fetch_all("SELECT id,description as `desc` FROM bd_host_family_list WHERE parent_id='0' ORDER BY id ASC");
                    }else{
                        $table = 'bd_weakpwd_vul_lib';
                    }
                    $index=3;
                }

                foreach($hflist as $h=>$f){
                    $html.='<div name="hostname" class="y-report-ui-comp-section"  ><div id="t'.$index.'_'.($h+1).'" class="y-report-ui-element-title-level-3 '.$classTemp.'">'.$index.'.'.($h+1).' '.$f['desc'].'</div>';
                    $whtml.='<p style="vertical-align:middle;line-height:30px;text-indent:2.5em;height:30px;font-size:16px;width:100%;font-weight:bold;'.$styleTemp.'">'.$hostIndex.'.'.($h+1).' '.$f['desc'].'</p>';

                    if($category=='level'){
                        if(stripos($tablevul, 'web') > 0){
                            $sql ="select a.*,count(1) as ipsum from $tablevul a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id WHERE a.level='{$f['level']}' GROUP BY a.vul_id";
                            $myrows = $db->fetch_all($sql);
                        }elseif (stripos($tablevul, 'host') > 0){
                            $sql ="select a.*,count(1) as ipsum from $tablevul a LEFT JOIN bd_host_vul_lib b  on a.vul_id = b.vul_id WHERE a.vul_level='{$f['level']}' GROUP BY a.vul_id";
                            $myrows = $db->fetch_all($sql);
                        }else{
                            $table = 'bd_weakpwd_vul_lib';
                        }

                     //   var_dump($myrows);die;
                    }elseif($category=='ip'){
                        if(stripos($tablevul, 'web') > 0){
                            $sql ="select a.*,count(1) as ipsum from $tablevul a LEFT JOIN bd_web_vul_lib b  on a.vul_id = b.vul_id  GROUP BY a.vul_id";
                            $myrows = $db->fetch_all($sql);
                        }elseif (stripos($tablevul, 'host') > 0){
                            $sql ="select a.*,count(1) as ipsum from $tablevul a LEFT JOIN bd_host_vul_lib b  on a.vul_id = b.vul_id  GROUP BY a.vul_id";
                            //echo $sql;die;
                            $myrows = $db->fetch_all($sql);
                        }else{
                            $table = 'bd_weakpwd_vul_lib';
                        }

                        //   var_dump($myrows);die;
                    }else{  //类型
                        $sql ="select a.*,b.category,count(1) as ipsum from $tablevul a LEFT JOIN bd_host_vul_lib b  on a.vul_id = b.vul_id WHERE b.category = {$f['id']} GROUP BY a.vul_id";
                        //echo $sql;die;
                        $myrows = $db->fetch_all($sql);
                    }

                        // $sql="SELECT vul.solution, vul.ip,vul.vul_id,vul.risk_factor,vul.port,vul.proto,vul.output,vinfo.cve,vinfo.cnvd,vinfo.cnnvd,vinfo.vul_name,vinfo.desc_cn,vinfo.ref_cn,count(1) as ipsum FROM $tablevul vul, host_family_ref hfr, vul_info vinfo WHERE vul.vul_id=hfr.vul_id AND vul.vul_id=vinfo.vul_id AND hfr.family IN (SELECT id FROM host_family_list WHERE parent_id={$f['id']}) group by vul_id order by risk_factor_num ASC";
//                        $sql="select *,count(1) as ipsum  from $tablevul GROUP by vul_id order by vul_level desc";

                        if(empty($myrows)){
                            $html.='<p class="y-report-ui-element-content">本次扫描没有发现该风险。</p>';
                            $whtml.='<p style="line-height:20px;text-indent:4em;width:100%">本次扫描没有发现该风险。</p>';
                        }else{
                            //$html.='<p class="y-report-ui-element-content">本次扫描共发现该风险<span class="y-report-ui-text-normal-b"> '.$v['ipnum'].' </span>个。</p>';
                            $html.='<div><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody><tr><th width="10%" class="y-report-ui-comp-data-grid-th">风险评级</th><th width="70%" class="y-report-ui-comp-data-grid-th y-report-ui-comp-data-grid-td-text-align-left">风险名称</th><th width="10%" class="y-report-ui-comp-data-grid-th">影响主机数</th><th width="10%" class="y-report-ui-comp-data-grid-th">更多信息</th></tr>';

                            //$whtml.='<p style="line-height:20px;text-indent:4em;width:100%">本次扫描共发现该风险<span style="color:#000000;font-weight:bold"> '.($cums + $hums + $mums + $lums + $iums).' </span>个。</p>';

                            $whtml.= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';

                            foreach($myrows as $k=>$v){
                                //$total = $db->fetch_first("select count(1) from $tablevul WHERE category = $v");

                                $jieb = $vul_level["{$v['vul_level']}"];
                                $rcss = $risk_css["{$v['vul_level']}"];
                                $rcor = $rcccolor["{$v['vul_level']}"];
                                $html.='<tr><td colspan="4"><span id="record-show"><table cellpadding="0" class="y-report-ui-comp-data-grid" special="objectType#expandableGrid" cellspacing="0"><tbody>';
                                $html.= '<tr class="y-report-ui-comp-data-grid-tr-'.($k%2).'"><td class="y-report-ui-text-level-'.$rcss.'-b"><div id="leaklevel">'.$jieb.'</div></td><td class="y-report-ui-comp-data-grid-td-text-align-left"><div id="leakname">'.$v['vul_name'].'</div></td><td>'.$v['ipsum'].'</td><td special="openDetail" class="y-report-ui-element-more-info-link">展开详情</td></tr>';
                                $whtml.= '<tr><td colspan="2" style=" border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px"><div><div><div style="background-color:#91C5F6; vertical-align:middle; height:20px; line-height: 20px; width: 100%"> [ <span style="color:'.$rcor.'">'.$jieb.'</span> ] '.$v['vul_name'].'</div><div style="clear:both"></div></div>';
                                $whtml.= '<div><div><div style="position:relative;">';
                                $whtml.= '<table cellpadding="0" style="font-size:12px;width:100%;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px" cellspacing="0"><tbody>';
                                $whtml.= '<tr><td style="width:140px; padding:3px; border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">影响主机数</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">'.$v['ipsum'].'</td></tr>';
                                $html.= '<tr style="display:none"><td colspan="4" style="opacity:0;-ms-filter:\'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)\';filter:alpha(opacity=0);-webkit-opacity:0;-moz-opacity:0;-khtml-opacity:0"><div class="y-report-ui-object-expandable-grid-detail-panel"><div class="y-report-ui-object-expandable-grid-detail-panel-header-frame"><div class="y-report-ui-object-expandable-grid-detail-panel-header-title"> [ <span class="y-report-ui-text-level-'.$rcss.'-b">'.$jieb.'</span> ] '.$v['vul_name'].'</div><div class="y-report-ui-object-expandable-grid-detail-panel-header-close" special="closeDetail">关闭</div><div style="clear:both"></div></div><div class="y-report-ui-object-expandable-grid-detail-panel-content-frame"><div class="y-report-ui-object-tab-panel-frame" special="objectType#tabPanel"><div style="position:relative;" class="y-report-ui-object-tab-panel-header-frame"><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button-toggled">主机列表（共'.$v['ipsum'].'项）</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">风险描述</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">解决方案</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">相关编号</div><div style="float:left;" class="y-report-ui-object-host-vuln-list-tab-header y-report-ui-object-tab-panel-header-button">参考信息</div><div style="clear:both"></div></div><div class="y-report-ui-object-tab-panel-content-frame"><div style="float:left" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-accordion-list-frame" special="objectType#accordionList">';

                                $whtml.= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">主机列表（共'.$v['ipsum'].'项）</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">';

                                $louat = $db->fetch_all("SELECT ip,null as output,port_proto FROM $tablevul WHERE vul_id='{$v['vul_id']}'");
                                foreach($louat as $i=>$l){
                                    $html.='<div class="y-report-ui-object-accordion-list-item-frame"><div class="y-report-ui-object-accordion-list-item-header">'.$l['ip'].' [ '.$l['port_proto'].' ]</div><div class="y-report-ui-object-accordion-list-item-content"><div class="y-report-ui-object-accordion-list-item-content-text-container"><br />'.$l['output'].'</div></div></div>';
                                    $whtml.= $l['ip'].' [ '.$l['port_proto'].' ] <br />';
                                }
                                $whtml.= '</td></tr>';
                                $whtml.= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">风险描述</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">'.$v['description'].'</td></tr>';
                                $whtml.= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">解决方案</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">'.$v['solution'].'</td></tr>';
                                $whtml.= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">相关编号</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">CVE：'.$v['cve'].'<br />CNVD：'.$v['cnvd'].'<br />CNNVD：'.$v['cnnvd'].'</td></tr>';
                                $whtml.= '<tr><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">参考信息</td><td style="padding:3px;border-style:solid;border-color:#6296D3;table-layout:fixed;border-width:1px">'.$v['ref'].'</td></tr>';
                                $whtml.= '</tbody></table>';
                                $whtml.= '</div></div></div>';
                                $whtml.= '</td></tr>';

                                $html.='</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="desc_cn">'.$v['description'].'</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="solu_cn">'.$v['solution'].'</div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="host_xgbh"><table cellpadding="0" cellspacing="0" width="100%"><tbody><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" width="7%" style="vertical-align:top">CVE</th><td width="5px" style="vertical-align:top"> : </td><td id="cve-val">'.$v['cve'].'</td></tr><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" style="vertical-align:top">CNVD</th><td width="5px" style="vertical-align:top"> : </td><td>'.$v['cnvd'].'</td></tr><tr><th class="y-report-ui-text-align-left y-report-ui-text-weight-normal" style="vertical-align:top">CNNVD</th><td width="5px" style="vertical-align:top"> : </td><td>'.$v['cnnvd'].'</td></tr></tbody></table></div></div><div style="float:left;display:none" class="y-report-ui-object-tab-panel-content-element"><div class="y-report-ui-object-tab-panel-content-element-text-container" id="ref_cn">'.$v['ref'].'</div></div></div></div></div></div></td></tr>';
                                $html.='</tbody></table></span></td></tr>';
                            }
                            $html.='</tbody></table></div>';
                            $whtml.='</tbody></table>';
                        }

                    $html.='</div>';
                }
                if($type=='pdf' || $type=='word'){
                    return $whtml;
                }else{
                    return $html;
                }
            }
//            $dataProvider = new ActiveDataProvider([
//                'query' => $query->select($sql)->from($tablevul)->leftJoin("$lib as b", "b.vul_id=$tablevul.vul_id")->where($where),
//                'pagination' => false
//            ]);
//
//
//            if ($dataProvider->getCount() == 0) {
//                return "<p style=\"line-height:20px;text-indent:4em;width:100%\">本次扫描没有发现该风险。</p>";
//            } else {
//                $gridview = GridView::widget([
//                    'dataProvider' => $dataProvider,
//                    'headerRowOptions' => ['style' => 'width:100%'],
//                    //'rowOptions'=>['style'=>'width:10%'],
//                    'layout' => "{items}\n{pager}",
//                    'columns' => [
//                        [
//                            'headerOptions' => ['style' => 'width:12%'],
//                            'attribute' => 'ip',
//                        ],
//                        [
//                            'headerOptions' => ['style' => 'width:10%'],
//                            'attribute' => 'family',
//                            'label' => '分类',
//                            'value' => function ($data) use ($category, $tablevul) {
//                                if (stripos($tablevul, 'web') > 0) {
//                                    $arr = $this->getParentCategory('web');
//                                } else {
//                                    $arr = $this->getParentCategory('host');
//                                    //$arr= [1=>'系统漏洞',2=>'服务漏洞',3=>'应用漏洞',4=>'网络设备漏洞',5=>'数据库漏洞',6=>'虚拟化平台漏洞'];
//                                }
//                                if (!is_numeric($category)) {
//                                    return $data['family'];
//                                } else {
//                                    return $arr[$data['category']];
//                                }
//
//                            }
//                        ],
//                        [
//                            'headerOptions' => ['style' => 'width:15%'],
//                            'attribute' => 'vul_name',
//                            'label' => '漏洞名'
//                        ],
//                        [
//                            'headerOptions' => ['style' => 'width:8%'],
//                            'attribute' => 'vul_level',
//                            'label' => '漏洞级别',
//                            'value' => function ($data) use ($tablevul) {
//                                if (stripos($tablevul, 'web') > 0) {
//                                    return $data['level'] == 'H' ? '高危' : ($data['level'] == 'M' ? '中危' : ($data['level'] == 'L' ? '低危' : '信息'));
//                                } elseif (stripos($tablevul, 'weakpwd') > 0) {
//                                    return '高危';   //弱密码默认高危
//                                } else {
//                                    return $data['vul_level'] == 'H' ? '高危' : ($data['vul_level'] == 'M' ? '中危' : ($data['vul_level'] == 'L' ? '低危' : '信息'));
//                                }
//                            },
//                            //  'options'=>['style'=>'width:10%']
//                        ],
//                        [
//                            'headerOptions' => ['style' => 'width:30%'],
//                            'attribute' => 'description',
//                            'label' => '漏洞描述'
//                        ],
//                        [
//                            'headerOptions' => ['style' => 'width:25%'],
//                            'attribute' => 'solution',
//                            'label' => '解决方案'
//                        ],
//                        // ...
//                    ],
//
//                    //'headerRowOptions' => ['class'=>'th'],
//                ]);
//
//
//                return $html;
//                //return $gridview;
//            }
        }
    }

    //获取父分类
    function getParentCategory($type){
        global $db;
        if($type=='web'){
            $category = $db->fetch_all("select id,description from bd_web_family WHERE parent_id=0");
            $category = ArrayHelper::map($category,'id','description');
        }elseif ($type=='host'){
            $category = $db->fetch_all("select id,description from bd_host_family_list WHERE parent_id=0");
            $category = ArrayHelper::map($category,'id','description');
        }
        return $category;
    }


    function getWordDocument( $content , $absolutePath = "" , $isEraseLink = true )
    {
        $mht = new MhtFileMaker();
        if ($isEraseLink)
            $content = preg_replace('/<a\s*.*?\s*>(\s*.*?\s*)<\/a>/i' , '$1' , $content);   //去掉链接
        $images = array();
        $files = array();
        $matches = array();
        //这个算法要求src后的属性值必须使用引号括起来
        if ( preg_match_all('/<img[.\n]*?src\s*?=\s*?[\"\'](.*?)[\"\'](.*?)\/>/i',$content ,$matches ) )
        {
            $arrPath = $matches[1];
            for ( $i=0;$i<count($arrPath);$i++)
            {
                $path = $arrPath[$i];
                $imgPath = trim( $path );
                if ( $imgPath != "" )
                {
                    $files[] = $imgPath;
                    if( substr($imgPath,0,7) == 'http://')
                    {
                        //绝对链接，不加前缀
                    }
                    else
                    {
                        $imgPath = $absolutePath.$imgPath;
                    }
                    $images[] = $imgPath;
                }
            }
        }
        $mht->AddContents("tmp.html",$mht->GetMimeType("tmp.html"),$content);

        for ( $i=0;$i<count($images);$i++)
        {
            $image = $images[$i];
            if ( @fopen($image , 'r') )
            {
                $imgcontent = @file_get_contents( $image );
                if ( $content )
                    $mht->AddContents($files[$i],$mht->GetMimeType($image),$imgcontent);
            }
            else
            {
                echo "file:".$image." not exist!<br />";
            }
        }

        return $mht->GetFile();
    }
}



?>