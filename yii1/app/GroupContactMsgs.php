<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 10.12.2015
 * Time: 10:59
 */

class GroupContactMsgs extends ContactMsgs{

    public function __construct()
    {
        $this->type = ContactMsgs::TYPE_GROUP;
        $this->thread_hash = 0;
        $this->me_sender = 1;
    }

    function processMsg($userId,$receiver,$msg,$attach,$issupport,$isinternal)
    {
        $contactId = $receiver->id;

        if(!$this->isMemberGroup($userId,$contactId))
            return array('entity'=>null,'error'=>1,'info_msg'=>'Вы не являетесь участником группы и не можете написать им сообщение');

        $results = [];
        $attachSaveError = true;
        //вторым параметром указываем ноль потому что для группового сообшения не нужен id юзера который получит это сообщение
        $result = self::saveMsg($userId,$contactId,ContactMsgs::TYPE_GROUP,$msg,$isinternal);
        if(!$result['error']){

            if(PModulesClientMessagesAttachment::addAttach($result['entity']['id'],$attach)){
                $result['entity']['attach']['data'] = $attach;
            }else{
                $result['entity']['attach']['data'] = [];
            }

        }else{
            $result['entity']['attach']['data'] = [];
        }
        return $result;
    }

    protected function isMemberGroup($memberId, $groupId)
    {
        return Yii::app()->db->createCommand()
            ->select('id')
            ->from('modules_chat_group_contain')
            ->where('group_id = :groupId AND member_id = :memberId',
                array(':groupId'=>$groupId,':memberId'=>$memberId))
            ->queryScalar() ? true : false;
    }

    public function getMsgsThread($userId,$contactId,$period=self::PERIOD_DEFAULT,$issupport=false)
    {
        $isinternal = $issupport == false ? ' AND NOT is_internal' : '';
        $limit = 15;
        if($period == self::PERIOD_DEFAULT)
        {
            $sql = <<<SQL
SELECT v.id, v.thread_hash, v.sender_id, v.receiver_id, v.msg, v.is_answered,v.is_viewed, v.date_created, v.group_id, v.is_internal, v.is_copy
FROM (SELECT * FROM `modules_client_messages` WHERE group_id = :groupId $isinternal  ORDER BY id DESC LIMIT $limit) as v
WHERE v.group_id = :groupId
ORDER BY v.date_created
SQL;
        }
        else
        {
            $periodQuery = self::getPeriodQuery($period,'date_created');
            $sql = <<<SQL
SELECT id, thread_hash, sender_id, receiver_id, msg, is_answered,is_viewed, date_created, group_id, is_internal, is_copy
FROM modules_client_messages
WHERE group_id = :groupId
$isinternal
$periodQuery
ORDER BY date_created
SQL;
        }


        $result = Yii::app()->db->createCommand($sql)
            ->bindParam(':groupId',$contactId,PDO::PARAM_INT)
            ->queryAll();
        $coder = new MessagesCoder();
        foreach($result as &$val){
            $val['sender'] = ManagerContacts::getInfoUser($val['sender_id']);
            $val['type'] = Contact::TYPE_GROUP;
            $val['msg'] = nl2br($coder->decodeUserData($val['msg']));
            $val['own_msg'] = $val['sender_id'] == $userId ? 1 : 0;
            $val['attach']['data'] = PModulesClientMessagesAttachment::getAttach($val['id']);
        }

        return $result;
    }
    public function createNullMsgsThread($userId,$contactId)
    {
        $anyMemberId = Yii::app()->db->createCommand()
            ->select('member_id')
            ->from('modules_chat_group_contain')
            ->where('group_id = :groupId AND member_id != :memberId',
                array(':groupId'=>$contactId,':memberId'=>$userId))
            ->queryScalar();
        $hash = createHash($userId,$anyMemberId);
        return [['id'=>0,'contactId'=>$contactId,'thread_hash'=>$hash, 'type'=>Contact::TYPE_GROUP,'sender_id'=>$userId,
            'receiver_id'=>$anyMemberId, 'msg'=>'','is_answered'=>0,
            'is_viewed'=>1, 'date_created'=>0, 'group_id'=>$contactId]];
    }

    public  function setViewed($userId,$contactId)
    {
        $count = self::countContactMsgs($userId, $contactId);
        Yii::app()->db->createCommand()
            ->update('modules_chat_group_contain', array('last_viewed'=>time()),
                'group_id = :groupId AND member_id = :memberId',
                array(':groupId'=>$contactId,':memberId'=>$userId));
        return (int)$count;
    }

    public static function getAllThread($userId,$issupport)
    {
        //sender_id -  кто последний в группе отправил сообщение,
        // receiver_id - получателем является сама группа, то есть указан id группы
//        $sql = <<<SQL
//SELECT group_id , sender_id, group_id as receiver_id, date_created, msg
//FROM  modules_client_messages
//WHERE id IN
//(
//SELECT MAX(msgs.id)
//FROM  modules_chat_group_contain contain
//INNER JOIN modules_client_messages msgs ON msgs.group_id=contain.group_id
//WHERE contain.member_id = :id
//GROUP BY contain.group_id
//)
//ORDER BY id DESC
//SQL;
        $isinternal = $issupport == false ? ' AND NOT is_internal' : '';
        $sql = <<<SQL
SELECT group_id, sender_id, group_id as receiver_id, date_created, msg
FROM  modules_client_messages m
INNER JOIN  (
	SELECT MAX(msgs.id) id
	FROM  modules_chat_group_contain contain
	INNER JOIN modules_client_messages msgs ON msgs.group_id=contain.group_id
	WHERE contain.member_id = :id
	$isinternal
	GROUP BY contain.group_id
) m2 ON m2.id = m.id
ORDER BY m.id DESC
SQL;



        $threads= Yii::app()->db->createCommand($sql)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'GroupContactMsgs')
            ->bindParam(':id',$userId, PDO::PARAM_INT)
            ->queryAll();

        $counts = self::countContactsMsgs($userId);
        $coder = new MessagesCoder();
        foreach($threads as &$thread)
        {
            $thread->contactId = $thread->group_id;
            $thread->msg = nl2br($coder->decodeUserData($thread->msg));
            $thread->count_msgs = (int)$counts[$thread->group_id];
            self::addInfoMembers($thread,self::TYPE_GROUP);
        }
        return $threads;
    }

    public static function amountMsgs($userId, $which='new')
    {
        $sql = <<<SQL
SELECT msgs.group_id as contactId,msgs.group_id as id, count(msgs.id) as count_msgs FROM  modules_chat_group_contain contain
LEFT JOIN modules_client_messages msgs ON msgs.group_id=contain.group_id AND contain.status NOT IN ('delete','cancel','block')
WHERE contain.member_id = :id AND msgs.sender_id <> :id AND msgs.date_created > contain.last_viewed
GROUP BY msgs.group_id
SQL;

        return Yii::app()->db->createCommand($sql)->bindParam(':id',$userId, PDO::PARAM_INT)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'GroupContactMsgs')->queryAll();
    }

    public static function getNewMsgs($userId)
    {
        $sql = <<<SQL
SELECT gr.name,gr.logo, 'group' as contact_type, msgs.group_id as contactId,msgs.group_id as id, 1 as count_msgs,msg
FROM  modules_chat_group_contain contain
LEFT JOIN modules_chat_group as gr ON gr.id = contain.group_id
LEFT JOIN modules_client_messages msgs ON msgs.group_id=contain.group_id AND contain.status NOT IN ('delete','cancel','block')
WHERE contain.member_id = :id AND NOT is_internal AND msgs.sender_id <> :id AND msgs.date_created > contain.last_viewed
SQL;
        return Yii::app()->db->createCommand($sql)->bindParam(':id',$userId, PDO::PARAM_INT)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'GroupContactMsgs')->queryAll();
    }

    /*
     * count new msgs for all contacts
     */
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

    /*
     * count new msgs for one contact
     */
    public static function countContactMsgs($userId,$contactId)
    {
        $sql = <<<SQL
SELECT count(msgs.id) as count_msgs FROM  modules_chat_group_contain contain
LEFT JOIN modules_client_messages msgs ON msgs.group_id=contain.group_id AND contain.status NOT IN ('delete','cancel','block')
WHERE msgs.group_id = :groupId AND contain.member_id = :id AND msgs.date_created > contain.last_viewed
GROUP BY msgs.group_id
SQL;

        return Yii::app()->db->createCommand($sql)
            ->bindParam(':id',$userId, PDO::PARAM_INT)
            ->bindParam(':groupId',$contactId, PDO::PARAM_INT)
            ->queryScalar();
    }

} 