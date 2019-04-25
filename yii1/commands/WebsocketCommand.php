<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 10.09.2015
 * Time: 15:32
 */
ini_set('display_errors',1);
error_reporting(E_ALL);
   // php ../yiic.php websocket start --server=chat

require_once(__DIR__.'/../extensions/websocket/Server.php');
class WebsocketCommand extends CConsoleCommand{

    //public $component = 'websocket';

    public function actionStart($server)
    {
        $WebsocketServer = new Server(Yii::app()->websocket->servers[$server]);
        call_user_func(array($WebsocketServer, 'start'));
    }

    public function actionStop($server)
    {
        $WebsocketServer = new Server(Yii::app()->websocket->servers[$server]);
        call_user_func(array($WebsocketServer, 'stop'));
    }

    public function actionRestart($server)
    {
        $WebsocketServer = new Server(Yii::app()->websocket->servers[$server]);
        call_user_func(array($WebsocketServer, 'restart'));
    }
} 