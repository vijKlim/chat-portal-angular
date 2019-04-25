<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 06.10.2015
 * Time: 11:02
 */

class PModulesClientMessagesAttachment extends ModulesClientMessagesAttachment{

    const TYPE_IMAGE = 'image';
    const TYPE_MEDIA = 'media';

    const TYPE_MSG_CHAT = 'chat';
    const TYPE_MSG_COMMENT = 'comment';
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesClientMessagesAttachment the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'modules_client_messages_attachment';
    }

    public static  function addAttach($msgid,$attach,$type_msg=self::TYPE_MSG_CHAT)
    {
        $ordernum = 0;
        if($attach){
            if(count($attach) > 8){
                $attach = array_slice($attach, 0, 7);
            }
            foreach($attach as $val){
                $model = new PModulesClientMessagesAttachment();
                $model->msg_id = $msgid;
                $model->type_msg = $type_msg;
                $model->type = $val['type'];
                if($val['type'] == self::TYPE_MEDIA) {
                    $model->url = $val['url'];
                    $model->title = filter_var($val['title'], FILTER_SANITIZE_STRING) ? : '';
                    $model->description = filter_var($val['description'], FILTER_SANITIZE_STRING) ? : '';
                    $model->site = $val['site'];
                    if(isset($val['category'])){
                        $json = json_encode(array('category'=>$val['category']));
                        $model->additional_info = $json;
                    }
                }

                $model->image = $val['image'];
                $model->dateadded = time();
                $model->ordernum = ++$ordernum;


                if(!$model->save()){
                    return false;
                }
//                file_put_contents('/tmp/chat.txt',print_r($attach,true));
            }
        }
        return true;
    }

    public static function getAttach($msg_id,$type_msg=self::TYPE_MSG_CHAT)
    {
        $sql = <<<SQL
SELECT id,title,url,site,description,image,type,additional_info
 FROM modules_client_messages_attachment
 WHERE msg_id = :msg_id AND type_msg = :typeMsg
 ORDER BY ordernum
SQL;

        $result = Yii::app()->db->createCommand($sql)
            ->bindParam(':msg_id',$msg_id,PDO::PARAM_INT)
            ->bindParam(':typeMsg', $type_msg, PDO::PARAM_STR)->queryAll();

        foreach($result as $k=>&$v){
            if($v['additional_info']){
                $v['additional_info'] = json_decode($v['additional_info']);
            }
        }
        return $result;
    }

    /*
     * крепим атач с базы если он есть или атач с переданного пареметра
     */
    public static function attachMediaContentForComments(&$comment, $attach = [])
    {
        $attach = $attach ? : PModulesClientMessagesAttachment::getAttach($comment['id'],PModulesClientMessagesAttachment::TYPE_MSG_COMMENT);
        if(!empty($attach))
        {
            foreach($attach as $att)
            {
                if($att['type'] == PModulesClientMessagesAttachment::TYPE_MEDIA)
                    $comment['media_attach'] = $att;
                else if($att['type'] == PModulesClientMessagesAttachment::TYPE_IMAGE)
                    $comment['images_attach'][] = $att;
            }
        }else{
            $comment['media_attach'] = [];
            $comment['images_attach'] = [];
        }

    }
} 