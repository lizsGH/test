<?php
namespace app\controllers;
use yii\helpers\ArrayHelper;

/**
 * Class TuopuController
 * @package app\controllers
 * 网络拓扑
 */
class TuopuController extends BaseController
{

    function actionIndex()
    {
        global $act;
        $data = array();
        template2($act . '/index', $data);
    }

    function actionShow_network_all()
    {
        global $act;
        $data = array();
        template2($act . '/show_network_all', $data);
    }

    //显示拓扑配置
    function actionShow_tp_pz()
    {
        global $act;
        $data = array();
        template2($act . '/show_tp_pz', $data);
    }

    //拓扑配置
    function actionPz_show()
    {
        global $db;
        $aData = array();

        if ($_SESSION) {
            $sql = "select tp_sum,tp_ye_sum from bd_sys_user where id = " . intval($_SESSION['userid']);
            $res = $db->fetch_first($sql);
            $aData['sz_sum'] = $res['tp_sum'];
            $aData['sz_ye_sum'] = $res['tp_ye_sum'];
            echo json_encode($aData);
            exit;
        }

    }
    function actionPz_view(){
        global $db;
        $aData = array();
        $sPost = $_POST;
        if($_SESSION){
            if($sPost){
                $userid = intval($_SESSION['userid']);
                $v_sum  = intval($sPost['v_sum']);
                $v_ye_sum = intval($sPost['v_ye_sum']);
                $sql = "update bd_sys_user set tp_sum = '".$v_sum."',tp_ye_sum ='".$v_ye_sum."' where id =".$userid;

                if($db->query($sql)){
                    $aData['success']  = true;
                    $aData['msg']      = '配置成功！';

                }else{
                    $aData['success']  = false;
                    $aData['msg']      = '配置失败！';

                }

            }
        }
        echo json_encode($aData);
        exit;
    }

    //配置处理
    function actionPz_handle()
    {
        global $db;
        $aData = array();
        $sPost = $_POST;
        if ($_SESSION) {
            if ($sPost) {
                $userid = intval($_SESSION['userid']);
                $v_sum = intval($sPost['v_sum']);
                $v_ye_sum = intval($sPost['v_ye_sum']);
                $sql = "update bd_sys_user set tp_sum = '" . $v_sum . "',tp_ye_sum ='" . $v_ye_sum . "' where id =" . $userid;

                if ($db->query($sql)) {
                    $aData['success'] = true;
                    $aData['msg'] = '配置成功！';

                } else {
                    $aData['success'] = false;
                    $aData['msg'] = '配置失败！';

                }

            }
        }
        echo json_encode($aData);
        exit;
    }

    function create_guid() {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = "";//chr(45);
        $uuid = //chr(123)
            substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        //.chr(125);
        return $uuid;
    }

    //部门
    function actionDepart()
    {
        global $db;
        $sql = "select * from bd_asset_depart_info";
        $tdata = $db->fetch_all($sql);
        echo json_encode($tdata);
    }

    //根据id获取部门名字
    function actionGetDepartnameByid()
    {
        global $db;
        $ndata = array();
        $sPost = $_POST;
        if (!empty($sPost['bumen_id'])) {
            $sql = "select name from bd_asset_depart_info where id=" . filterStr($sPost['bumen_id']);
            $ndata = $db->result_first($sql);
        }

        echo json_encode($ndata);
    }

    function actionBumen(){
        global $db;
        $sql = "select * from bd_asset_depart_info";
        $tdata = $db->fetch_all($sql);
        echo json_encode($tdata);
    }

    //获取用户网络搜索记录
    function actionGetusersearch(){
        global $db;
        //$sql = "select uuid,target from bd_asset_gplot_target  WHERE target!='' GROUP by target";
        $sql = "select recordid,target,scan_time,uuid from bd_asset_gplot_info  WHERE target!='' AND userid = ".intval($_SESSION['userid'])." GROUP by recordid limit 5";
        $tdata['Rows'] = $db->fetch_all($sql);

        echo json_encode($tdata);
    }

    //网络搜索
    function actionTo_search(){
        global $db;
        $data = array();
        $data_s = array();
        $sPost = $_POST;

        if(!empty($sPost['tp_cel'])){
            $tp_kill = "/home/bluedon/bdscan/bdasset/search/search_topology ".filterStr($_SESSION['tp_search']);
            exec($tp_kill);

            //$del = 'delete from asset_search_target where uuid = '."'".filterStr($_SESSION['tp_search'])."'";
            //$db->query($del);
            $data['msg'] = '成功';
            echo json_encode($data);
        }
        if(!empty($sPost['bumen'])){
            if($_SESSION['tp_search'] != null){
                $tp_kill = "/home/bluedon/bdscan/bdasset/search/search_topology ".filterStr($_SESSION['tp_search']);
                exec($tp_kill);
            }
            //先统计并且更新asset_target_info的风险等级，再去取
            $bm = filterStr($sPost['bumen']);
            $bm_tmp = explode(',',$bm);
            $f_bm = '';
            foreach ($bm_tmp as $k=>$v){
                $f_bm.=intval($v).',';
            }
            $f_bm = '('.trim($f_bm,',').')';
            $sql_bm = "select name from bd_asset_depart_info where id in ".$f_bm;
            $a_bm = $db->fetch_all($sql_bm);
            //var_dump($a_bm);exit;
            foreach ($a_bm as $v){

                $a_bm = "'资产管理-".filterStr($v['name'])."'";
                $sql_taskid = "select uuid from bd_host_task_manage where name = ".$a_bm.'order by uuid desc';//取最新的任务
                $a_tid = $db->fetch_first($sql_taskid);//看是否存在这个任务
                if(!empty($a_tid)){

                    $sql_f = "select ip,max(end_time) as time,i,m,h,l from bd_host_history where uuid = ".intval($a_tid['id'])." group by ip";
                    $arr_f = $db->fetch_all($sql_f);
                    if(!empty($arr_f)){
                        //更新asset_target_info的风险等级
                        $sql_up = "update bd_asset_device_info set riskrank = case ipv4 ";
                        foreach ($arr_f as $k=>$val){
                            $therank = $val['h']>0? '3':($val['m']>0? '2':($val['l']>0? '1':'0'));//增加风险评估列的数据
                            $sql_up.= sprintf("WHEN %s THEN %d ","'".$val['ip']."'",$therank);
                        }
                        $newrank = array_column($arr_f,'ip');
                        $up_ip = array_values($newrank);
                        foreach ($up_ip as $k=>$val){
                            $val = "'".$val."'";
                            $up_ip[$k] = $val;
                        }
                        $newrank_f = implode(',',$up_ip);
                        $sql_up.= "END WHERE ipv4 IN ($newrank_f)";
                        $db->query($sql_up);

                    }



                }

            }//更新风险等级结束
            //取数据

            $sql = 'select * from bd_asset_device_info where depart_id in '.$f_bm;//同一个部门有可能存在多个网段的设备,只能这样查
            $data_z = $db->fetch_all($sql);//原始数组，接下来筛选这个数组

            $data_wd_f = array_column($data_z,'ipv4_segment');
            $data_wd_f = array_unique($data_wd_f);//得到网段数组
            //var_dump($data_wd_f);exit;
            $data_c = array();
            $data_f = array();
            foreach ($data_wd_f as $v){
                $data_c[$v] = $v;
                $data_f[$v] = array();
            }
            foreach ($data_z as $k=>$v){
                //var_dump($v);exit;
                $d_flag = false;
                if($v['device_type']=='route' || $v['device_type']=='switch'){
                    if($data_c[$v['ipv4_segment']] != null){
                        if($data_c[$v['ipv4_segment']]['device_type'] != 'route'){

                            $data_c[$v['ipv4_segment']] = $v;

                        }else{
                            $d_flag = true;
                        }
                    }else{

                        $data_c[$v['ipv4_segment']] = $v;

                    }
                }else{
                    $d_flag = true;
                }
                if($d_flag){
                    array_push($data_f[$v['ipv4_segment']],$v);
                }

            }


            $data['f'] = $data_f;
            $data['c'] = $data_c;

            //var_dump($data_z);
            //var_dump($data_f);
            //var_dump($data_c);
            //var_dump($data);exit;
            echo json_encode($data);
        }
        if(!empty($sPost['tporder'])){

            $tp_data = explode(',',$sPost['tporder']);
            $yz = true;
            $yz_data = '输入格式不正确！请按正确格式输入！';
            foreach ($tp_data as $k=>$v){
                $tp_tmp = explode('-',$v);
                if(strlen($tp_tmp)>2){
                    $yz = false;
                }else{
                    if(!filter_var($tp_tmp[0],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){//不是ipv4
                        $yz = false;
                    }
                    if(!empty($tp_tmp[1])){
                        if(is_numeric($tp_tmp[1])){
                            if(intval($tp_tmp[1])<1 || intval($tp_tmp[1])>254){
                                $yz = false;
                            }
                        }else{//不是数字或数字串
                            $yz = false;
                        }
                    }
                }
            }
            if(!$yz){
                $data_s['z'] = $yz_data;
                echo json_encode($data_s);
                exit;
            }else{
                $data_s['z'] = 1;
            }
            if($sPost['s_the'] == 0){//点击开始时执行
                $uuid = $this->create_guid();//获取自己的唯一识别码uuid
                if($_SESSION['tp_search']!=null){
                    $tp_kill = "/home/soc/openvas/topology/search/search_topology ".filterStr($_SESSION['tp_search']);
                    exec($tp_kill);

                        //$del = 'delete from bd_asset_gplot_info where uuid = '."'".filterStr($_SESSION['tp_search'])."'";
                        //$db->query($del);
                    }
                    $_SESSION['tp_search'] = $uuid;//更新会话的uuid,无论$_SESSION['tp_search']是否为空
                    /*$startTime = 0;
                    $stopTime = 0;*/
                    //加/dev/null重定向，可以不用等待
                    $u = '/home/bluedon/bdscan/bdasset/search/search_topology ' .intval($_SESSION['userid']).' '.$uuid.' '.filterStr($sPost['tporder']).' >/dev/null &';
                    //$startTime = microtime(true);
                    system($u);
                    sleep(3);
                    //$stopTime = microtime(true);
                    //$data = $stopTime-$startTime;
                }

            //判断是否异常结束，还要结合进度；如果结束时，进度不是100%即为异常
            /*判断开始*/
            $cs = popen("ps -eo args | grep search_topology | grep -v grep", "r");
            $tp_s_flag = false;
            while(!feof($cs)){
                //var_dump(fgets($cs));
                $tp_fg = explode(' ',fgets($cs));
                if($tp_fg[2] == filterStr($_SESSION['tp_search'])){
                    $tp_s_flag = false;
                    break;
                }else{
                    $tp_s_flag = true;
                }
            }
            if($tp_s_flag){
                //程序已结束
                $data_s['p'] = 1;
            }else{
                $data_s['p'] = 0;
            }
            pclose($cs);
            /*判断结束*/

            $sql = 'select * from bd_asset_gplot_info where uuid = '."'".filterStr($_SESSION['tp_search'])."'";
            $data = $db->fetch_all($sql);

            $data_ip = array_column($data,'ipv4');
            $f_ip = implode("','",$data_ip);

            $sql_f = "select ip,max(end_time),i,l,m,h from bd_host_history where ip in "."("."'".$f_ip."'".")"." group by ip ";
            $data_f = $db->fetch_all($sql_f);

            if(!empty($data_f)){
                if(count($data_ip) == count($data_f)){
                    $i = 0;
                    foreach($data_f as $k=>$val){
                        $val['riskrank'] = $val['h']>0? '3':($val['m']>0? '2':($val['l']>0? '1':'0'));//增加风险评估列的数据
                        $data[$i]['riskrank'] = $val['riskrank'];
                        $i++;
                    }
                }else{
                    foreach ($data_f as $k=>$v){
                        $index_f = array_search($v['ip'],$data_ip);
                        $data[$index_f]['riskrank'] = $v['h']>0? '3':($v['m']>0? '2':($v['l']>0? '1':'0'));//在ip表查到的，先添加风险列
                        unset($data_ip[$index_f]);
                    }
                    $f_index = array_keys($data_ip);//用于增加风险列
                    foreach ($f_index as $k=>$v){
                        $data[$v]['riskrank'] = 4;
                    }

                }
            }else{//如果ip表都没有数据，即全部为未知
                $i = 0;
                foreach($data as $k=>$val){
                    $val['riskrank'] = 4;//增加风险评估列的数据
                    $data[$i]['riskrank'] = $val['riskrank'];
                    $i++;
                }
            }



            //接下来对data筛选和重组数据
            $data_wd_ff = array_column($data,'ipv4_segment');
            $data_wd_ff = array_unique($data_wd_ff);//得到网段数组
            //var_dump($data_wd_ff);exit;
            $data_cc = array();
            $data_ff = array();
            foreach ($data_wd_ff as $v){

                if($v==null){
                    $v = '未知网段';
                }

                $data_cc[$v] = $v;
                $data_ff[$v] = array();
            }

            foreach ($data as $k=>$v){
                //var_dump($v);exit;
                $dd_flag = false;
                if($v['device_type']=='route' || $v['device_type']=='switch'){
                    if($data_cc[$v['ipv4_segment']] != null){
                        if($data_cc[$v['ipv4_segment']]['device_type'] != 'route'){

                            $data_cc[$v['ipv4_segment']] = $v;

                        }else{
                            $dd_flag = true;
                        }
                    }else{

                        $data_cc[$v['ipv4_segment']] = $v;

                    }
                }else{
                    $dd_flag = true;
                }
                if($dd_flag){
                    if($v['ipv4_segment'] != null){
                        array_push($data_ff[$v['ipv4_segment']],$v);
                    }else{
                        array_push($data_ff['未知网段'],$v);
                    }

                }

            }

            $data_s['f'] = $this->px_fx($data_ff,'riskrank');
            $data_s['c'] = $data_cc;

            echo json_encode($data_s);


        }
    }

    function  actionH_tp_search(){
        global $db;
        $data = array();
        $sPost = $_POST;
        $tp_data = explode(',',filterStr($sPost['h_target']));
        $yz = true;
        $yz_data = '输入格式不正确！请按正确格式输入！';
        foreach ($tp_data as $k=>$v){
            $tp_tmp = explode('-',$v);
            if(strlen($tp_tmp)>2){
                $yz = false;
            }else{
                if(!filter_var($tp_tmp[0],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){//不是ipv4
                    $yz = false;
                }
                if(!empty($tp_tmp[1])){
                    if(is_numeric($tp_tmp[1])){
                        if(intval($tp_tmp[1])<1 || intval($tp_tmp[1])>254){
                            $yz = false;
                        }
                    }else{//不是数字或数字串
                        $yz = false;
                    }
                }
            }
        }
        if(!$yz){
            $data_s['z'] = $yz_data;
            echo json_encode($data_s);
            exit;
        }else{
            $data_s['z'] = 1;
        }


        $sql="select * from bd_asset_gplot_info WHERE target ="."'".filterStr($sPost['h_target'])."'"." AND uuid =".intval($sPost['h_uuid']);
        $data=$db->fetch_all($sql);


        $data_ip = array_column($data,'ipv4');
        $f_ip = implode("','",$data_ip);

        $sql_h = "select ip,report_time,max(num) as num,i,l,m,h from history_all_assets where ip in "."("."'".$f_ip."'".")"." group by ip ";
        $data_h = $db->fetch_all($sql_h);
        //var_dump($data_h);die;
        if(!empty($data_h)){//如果history表有数据
            if(count($data_ip) == count($data_h)){//如果history表有全部的数据
                $i = 0;
                foreach($data_h as $k=>$val){
                    $val['riskrank'] = $val['h']>0? '3':($val['m']>0? '2':($val['l']>0? '1':'0'));//增加风险评估列的数据
                    $data[$i]['riskrank'] = $val['riskrank'];
                    $i++;
                }
            }else{//history表没有的数据到ip表去查
                foreach ($data_h as $k=>$v){//将要到ip表查的index记录下来
                    $index_h = array_search($v['ip'],$data_ip);
                    $data[$index_h]['riskrank'] = $v['h']>0? '3':($v['m']>0? '2':($v['l']>0? '1':'0'));//在history表查到的，先添加风险列
                    unset($data_ip[$index_h]);
                }
                $h_ip = implode("','",$data_ip);//用于$sql_hf
                $h_index = array_keys($data_ip);//用于增加风险列
                $sql_hf = "select ip,report_time,max(num) as num,count(risk_factor='i' or null) as i,count(risk_factor='l' or null) as l,count(risk_factor='m' or null) as m,count(risk_factor='h' or null) as h from vul_details_sum_ip where ip in "."("."'".$h_ip."'".")"." group by ip ";
                $data_hf = $db->fetch_all($sql_hf);
                if(!empty($data_hf)) {//如果ip表有数据
                    foreach ($data_hf as $k=>$v){//如果剩下的在ip表全部能查到，那么用$h_index的全部数据
                        $data[$h_index[$k]]['riskrank'] = $v['h']>0? '3':($v['m']>0? '2':($v['l']>0? '1':'0'));//增加风险评估列的数据
                    }
                    if (count($data_ip) != count($data_hf)){
                        //如果在ip表查不到，即认为是未知的,从$h_index里面挑数据
                        foreach ($data_hf as $k=>$v){
                            $index_hf = array_search($v['ip'],$h_index);
                            unset($data_ip[$index_hf]);
                        }
                        $hf_index = array_keys($data_ip);//用于增加风险列
                        foreach ($hf_index as $k=>$v){
                            $data[$v]['riskrank'] = 4;
                        }
                    }
                }else{//如果ip表都没有数据的话，剩余的全部认为未知
                    foreach ($h_index as $k=>$v){
                        $data[$v]['riskrank'] = 4;
                    }
                }
            }
        }else{//如果history表没数据即去ip表查，
            $sql_f = "select ip,report_time,max(num) as num,count(risk_factor='i' or null) as i,count(risk_factor='l' or null) as l,count(risk_factor='m' or null) as m,count(risk_factor='h' or null) as h from vul_details_sum_ip where ip in "."("."'".$f_ip."'".")"." group by ip ";
            $data_f = $db->fetch_all($sql_f);

            if(!empty($data_f)){
                if(count($data_ip) == count($data_f)){
                    $i = 0;
                    foreach($data_f as $k=>$val){
                        $val['riskrank'] = $val['h']>0? '3':($val['m']>0? '2':($val['l']>0? '1':'0'));//增加风险评估列的数据
                        $data[$i]['riskrank'] = $val['riskrank'];
                        $i++;
                    }
                }else{
                    foreach ($data_f as $k=>$v){
                        $index_f = array_search($v['ip'],$data_ip);
                        $data[$index_f]['riskrank'] = $v['h']>0? '3':($v['m']>0? '2':($v['l']>0? '1':'0'));//在ip表查到的，先添加风险列
                        unset($data_ip[$index_f]);
                    }
                    $f_index = array_keys($data_ip);//用于增加风险列
                    foreach ($f_index as $k=>$v){
                        $data[$v]['riskrank'] = 4;
                    }

                }
            }else{//如果ip表都没有数据，即全部为未知
                $i = 0;
                foreach($data as $k=>$val){
                    $val['riskrank'] = 4;//增加风险评估列的数据
                    $data[$i]['riskrank'] = $val['riskrank'];
                    $i++;
                }
            }

        }

        //接下来对data筛选和重组数据
        $data_wd_ff = array_column($data,'ipv4_segment');
        $data_wd_ff = array_unique($data_wd_ff);//得到网段数组

        $data_cc = array();
        $data_ff = array();

        foreach ($data_wd_ff as $v){
            if($v==null){
                $v = '未知网段';
            }
            $data_cc[$v] = $v;
            $data_ff[$v] = array();
        }


        foreach ($data as $k=>$v){
            //var_dump($v);exit;
            $dd_flag = false;
            if($v['device_type']=='route' || $v['device_type']=='switch'){
                if($data_cc[$v['ipv4_segment']] != null){
                    if($data_cc[$v['ipv4_segment']]['device_type'] != 'route'){

                        $data_cc[$v['ipv4_segment']] = $v;

                    }else{
                        $dd_flag = true;
                    }
                }else{

                    $data_cc[$v['ipv4_segment']] = $v;

                }
            }else{
                $dd_flag = true;
            }
            if($dd_flag){
                if($v['ipv4_segment'] != null){
                    array_push($data_ff[$v['ipv4_segment']],$v);
                }else{
                    array_push($data_ff['未知网段'],$v);
                }

            }

        }

        $data_s['f'] = $this->px_fx($data_ff,'riskrank');
        $data_s['c'] = $data_cc;

        echo json_encode($data_s);



    }

//风险排序
    function px_fx($array, $key, $order = "desc")
    {
        $arr_f = array();
        foreach ($array as $k => $v) {
            $arr_f[$k] = $this->arr_sort($v, $key, $order);
        }
        return $arr_f;
    }

//二维数组按指定键值排序
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

            $arr[] = $array[$k];//这里是排序成功的关键

        }

        return $arr;

    }
}

?>