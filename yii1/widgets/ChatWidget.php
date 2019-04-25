<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 14.09.2015
 * Time: 14:23
 */

class ChatWidget extends CWidget{

    protected $tpl;
    protected  $myId;
    protected $multi_accaunt;
    protected $sp;

    public function init()
    {
        $this->tpl = 'application.modules.pfront.widgets.views.chat.chat';
        $this->myId = Yii::app()->client->id;

        $count =Yii::app()->db->createCommand()
            ->select('count(account_id)')->from('modules_chat_multi_accounts')
            ->where('user_id=:id', array(':id'=>Yii::app()->client->id)
            )->queryColumn()[0];
        if($count){
            $this->sp = 1;
            $this->multi_accaunt = 1;
        }else{
            $this->sp = 0;
            $this->multi_accaunt = 0;
        }

    }

    public function run()
    {
        if(Yii::app()->client->id){
            if(!isIE9andLower()){

                $basePath = $_SERVER['SERVER_NAME'] != 'blog.rixetka.com' ? Yii::app()->params['protocol'].'://'.$_SERVER['SERVER_NAME'] : Yii::app()->params['protocol'].'://tatet.ua';

                $pageURL = $_SERVER["REQUEST_URI"];
                $chatProfile = preg_match('/tatetChat/',$pageURL) ? 1 : 0;
                $script = '
                var tatetChat={ myId: '.Yii::app()->client->id.',
                                muacc: '.$this->multi_accaunt.',
                                sp: '.$this->sp.',
                                domenApp: "'.$basePath.'",
                                chatProfile: '.$chatProfile.',
                                isShop: 0
                              };

                 ';
                Yii::app()->clientScript->registerScript("varApp2", $script, CClientScript::POS_BEGIN);
                Yii::app()->clientScript->registerCssFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.css.scrollbar').'/jquery.jscrollpane.css'));
                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.scrollbar').'/jquery.jscrollpane.min.js'), CClientScript::POS_END );
                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.scrollbar').'/jquery.mousewheel.js'), CClientScript::POS_END );

                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/1.4.3/angular.min.js'), CClientScript::POS_END );
                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/1.4.3/i18n/angular-locale_ru.js'), CClientScript::POS_END );
                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/1.4.3/angular-route.min.js'), CClientScript::POS_END );
                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular.angular-ui-bootstrap').'/0.13.3/ui-bootstrap-tpls.min.js'), CClientScript::POS_END );

                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/angular-notification/angular-notification.min.js'), CClientScript::POS_END );


                Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular.apps.chat.scripts').'/chatApp.min.js'), CClientScript::POS_END );

                //$this->render('chat');
                $this->render($this->tpl);
            }

        }

    }
} 