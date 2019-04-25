<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 23.12.2015
 * Time: 15:26
 */

class SupportContactMsgs extends ContactMsgs{

    public function __construct()
    {
        $this->type = self::TYPE_SUPPORT;
        $this->group_id = 0;
    }

    function processMsg($userId,$receiver,$msg,$attach,$issupport,$isinternal)
    {
        $adminReceiver = PModulesClient::model()->cache(86400)->findByPk($receiver->id,'type IN ("team","shops_support")');
        $adminReceiver = $adminReceiver->attributes;
        $hash = createHash($userId,$receiver->id);
        $contact = AloneContact::getInstance($userId,$hash);
        if(!empty($adminReceiver)){
            //если контакт существеут в базе данных
            if($contact->isSimple()){
                $contact->create($userId,$receiver->id,Contact::STATUS_ACCEPTED,true);
            }
            $result = self::saveMsg($userId,$contact->contactId,ContactMsgs::TYPE_ALONE,$msg,$isinternal);
            if(!$result['errorMsg']){

                if(PModulesClientMessagesAttachment::addAttach($result['entity']['id'],$attach)){
                    $result['attachError'] = 0;
                    $result['entity']['attach']['data'] = $attach;
                }else{
                    $result['attachError'] = 1;
                    $result['entity']['attach']['data'] = [];
                }

                //if(!$receiver->isOnline)
                //$this->informNewMsg($userId,$receiver->id);
            }else{
                $result['attachError'] = 1;
            }

            return $result;
        }
    }

    public function createNullMsgsThread($userId,$contactId)
    {
        return [['id'=>0,'contactId'=>$contactId,'thread_hash'=>0,'type'=>Contact::TYPE_SUPPORT,'sender_id'=>$userId,
            'receiver_id'=>$contactId, 'msg'=>'','is_answered'=>0,
            'is_viewed'=>1, 'date_created'=>0, 'group_id'=>0]];


    }
} 