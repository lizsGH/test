<?php
namespace app\controllers;
/**
 * Class TemplatemanageController
 * @package app\controllers
 * 模板管理
 */
class TemplatemanageController extends BaseController
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
        global $db, $act;
        $aData = array();
        $userid = intval($_SESSION['userid']);
        $loginuser = $db->fetch_first("select role_id as  role from bd_sys_user WHERE id=$userid ");
        $minute = array('00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55');
        $hour = array('00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'); //时间 00-23
        $day_of_month = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'); //天数 01-31
        $month = array('01' => '1月', '02' => '2月', '03' => '3月', '04' => '4月', '05' => '5月', '06' => '6月', '07' => '7月', '08' => '8月', '09' => '9月', '10' => '10月', '11' => '11月', '12' => '12月'); //月份 1-12
        $year = array('2012', '2013', '2014', '2015', '2016', '2017');
        $periodunit = array('hour' => Yii::t('app', '小时'), 'day' => Yii::t('app', '天'), 'week' => Yii::t('app', '周'), 'month' => Yii::t('app', '月'));
        // 端口
        $ports_where = " WHERE 1=1 AND preset=0";
        if ($loginuser['role'] != 16) { //不是系统管理员
            $ports_where .= " AND user_id=$userid";
        }
        $ports_where .= " OR preset = 2 OR preset = 1";
        $ports = $db->fetch_all("SELECT * FROM port_manage $ports_where ORDER BY id DESC ");
        //*添加默认端口*/
        /* $portres = $db->fetch_all("SELECT * FROM port_manage where preset=1 OR preset = 2");
         foreach($portres as $k =>$v){
             array_push($ports,$v);
         }*/
        //主机
        $host_where = " WHERE preset=0 ";
        if ($loginuser['role'] != 16) { //不是系统管理员
            $host_where .= " AND user_id=$userid";
        }
        $host_where .= " OR preset = 2 OR preset = 1";
        $host = $db->fetch_all("SELECT * FROM host_policy $host_where ORDER BY id DESC");
        //*添加默认策略、*/
        /*$hostres = $db->fetch_all("SELECT * FROM host_policy where  preset=1 OR preset = 2");
        foreach($hostres as $k =>$v){
            array_push($host,$v);
        }*/
        //web
        $web_where = " WHERE 1=1 AND permission=0";
        if ($loginuser['role'] != 16) { //不是系统管理员
            $web_where .= " AND user_id=$userid";
        }
        $web_where .= " OR permission = 2 OR permission = 1";
        $webs = $db->fetch_all("SELECT * FROM bd_web_policy $web_where ORDER BY id DESC");
        //*添加默认策略、*/
        /*$webres = $db->fetch_all("SELECT * FROM web_policy where preset=1 OR preset = 2");
        foreach($webres as $k =>$v){
            array_push($webs,$v);
        }*/
        //弱密码
        $weaks_where = " WHERE 1=1 AND preset=0";
        if ($loginuser['role'] != 16) { //不是系统管理员
            $weaks_where .= " AND user_id=$userid";
        }
        $weaks_where .= " OR preset = 2 OR preset = 1";
        $weaks = $db->fetch_all("SELECT * FROM weak_policy $weaks_where ORDER BY id DESC");
        //*添加默认策略、*/
        /*$weakres = $db->fetch_all("SELECT * FROM weak_policy where preset=1 OR preset = 2");
        foreach($weakres as $k =>$v){
            array_push($weaks,$v);
        }*/
        //当前时间
        date_default_timezone_set(PRC);
        $now = date('Y-m-d H:i:s');
        $dst = explode(" ", $now);
        $date = explode("-", $dst[0]);
        $time = explode(":", $dst[1]);
        $aData['nowYear'] = $date[0];
        $aData['nowMonth'] = $date[1];
        $aData['nowDate'] = $date[2];
        $aData['nowHour'] = $time[0];
        $aData['nowMinute'] = $time[1];
        $aData['nowSecond'] = $time[2];
        $aData['minute'] = $minute;
        $aData['hour'] = $hour;
        $aData['day_of_month'] = $day_of_month;
        $aData['month'] = $month;
        $aData['year'] = $year;
        $aData['periodunit'] = $periodunit;
        $aData['ports'] = $ports;
        $aData['host'] = $host;
        $aData['webs'] = $webs;
        $aData['weaks'] = $weaks;
        template2($act . '/edit', $aData);
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
        $aData = $aItem = array();

        $where = " WHERE 1=1";
        $page = $page > 1 ? $page : 1;
        $userrow = $db->fetch_first("select role_id as  role from bd_sys_user WHERE id=$userid ");
        if ($userrow['role'] != 16) { //不是系统管理员
            $where .= " AND user_id=$userid";
        }

        $total = $db->result_first("SELECT COUNT(`id`) FROM template_manage $where");
        $maxPage = ceil($total / $perpage);
        $page = $page >= $maxPage ? $maxPage : $page;
        if ($total) {
            $start = ($page - 1) * $perpage;
            $rows = $db->fetch_all("SELECT * FROM template_manage  $where ORDER BY id DESC  LIMIT $start,$perpage");
        }

        foreach ($rows as $k => $v) {
            $targets = $v['target'];
            $a_target = explode("##", $targets);
            $rows[$k]['target_domain'] = $a_target[0];
            $rows[$k]['target_ip'] = $a_target[1];
            if (!empty($v['schedule'])) {
                $a_tt = unserialize($v['schedule']);
                $rows[$k]['schedule_state'] = Yii::t('app', "自动扫描");
                $rows[$k]['hour'] = $a_tt['hour'];
                $rows[$k]['minute'] = $a_tt['minute'];
                $rows[$k]['day_of_month'] = $a_tt['day_of_month'];
                $rows[$k]['month'] = $a_tt['month'];
                $rows[$k]['year'] = $a_tt['year'];
                $rows[$k]['period'] = $a_tt['period'];
                $rows[$k]['periodunit'] = $a_tt['periodunit'];
            } else {
                $rows[$k]['schedule_state'] = Yii::t('app', "手动扫描");
            }
        }

        $data['Rows'] = $rows;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }


    /**
     * @新增或者编辑任务，保存到数据库
     */
    function actionAddandedit()
    {
        global $db, $act, $show;
        //在入口这里修改$_POST['target_ip']
        $chuli_ip = trim($_POST['target_ip']);
        if (!empty($chuli_ip)) {
            $arr3333 = explode("\r\n", $chuli_ip);
            if (count($arr3333) != count(array_unique($arr3333))) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '请勿输入相同的ip');
                echo json_encode($data);
                exit;
            }
            //验证单个ip是否在段ip中
            $arr = $arr2 = array();
            foreach ($arr3333 as $k => $v) {
                $a_tar = explode("-", trim($v));
                if (count($a_tar) == 2) {
                    $arr[$k][] = ip2long($a_tar[0]);
                    $arr[$k][] = ip2long($a_tar[1]);
                } else {
                    $arr2[] = ip2long($a_tar[0]);
                }
            }
            foreach ($arr as $key => $val) {
                foreach ($arr2 as $v) {
                    if ($v >= $val[0] && $v <= $val[1]) {
                        $data['success'] = false;
                        $data['msg'] = Yii::t('app', '请检查输入ip,是否已重复');
                        echo json_encode($data);
                        exit;
                    }
                }
            }
            unset($arr, $arr2);
            //验证单个ip是否在段ip中END
            array_filter($arr3333);
            //启用pptp时，不允许扫描本机ip
            if ($_POST['pptp_enable'] == 1) { // lousaoip&lousaomask==renwuip&lousaomask
                $ipshell = "/sbin/ifconfig eth1 |awk -F'[ :]+' 'NR==2{print $4}'";
                $ipshell2 = "/sbin/ifconfig eth1 |awk -F'[ :]+' 'NR==2{print $8}'";
                $ipfh = popen($ipshell, 'r');
                $lousaoip = fgets($ipfh);
                pclose($ipfh);
                $ipfh2 = popen($ipshell2, 'r');
                $lousaomask = fgets($ipfh2);
                pclose($ipfh2);
                $lousaoip = str_replace("\n", '', $lousaoip);
                $lousaomask = str_replace("\n", '', $lousaomask);

                $lousaoip = ip2long($lousaoip);
                $lousaomask = ip2long($lousaomask);
                $lousao = ($lousaoip & $lousaomask);
                //var_dump($lousaoip&$lousaomask);exit;
                //$myIp = substr($myIp,0,strrpos($myIp,'.'));
                foreach ($arr3333 as $v) {
                    $x = explode('-', $v);
                    //$wangduan = substr($v,0,strrpos($x[0],'.'));
                    $renwu = (ip2long($x[0]) & $lousaomask);
                    if ($lousao == $renwu) {
                        $data['success'] = false;
                        $data['msg'] = Yii::t('app', '启用pptp时，不允许扫描同一网段');
                        echo json_encode($data);
                        exit;
                    }
                }
            }
            foreach ($arr3333 as $k => $v) {
                //把2001::1:200:7-20 处理为 2001::1:200:7-2001::1:200:20
                $v = $this->ips_chuli($v);
                $post_ips = $v . "\r\n" . $post_ips;
            }
            $_POST['target_ip'] = $post_ips;
        }


        //在入口这里修改$_POST['target_domain']
        $chuli_domain = trim($_POST['target_domain']);
        $post_domains = '';
        if (!empty($chuli_domain)) {
            $cl_domain = explode("\r\n", $chuli_domain);
            if (count($cl_domain) != count(array_unique($cl_domain))) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '请勿输入相同的域名');
                echo json_encode($data);
                exit;
            }
            //$cl_domain = array_unique($cl_domain);
            array_filter($cl_domain);
            foreach ($cl_domain as $k => $v) {
                $post_domains = $v . "\r\n" . $post_domains;
            }
            $_POST['target_domain'] = $post_domains;
        }

        $sPost = $_POST;
        $sRows = array();
        $userid = intval($_SESSION['userid']);
        $loginuser = $db->fetch_first("SELECT * FROM bd_sys_scanset");
        $id = intval($sPost['id']);     //任务id
        //基本信息
        $sRows['template_name'] = filterStr($sPost['template_name']);
        $sRows['template_remarks'] = filterStr($sPost['template_remarks']);
        $sRows['port_enable'] = intval($sPost['port_enable']);
        $sRows['port_timeout'] = intval($sPost['port_timeout']);
        $sRows['port_thread'] = intval($sPost['port_thread']);
        $sRows['port_policy'] = intval($sPost['port_policy']);
        if ($sRows['port_enable'] == 0) {   //快速扫描，预设的主机策略和端口策略
            $sRows['port_policy'] = $db->result_first("SELECT `id` FROM port_manage where preset=1 limit 1 ");
        }

        $a_schedule = array();
        if (intval($sPost['ifSchedule']) == 1) {    //定时执行
            $a_schedule['hour'] = trim($sPost['hour']);
            $a_schedule['minute'] = trim($sPost['minute']);
            $a_schedule['day_of_month'] = trim($sPost['day_of_month']);
            $a_schedule['month'] = trim($sPost['month']);
            $a_schedule['year'] = trim($sPost['year']);
            $a_schedule['period'] = trim($sPost['period']);
            $a_schedule['periodunit'] = trim($sPost['periodunit']);
            $sRows['start_time'] = $a_schedule['year'] . "-" . $a_schedule['month'] . "-" . $a_schedule['day_of_month'] . " " . $a_schedule['hour'] . ":" . $a_schedule['minute'] . ":00";
            $sRows['schedule'] = serialize($a_schedule);
        } else {
            $sRows['schedule'] = "";
        }
        if (intval($sPost['ifEmail']) == 1) {    //自动发送email
            $sRows['email'] = filterStr($sPost['email']);
        } else {
            $sRows['email'] = "";
        }
        if (isset($sPost['ftp_enable'])) {    //自动发送ftp
            $sRows['ftp_enable'] = intval($sPost['ftp_enable']);
        } else {
            $sRows['ftp_enable'] = 0;
        }
        if (isset($sPost['pptp_enable'])) {    //自动发送ftp
            $sRows['pptp_enable'] = intval($sPost['pptp_enable']);
        } else {
            $sRows['pptp_enable'] = 0;
        }

        if ($sPost['port_timeout'] < 1 || $sPost['port_timeout'] > 120) {
            $data['success'] = false;
            $data['msg'] = Yii::t('app', '基本配置：请修改"扫描端口超时"在1至120范围之内');
            echo json_encode($data);
            exit;
        }
        if ($sPost['port_thread'] < 1 || $sPost['port_thread'] > 10) {
            $data['success'] = false;
            $data['msg'] = Yii::t('app', '基本配置：请修改"扫描端口线程"在1至120范围之内');
            echo json_encode($data);
            exit;
        }


        //扫描对象
        $targrtdomain = trim($sPost['target_domain']);
        $webloginstatus = trim($sPost['webloginstatus']);
        $webscancookie = trim($sPost['webscancookie']);
        $loginUrl = trim($sPost['loginUrl']);
        $loginUrl = substr($loginUrl, 0, strpos($loginUrl, '/', 8));
        $loginUrl_arr = explode("://", $loginUrl);
        $loginUrl = $loginUrl_arr[1];

        $targrt_domain = "";
        $d_count = 0;
        if (!empty($targrtdomain)) {
            $targrt_domain = nl2br($targrtdomain);  //将分行符"\r\n"转义成HTML的换行符"<br>"
            $targrt_domain = str_replace("<br />", ",", $targrt_domain);
            $targrt_domain = str_replace("\r\n", "", $targrt_domain);
            //$a = htmlspecialchars($targrt_domain);var_dump($a);exit;
            $a_domain = explode(",", $targrt_domain);
            array_filter($a_domain);
            $d_count = count($a_domain);
            if ($d_count > 10) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '扫描对象：批量域名不能多于10');
                echo json_encode($data);
                exit;
            }
            $s_r = count($a_domain) + 1;
            foreach ($a_domain as $k => $v) {
                $s_r = $s_r - 1;
                if (!checkDomain($v)) {
                    $data['success'] = false;
                    $data['msg'] = Yii::t('app', '扫描对象：批量域名第') . $s_r . Yii::t('app', '行格式错误');
                    echo json_encode($data);
                    exit;
                }
            }
        }
        $targrtip = trim($sPost['target_ip']);
        $target_ip = "";
        $i_count = 0;
        if (!empty($targrtip)) {
            $target_ip = nl2br($targrtip);  //将分行符"\r\n"转义成HTML的换行符"<br />"
            $target_ip = str_replace("<br />", ",", $target_ip);
            $target_ip = str_replace("\r\n", "", $target_ip);
            $a_ip = explode(",", $target_ip);
            array_filter($a_ip);
            $s_r = count($a_ip) + 1;
            foreach ($a_ip as $k => $v) {
                //$s_r = $k+1;
                $s_r = $s_r - 1;
                //把2001::1:200:7-20 处理为 2001::1:200:7-2001::1:200:20
                //$v = ips_chuli($v);

                //此方法也可以用来验证ipv6
                //$v = filter_var($v, FILTER_VALIDATE_IP);
                if (!$this->filter_ip($v)) {
                    $data['success'] = false;
                    $data['msg'] = Yii::t('app', '扫描对象：批量IP第') . $s_r . Yii::t('app', '行格式错误');
                    echo json_encode($data);
                    exit;
                } else {
                    $a_target_single = explode("-", trim($v));
                    if (count($a_target_single) == 2) { //ip段
                        $sin0 = $a_target_single[0];
                        $sin1 = $a_target_single[1];
                        //ipv6
                        if (strpos($sin0, ':')) {
                            /*if(!substr($sin0,(strrpos($sin0, ':') + 1)) || !substr($sin1,(strrpos($sin1, ':') + 1))){
                                $data['success'] = false;
                                $data['msg'] = '第'.$s_r.'行不允许扫描网段IP';
                                echo json_encode($data);
                                exit;
                            }*/
                            if (!empty($loginuser['allowIPs'])) {
                                if (!in_allowipv6(explode(',', $loginuser['allowIPs']), $v, 2)) {
                                    $data['success'] = false;
                                    $data['msg'] = Yii::t('app', '第 ') . $s_r . Yii::t('app', '行不在允许扫描的IP范围内');
                                    echo json_encode($data);
                                    exit;
                                }
                            }
                            //
                            $ipv6first = intval(substr($sin0, strrpos($sin0, ':') + 1), 16);
                            $ipv6last = intval(substr($sin1, strrpos($sin1, ':') + 1), 16);
                            $ipv6count = intval($ipv6last - $ipv6first) + 1;
                            $i_count = $i_count + $ipv6count;
                        } else {
                            $a_sin0 = explode(".", $sin0);
                            $a_sin1 = explode(".", $sin1);
                            if ($a_sin0[0] == $a_sin1[0] && $a_sin0[1] == $a_sin1[1] && $a_sin0[2] == $a_sin1[2]) {
                                $rootip = INTERFACE_ROOT;
                                $a_rootip = explode(".", $rootip);
                                if ($a_sin0[0] == $a_rootip[0] && $a_sin0[1] == $a_rootip[1] && $a_sin0[2] == $a_rootip[2]) {
                                    if (intval($a_rootip[3]) >= intval($a_sin0[3]) && intval($a_rootip[3]) <= intval($a_sin1[3])) {
                                        $data['success'] = false;
                                        $data['msg'] = Yii::t('app', '扫描对象：批量IP第') . $s_r . Yii::t('app', '行网段包含了本机IP');
                                        echo json_encode($data);
                                        exit;
                                    }
                                }
                                if (!empty($loginuser['allowIPs'])) {
                                    if (!in_allowip(explode(',', $loginuser['allowIPs']), $v, 2)) {
                                        $data['success'] = false;
                                        $data['msg'] = Yii::t('app', '扫描对象：批量IP第') . $s_r . Yii::t('app', '行不在允许扫描的IP范围内');
                                        echo json_encode($data);
                                        exit;
                                    }
                                }
                                $i_thiscount = intval($a_sin1[3]) - intval($a_sin0[3]) + 1;
                                $i_count = $i_count + $i_thiscount;
                            } else {
                                $data['success'] = false;
                                $data['msg'] = Yii::t('app', '扫描对象：批量IP第') . $s_r . Yii::t('app', '行格式错误，不能跨网段扫描');
                                echo json_encode($data);
                                exit;
                            }
                        }
                    } else {  //ip
                        if (!empty($loginuser['allowIPs'])) {
                            if (!strpos($v, ':') && !in_allowip(explode(',', $loginuser['allowIPs']), $v, 1)) {
                                $data['success'] = false;
                                $data['msg'] = Yii::t('app', '扫描对象：批量IPV4第') . $s_r . Yii::t('app', '行不在允许扫描的IP范围内');
                                echo json_encode($data);
                                exit;
                            } else if (strpos($v, ':') && !in_allowipv6(explode(',', $loginuser['allowIPs']), $v, 1)) {
                                $data['success'] = false;
                                $data['msg'] = Yii::t('app', '扫描对象：批量IPV6第') . $s_r . Yii::t('app', '行不在允许扫描的IP范围内');
                                echo json_encode($data);
                                exit;
                            }
                        }
                        $i_count = $i_count + 1;
                    }
                }
            }
        }

        $icount = $i_count;

        if (!empty($targrt_domain) || !empty($target_ip)) {
            $newinfo = explode(',', $targrt_domain);
            if (count($newinfo) > 500) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', "批量域名不能大于500行");
                echo json_encode($data);
                exit;
            }
            $newin = explode(',', $target_ip);
            if (count($newin) > 500) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', "批量IP不能大于500行");
                echo json_encode($data);
                exit;
            }
            $target = $targrt_domain . "," . $target_ip;
            $target = trim($target, ',');
            $sRows['target'] = $targrt_domain . "##" . $target_ip;
        } else {
            /*$data['success'] = false;
            $data['msg'] = "扫描对象不能为空";
            echo json_encode($data);
            exit;*/
            $sRows['target'] = "##";
        }

        //主机配置
        if (intval($sPost['host_enable']) == 1) {
            $sRows['host_enable'] = 1;
            $host_enable = '1';
        } else {
            $sRows['host_enable'] = 0;
            $host_enable = '0';
        }
        $sRows['enable_ddos'] = intval($sPost['enable_ddos']);
        $sRows['host_timeout'] = intval($sPost['host_timeout']);
        $sRows['host_max_script'] = intval($sPost['host_max_script']);
        $sRows['host_thread'] = intval($sPost['host_thread']);
        $sRows['host_policy'] = intval($sPost['host_policy']);
        if ($sRows['host_enable'] == 1) {
            $sRows['host_state'] = 1;
            if (empty($sRows['host_policy']) && $sRows['port_enable'] != 0) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '请选择主机策略');
                echo json_encode($data);
                exit;
            }
        } else {
            $sRows['host_state'] = 0;
            $sRows['host_policy'] = "";
        }

        if (isset($sPost['host_timeout']) && $sPost['host_timeout'] != '') {
            if ($sPost['host_timeout'] < 5 || $sPost['host_timeout'] > 50000) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '主机扫描配置：请修改"扫描超时"在5至50000范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['host_max_script']) && $sPost['host_max_script'] != '') {
            if ($sPost['host_max_script'] < 1 || $sPost['host_max_script'] > 100) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '主机扫描配置：请修改"主机线程数"在1至100范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['host_thread']) && $sPost['host_thread'] != '') {
            if ($sPost['host_thread'] < 1 || $sPost['host_thread'] > 200) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '主机扫描配置：请修改"并发主机数"在1至200范围之内');
                echo json_encode($data);
                exit;
            }
        }

        //主机登录扫配置
        $sshandsmb = "";
        if (intval($sPost['smbon']) == 1) {
            $sRows['smbon'] = 1;
            $sRows['smbuser'] = trim($sPost['smbuser']);
            $sRows['smbpasswd'] = trim($sPost['smbpasswd']);
            $sshandsmb .= $sRows['smbon'] . "|" . $sRows['smbuser'] . "|" . $sRows['smbpasswd'] . "|";
        } else {
            $sRows['smbon'] = 0;
            $sRows['smbuser'] = "";
            $sRows['smbpasswd'] = "";
            $sshandsmb .= $sRows['smbon'] . "|--|--|";
        }

        //主机配置的SSH

        if (intval($sPost['sshon']) == 1) {
            $sRows['sshon'] = 1;
            $sRows['sshuser'] = trim($sPost['sshuser']);
            $sRows['sshpasswd'] = trim($sPost['sshpasswd']);
            $sRows['sshport'] = trim($sPost['sshport']);
            $sshandsmb .= $sRows['sshon'] . "|" . $sRows['sshuser'] . "|" . $sRows['sshpasswd'] . "|" . $sRows['sshport'];
        } else {
            $sRows['sshon'] = 0;
            $sRows['sshuser'] = "";
            $sRows['sshpasswd'] = "";
            $sRows['sshport'] = 22;
            $sshandsmb .= $sRows['sshon'] . "|--|--|--";
        }

        //web配置
        $sRows['web_enable'] = intval($sPost['web_enable']);
        $sRows['web_getdomain_enable'] = intval($sPost['web_getdomain_enable']);
        $sRows['force_enable'] = intval($sPost['force_enable']);
        //$sRows['spider_flag'] = intval($sPost['spider_flag']);
        //$sRows['spider_flag'] = 0;
        $sRows['spider_flag'] = intval($sPost['spider_flag']);
        //$sRows['web_spider_enable'] = intval($sPost['spider_flag']);
        $sRows['web_thread'] = intval($sPost['web_thread']);
        $sRows['web_url_count'] = intval($sPost['web_url_count']);
        $sRows['web_deep'] = intval($sPost['web_deep']);
        $sRows['web_dir'] = intval($sPost['web_dir']);
        $sRows['web_timeout'] = intval($sPost['web_timeout']);
        $sRows['web_getdomain_timeout'] = intval($sPost['web_getdomain_timeout']);
        $sRows['web_speed'] = intval($sPost['web_speed']);
        $sRows['web_minute_package_count'] = intval($sPost['web_minute_package_count']);
        $sRows['web_exp_try_times'] = intval($sPost['web_exp_try_times']);
        $sRows['web_exp_try_interval'] = intval($sPost['web_exp_try_interval']);
        $sRows['web_policy'] = intval($sPost['web_policy']);
        $sRows['depthon'] = intval($sPost['depthon']);

        if ($sRows['web_enable'] == 1) {
            $sRows['web_state'] = 1;
            if (empty($sRows['web_policy'])) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '请选择WEB策略');
                echo json_encode($data);
                exit;
            }
        } else {
            $sRows['web_state'] = 0;
            $sRows['web_enable'] = 0;
            $sRows['web_policy'] = "";
        }

        if (isset($sPost['web_minute_package_count']) && $sPost['web_minute_package_count'] != '') {
            if ($sPost['web_minute_package_count'] < 1 || $sPost['web_minute_package_count'] > 10000) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"每分钟请求URL数"在1至10000范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['web_thread']) && $sPost['web_thread'] != '') {
            if ($sPost['web_thread'] < 1 || $sPost['web_thread'] > 10) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"扫描线程"在1至10范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['web_url_count']) && $sPost['web_url_count'] != '') {
            if ($sPost['web_url_count'] < 1 || $sPost['web_url_count'] > 99999999) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"爬虫地址数"在1至99999999范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['web_timeout']) && $sPost['web_timeout'] != '') {
            if ($sPost['web_timeout'] < 1 || $sPost['web_timeout'] > 120) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"扫描超时"在1至120范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['web_getdomain_timeout']) && $sPost['web_getdomain_timeout'] != '') {
            if ($sPost['web_getdomain_timeout'] < 1 || $sPost['web_getdomain_timeout'] > 120) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"获取域名超时"在1至120范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['web_exp_try_times']) && $sPost['web_exp_try_times'] != '') {
            if ($sPost['web_exp_try_times'] < 1 || $sPost['web_exp_try_times'] > 10) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"通信异常请求次数"在1至10范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['web_exp_try_interval']) && $sPost['web_exp_try_interval'] != '') {
            if ($sPost['web_exp_try_interval'] < 1 || $sPost['web_exp_try_interval'] > 60) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', 'web扫描配置：请修改"通信异常请求间隔"在1至60范围之内');
                echo json_encode($data);
                exit;
            }
        }
        /*$addShell = "/bin/touch /tmp/send_email.py";
        pipe('cmd_');*/

        //弱密码配置
        if (intval($sPost['weak_enable']) == 1) {
            $sRows['weak_enable'] = 1;
            $weak_enable = '1';
        } else {
            $sRows['weak_enable'] = 0;
            $weak_enable = '0';
        }
        $sRows['weak_thread'] = intval($sPost['weak_thread']);
        $sRows['weak_timeout'] = intval($sPost['weak_timeout']);
        $sRows['weak_policy'] = intval($sPost['weak_policy']);
        if ($sRows['weak_enable'] == 1) {
            $sRows['weak_state'] = 1;
            if (empty($sRows['weak_policy'])) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '请选择弱密码策略');
                echo json_encode($data);
                exit;
            }
        } else {
            $sRows['weak_state'] = 0;
            $sRows['weak_policy'] = "";
        }

        if (isset($sPost['weak_thread']) && $sPost['weak_thread'] != '') {
            if ($sPost['weak_thread'] < 1 || $sPost['weak_thread'] > 20) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '弱口令扫描配置：请修改"扫描线程"在1至20范围之内');
                echo json_encode($data);
                exit;
            }
        }

        if (isset($sPost['weak_timeout']) && $sPost['weak_timeout'] != '') {
            if ($sPost['weak_timeout'] < 1 || $sPost['weak_timeout'] > 600) {
                $data['success'] = false;
                $data['msg'] = Yii::t('app', '弱口令扫描配置：请修改"扫描超时"在1至600范围之内');
                echo json_encode($data);
                exit;
            }
        }


        /*if(($sRows['web_policy'] !="")  &&  ($targrt_domain =="")){
            $data['success'] = false;
            $data['msg'] = 'web扫描必须在扫描对象中填写域名';
            echo json_encode($data);
            exit;
        }
        if(($sRows['host_enable'] !="")  &&  ($target_ip =="")){
            $data['success'] = false;
            $data['msg'] = '主机扫描必须在扫描对象中填写IP';
            echo json_encode($data);
            exit;
        }
        if(($sRows['weak_enable'] !="")  &&  ($target_ip =="")){
            $data['success'] = false;
            $data['msg'] = '弱密码扫描必须在扫描对象中填写IP';
            echo json_encode($data);
            exit;
        }*/

        if ($id) {//编辑
            $iTotal = $db->result_first("SELECT COUNT(`template_name`) FROM template_manage where template_name='" . $sRows['template_name'] . "' And id !=" . $id);
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $sRows['template_name'] . Yii::t('app', '已存在，请更换');
                echo json_encode($data);
                exit;
            }

            $sFieldValue = "";
            foreach ($sRows as $k => $v) {
                $sFieldValue .= $k . "= '" . $v . "',";
            }
            $sFieldValue = rtrim($sFieldValue, ",");
            $sql = "UPDATE template_manage SET " . $sFieldValue . " WHERE id=" . $id;
            if ($db->query($sql)) {
                $success = true;
                $msg = Yii::t('app', "编辑成功");
                $hdata['sDes'] = Yii::t('app', '编辑模板成功');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            } else {
                $success = false;
                $msg = Yii::t('app', "新增失败");
            }
        } else {//新增
            $iTotal = $db->result_first("SELECT COUNT(`template_name`) FROM template_manage where template_name='" . $sRows['template_name'] . "'");
            if (!empty($iTotal)) {
                $data['success'] = false;
                $data['msg'] = $sRows['template_name'] . Yii::t('app', '已存在，请更换');
                echo json_encode($data);
                exit;
            }

            $sRows['port_state'] = 0;
            //$sRows['host_thread'] = 0;
            $sRows['web_getdomain_state'] = 0;
            $sRows['web_getdomain_policy'] = 0;
            //others
            $sRows['user_id'] = $userid;
            $sRows['i'] = 0;
            $sRows['l'] = 0;
            $sRows['m'] = 0;
            $sRows['h'] = 0;
            $sRows['c'] = 0;

            $sField = "";
            $sValue = "";
            foreach ($sRows as $k => $v) {
                $sField .= $k . ",";
                $sValue .= "'" . $v . "',";
            }
            $sField = rtrim($sField, ",");
            $sValue = rtrim($sValue, ",");
            $sql = "INSERT INTO template_manage (" . $sField . ") VALUES (" . $sValue . ")";
            if ($db->query($sql)) {
                $success = true;
                $msg = Yii::t('app', "新增成功");
                $hdata['sDes'] = Yii::t('app', '新增模板成功');
                $hdata['sAct'] = $act . '/' . $show;
                saveOperationLog($hdata);
            } else {
                $success = false;
                $msg = Yii::t('app', "新增失败");
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
        $ids = filterStr($sPost['ids']);
        $query = "DELETE FROM template_manage where id in (" . $ids . ") ";
        if ($db->query($query)) {
            $success = true;
            $msg = Yii::t('app', "操作成功");
            $hdata['sDes'] = Yii::t('app', '删除模板成功');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
        } else {
            $success = false;
            $msg = Yii::t('app', "操作失败");
        }
        $data['success'] = $success;
        $data['msg'] = $msg;
        echo json_encode($data);
        exit;
    }


    /**
     * @ 获取所有的模板
     */
    function actionTettemplate()
    {
        global $db;
        $rows = $db->fetch_all("SELECT * FROM template_manage");
        echo json_encode($rows);
        exit;
    }

    /**
     * @ 获取模版信息
     * @ params ： id
     */
    function actionGetbyid()
    {
        global $db;
        $sPost = $_POST;
        $id = intval($sPost['id']);
        $where = " WHERE id=" . $id;
        $rows = $db->fetch_first("SELECT * FROM template_manage" . $where);
        if (!empty($rows)) {
            $targets = $rows['target'];
            $a_target = explode("##", $targets);
            $rows['target_domain'] = $a_target[0];
            $rows['target_ip'] = $a_target[1];
            if (!empty($rows['schedule'])) {
                $a_tt = unserialize($rows['schedule']);
                $rows['schedule_state'] = Yii::t('app', "自动扫描");
                $rows['hour'] = $a_tt['hour'];
                $rows['minute'] = $a_tt['minute'];
                $rows['day_of_month'] = $a_tt['day_of_month'];
                $rows['month'] = $a_tt['month'];
                $rows['year'] = $a_tt['year'];
                $rows['period'] = $a_tt['period'];
                $rows['periodunit'] = $a_tt['periodunit'];
            }
            $success = true;
            $datas = $rows;
        } else {
            $success = false;
            $datas = Yii::t('app', "无数据");
        }

        $data['success'] = $success;
        $data['data'] = $datas;
        echo json_encode($data);
        exit;
    }

    function in_allowip($ipArr, $CheckIp, $tag)
    {
        if ($tag == 1) {    // 检测ip
            foreach ($ipArr as $v) {
                if ($CheckIp == $v) {
                    return true;
                } else {
                    if (strpos($v, '-')) {
                        $ips = explode('-', $v);
                        $ipfirst = intval(substr($ips[0], strrpos($ips[0], '.') + 1));
                        $iplast = intval(substr($ips[1], strrpos($ips[1], '.') + 1));
                        $ipChk = intval(substr($CheckIp, strrpos($CheckIp, '.') + 1));
                        if ($ipChk >= $ipfirst && $ipChk <= $iplast) {
                            $checkIp_arr = explode('.', $CheckIp);
                            $ips_first = explode('.', $ips[0]);
                            //不允许跨网段扫描
                            if ($ips_first[0] == $checkIp_arr[0] && $ips_first[1] == $checkIp_arr[1] && $ips_first[2] == $checkIp_arr[2]) {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                }
            }
        } elseif ($tag == 2) {   //检测ip段
            $chkArr = explode('-', $CheckIp);
            $chkipfirst = intval(substr($chkArr[0], strrpos($chkArr[0], '.') + 1));
            $chkiplast = intval(substr($chkArr[1], strrpos($chkArr[1], '.') + 1));
            foreach ($ipArr as $v) {
                $allowArr = explode('-', $v);
                $allowipfirst = intval(substr($allowArr[0], strrpos($allowArr[0], '.') + 1));
                $allowiplast = intval(substr($allowArr[1], strrpos($allowArr[1], '.') + 1));
                if ($chkipfirst >= $allowipfirst && $chkiplast <= $allowiplast) {
                    return true;
                }
            }
        }
        return false;
    }

    function in_allowipv6($ipArr, $CheckIp, $tag)
    {
        if ($tag == 1) {    // 检测ip
            foreach ($ipArr as $v) {
                if ($CheckIp == $v) {
                    return true;
                } else {
                    if (strpos($v, '-')) {
                        $ips = explode('-', $v);
                        $ipfirst = intval(substr($ips[0], strrpos($ips[0], ':') + 1), 16);
                        $iplast = intval(substr($ips[1], strrpos($ips[1], ':') + 1), 16);
                        $ipChk = intval(substr($CheckIp, strrpos($CheckIp, ':') + 1), 16);
                        if ($ipChk >= $ipfirst && $ipChk <= $iplast) {
                            return true;
                        }
                    }
                }
            }
        } elseif ($tag == 2) {   //检测ip段
            $chkArr = explode('-', $CheckIp);
            $chkipfirst = intval(substr($chkArr[0], strrpos($chkArr[0], ':') + 1), 16);
            $chkiplast = intval(substr($chkArr[1], strrpos($chkArr[1], ':') + 1), 16);
            foreach ($ipArr as $v) {
                $allowArr = explode('-', $v);
                $allowipfirst = intval(substr($allowArr[0], strrpos($allowArr[0], ':') + 1), 16);
                $allowiplast = intval(substr($allowArr[1], strrpos($allowArr[1], ':') + 1), 16);
                if ($chkipfirst >= $allowipfirst && $chkiplast <= $allowiplast) {
                    return true;
                }
            }
        }
        return false;
    }

    function ips_chuli($v)
    {
        //把2001::1:200:7-20 处理为 2001::1:200:7-2001::1:200:20
        $a_target_str = explode("-", trim($v));
        if (count($a_target_str) == 2) {
            $sl = $a_target_str[0];
            $sr = $a_target_str[1];

            if (strpos($sl, '.') && !strpos($sr, '.')) {
                $sr = substr($sl, 0, (strrpos($sl, '.') + 1)) . $sr;
            } else if (strpos($sl, ':') && !strpos($sr, ':')) {
                $sr = substr($sl, 0, (strrpos($sl, ':') + 1)) . $sr;
            }
            $v = $sl . '-' . $sr;
        }
        return $v;
    }

//若IP格式不对，则返回false。否则返回true
    function filter_ip($ip)
    {
        if (strrpos($ip, '-')) {
            $ip_str = explode("-", trim($ip));
            $sl = $ip_str[0];
            $sr = $ip_str[1];
            if (!filter_var($sl, FILTER_VALIDATE_IP) || !filter_var($sr, FILTER_VALIDATE_IP))
                return false;
        } else {
            if (!filter_var($ip, FILTER_VALIDATE_IP))
                return false;
        }
        return true;
    }

}
