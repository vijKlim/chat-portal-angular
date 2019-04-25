<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 12.08.2015
 * Time: 12:02
 */
require_once( __DIR__ . '/../helpers/EmailClient.php');
require_once(dirname(__FILE__).'/../helpers/Coder.php');
class PModulesClientMessages extends ModulesClientMessages{

    const STATUS_DELETE = 'delete';
    const STATUS_SPAM   = 'spam';
    const STATUS_ACTIVE = 'active';
    const LIMIT_FREE_MSG = 3;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesClientMessages the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public static function getCountNotViewedMsg($id)
    {
        $sql =
            <<<SQL
            SELECT count(id) as c_notviewed
FROM modules_client_messages
WHERE NOT is_answered AND receiver_id = :id  AND NOT is_viewed
SQL;
        return Yii::app()->db->createCommand($sql)->bindParam(':id',$id,PDO::PARAM_INT)->queryScalar();
    }

    public static function getClientThreads($id)
    {
        $sql = <<<SQL
SELECT thread_hash,sender_id,receiver_id,date_created,msg
FROM modules_client_messages
WHERE date_created IN (SELECT MAX(m.date_created) FROM modules_client_messages m WHERE (m.sender_id = :id AND m.status_sender = 'active') OR (m.receiver_id = :id AND m.status_receiver = 'active')
GROUP BY m.thread_hash)
GROUP BY date_created
ORDER BY date_created DESC
SQL;

        $threads= Yii::app()->db->createCommand($sql)->bindParam(':id',$id, PDO::PARAM_INT)->queryAll();


        $sql2 = <<<SQL
SELECT thread_hash,count(is_viewed) as count_notviewed
FROM modules_client_messages
WHERE NOT is_viewed AND receiver_id = :id AND status_receiver = 'active'
GROUP BY thread_hash
SQL;

        $countNotAnswered = Yii::app()->db->createCommand($sql2)->bindParam(':id',$id, PDO::PARAM_INT)->queryAll();
        $coder = new MessagesCoder();
        foreach($threads as &$thr)
        {
            $thr['msg'] = $coder->decodeUserData($thr['msg']);
            if($thr['sender_id'] == $id)
                $thr['me_sender'] = 1;
            else
                $thr['me_sender'] = 0;
            if(!empty($countNotAnswered))
            {
                foreach($countNotAnswered as $count){
                    if($thr['thread_hash'] == $count['thread_hash']){
                        $thr['notviewed'] = $count['count_notviewed'];
                    }
                }
            }else{

                $thr['notviewed'] = 0;
            }

            self::addClientInfo($thr);
        }
        return $threads;
    }


    public static function getThreadMsgs($ownId,$thread_hash)
    {
        $sql = <<<SQL
SELECT id, thread_hash, sender_id, receiver_id, msg, is_answered,is_viewed, date_created
FROM modules_client_messages
WHERE thread_hash = :thash AND
((status_sender = 'active' AND sender_id = :ownId) OR
(status_receiver = 'active' AND receiver_id = :ownId) )
ORDER BY date_created
SQL;
        $result = Yii::app()->db->createCommand($sql)
            ->bindParam(':thash',$thread_hash,PDO::PARAM_INT)
            ->bindParam(':ownId',$ownId,PDO::PARAM_INT)->queryAll();
        $coder = new MessagesCoder();
        foreach($result as &$val)
            $val['msg'] = $coder->decodeUserData($val['msg']);

        if(!empty($result)){
            return array('msgs'=>$result,'countViewed'=>self::setViewed($thread_hash,$ownId));
        }else{
            return array('msgs'=>$result,'countViewed'=>0);
        }


    }

    public static function updateStatusMsgs($ownId,$mids, $status)
    {
        foreach($mids as $id)
        {
            $model = self::model()->findByPk((int)$id);
            if($model->sender_id == $ownId)
                $model->status_sender = $status;
            else
                $model->status_receiver   = $status;
            if(!$model->save())
                return false;
        }
        return true;
    }

    public static
    function addClientInfo(&$thread)
    {
        $thread['sender_info'] = (new PModulesClient())->getInfo($thread['sender_id'],true);
        $thread['receiver_info'] = (new PModulesClient())->getInfo($thread['receiver_id'],true);
    }

    public static function AddMsg($senderId,$receiverId,$text,$groupId=0,$isOnline=false)
    {
        $hash = createHash($senderId,$receiverId);
        $adminReceiver = PModulesClient::model()->findByPk($receiverId,'type="team"');
        if(!$adminReceiver){
            if(!Yii::app()->client->isTeam() && self::isExceedingLimitFreeMsgs($senderId,$receiverId)){
                return array('entity'=>null,'error'=>1,'info_msg'=>'Превышен лимит сообщений. Вы не можете отправить более 3 сообщений в день не своим контактам.');
            }
        }else{
            PModulesClientRelations::add($senderId,$receiverId,PModulesClientRelations::STATUS_ACCEPTED,true);
        }

        $dialog = PModulesClientMessages::model()->find('thread_hash = :hash',array(':hash'=>$hash));
        $hash = $dialog && !empty($dialog) ? $dialog->thread_hash : $hash;
        $coder = new MessagesCoder();
        $msg = new ModulesClientMessages;

        $msg->thread_hash  = $hash;
        $msg->sender_id    = $senderId;
        $msg->receiver_id  = $receiverId;
        $msg->msg          = $coder->codeUserData(preg_replace('`javascript:.*?`is', '', HTMLHelper::stripDefault(htmlentities(strip_tags($text),ENT_QUOTES))));
        $msg->date_created = time();
        $msg->ip           = ip2long(Request::getClientIp());
        $msg->status_sender       = self::STATUS_ACTIVE;
        $msg->status_receiver       = self::STATUS_ACTIVE;

        if($msg->save())
        {
            if(!$isOnline)
            {
                $replaceTable = [];
                $sender = Yii::app()->client->info;
                $receiver = PModulesClient::model()->findByPk($receiverId);

                if ( $receiver ){
                    $rentity  = new EntityClient;
                    $rentity->id_external = $receiver->id_external;
                    $rentity->type        = $receiver->type;
                    $rentity->logo        = $receiver->logo;
                    $replaceTable = array_merge($replaceTable, [
                        '{$receiver_name}' => $receiver->name,
                        '{$receiver_logo}' => preg_match('`http://`',$rentity->logo) ? $rentity->logo : 'http://tatet.ua'.$rentity->logo,
                        '{$sender_name}'=>$sender->name,
                        '{$sender_logo}'=>$sender->logo
                    ]);
                    EmailClient::sendByUser($receiverId,PModulesMailTpl::ALIAS_INFO_CHAT_MSG,Controller::$portalId,Controller::$portalLocale,$replaceTable);

                }
            }
            $result = $msg->attributes;
            $result['msg'] = html_entity_decode((new MessagesCoder())->decodeUserData($result['msg'],ENT_QUOTES));
            return array('entity'=>$result,'error'=>0,'info_msg'=>'Сообщение отправлено!');
        }
        else
            return array('entity'=>null,'error'=>1,'info_msg'=>'Произошла ошибка, попробуйте позже.');

    }

    static function isExceedingLimitFreeMsgs($senderId,$receiverId)
    {
        $hash = createHash($senderId,$receiverId);
        if(!PModulesClientRelations::model()->count('hash = :hash AND status = "accepted" ',array(':hash'=>$hash)))
        {
            $ip = ip2long(Request::getClientIp());
            $sql =<<<SQL
SELECT count(*) FROM `modules_client_messages` msg
LEFT JOIN `modules_client_relations` rel
ON rel.hash = msg.thread_hash
WHERE sender_id=:senderId AND ip = :ip AND rel.hash IS NULL AND NOT rel.is_approved  AND date_created > UNIX_TIMESTAMP(NOW() - INTERVAL '1' DAY);
SQL;
            if(Yii::app()->db->createCommand($sql)
                    ->bindParam(':senderId',$senderId,PDO::PARAM_INT)
                    ->bindParam(':ip',$ip,PDO::PARAM_INT)
                    ->queryScalar() >= self::LIMIT_FREE_MSG)
                return true;
            else
                return false;
        }
        return false;
    }

    public static function setViewed($hash,$ownid)
    {
        return Yii::app()->db->createCommand()
            ->update('modules_client_messages', array('is_viewed'=>1),
                'thread_hash = :hash  AND receiver_id = :id AND NOT is_viewed',
                array(':hash'=>$hash,':id'=>$ownid));
    }

    private static function setReplied($hash,$id)
    {
        return Yii::app()->db->createCommand()
            ->update('modules_client_messages', array('is_answered'=>1),
                'thread_hash = :hash  AND sender_id = :id AND NOT is_answered',
                array(':hash'=>$hash,':id'=>$id));
    }

    public static function getStatMessageUser($skipIds = null)
    {
        $sqlSkip = $skipIds ? ' sender_id NOT IN (' . implode(',', $skipIds) . ')' : '1=1';

        $sql = <<<SQL
			SELECT id, sender_id, date_created
			FROM modules_client_messages
			WHERE $sqlSkip
SQL;
        return Yii::app()->db->createCommand($sql)->queryAll();
    }

    public static function getList($skipIds = [1, 3, 4, 3642, 7681, 7821, 9636], $page = 0)
    {
        if ( $skipIds )
        {
            $ids = ' (' . implode(',', $skipIds) . ') ';
            $sqlSkip = "sender_id NOT IN $ids AND receiver_id NOT IN $ids";
        }
        else
            $sqlSkip = '';

		$page = (int)$page;

        $sql = <<<SQL
			SELECT m.id, sender_id, receiver_id, date_created, c.name sender, c1.name receiver, m.is_viewed
			FROM modules_client_messages m
			LEFT JOIN modules_client c ON c.id = sender_id
			LEFT JOIN modules_client c1 ON c1.id = receiver_id
			WHERE  $sqlSkip
			ORDER BY date_created DESC
			LIMIT $page, 50
SQL;
        return Yii::app()->db->createCommand($sql)->setFetchMode(PDO::FETCH_ASSOC)
            ->queryAll();
    }

} 