<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 17.11.2015
 * Time: 17:01
 */

class PModulesChatUserSettings extends ModulesChatUserSettings{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public static function getDefaultSettings()
    {
        return Yii::app()->db//->cache(86400)
            ->createCommand("SELECT * FROM `modules_chat_default_settings`")->queryAll();
    }

    public static function getSettings($userId)
    {
        $settings = self::getDefaultSettings();
        $user_settings = PModulesChatUserSettings::model()->findAll("user_id = :userId", array(':userId'=>$userId));

        if(empty($user_settings)){
            return $settings;
        }else{
            foreach($settings as &$defsett){
                foreach($user_settings as $usett){
                    if($usett->setting_id == $defsett['id'])
                        $defsett['value'] = $usett->value;
                }
            }
        }
        return $settings;
    }
    public static function updateSetting($id, $attributes, $user_id)
    {
        $model = null ;
        if($model = PModulesChatUserSettings::model()
            ->find("setting_id = :id AND user_id= :userId", array(":id"=>$id,":userId"=>$user_id))){
            $model->value = $attributes['value'];
        }else{
            $model = new PModulesChatUserSettings();
            $model->setting_id = $attributes['id'];
            $model->user_id = $user_id;
            $model->value = $attributes['value'];
        }

        return $model->save();
    }
} 