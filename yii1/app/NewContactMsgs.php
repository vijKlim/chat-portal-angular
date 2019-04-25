<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 15.02.2016
 * Time: 16:16
 */

class NewContactMsgs extends ContactMsgs{

    const WARNING_OVERFLOW_LIMIT_SEND_FREE_MSG = 'Превышен лимит сообщений. Вы не можете отправить более 3 сообщений в день не своим контактам.';

    function processMsg($userId,$receiver,$msg,$attach,$issupport,$isinternal)
    {

        //если отправитель не саппорт, контакта не существует и превышен лимит количества сообщений рассылаемых вернуть предупреждение
        if(!$issupport  && $this->isExceedingLimitFreeMsgs($receiver->id)){
            $results[] = array('entity'=>null,'error'=>1,'info_msg'=>self::WARNING_OVERFLOW_LIMIT_SEND_FREE_MSG);
        }

        $receiverId = 0;
        list($m1,$m2) = decodeHash($receiver->id);
        $receiverId = (int)$userId == (int)$m1 ? $m2 : $m1;
        $contact = AloneContact::getInstance($userId,$receiver->id);
            //если контакт существеут в базе данных
            if($contact->isSimple()){
                $contact->create($userId,$receiverId,Contact::STATUS_ACCEPTED,true);
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

    private function isExceedingLimitFreeMsgs($senderId)
    {
        $ip = ip2long(Request::getClientIp());
        $sql =<<<SQL
SELECT count(*) FROM `modules_client_messages` msg
LEFT JOIN `modules_client_relations` rel
ON rel.hash = msg.thread_hash
WHERE sender_id=:senderId AND ip = :ip AND NOT group_id AND rel.hash IS NULL AND NOT rel.is_approved  AND date_created > UNIX_TIMESTAMP(NOW() - INTERVAL '1' DAY);
SQL;
        if(Yii::app()->db->createCommand($sql)
                ->bindParam(':senderId',$senderId,PDO::PARAM_INT)
                ->bindParam(':ip',$ip,PDO::PARAM_INT)
                ->queryScalar() >= ContactMsgs::LIMIT_FREE_MSG)
            return true;
        else
            return false;
    }
} 