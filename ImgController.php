<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/15
 * Time: 11:54
 */
namespace app\controllers;
use app\models\ContactForm;
use yii\base\Controller;

class ImgController extends Controller {
    public function actionHost(){
//        echo DIR_ROOT.'report/attack-host.html';die;
        return $this->render('index');
        echo file_get_contents(DIR_ROOT.'report/attack_host.html');
    }
}
