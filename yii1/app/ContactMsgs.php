<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 10.12.2015
 * Time: 10:58
 */

abstract class ContactMsgs {

    const TYPE_ALONE = 'alone';
    const TYPE_GROUP = 'group';
    const TYPE_SUPPORT = 'support';
    const TYPE_NEW     = 'new';
    const STATUS_DELETE = 'delete';
    const STATUS_SPAM   = 'spam';
    const STATUS_ACTIVE = 'active';
    const LIMIT_FREE_MSG = 3;

    const PERIOD_DEFAULT   =  'default';
    const PERIOD_TODAY     = 'today';
    const PERIOD_YESTERDAY = 'yesterday';
    const PERIOD_7_DAYS    = 'seven-days';
    const PERIOD_30_DAYS   = 'thirty-days';
    const PERIOD_3_MONTHS  = 'three-months';
    const PERIOD_6_MONTHS  = 'six-months';
    const PERIOD_1_YEAR    = 'one-year';

    public $type;

    public static function getPeriodQuery($period,$ColumnDateName)
    {
        switch($period){
            case self::PERIOD_TODAY:
                return "AND  $ColumnDateName >= UNIX_TIMESTAMP(CURDATE())";
            case self::PERIOD_YESTERDAY:
                return "AND $ColumnDateName BETWEEN UNIX_TIMESTAMP(CURDATE()-1) AND UNIX_TIMESTAMP(CURDATE())";
            case self::PERIOD_7_DAYS:
                return "AND UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 7 DAY)) <= $ColumnDateName";
            case self::PERIOD_30_DAYS:
                return "AND UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 30 DAY)) <= $ColumnDateName";
            case self::PERIOD_3_MONTHS:
                return "AND UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 3 MONTH)) <= $ColumnDateName";
            case self::PERIOD_6_MONTHS:
                return "AND UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 6 MONTH)) <= $ColumnDateName";
            case self::PERIOD_1_YEAR:
                return "AND UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 1 YEAR)) <= $ColumnDateName";

        }
    }

    public static
    function addInfoMembers(&$thread, $type)
    {
        $thread->sender_info = (new PModulesClient())->getInfo($thread->sender_id,true);
        if($type == self::TYPE_GROUP)
            $thread->receiver_info = PModulesChatGroup::model()->findByPk($thread->receiver_id)->attributes;
        elseif($type == self::TYPE_ALONE)
            $thread->receiver_info = (new PModulesClient())->getInfo($thread->receiver_id,true);

    }

    public  static function saveMsg($senderId,$contactId, $typeContact,$text,$isinternal)
    {
        $receiverId = 0;
        if($typeContact != ContactMsgs::TYPE_GROUP){
            list($m1,$m2) = decodeHash($contactId);
            $receiverId = (int)$senderId == (int)$m1 ? $m2 : $m1;
        }

        $coder = new MessagesCoder();
        $msg = new ModulesClientMessages;

        $msg->thread_hash     = $contactId;
        $msg->sender_id       = $senderId;
        $msg->receiver_id     = $receiverId;
        $msg->msg             = $coder->codeUserData(preg_replace('`javascript:.*?`is', '', HTMLHelper::stripDefault(htmlentities(strip_tags($text),ENT_QUOTES))));
        $msg->date_created    = time();
        $msg->ip              = ip2long(Request::getClientIp());
        $msg->status_sender   = ContactMsgs::STATUS_ACTIVE;
        $msg->status_receiver = ContactMsgs::STATUS_ACTIVE;
        $msg->group_id        = $typeContact == ContactMsgs::TYPE_GROUP ? $contactId : 0;
        $msg->is_internal     = $isinternal;

        if($msg->save())
        {
            $entity = $msg->attributes;
            $entity['msg'] = nl2br(html_entity_decode((new MessagesCoder())->decodeUserData($entity['msg'],ENT_QUOTES)));
            $entity['own_msg'] = $entity['sender_id'] == $senderId ? 1 : 0;
            $entity['contactId'] = $entity['group_id'] ? : $entity['thread_hash'];
            $entity['type'] = $typeContact;
            return array('entity'=>$entity,'error'=>0, 'info_msg'=>ManagerContacts::SUCCESS_SEND_MSG);
        }
        else
            return array('entity'=>null,'error'=>1,'info_msg'=>ManagerContacts::ERROR_GENERAL);
    }

    public static function updateStatusMsgs($ownId,$mids, $status)
    {
        if($status == ContactMsgs::STATUS_DELETE ||
            $status == ContactMsgs::STATUS_SPAM){

            foreach($mids as $id)
            {
                $model = PModulesClientMessages::model()->findByPk((int)$id);
                if($model->sender_id == $ownId)
                    $model->status_sender = $status;
                else
                    $model->status_receiver   = $status;
                if(!$model->save())
                    return false;
            }
            return true;
        }else{
            return false;
        }

    }


} 