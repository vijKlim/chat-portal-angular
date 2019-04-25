<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 10.12.2015
 * Time: 10:59
 */

class AloneContactMsgs extends ContactMsgs {

    public function __construct()
    {
        $this->type = self::TYPE_ALONE;
        $this->group_id = 0;
    }

    function processMsg($userId,$receiver,$msg,$attach,$issupport,$isinternal)
    {
        $contact = AloneContact::getInstance($userId,$receiver->id);
        if(!$contact->isSimple()){
            $result = self::saveMsg($userId,$contact->contactId,ContactMsgs::TYPE_ALONE,$msg,$isinternal);
            if(!$result['error']){

                if(PModulesClientMessagesAttachment::addAttach($result['entity']['id'],$attach)){
                    $result['entity']['attach']['data'] = $attach;
                }else{
                    $result['entity']['attach']['data'] = [];
                }

                if(!$receiver->isOnline)
                    $this->informNewMsg($userId,$receiver->id);
            }else{
                $result['entity']['attach']['data'] = [];
            }

            return $result;
        }
    }

    private function informNewMsg($senderId,$receiverId)
    {
        $replaceTable = [];
        $sender = ManagerContacts::getInfoUser($senderId);
        $receiver = ManagerContacts::getInfoUser($receiverId);

        if ( $receiver ){
            $replaceTable = array_merge($replaceTable, [
                '{$receiver_name}' => $receiver->name,
                '{$receiver_logo}' => preg_match('`http://`',$receiver->logo) ? $receiver->logo : 'http://tatet.ua'.$receiver->logo,
                '{$sender_name}'=>$sender->name,
                '{$sender_logo}'=>$sender->logo
            ]);
            EmailClient::sendByUser($receiverId,PModulesMailTpl::ALIAS_INFO_CHAT_MSG,Controller::$portalId,Controller::$portalLocale,$replaceTable);

        }
    }

    public function getMsgsThread($userId,$contactId,$period=self::PERIOD_TODAY,$issupport=false)
    {
        $isinternal = $issupport == false ? ' AND NOT is_internal' : '';
        if($period == self::PERIOD_DEFAULT){
            $limit = "LIMIT 15";
            $sql = <<<SQL
SELECT tmp.id, tmp.thread_hash, tmp.sender_id, tmp.receiver_id, tmp.msg, tmp.is_answered,tmp.is_viewed, tmp.date_created, tmp.group_id, tmp.is_internal, tmp.is_copy
FROM
(
SELECT id, thread_hash, sender_id, receiver_id, msg, is_answered,is_viewed, date_created, group_id,is_internal,is_copy
FROM modules_client_messages
WHERE NOT group_id AND thread_hash = :thash AND
((status_sender = 'active' AND sender_id = :ownId) OR
(status_receiver = 'active' AND receiver_id = :ownId) )
$isinternal
ORDER BY id DESC $limit
) as tmp
ORDER BY tmp.id ASC
SQL;
        }else{
            $periodQuery = self::getPeriodQuery($period,'tmp.date_created');
            $sql = <<<SQL
SELECT tmp.id, tmp.thread_hash, tmp.sender_id, tmp.receiver_id, tmp.msg, tmp.is_answered,tmp.is_viewed, tmp.date_created, tmp.group_id, tmp.is_internal, tmp.is_copy
FROM modules_client_messages as tmp
WHERE NOT tmp.group_id AND tmp.thread_hash = :thash AND
((tmp.status_sender = 'active' AND tmp.sender_id = :ownId) OR
(tmp.status_receiver = 'active' AND tmp.receiver_id = :ownId) )
$isinternal
$periodQuery
ORDER BY id
SQL;
        }


        $result = Yii::app()->db->createCommand($sql)
            ->bindParam(':thash',$contactId,PDO::PARAM_INT)
            ->bindParam(':ownId',$userId,PDO::PARAM_INT)->queryAll();

        $coder = new MessagesCoder();
        foreach($result as &$val){
            $val['type'] = Contact::TYPE_ALONE;
            $val['msg'] = nl2br(html_entity_decode($coder->decodeUserData($val['msg'],ENT_QUOTES)));
            $val['own_msg'] = $val['sender_id'] == $userId ? 1 : 0;
            $val['attach']['data'] = PModulesClientMessagesAttachment::getAttach($val['id']);

        }

        return $result;
    }

    public function createNullMsgsThread($userId,$contactId)
    {
        $contact = PModulesClientRelations::model()->find('hash=:hash',array(':hash'=>$contactId));
        if(!empty($contact)){
            $receiver_id = $contact['client_offer_id'] == $userId ? $contact['client_invited_id'] : $contact['client_offer_id'];

            return [['id'=>0,'contactId'=>$contactId,'thread_hash'=>$contact['hash'],'type'=>Contact::TYPE_ALONE,'sender_id'=>$userId,
                'receiver_id'=>$receiver_id, 'msg'=>'','is_answered'=>0,
                'is_viewed'=>1, 'date_created'=>0, 'group_id'=>0]];
        }else{
            return null;
        }

    }


    public  function setViewed($userId,$contactId)
    {
        return (int)Yii::app()->db->createCommand()
            ->update('modules_client_messages', array('is_viewed'=>1),
                'thread_hash = :hash  AND receiver_id = :id AND NOT is_viewed',
                array(':hash'=>$contactId,':id'=>$userId));
    }

    public static function getAllThread($userId,$issupport)
    {
        $isinternal = $issupport == false ? ' AND NOT is_internal' : '';
        //sender_id -  кто последний в диалоге отправил сообщение
        $sql = <<<SQL
SELECT thread_hash,sender_id,receiver_id,date_created,msg
FROM modules_client_messages m
INNER JOIN
(
SELECT MAX(id) iid FROM modules_client_messages
WHERE NOT group_id AND (sender_id = :id AND status_sender = 'active')
OR (receiver_id = :id AND status_receiver = 'active')
$isinternal
GROUP BY thread_hash
) m2 ON m2.iid = m.id
ORDER BY id DESC
SQL;

        $threads =  Yii::app()->db->createCommand($sql)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'AloneContactMsgs')
            ->bindParam(':id',$userId, PDO::PARAM_INT)->queryAll();
        $counts = AloneContactMsgs::countContactsMsgs($userId);
        $coder = new MessagesCoder();
        foreach($threads as &$thread)
        {
            $thread->contactId = $thread->thread_hash;

            self::whoLastSender($thread, $userId);
            $thread->msg = nl2br($coder->decodeUserData($thread->msg));
            if(!empty($counts))
                $thread->count_msgs = (int)$counts[$thread->thread_hash];
            else
                $thread->count_msgs = 0;
            self::addInfoMembers($thread,self::TYPE_ALONE);
        }
        return $threads;
    }

    public static function amountMsgs($userId, $which='new')
    {
        $where = '';
        switch($which){
            case 'new':
                $where .= ' AND NOT is_viewed';
                break;
        }
        $sql = <<<SQL
SELECT sender_id as contactId,thread_hash as id,count(id) as count_msgs,msg
FROM modules_client_messages
WHERE  receiver_id = :id AND status_receiver = 'active' $where
GROUP BY thread_hash
SQL;
        return Yii::app()->db->createCommand($sql)->bindParam(':id',$userId, PDO::PARAM_INT)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'AloneContactMsgs')->queryAll();
    }

    public static function getNewMsgs($userId)
    {
        $sql = <<<SQL
SELECT mcl.id, mcl.id_external,mcl.type,mcl.name,mcl.logo,ms.thread_hash as contactId, 'alone' as contact_type,ms.thread_hash as msg_id,1 as count_msgs,ms.msg
FROM modules_client_messages as ms
LEFT JOIN modules_client mcl ON mcl.id = ms.sender_id
WHERE  receiver_id = :id AND status_receiver = 'active' AND NOT is_answered AND NOT is_internal
SQL;
        return Yii::app()->db->createCommand($sql)->bindParam(':id',$userId, PDO::PARAM_INT)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')->queryAll();
    }

    public static function countContactsMsgs($userId, $which='new')
    {
        $results = self::amountMsgs($userId,$which);
        $counts = array();
        foreach($results as $res)
        {
            $counts[$res->id] = $res->count_msgs;
        }

        return $counts;
    }

    static function whoLastSender(&$thread, $userId)
    {
        if($thread->sender_id == $userId)
            $thread->me_sender = 1;
        else
            $thread->me_sender = 0;
    }


} 