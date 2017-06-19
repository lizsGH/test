<?php

namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class UserController extends BaseController
{
    /**
     * @列表页
     */
    function actionIndex(){
        global $act, $db;
        $aData = array();
        $userconfig = $db->fetch_first("SELECT * FROM ".getTable('userconfig')." where iId=1");
        $aData['userconfig'] = $userconfig;
        $aData['iSuid'] = intval($_SESSION['userid']);
        //return $this->render('index');
        $aData['roles'] = $db->fetch_all("SELECT * FROM ".getTable('role')." where id != 1 and id !=2  ");
//        var_dump($aData);die;
        template2($act.'/index', $aData);
    }

    /**
     * @新增和编辑页
     */
    function actionEdit(){
        global $act,$db;
        $aData = array();
        $pswstrategy = file_get_contents(DIR_ROOT . "../config/data/system/pswstrategy.config");
        $aData['pswstrategy'] = unserialize($pswstrategy);
        //var_dump($aData);die;
        $aData['roles']=$db->fetch_all("SELECT * FROM bd_sys_role where id != 1" );
        //var_dump($aData['roles']);die;
      //  extract($aData);
        template2($act.'/edit', $aData);
    }

    /**
     * @查看页
     */
    function actionView(){
        global $act;
        template2($act.'/view', array());
    }

    /**
     * @获取列表数据
     */
    function actionLists(){
        global $db;
        $sPost = $_POST;
        $page = intval($sPost['start']);
        $perpage = intval($sPost['length']);
        $total = 0;
        $row  = $roles = array();
        $rows= $aItem =array();
        $roleid = intval($_SESSION['role']);
        $uid = intval($_SESSION['userid']);

        $where = " WHERE 1=1 ";

        $page  = $page > 1 ? $page : 1;
        $username = filterStr($sPost['username']);
        $role = intval($sPost['role']);
        if (!empty($username)) {
            $where .= " AND username LIKE '%{$username}%'";
        }
        if (!empty($role)) {
            if($role =='all'){
                $where .= "";
            }else{
                $where .= " AND role = $role";
            }
        }
        $total   = $db->result_first("SELECT COUNT(`username`) FROM ".getTable('user')."$where"." and id not in(2,30,31,32)");
        $maxPage = ceil($total/$perpage);
        $page    = $page >= $maxPage ? $maxPage : $page;
        if($total){
            $start = ($page-1)*$perpage;
            $where .= " and u.id not in(2,30,31,32)";
            $row  = $db->fetch_all("SELECT u.*,r.role_name,r.bVisible FROM ".getTable('user')." as u inner join ".getTable('role')." as r on u.role_id = r.id  $where order by u.id LIMIT $start,$perpage ");
        }
//var_dump($row);die;
        foreach($row as $k=>$v){
            $aItem=array(
                'id'=>$v['id'],
                'username' =>$v['username'],
                'role_id' =>$v['role_id'],
                'role_name' =>$v['role_name'],
                'status'=>$v['status'],
                'iStatus' =>$v['status'] =1? Yii::t('app', "正常"): Yii::t('app', "禁止"),
                'bVisible'=>$v['bVisible'],
                'iSuid' =>$_SESSION['userid'],
            );
            array_push($rows,$aItem);
        }

        $data['Rows'] = $rows;
        $data['Total'] = $total;
        echo json_encode($data);
        exit;
    }

    /**
     * @新增或者编辑用户，保存到数据库
     */
    function actionAddandedit(){
        global $db,$act,$show;
        $sPost = $_POST;
        $id = intval($sPost['id']);
        $username = filterStr($sPost['username']);
        $status = intval($sPost['status']);
        $role = intval($sPost['role_id']);
        $handler = filterStr($_SESSION['username']);
        $user_role = intval($_SESSION['role']);

        if($id ==30 || $id ==31 || $id ==32 || $id==2){
            $data['success'] = false;
            $data['msg'] = $username.Yii::t('app', '内置用户不能编辑');
            $hdata['sDes'] = Yii::t('app', '编辑内置用户');
            $hdata['srs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act.'/'.$show;
            saveOperationLog($hdata);
            echo json_encode($data);
            exit;
        }

        if($id){//编辑
            $query = "update ".getTable('user')." set username='".$username."',status=$status, role_id=$role  where id=$id";
           # echo  $query;die;
            if($db->execute($query)){
                $sqlroleuser = "update ".getTable('roleuser')." set role_id = '".$role."' where id ='".$id."'";
                $db->query($sqlroleuser);
                $sqloldRid = "select role_id as  role from ".getTable('user')." where username ='".$username."' And id =".$id;
                $oleRid = $db->fetch_first($sqloldRid);
                $sqlrolenav = "update ".getTable('rolenavtree')." set role_id ='".$role."' where role_id='".$oleRid['role']."'";
                $db->query($sqlrolenav);
                $success = true;
                $msg = "操作成功";
                $hdata['sDes'] = '编辑用户';
                $hdata['srs'] ='成功';
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }else{
                $success = false;
                $msg = "操作失败";
                $hdata['sDes'] = '编辑用户';
                $hdata['srs'] ='失败';
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }


        }else{//新增
          //  echo 2;die;
            if($_SESSION['userid'] == '2'){

                $pd_password = 'bluedon';       //默认密码/普通版
                $password = md5(md5($pd_password).$username);


                $iTotal = $db->result_first("SELECT COUNT(`username`) FROM ".getTable('user')." where username='".$username."'");
                if(!empty($iTotal)){
                    $data['success'] = false;
                    $data['msg'] = $username.Yii::t('app', '已存在，请更换');
                    echo json_encode($data);
                    exit;
                }
                $query = "insert into ".getTable('user')." (username,password,status,role_id) values('".$username."','".$password."',$status,$role)";
                if($db->query($query)){
                    $uIdsql  ="select id from ".getTable('user')." where username ='".$username."'";
                    $rsUid = $db->fetch_first($uIdsql);
                    $sqlroleuser = "insert into ".getTable('roleuser')." (id,role_id,user_id) values('','".$role."','".$rsUid['id']."')";
                    $db->query($sqlroleuser);
                    $success = true;
                    $msg = Yii::t('app', "操作成功");
                    $hdata['sDes'] = Yii::t('app', '新增用户');
                    $hdata['sRs'] = Yii::t('app', '成功');
                    $hdata['sAct'] = $act.'/'.$show;
                    saveOperationLog($hdata);
                }else{
                    $success = false;
                    $msg = Yii::t('app', "操作失败");
                    $hdata['sDes'] = Yii::t('app', '新增用户');
                    $hdata['srs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act.'/'.$show;
                    saveOperationLog($hdata);
                }
            }else{
                $success = false;
                $msg = Yii::t('app', "无权限");
                $hdata['sDes'] = Yii::t('app', '新增用户');
                $hdata['srs'] = Yii::t('app', '失败');
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
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
    function actionDel(){
        global $db,$act,$show;
        $sPost = $_POST;
        $id = filterStr($sPost['id']);
        $idArr = explode(',',$id);
        foreach($idArr as $k =>$v){
            if($v == 30 || $v == 31 ||$v ==32 || $v==2){
                $success = false;
                $msg = Yii::t('app', "删除用户中含有内置用户，删除失败。");
                $hdata['sDes'] = Yii::t('app', '删除用户中含有内置用户');
                $hdata['sRs'] = Yii::t('app', '失败');
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
                $data['success'] = $success;
                $data['msg'] = $msg;
                echo json_encode($data);
                exit;
            }
        }
        if($id != 30 || $id != 31 ||$id != 32 || $v!=2){
            $query = "DELETE FROM ".getTable('user')." where id in (".$id.") ";
            if($db->query($query)){
                $deltask= "delete from task_manage where id in (".$id.") ";
                $db->query($deltask);
                $success = true;
                $msg = Yii::t('app', "操作成功");
                $hdata['sDes'] = Yii::t('app', '删除用户');
                $hdata['sRs'] = Yii::t('app', '成功');
                $hdata['sAct'] = $act.'/'.$show;
                saveOperationLog($hdata);
            }else{
                $success = false;
                $msg = Yii::t('app', "操作失败");
            }
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }else{
            $success = false;
            $msg = Yii::t('app', "内置用户不能删除");
            $hdata['sDes'] = Yii::t('app', '删除内置用户');
            $hdata['sRs'] = Yii::t('app', '失败');
            $hdata['sAct'] = $act.'/'.$show;
            saveOperationLog($hdata);
            $data['success'] = $success;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit;
        }

    }

    function actionResetpasswd(){
        global $act,$db;
        $aData = array();
        $pswstrategy = file_get_contents(DIR_ROOT . "config/data/system/pswstrategy.config");
        $aData['pswstrategy'] = unserialize($pswstrategy);
        template2($act.'/resetpasswd', $aData);
    }

    function actionResetuserpass(){
        global $db,$act,$show;
        $sPost = $_POST;
        $ajson = array();
        if($_SESSION){
            $userid = trim($sPost['id']);
            $password = trim($sPost['resetpassword']);
            if(empty($userid)){
                $ajson['success'] = false;
                $ajson['msg'] = Yii::t('app', "请选择用户！");
            }else{
                if($password == 'Bluedon2100'){
                    $ajson['success'] = false;
                    $ajson['msg'] = Yii::t('app', "不允许使用初始密码！");
                }else{
                    $sFile = DIR_ROOT . "config/data/system/pswstrategy.config";
                    $cPw = file_get_contents($sFile);
                    $cPw =unserialize($cPw);
                    if($cPw){
                        if(!empty($cPw['pLength'])){
                            if(strlen($password)<$cPw['pLength']){
                                $ajson['success'] = false;
                                $ajson['msg'] = Yii::t('app', "密码长度不能小于").$cPw['pLength'];
                                echo json_encode($ajson);
                                exit;
                            }
                            if(strlen($password)>20){
                                $ajson['success'] = false;
                                $ajson['msg'] = Yii::t('app', "密码长度不能大于20个字符");
                                echo json_encode($ajson);
                                exit;
                            }

                        }

                        if(!empty($cPw['pStrength'])){
                            if($cPw['pStrength'] ==1){
                                if(empty($cPw['pLength'])){
                                    $cPw['pLength'] =6;
                                }
                                if(!preg_match('/(?!^\d+$)(?!^[a-zA-Z]+$)[0-9a-zA-Z]{'.$cPw['pLength'].',20}/',$password)){
                                    $ajson['success'] = false;
                                    $ajson['msg'] = Yii::t('app', "密码必须是字母和数字组合");
                                    echo json_encode($ajson);
                                    exit;
                                }
                            }
                            if($cPw['pStrength'] ==2){
                                if(empty($cPw['pLength'])){
                                    $cPw['pLength'] =6;
                                }
                                if(!preg_match('/(?=.*[\d]+)(?=.*[a-zA-Z]+)(?=.*[^a-zA-Z0-9]+).{'.$cPw['pLength'].',20}/',$password)){
                                    $ajson['success'] = false;
                                    $ajson['msg'] = Yii::t('app', "密码必须是字母数字和特殊字符组合");
                                    echo json_encode($ajson);
                                    exit;
                                }
                            }
                        }
                    }
                    $sql ="select * from ".getTable('user')." where id = ".$userid;
                    $res = $db->fetch_first($sql);
                    $name = trim($res['username']);
                    $pwdnew =  md5(md5($password).$name);
                    $upsql = "UPDATE ".getTable('user')." set password = '".$pwdnew."',update_pwd_time='".time()."' where id =".$userid;
                    if($db->query($upsql)){
                        $ajson['success'] = true;
                        $ajson['msg'] = Yii::t('app', '操作成功') . "！";
                        $hdata['sDes'] = Yii::t('app', '重置').$name.Yii::t('app', '密码');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act.'/'.$show;
                        saveOperationLog($hdata);
                        $username = filterStr($_SESSION['username']);
                        if(!empty($username)){
                            $hdata2['sDes'] = Yii::t('app', '退出系统');
                            $hdata2['sRs'] = Yii::t('app', '成功');
                            $hdata2['username'] = $username;
                            $hdata2['sAct'] = $act.'/'.$show;
                            saveOperationLog($hdata2);
                        }
                        $db->query("DELETE FROM "."bd_sys_sessions"." WHERE username='".$_SESSION['username']."'");
                        session_unset();
                        session_destroy();
                    }else{
                        $hdata['sDes'] = $name.Yii::t('app', '重置密码');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act.'/'.$show;
                        saveOperationLog($hdata);
                        $ajson['success'] = false;
                        $ajson['msg'] = Yii::t('app', '操作失败') . "！";
                    }
                }
            }
            echo json_encode($ajson);
            exit;
        }
    }

    //修改密码
    function actionUpdatepasswd(){
        global $act,$db;
        $aData = array();
        $pswstrategy = file_get_contents(DIR_ROOT . "../config/data/system/pswstrategy.config");
        $aData['pswstrategy'] = unserialize($pswstrategy);
        template2($act.'/updatepasswd', $aData);
    }

    function actionUpdateloginpasswd(){
        global $act,$db;
        $aData = array();
        $pswstrategy = file_get_contents(DIR_ROOT . "config/data/system/pswstrategy.config");
        $aData['pswstrategy'] = unserialize($pswstrategy);
        template2($act.'/updateloginpasswd', $aData);
    }

    function actionChangepass(){
        global $db,$act,$show;
        $sPost = $_POST;
        $ajson = array();
        if($_SESSION){
            $userid = intval($_SESSION['userid']);
            $name = filterStr($_SESSION['username']);
            $oldpwd = trim($sPost['sPasswordOld']);
            $newpwd = trim($sPost['sPasswordnNew']);
            $sql ="select * from ".getTable('user')." where id = ".$userid;
            $res = $db->fetch_first($sql);
            $pwdold = md5(md5($oldpwd).$name);
            if($newpwd == 'Bluedon2100'){
                $ajson['success'] = false;
                $ajson['msg'] = Yii::t('app', "不允许使用初始密码！");
            }else{
                if($res['password'] == $pwdold){
                    $sFile = DIR_ROOT . "config/data/system/pswstrategy.config";
                    $cPw = file_get_contents($sFile);
                    $cPw =unserialize($cPw);
                    if($cPw){
                        if(!empty($cPw['pLength'])){
                            if(strlen($newpwd)<$cPw['pLength']){
                                $ajson['success'] = false;
                                $ajson['msg'] = Yii::t('app', "密码长度不能小于").$cPw['pLength'];
                                echo json_encode($ajson);
                                exit;
                            }
                            if(strlen($newpwd)>20){
                                $ajson['success'] = false;
                                $ajson['msg'] = Yii::t('app', "密码长度不能大于20个字符");
                                echo json_encode($ajson);
                                exit;
                            }
                        }
                        if(!empty($cPw['pStrength'])){
                            if($cPw['pStrength'] ==1){
                                if(empty($cPw['pLength'])){
                                    $cPw['pLength'] =6;
                                }
                                if(!preg_match('/(?!^\d+$)(?!^[a-zA-Z]+$)[0-9a-zA-Z]{'.$cPw['pLength'].',20}/',$newpwd)){
                                    $ajson['success'] = false;
                                    $ajson['msg'] = Yii::t('app', "密码必须是字母和数字组合");
                                    echo json_encode($ajson);
                                    exit;
                                }
                            }
                            if($cPw['pStrength'] ==2){
                                if(empty($cPw['pLength'])){
                                    $cPw['pLength'] =6;
                                }
                                if(!preg_match('/(?=.*[\d]+)(?=.*[a-zA-Z]+)(?=.*[^a-zA-Z0-9]+).{'.$cPw['pLength'].',20}/',$newpwd)){
                                    $ajson['success'] = false;
                                    $ajson['msg'] = Yii::t('app', "密码必须是字母数字和特殊字符组合");
                                    echo json_encode($ajson);
                                    exit;
                                }
                            }
                        }
                    }
                    $pwdnew =  md5(md5($newpwd).$name);
                    $upsql = "UPDATE ".getTable('user')." set password = '".$pwdnew."',update_pwd_time='".time()."' where id =".$userid;
                    if($db->query($upsql)){
                        $ajson['success'] = true;
                        $ajson['msg'] = Yii::t('app', '操作成功') . "！";
                        $hdata['sDes'] = Yii::t('app', '修改').$name.Yii::t('app', '密码成功');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act.'/'.$show;
                        saveOperationLog($hdata);
                        $username = filterStr($_SESSION['username']);
                        if(!empty($username)){
                            $hdata2['sDes'] = Yii::t('app', '退出系统');
                            $hdata2['sRs'] = Yii::t('app', '成功');
                            $hdata2['username'] = $username;
                            $hdata2['sAct'] = $act.'/'.$show;
                            saveOperationLog($hdata2);
                        }
                        $db->query("DELETE FROM "."bd_sys_sessions"." WHERE username='".$_SESSION['username']."'");
                        session_unset();
                        session_destroy();
                    }else{
                        $hdata['sDes'] = Yii::t('app', '修改').$name.Yii::t('app', '密码');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act.'/'.$show;
                        saveOperationLog($hdata);
                        $ajson['success'] = false;
                        $ajson['msg'] = Yii::t('app', '操作失败') . "！";
                    }
                }else{
                    $ajson['success'] = false;
                    $ajson['msg'] = Yii::t('app', "输入的旧密码错误！");
                    $hdata['sDes'] = Yii::t('app', '修改').$name.Yii::t('app', '密码');
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act.'/'.$show;
                    saveOperationLog($hdata);
                }
            }
            echo json_encode($ajson);
            exit;
        }
    }


    function actionChangeloginpass(){
        global $db,$act,$show;
        $sPost = $_POST;
        $ajson = array();
        if($_SESSION){
            $userid = intval($_SESSION['userid']);
            $name = filterStr($_SESSION['username']);
            $oldpwd = trim($sPost['sPasswordOld']);
            $newpwd = trim($sPost['sPasswordnNew']);
            if($oldpwd == $newpwd){
                $ajson['success'] = false;
                $ajson['msg'] = Yii::t('app', "新旧密码不能相同！");
                echo json_encode($ajson);
                exit;
            }
            $sql ="select * from ".getTable('user')." where id = ".$userid;
            $res = $db->fetch_first($sql);
            $pwdold = md5(md5($oldpwd).$name);
            if($newpwd == 'Bluedon2100'){
                $ajson['success'] = false;
                $ajson['msg'] = Yii::t('app', "不允许使用初始密码！");
            }else{
                if($res['password'] == $pwdold){
                    $sFile = DIR_ROOT . "config/data/system/pswstrategy.config";
                    $cPw = file_get_contents($sFile);
                    $cPw =unserialize($cPw);
                    if($cPw){
                        if(!empty($cPw['pLength'])){
                            if(strlen($newpwd)<$cPw['pLength']){
                                $ajson['success'] = false;
                                $ajson['msg'] = Yii::t('app', "密码长度不能小于").$cPw['pLength'];
                                echo json_encode($ajson);
                                exit;
                            }
                            if(strlen($newpwd)>20){
                                $ajson['success'] = false;
                                $ajson['msg'] = Yii::t('app', "密码长度不能大于20个字符");
                                echo json_encode($ajson);
                                exit;
                            }
                        }
                        if(!empty($cPw['pStrength'])){
                            if($cPw['pStrength'] ==1){
                                if(empty($cPw['pLength'])){
                                    $cPw['pLength'] =6;
                                }
                                if(!preg_match('/(?!^\d+$)(?!^[a-zA-Z]+$)[0-9a-zA-Z]{'.$cPw['pLength'].',20}/',$newpwd)){
                                    $ajson['success'] = false;
                                    $ajson['msg'] = Yii::t('app', "密码必须是字母和数字组合");
                                    echo json_encode($ajson);
                                    exit;
                                }
                            }
                            if($cPw['pStrength'] ==2){
                                if(empty($cPw['pLength'])){
                                    $cPw['pLength'] =6;
                                }
                                if(!preg_match('/(?=.*[\d]+)(?=.*[a-zA-Z]+)(?=.*[^a-zA-Z0-9]+).{'.$cPw['pLength'].',20}/',$newpwd)){
                                    $ajson['success'] = false;
                                    $ajson['msg'] = Yii::t('app', "密码必须是字母数字和特殊字符组合");
                                    echo json_encode($ajson);
                                    exit;
                                }
                            }
                        }
                    }
                    $pwdnew =  md5(md5($newpwd).$name);
                    $upsql = "UPDATE ".getTable('user')." set password = '".$pwdnew."',update_pwd_time='".time()."' where id =".$userid;
                    if($db->query($upsql)){
                        $ajson['success'] = true;
                        $ajson['msg'] = Yii::t('app', '操作成功') . "！";
                        $hdata['sDes'] = Yii::t('app', '修改').$name.Yii::t('app', '密码成功');
                        $hdata['sRs'] = Yii::t('app', '成功');
                        $hdata['sAct'] = $act.'/'.$show;
                        saveOperationLog($hdata);
                    }else{
                        $hdata['sDes'] = Yii::t('app', '修改').$name.Yii::t('app', '密码');
                        $hdata['sRs'] = Yii::t('app', '失败');
                        $hdata['sAct'] = $act.'/'.$show;
                        saveOperationLog($hdata);
                        $ajson['success'] = false;
                        $ajson['msg'] = Yii::t('app', '操作失败') . "！";
                    }
                }else{
                    $ajson['success'] = false;
                    $ajson['msg'] = Yii::t('app', "输入的旧密码错误！");
                    $hdata['sDes'] = Yii::t('app', '修改').$name.Yii::t('app', '密码');
                    $hdata['sRs'] = Yii::t('app', '失败');
                    $hdata['sAct'] = $act.'/'.$show;
                    saveOperationLog($hdata);
                }
            }
            echo json_encode($ajson);
            exit;
        }
    }

}
