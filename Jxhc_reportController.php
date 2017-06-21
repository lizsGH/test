<?php

namespace app\controllers;
use app\components\client_db;
class Jxhc_reportController extends BaseController
{

function actionIndex()
{
    global $act;
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $hPost['task_id'] = intval($hPost['task_id']);
    $descData = array();
    if(isset($hPost['task_id'])&&!empty($hPost['task_id'])){
        /* $sql = "SELECT
                             d.*,a.task_name,a.creator,a.time_type
                         FROM
                             (
                                 SELECT
                                     max(c.id) AS maxid,
                                     c.task_id,c.task_status,c.create_time,c.finish_time
                                 FROM
                                     t_task_instance c
                                 WHERE
                                     c.task_id = :task_id
                             ) d
                         LEFT JOIN t_task a ON a.id = d.task_id";
         $dData = Yii::app()->bd->createCommand($sql)->queryRow(true,array(':task_id'=>$hPost['task_id']));*/
        $sql = "SELECT
                            d.*,a.task_name,a.creator,a.time_type
                        FROM
                            (
                                SELECT
                                    max(c.id) AS maxid,
                                    c.task_id,c.task_status,c.create_time,c.finish_time
                                FROM
                                    t_task_instance c
                                WHERE
                                    c.task_id = ".intval($hPost['task_id'])."
                            ) d
                        LEFT JOIN t_task a ON a.id = d.task_id";
        $dData = $db_jx->fetch_first($sql,'db_jx');
        if($dData){
            $descData['task_name'] = $dData['task_name'];
            $descData['create_time'] = $dData['create_time'];
            $descData['finish_time'] = $dData['finish_time'];
            $descData['creator'] = $dData['creator'];
        }
        //查询风险数
        /*$sql = "select count(dev_id) as counts,risk from t_task_log where task_instance_id = :task_instance_id GROUP BY risk";
        $rData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$dData['maxid']));*/
        $sql = "select count(dev_id) as counts,risk from t_task_log where task_instance_id = ".$dData['maxid']." GROUP BY risk";
        $rData = $db_jx->fetch_all($sql,'db_jx');
        $descData['riskcounts']=$descData['lowrisk']=$descData['midrisk']=$descData['highrisk']=0;
        foreach($rData as $v){
            if($v['risk']>0 && $v['risk']<4){
                $descData['lowrisk'] += $v['counts'] ? $v['counts'] : 0;
            }elseif($v['risk']>=4 && $v['risk']<7){
                $descData['midrisk'] += $v['counts'] ? $v['counts'] : 0;
            }elseif($v['risk']>=7){
                $descData['highrisk'] += $v['counts'] ? $v['counts'] : 0;
            }
            $descData['riskcounts'] += $v['counts'] ? $v['counts'] : 0;
        }
        //查询设备数
        /*$sql = "select count(DISTINCT dev_id) as counts,status from t_task_log where task_instance_id = :task_instance_id GROUP BY status";
        $devData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$dData['maxid']));*/
        $sql = "select count(DISTINCT dev_id) as counts,status from t_task_log where task_instance_id = ".$dData['maxid']." GROUP BY status";
        $devData = $db_jx->fetch_all($sql,'db_jx');
        $descData['devcounts'] = $descData['qualdev'] = $descData['unqualdev'] = $descData['devcounts'] = 0;
        foreach($devData as $v){
            if($v['status']==1){
                $descData['qualdev'] = $v['counts'] ? $v['counts'] : 0;
            }elseif($v['status']==2){
                $descData['unqualdev'] = $v['counts'] ? $v['counts'] : 0;
            }elseif($v['status']==4){
                $descData['faildev'] = $v['counts'] ? $v['counts'] : 0;
            }
            $descData['devcounts'] += $v['counts'] ? $v['counts'] : 0;
        }
        $descData['devper'] =  $descData['devcounts'] ? floor($descData['qualdev'] * 100 / $descData['devcounts']) : 0;

        //使用规范
        /*$sql = "select name from t_standard where id in (SELECT DISTINCT standard_id from t_task_log where task_instance_id = :task_instance_id)";
        $sData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$dData['maxid']));*/
        $sql = "select name from t_standard where id in (SELECT DISTINCT standard_id from t_task_log where task_instance_id = ".$dData['maxid'].")";
        $sData = $db_jx->fetch_all($sql,'db_jx');
        $descData['standard'] = array();
        foreach($sData as $v){
            array_push($descData['standard'],$v['name']);
        }
        $descData['standard'] = implode(",",$descData['standard']);

        /*$sql = "SELECT
                    t.*, d.*,r.method,p.title,s.name as standard_name
                FROM
                    t_task_log t
                LEFT JOIN t_dev d ON t.dev_id = d.id
                LEFT JOIN t_policy p on t.policy_id = p.id
                LEFT JOIN t_rule r on p.rule_id = r.rule_id
                LEFT JOIN t_standard s on t.standard_id = s.id
                WHERE
                    t.task_instance_id = (
                        SELECT
                            max(id)
                        FROM
                            t_task_instance
                        WHERE
                            task_id = :task_id
                    )order by t.risk desc,r.title asc";
        $tData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_id'=>$hPost->task_id));*/
        $sql = "SELECT
                    t.*, d.*,r.method,p.title,s.name as standard_name
                FROM
                    t_task_log t
                LEFT JOIN t_dev d ON t.dev_id = d.id
                LEFT JOIN t_policy p on t.policy_id = p.id
                LEFT JOIN t_rule r on p.rule_id = r.rule_id
                LEFT JOIN t_standard s on t.standard_id = s.id
                WHERE
                    t.task_instance_id = (
                        SELECT
                            max(id)
                        FROM
                            t_task_instance
                        WHERE
                            task_id = ".intval($hPost['task_id'])."
                    )order by t.risk desc,r.title asc";
        $tData = $db_jx->fetch_all($sql,'db_jx');

        $riskarr = array('countrisk'=>0,'highrisk'=>0,'midrisk'=>0,'lowrisk'=>0);
        foreach($tData as $v){
            if($v['risk']!=0){
                $riskarr['countrisk'] += 1;
                if($v['risk']>=7){
                    $riskarr['highrisk'] += 1;
                }elseif($v['risk']>=4 && $v['risk']<7){
                    $riskarr['midrisk'] += 1;
                }elseif($v['risk']>0 && $v['risk']<4){
                    $riskarr['lowrisk'] += 1;
                }
            }
        }

        //资产类型处理
        $devtype = $this->Devtypename();
        foreach ($tData as $k=>$v){
            //$devtype中的值是系统软件mask值，$v['dev_type']值是由系统mask值+左移位值，如4098=4096+2（linux为2，Oracle数据库为4096）
            foreach($devtype as $dk=>$dv){
                if($dk & $v['dev_type']){//2 & 4098 = 2
                    $tData[$k]['dev_type'] = $dk;
                }
            }
            if($v['status'] == 1){
                $tData[$k]['logs'] = '符合规范';
            }
            $tData[$k]['solution'] = htmlspecialchars($v['solution']);
        }

        if(isset($hPost['result_extend'])){
            template2($act.'/index',array('result_extend'=>1,'tData'=>$tData,'riskarr'=>$riskarr,'descData'=>$descData,'task_id'=>$hPost['task_id'],'task_instance_id'=>$dData['maxid'],'devtype'=>$devtype));
            exit;
        }
        template2($act.'/index',array('tData'=>$tData,'riskarr'=>$riskarr,'descData'=>$descData,'task_id'=>$hPost['task_id'],'task_instance_id'=>$dData['maxid'],'devtype'=>$devtype));
    }

}

function actionExpreport()
{

    $hPost = $_REQUEST;
    $success = false;
    $aJson = array();
    $aJson['msg'] = '';
    $hPost['task_id'] = intval($hPost['task_id']);
    $portType = filterStr($hPost['portType']);
    if($portType == 'html' ){
        $_REQUEST['ex']=1;
        $_REQUEST['task_id'] = intval($hPost['task_id']);
        index();
        exit;
    }
    //require_once DIR_ROOT."../controllers/WkHtmlToPdf.php";
    $pdf = new \app\components\WkHtmlToPdf();
    //var_dump($pdf);
    //请求路径
    //$sUrl = Yii::app()->request->hostInfo.$this->createUrl('Taskreport/Index',array('ex'=>1,'task_id'=>$hPost->task_id));
    $sUrl = SITE_ROOT."/jxhc_report/index?ex=1&task_id=".intval($hPost['task_id'])."&result_extend=1";
    //var_dump($sUrl);
    $pdf->addCover($sUrl);
    $tarname = $hPost['task_name'].date("Y-m-d",time()).'-'.time().'.zip';
    $pdfpath = $hPost['task_name'].date("Y-m-d",time()).'-'.time().'.pdf';
    $htmlpath = $hPost['task_name'].date("Y-m-d",time()).'-'.time().'.html';
    //$h2fCmd = "/usr/local/nginx/wkhtmltox/bin/wkhtmltopdf ".$sUrl." /tmp/".$pdfpath;
    //shellResult($h2fCmd);
    $res = $pdf->saveAs('/tmp/'.$pdfpath);
    //var_dump($sUrl);
    //var_dump($pdfpath);
    if($hPost['report_type']=='pdf'){
        //$sCmd = "cd /tmp;/bin/tar -cf ".$tarname." ".$pdfpath;
        $sCmd = "cd /tmp;zip -q -r -j ".$tarname." ".$pdfpath;
        $rs = shellResult($sCmd);
        if($rs == 0){
            $success = true;
            $aJson['path'] = '/tmp/'.$tarname;
            $aJson['report_type'] = 'pdf';
            downloadFile($tarname,'/tmp/'.$tarname);
        }
        //$pdf->send($pdfpath);
    }elseif($hPost['report_type']=='html'){
        $sCmd = "/usr/bin/pdf2htmlEX -l 210 --no-drm 1 --fit-width 1024 --dest-dir /tmp --split-pages 0 /tmp/$pdfpath $htmlpath";
        $rs = shellResult($sCmd);
        //sleep(5)
        if($rs==0){
            $success = true;
            $sCmd = "cd /tmp;zip -q -r -j ".$tarname." ".$htmlpath;
            shellResult($sCmd);
            $aJson['path'] = '/tmp/'.$tarname;
            $aJson['report_type'] = 'html';

            downloadFile($tarname,'/tmp/'.$tarname);
        }else{
            $aJson['msg'] = '导出失败！';
        }
    }
    $aJson['success'] = $success;
    //$pdf->send($hPost['task_name'].date("Y-m-d",time()).'-'.time().'.pdf');
    echo json_encode($aJson);
    exit;
}

function Loadhtml(){
    $hPost = $_REQUEST;
    $file_path = str_replace('/tmp/','',$hPost['path']);
    downloadFile($file_path,$hPost['path']);
}


function actionCompliance(){
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $hPost['task_instance_id'] = intval($hPost['task_instance_id']);
    /*$sql = "select count(id) as num,status from {{task_log}} where task_instance_id = :task_instance_id GROUP BY status";
    $aData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$hPost->task_instance_id));*/
    $sql = "select count(id) as num,status from t_task_log where task_instance_id = ".intval($hPost['task_instance_id'])." GROUP BY status";
    $aData = $db_jx->fetch_all($sql,'db_jx');
    $data = array();$data['data'] = array();
    $com = $uncom = 0;
    foreach($aData as $v){
        if($v['status']>0){
            if($v['status']==1){
                $com = $v['num'];
            }else{
                $uncom += $v['num'];
            }
        }
    }
    array_push($data['data'],array('value'=>intval($com),'name'=>'合规检查项数'));
    array_push($data['data'],array('value'=>intval($uncom),'name'=>'不合规检查项数'));
    $aJson['series'][] =  $data;
    $aJson['legend']['data'] =  array('合规检查项数','不合规检查项数');
//        $aJson['xAxis']['type'] = 'category';
//        $aJson['xAxis']['data'] =  array('高','中','低');
    echo json_encode($aJson);
    exit;

}

function actionRiskpieData(){
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $hPost['task_instance_id'] = intval($hPost['task_instance_id']);
    /*$sql = "select count(id) as num,risk from {{task_log}} where task_instance_id = :task_instance_id GROUP BY risk";
    $aData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$hPost->task_instance_id));*/
    $sql = "select count(id) as num,risk from t_task_log where task_instance_id = ".intval($hPost['task_instance_id'])." GROUP BY risk";
    $aData = $db_jx->fetch_all($sql,'db_jx');
    $data = array();$data['data'] = array();
    $high = $mid = $low = 0;
    foreach($aData as $v){
        if($v['risk']>0){
            if($v['risk']>0 && $v['risk']<4){
                $low += $v['num'];
            }elseif($v['risk']>3 && $v['risk']<7){
                $mid += $v['num'];
            }elseif($v['risk']>6){
                $high += $v['num'];
            }
        }
    }
    array_push($data['data'],array('value'=>intval($high),'name'=>'高风险'));
    array_push($data['data'],array('value'=>intval($mid),'name'=>'中风险'));
    array_push($data['data'],array('value'=>intval($low),'name'=>'低风险'));
    $aJson['series'][] =  $data;
    $aJson['legend']['data'] =  array('高风险','中风险','低风险');
//        $aJson['xAxis']['type'] = 'category';

    echo json_encode($aJson);
    exit;

}

function actionRiskbarData(){
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $hPost['task_instance_id'] = intval($hPost['task_instance_id']);
    /*$sql = "select count(id) as num,risk from {{task_log}} where task_instance_id = :task_instance_id GROUP BY risk";
    $aData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$hPost->task_instance_id));*/
    $sql = "select count(id) as num,risk from t_task_log where task_instance_id = ".intval($hPost['task_instance_id'])." GROUP BY risk";
    $aData = $db_jx->fetch_all($sql,'db_jx');
    $data = array();$data['data'] = array();
    $high = $mid = $low = 0;
    foreach($aData as $v){
        if($v['risk']>0){
            if($v['risk']>0 && $v['risk']<4){
                $low += $v['num'];
            }elseif($v['risk']>3 && $v['risk']<7){
                $mid += $v['num'];
            }elseif($v['risk']>6){
                $high += $v['num'];
            }
        }
    }
    array_push($data['data'],array('value'=>intval($high),'name'=>'高风险'));
    array_push($data['data'],array('value'=>intval($mid),'name'=>'中风险'));
    array_push($data['data'],array('value'=>intval($low),'name'=>'低风险'));
    $aJson['series'][] =  $data;
    $aJson['xAxis']['data'] =  array('高风险','中风险','低风险');
//        $aJson['xAxis']['type'] = 'category';
//        $aJson['xAxis']['data'] =  array('高','中','低');
    echo json_encode($aJson);
    exit;

}

function actionRuleTypeRiskData(){
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $hPost['task_instance_id'] = intval($hPost['task_instance_id']);
    /*$sql = "SELECT
                count(tl.id) AS num,
                tl.risk,ru.rule_type
            FROM
                t_task_log tl
            LEFT JOIN t_policy po ON tl.policy_id = po.id
            LEFT JOIN t_rule ru on po.rule_id = ru.rule_id
            WHERE
                tl.task_instance_id = :task_instance_id
            GROUP BY
                ru.rule_type,tl.risk";
    $aData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$hPost->task_instance_id));*/
    $sql = "SELECT
                count(tl.id) AS num,
                tl.risk,ru.rule_type
            FROM
                t_task_log tl
            LEFT JOIN t_policy po ON tl.policy_id = po.id
            LEFT JOIN t_rule ru on po.rule_id = ru.rule_id
            WHERE
                tl.task_instance_id = ".intval($hPost['task_instance_id'])."
            GROUP BY
                ru.rule_type,tl.risk";
    $aData = $db_jx->fetch_all($sql,'db_jx');
    $data = array();
//		$ruletype = array(
//				0=>'ACCOUNT',
//				1=>'SOFTWARE',
//				2=>'LOG',
//				3=>'PROCESS'
//		);
    //查出各基线类型低风险
    $lowrisk = array(0,0,0,0);
    foreach($aData as $v){
        if($v['risk']>0 && $v['risk']<4){
            if($v['rule_type']==0){
                $lowrisk[0] = $v['num'];
            }elseif($v['rule_type']==1){
                $lowrisk[1] = $v['num'];
            }elseif($v['rule_type']==2){
                $lowrisk[2] = $v['num'];
            }elseif($v['rule_type']==3){
                $lowrisk[3] = $v['num'];
            }
        }
    }
    //查出各基线类型中风险
    $midrisk = array(0,0,0,0);
    foreach($aData as $v){
        if($v['risk']>=4 && $v['risk']<7){
            if($v['rule_type']==0){
                $midrisk[0] = $v['num'];
            }elseif($v['rule_type']==1){
                $midrisk[1] = $v['num'];
            }elseif($v['rule_type']==2){
                $midrisk[2] = $v['num'];
            }elseif($v['rule_type']==3){
                $midrisk[3] = $v['num'];
            }
        }
    }
    //查出各基线类型高风险
    $highrisk = array(0,0,0,0);
    foreach($aData as $v){
        if($v['risk']>=7){
            if($v['rule_type']==0){
                $highrisk[0] = $v['num'];
            }elseif($v['rule_type']==1){
                $highrisk[1] = $v['num'];
            }elseif($v['rule_type']==2){
                $highrisk[2] = $v['num'];
            }elseif($v['rule_type']==3){
                $highrisk[3] = $v['num'];
            }
        }
    }
    array_push($data,array('data'=>$highrisk,'name'=>'高风险'));
    array_push($data,array('data'=>$midrisk,'name'=>'中风险'));
    array_push($data,array('data'=>$lowrisk,'name'=>'低风险'));

    $aJson['series'] =  $data;
//		$aJson['xAxis']['data'] =  array('高风险','中风险','低风险');
//        $aJson['xAxis']['type'] = 'category';
//        $aJson['xAxis']['data'] =  array('高','中','低');
    echo json_encode($aJson);
    exit;

}

function actionDevTypeRiskData(){
    $db_jx= new client_db();

    $hPost = $_REQUEST;
    $hPost['task_instance_id'] = intval($hPost['task_instance_id']);
    /*$sql = "SELECT
                count(tl.id) AS num,
                tl.risk,
                dev.dev_type
            FROM
                t_task_log tl
            LEFT JOIN t_dev dev on tl.dev_id = dev.id
            WHERE
                tl.task_instance_id = :task_instance_id
            GROUP BY
                dev.dev_type,
                tl.risk";
    $aData = Yii::app()->bd->createCommand($sql)->queryAll(true,array(':task_instance_id'=>$hPost->task_instance_id));*/
    $sql = "SELECT
                count(tl.id) AS num,
                tl.risk,
                dev.dev_type
            FROM
                t_task_log tl
            LEFT JOIN t_dev dev on tl.dev_id = dev.id
            WHERE
                tl.task_instance_id = ".intval($hPost['task_instance_id'])."
            GROUP BY
                dev.dev_type,
                tl.risk";
    $aData = $db_jx->fetch_all($sql,'db_jx');
    $data = array();
//		$ruletype = array(
//				0=>'ACCOUNT',
//				1=>'SOFTWARE',
//				2=>'LOG',
//				3=>'PROCESS'
//		);
    //查出各基线类型低风险
    $lowrisk = array(0,0,0,0);
    foreach($aData as $v){
        if($v['risk']>0 && $v['risk']<4){
            if($v['dev_type']==0){
                $lowrisk[0] = $v['num'];
            }elseif($v['dev_type']==1){
                $lowrisk[1] = $v['num'];
            }elseif($v['dev_type']==2){
                $lowrisk[2] = $v['num'];
            }elseif($v['dev_type']==3){
                $lowrisk[3] = $v['num'];
            }
        }
    }
    //查出各基线类型中风险
    $midrisk = array(0,0,0,0);
    foreach($aData as $v){
        if($v['risk']>=4 && $v['risk']<7){
            if($v['dev_type']==0){
                $midrisk[0] = $v['num'];
            }elseif($v['dev_type']==1){
                $midrisk[1] = $v['num'];
            }elseif($v['dev_type']==2){
                $midrisk[2] = $v['num'];
            }elseif($v['dev_type']==3){
                $midrisk[3] = $v['num'];
            }
        }
    }
    //查出各基线类型高风险
    $highrisk = array(0,0,0,0);
    foreach($aData as $v){
        if($v['risk']>=7){
            if($v['dev_type']==0){
                $highrisk[0] = $v['num'];
            }elseif($v['dev_type']==1){
                $highrisk[1] = $v['num'];
            }elseif($v['dev_type']==2){
                $highrisk[2] = $v['num'];
            }elseif($v['dev_type']==3){
                $highrisk[3] = $v['num'];
            }
        }
    }
    array_push($data,array('data'=>$highrisk,'name'=>'高风险'));
    array_push($data,array('data'=>$midrisk,'name'=>'中风险'));
    array_push($data,array('data'=>$lowrisk,'name'=>'低风险'));

    $aJson['series'] =  $data;
//		$aJson['xAxis']['data'] =  array('高风险','中风险','低风险');
//        $aJson['xAxis']['type'] = 'category';
//        $aJson['xAxis']['data'] =  array('高','中','低');
    echo json_encode($aJson);
    exit;

}
function Devtypename(){
    $db_jx= new client_db();

    $sql = "select * from t_dev_type where is_base_os=1";
    $dData = $db_jx->fetch_all($sql,'db_jx');
    $devtypearr = array();
    foreach($dData as $d){
        $devtypearr[$d['mask']] = $d['type_name'];
    }
    return $devtypearr;
}

}
