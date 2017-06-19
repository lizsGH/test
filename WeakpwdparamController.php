<?php
/**
 * 弱密码参数
 */
namespace app\controllers;

use app\components\client_db;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class WeakpwdparamController extends BaseController
{
    /**
     * @列表页
     */
    function actionIndex()
    {
        global $act;
        $aData = array();
        template2($act . '/index', $aData);
    }

    /**
     * @ 保存弱口令参数
     */
    function actionUpdateparams()
    {
        global $act, $show;
        $aData = $setData = array();
        $sData = array();
        $sPost = $_POST;
        $aData['iDictionary'] = filterStr($sPost['iDictionary']);
        $aData['iContent'] = $sPost['iContent'];
        //验证导入文件内容的格式
        if (preg_match("/[\x{4e00}-\x{9fa5}]+/u", $aData['iContent'])) {
            $aJson['msg'] = Yii::t('app', "文件内容不允许使用中文");
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }

        /*$text = nl2br($aData['iContent']);  //将分行符PHP_EOL转义成HTML的换行符"<br />"
        $iCon = str_replace("<br />",",",$text);
        $iCon = str_replace(PHP_EOL,"",$iCon);
        $iCon = explode(",",$iCon);*/
        $iCon = explode(PHP_EOL, $aData['iContent']);

        for ($i = 0; $i < count($iCon); $i++) {
            if (!empty($iCon[$i])) {
                if ($aData['iDictionary'] == 'vnc') {
                    $sData[] = $iCon[$i];
                } else if ($aData['iDictionary'] == 'ibm_db2') {//冒号分成的3部分，均不能为空
                    $iAcc = explode(':', $iCon[$i]);
                    if (count($iAcc) != 3 || empty($iAcc[0]) || empty($iAcc[1]) || empty($iAcc[2])) {
                        $aJson['msg'] = Yii::t('app', "第 ") . ($i + 1) . Yii::t('app', " 行格式错误");
                        $aJson ['success'] = false;
                        $aJson ['iCon'] = $iCon;
                        echo json_encode($aJson);
                        exit;
                    } else {
                        $sData[] = $iCon[$i];
                    }
                } else {
                    $iAcc = explode(':', $iCon[$i]);
                    if (count($iAcc) != 2) {
                        $aJson['msg'] = Yii::t('app', "第 ") . ($i + 1) . Yii::t('app', " 行格式错误");
                        $aJson ['success'] = false;
                        $aJson ['iCon'] = $iCon;
                        echo json_encode($aJson);
                        exit;
                    } else {
                        $sData[] = $iCon[$i];
                    }
                }
            }
        }

        $sData = array_flip(array_flip($sData));
        $sString = implode(PHP_EOL, $sData);      //保存的数据列表
        $sFile = DIR_ROOT . "../config/weakpwdparam/" . $aData['iDictionary'] . ".config";
        $nFile = "/home/bluedon/bdscan/bdweakscan/bdweakscanparam/".$aData['iDictionary'].".config";
        if (file_put_contents($sFile, $sString) && file_put_contents($nFile, $sString)) {
            $aJson['msg'] = Yii::t('app', "操作成功");
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', '更新弱口令参数');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', "操作失败");
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }
    }

    /**
     * @ 导入字典
     */
    function actionImports()
    {
        global $act, $show;
        $aData = $setData = array();
        $sPost = $_POST;
        $aData['iDictionary'] = filterStr($sPost['iDictionary']);
        $sFile = DIR_ROOT . "../config/weakpwdparam/" . $aData['iDictionary'] . ".config";
        $filesconfig = explode('.', $_FILES["fileField"]["name"]);
        //验证导入文件内容的格式
        $c = file_get_contents($_FILES["fileField"]["tmp_name"]);//var_dump($c);die;
        if (preg_match("/[\x{4e00}-\x{9fa5}]+/u", $c)) {
            $aJson['msg'] = Yii::t('app', "导入文件内容不允许使用中文");
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }
        //PHP_EOL
        $iCon = explode(PHP_EOL, $c);//var_dump(PHP_EOL,$iCon);die;
        if ($aData['iDictionary'] != 'vnc') {
            foreach ($iCon as $i => $v) {
                if (!empty($iCon[$i])) {
                    $iAcc = explode(':', $iCon[$i]);
                   // var_dump(count($iAcc));
                    if (count($iAcc) !== 2) {
                        $aJson['msg'] = Yii::t('app', '导入文件') . Yii::t('app', "第 ") . ($i + 1) . Yii::t('app', " 行格式错误");
                        $aJson ['success'] = false;
                        $aJson ['iCon'] = $iCon;
                        echo json_encode($aJson);
                        exit;
                    } else {
                        $sData[] = $iCon[$i];
                    }
                }
            }
        }
        $sData = array_flip(array_flip($sData));
        $sString = implode(PHP_EOL, $sData);      //保存的数据列表
        file_put_contents($_FILES["fileField"]["tmp_name"], $sString);

        if (isset($_FILES["fileField"])) {   //是否选择了文件
            if ($filesconfig[1] == "config") {
                $upFilePath = DIR_ROOT . "../config/weakpwdparam/";
                $encodeType = mb_detect_encoding(file_get_contents($_FILES["fileField"]["tmp_name"]));
                if ($encodeType == "UTF-8") {   //判断文件的编码格式,若为UTF-8
                    $ok = @move_uploaded_file($_FILES["fileField"]["tmp_name"], $sFile);
                    if ($ok === FALSE) {      //上传不成功
                        $aJson['msg'] = Yii::t('app', '上传失败');
                        $aJson ['success'] = false;
                        echo json_encode($aJson);
                        exit;
                    } else {          //上传成功
                        $aJson['msg'] = Yii::t('app', "上传成功");
                        $aJson ['success'] = true;
                        $hdata['sDes'] = Yii::t('app', '导入弱口令字典');
                        $hdata['sAct'] = $act . '/' . $show;
                        saveOperationLog($hdata);
                        echo json_encode($aJson);
                        exit;
                    }
                } else {      //编码格式不为UTF-8
                    $aJson['msg'] = Yii::t('app', '请上传UTF-8编码格式的文件');
                    $aJson ['success'] = false;
                    echo json_encode($aJson);
                    exit;
                }
            } else {
                $aJson['msg'] = Yii::t('app', '请选择文件后缀名为config的文件');
                $aJson ['success'] = false;
                echo json_encode($aJson);
                exit;
            }

        } else {          //没有选择文件
            $aJson['msg'] = Yii::t('app', '请选择文件');
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }
    }


    /**
     * @ 导出当前使用的字典
     */
    function actionExportpwdparam()
    {
        global $act, $show;
        $aData = array();
        $sPost = $_POST;
        $iDictionary = filterStr($sPost['iDictionary']);
        $aJson['msg'] = '/weakpwdparam/downloadweak?idict=' . $iDictionary;
        echo json_encode($aJson);
        exit;
    }

    function actionDownloadweak()
    {
        $iDictionary = filterStr($_GET['idict']);
        $sTitle = $iDictionary . ".config";
        $sFilePath = DIR_ROOT . "../config/weakpwdparam/" . $iDictionary . ".config";
        downloadFile($sTitle, $sFilePath);
    }


    /**
     * @ 恢复默认字典
     */
    function actionDefaultparamsetting()
    {
        global $act, $show;
        $aData = $setData = array();
        $sPost = $_POST;
        $path = DIR_ROOT . "../config/weakpwdparam/";
        $sDefault = $this->getDefaultParams('dic');
        $this->deleteParamsfiles('config');
        $sDefaultFile = $sDefault . ".config";
        if (!empty($sDefault)) {
            foreach ($sDefault as $v) {
                $filename = substr($v, 0, -4);  //去掉后缀 .dic
                $iContent = file_get_contents($path . $v);
                $sFile = $path . $filename . ".config";
                if (file_put_contents($sFile, $iContent)) {

                } else {
                    $aJson['msg'] = $filename . Yii::t('app', "恢复失败");
                    $aJson ['success'] = false;
                    echo json_encode($aJson);
                    exit;
                }
            }
        }
        $aJson['msg'] = Yii::t('app', "操作成功");
        $aJson ['success'] = true;
        $hdata['sDes'] = Yii::t('app', '恢复默认字典');
        $hdata['sAct'] = $act . '/' . $show;
        saveOperationLog($hdata);
        echo json_encode($aJson);
        exit;

    }


    function defaultparamsettingOld()
    {
        global $act, $show;
        $aData = $setData = array();
        $sPost = $_POST;
        $sDefault = params('weakPassword');
        $sDefaultFile = $sDefault . ".config";
        if (file_exists(DIR_ROOT . "../config/weakpwdparam/" . $sDefaultFile)) {
            $aJson['msg'] = $sDefault;
            $aJson ['success'] = true;
            $hdata['sDes'] = Yii::t('app', '恢复默认字典');
            $hdata['sAct'] = $act . '/' . $show;
            saveOperationLog($hdata);
            echo json_encode($aJson);
            exit;
        } else {
            $aJson['msg'] = Yii::t('app', '操作失败');
            $aJson ['success'] = false;
            echo json_encode($aJson);
            exit;
        }
    }


    /**
     * @ 改变字典
     */
    function actionChangedictionary()
    {
        $aData = array();
        $sPost = $_POST;
        $iDictionary = filterStr($sPost['iDictionary']);
        $sFile = DIR_ROOT . "../config/weakpwdparam/" . $iDictionary . ".config";
        $iContent = file_get_contents($sFile);
        echo $iContent;
    }

    function getDefaultParams($file_type = 'dic')
    {
        $dics = array();
        $files = scandir(DIR_ROOT . "../config/weakpwdparam/");
        array_shift($files);
        for ($i = 0; $i < count($files); $i++) {
            if (preg_match('/(.*)(\.)' . $file_type . '$/i', $files[$i]))
                $dics[] = $files[$i];
        }
        return $dics;
    }

    function deleteParamsfiles($file_type = 'config')
    {
        $dics = array();
        $path = DIR_ROOT . "../config/weakpwdparam/";
        $files = scandir(DIR_ROOT . "../config/weakpwdparam/");
        array_shift($files);
        for ($i = 0; $i < count($files); $i++) {
            if (preg_match('/(.*)(\.)' . $file_type . '$/i', $files[$i]))
                unlink($path . $files[$i]);
        }
    }
}
