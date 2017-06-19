<?php
namespace app\controllers;

/**
 * 工具类
 */
use app\components\bdsec_daemon;
class ToolController extends BaseController {


    public function actionNettool(){
        $data = array();
//        $post = intval($_POST['post']);
        $post=\Yii::$app->request->post('post');
        $type = array('ping','telnet','traceroute','tcpdump','nslookup','arp');
        $file = CACHE_DIR. \Yii::$app->session->get('username');
        //echo $file;die;
        if($post == 1){
            $data = array('success'=>false, 'num'=>0, 'message'=>Yii::t('app', '操作失败'));

            $t    = trim($_POST['t']);
            $c    = trim(str_replace($type, '',$_POST['c']));

            if($t!='arp'){
                if($t=='' || $c=='' || !in_array($t, $type)){
                    $data['message'] = Yii::t('app', '指令为空或类型不对');
                    echo json_encode($data);
                    exit;
                }
            }

            $c = escapeshellcmd($c);
            if($t == 'tcpdump'){
                if(!preg_match('/\-[i] eth([0-9]) port \d+$/i',$c)){
                    $data['message'] = Yii::t('app', '指令参数不正确');
                    echo json_encode($data);
                    exit;
                }
                //exit;
                $file = $file."_tcp.txt";
                file_put_contents($file,"");
                exec("tcpdump $c -c 5 >>$file");
                $con = file_get_contents($file);
                if(!$con){file_put_contents($file,Yii::t('app', "没有返回结果"));}

            }else{//ping telnet traceroute nslookup
                if($t == 'ping'){
                    $c = $this->getIPUrl($c);
                    if($c == ''){
                        $data['message'] = Yii::t('app', 'IP或域名不正确');
                        echo json_encode($data);
                        exit;
                    }
                    $file = $file."_ping.txt";
                    file_put_contents($file,"");
                    //ping -c 4 172.16.35.2545  >/dev/null  1>send.txt 2>send.txt  //1为正常时，2为出错时
                    exec("ping -c 4 $c >/dev/null 1>$file 2>$file");
                    //exec("ping $c -c 5 -i 0.5 >>$file");

                }else if($t == 'telnet'){
                    $arr   = explode(' ',$c);
                    if(!is_array($arr) || intval($arr[1])<0 || count($arr)>2){
                        $data['message'] = Yii::t('app', '指令参数不正确');
                        echo json_encode($data);
                        exit;
                    }

                    $c = $this->getIPUrl($c);
                    $file  = $file."_telnet.txt";
                    file_put_contents($file,"");
                    exec("telnet $c >>$file");

                    $con = file_get_contents($file);
                    if(!$con){file_put_contents($file,Yii::t('app', "没有返回结果"));}

                }else if($t == 'nslookup'){
                    $c = $this->getIPUrl($c);
                    if($c == ''){
                        $data['message'] = Yii::t('app', 'IP或域名不正确');
                        echo json_encode($data);
                        exit;
                    }
                    $file = $file."_lookup.txt";
                    file_put_contents($file,"");
                    exec("nslookup $c >>$file");

                    $con = file_get_contents($file);
                    if(!$con){file_put_contents($file,Yii::t('app', "没有返回结果"));}

                }else if($t == 'traceroute'){
                    $c = $this->getIPUrl($c);

                    if($c == ''){
                        $data['message'] = Yii::t('app', 'IP或域名不正确');
                        echo json_encode($data);
                        exit;
                    }

                    $file = $file."_route.txt";
                    file_put_contents($file,"");
                    exec("traceroute $c >>$file");
                }else if($t == 'arp'){
                    /*$c = getIPUrl($c);
                    if($c == ''){
                        $data['message'] = 'IP或域名不正确';
                        echo json_encode($data);
                        exit;
                    }	*/
                    $file = $file."_arp.txt";
                    file_put_contents($file,"");
                    exec("/sbin/arp >>$file");
                    $con = file_get_contents($file);
                    if(!$con){file_put_contents($file,Yii::t('app', "没有返回结果"));}
                }
            }
            $data['success'] = true;
            $data['val']     = $c;
            $data['message'] = Yii::t('app', '即将输出结果');
            echo json_encode($data);
            exit;
        }else if($post == 2){
            $data = array('success'=>false, 'col'>'', 'message'=>Yii::t('app', '操作失败'));

            $t    = trim($_POST['t']);
            if($t == 'ping'){
                $file = $file."_ping.txt";
                //exec("sed -n '$i,1p' $file",$col);
                $col = file_get_contents($file);
            }else if($t == 'telnet'){
                $file = $file."_telnet.txt";
                $col = file_get_contents($file);
            }else if($t == 'nslookup'){
                $file = $file."_lookup.txt";
                $col = file_get_contents($file);
            }else if($t == 'traceroute'){
                $file = $file."_route.txt";
                $col = file_get_contents($file);
            }else if($t == 'tcpdump'){
                $file = $file."_tcp.txt";
                $col = file_get_contents($file);
            }else if($t == 'arp'){
                $file = $file."_arp.txt";
                $col = file_get_contents($file);
            }

            $data['success'] = true;
            $data['col']     = $col;
            echo json_encode($data);
            exit;
        }else{
            //$data['menu'] = $modules['system'].' - '.$items['system'][3]['name'].' - '.$three['system'][3][6]['name'];
            userLog('system',3,6,Yii::t('app', '查看'));
//            return $this->render('nettool',$data);

            template('nettool', $data);
        }
    }

    function getIPUrl($v){
        $vv = str_replace(array('http://','https://'),'',$v);
        //if(preg_match('/^(\d+).(\d+).(\d+).(\d+)/i',$v)){//不兼容172..7.253形式的错误ip输入
        if(!$v = filter_var($vv,FILTER_VALIDATE_IP))
        {
            $v = filter_var('http://'.$vv, FILTER_VALIDATE_URL);
            if($v) $v = preg_replace(array('/http:\/\//i','/https:\/\//i','/\/+(\S+)/i'),'', $v);
        }
        return $v;
        /*if(preg_match('/^(\d+).(\d+).(\d+).(\d+)/i',$v)){
            $v = filter_var($v,FILTER_VALIDATE_IP);
        }else{
            $v = str_replace(array('http://','https://'),'',$v);

            $v = filter_var('http://'.$v, FILTER_VALIDATE_URL);
            if($v) $v = preg_replace(array('/http:\/\//i','/https:\/\//i','/\/+(\S+)/i'),'', $v);
        }
        return $v;*/
    }
}
