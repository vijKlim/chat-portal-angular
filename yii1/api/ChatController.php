<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 22.09.2015
 * Time: 16:37
 */
Yii::import('application.modules.profile.components.ClientChat');
//require_once('../profile/components/ClientChat.php');
class ChatController extends Controller{
    use ClientChat;
    protected $salt = 'vfufpbybot';
    protected function beforeAction($action)
    {
        //echo $_GET['userId']."   ".$_GET['key'];Yii::app()->end();
        $error = true;
        if(isset($_GET['userId']) && isset($_GET['key']))
        {
            if(md5($_GET['userId'].$this->salt) == $_GET['key']){
                if($client = (new PModulesClient())->findByPk($_GET['userId'])){
                    Yii::app()->client->id = $client->id;
                    $error = false;
                }
            }

//            if($client = (new PModulesClient())->findByPk($_GET['userId'])){
//                //echo json_encode($client);Yii::app()->end();
//                Yii::app()->client->id = $client->id;
//                $error = false;
//            }
            return parent::beforeAction($action);
        }
        if($error)
            $this->emptyResponse();
    }
    private function emptyResponse()
    {
        echo 'пусто';
        Yii::app()->end();
    }
} 