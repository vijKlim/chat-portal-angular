<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 07.12.2015
 * Time: 10:37
 */

require_once(dirname(__FILE__). '/../../../helpers/EmailClient.php');
require_once(dirname(__FILE__).'/../../../helpers/Coder.php');
abstract class ManagerContacts {

    const TYPE_ALONE   = 'alone';
    const TYPE_GROUP   = 'group';
    const TYPE_SUPPORT = 'support';
    const TYPE_NEW     = 'new';

    const ERROR_ADD_OWN_CONTACT  = 'Ошибка, вы пытаетесь добавить свой контакт.';
    const ERROR_GENERAL          = 'Ошибка, попробуйте поже';
    const ERROR_MORFY_CONTACT    = 'Ошибка, группа создана, но диалог не передан. Обратитесь к администратору.';
    const WARNING_EXISTS_CONTACT = 'Контакт уже добавлен';
    const WARNING_ONLY_ADMIN_ADD_GROUP = 'Только администратор группы может добавлять в группу.Свяжитесь с администратором группы';
    const WARNING_OVERFLOW_LIMIT_SEND_FREE_MSG = 'Превышен лимит сообщений. Вы не можете отправить более 3 сообщений в день не своим контактам.';
    const SUCCESS_ADD_CONTACT    = 'Контакт добавлен';
    const SUCCESS_CREATE_GROUP   = 'Группа успешно создана';
    const SUCCESS_SEND_MSG       = 'Сообщение отправлено!';
    const FLAG_ERROR_TRUE = 1;
    const FLAG_ERROR_FALSE = 0;
    const NOT_USED = -1;

    protected  $accountId;


    public static function getInstance($accountId,$type)
    {
        switch($type)
        {
            case ManagerContacts::TYPE_ALONE:
                return new ManagerAloneContacts($accountId);
            case ManagerContacts::TYPE_SUPPORT:
                return new ManagerSupportContacts($accountId);
            case ManagerContacts::TYPE_GROUP:
                return new ManagerGroupContacts($accountId);
            case ManagerContacts::TYPE_NEW:
                return new ManagerAloneContacts($accountId);
        }
    }


    abstract public function setStatusContact($contactId, $status);

    static function getInfoUser($userId)
    {
        return (new PModulesClient())->getInfo($userId,true);
    }


    function formatContact($contactId,$memberId, $status, $typeContact)
    {
        $userInfo = self::getInfoUser($memberId);
        return array(
            'id'         =>$contactId,
            'inOnline'   => false,
            'count_msgs' => 0,
            'type'       => $typeContact,
            'status'     => $status,
            'name'          => $userInfo->name,
            'id_external'=> $userInfo->id_external,
            'is_default_logo'=> $userInfo->is_default_logo,
            'logo'           => $userInfo->logo
        );

    }

    public static function getCountContacts($userId,$which=Contact::CONTACTS_WHICH_ALL)
    {
        $alones = FormatAloneContact::getCountAll($userId, $which);
        //pr($alones);
        $groups = FormatGroupContact::getCountAll($userId, $which);
        //pr($groups);die();
        return (int)$alones+(int)$groups;
    }

    public static function fetchContacts($accountId,$which=Contact::CONTACTS_WHICH_ALL)
    {
        $supports = array();
        $alones = FormatAloneContact::getAll($accountId, $which);

        $groups = FormatGroupContact::getAll($accountId, $which);
        if($which == Contact::CONTACTS_WHICH_ALL)
            $supports = SupportContact::getAll($accountId,$which);

        foreach($alones as &$contact)
        {
            self::cleanDefaultSupports($contact->members[0], $supports);
        }
        $contacts = array_merge($alones,$groups);
        $contacts = array_merge($contacts,$supports);
        return $contacts;
    }

    public static function fetchThreads($userId,$issupport)
    {
        $alones = AloneContactMsgs::getAllThread($userId,$issupport);
        $groups = GroupContactMsgs::getAllThread($userId,$issupport);
        $threads = array_merge($alones,$groups);
        return $threads;
    }

    public static function countMsgs($userId, $type='new')
    {
        $all = [];
        $counts = 0;
        if($type == 'new'){
            $alones = AloneContactMsgs::amountMsgs($userId,$type);
            $groups = GroupContactMsgs::amountMsgs($userId,$type);
            $all = array_merge($alones,$groups);
            //TODO remove
//            foreach($all as $item){
//                $counts += $item['count_msgs'];
//            }
        }
        //return $counts;
        return $all;
    }

    public static function getNewMsgs($userId){
        $all = [];
        //для диалогов выбираем все не отвеченные сообщения(is_answered), а для групп все не просмотренные
        $alones = AloneContactMsgs::getNewMsgs($userId);
        $groups = GroupContactMsgs::getNewMsgs($userId);
        $coder = new MessagesCoder();
        $all = array_merge($alones,$groups);
        foreach($all as $elem){
            $elem->msg = $coder->decodeUserData($elem->msg);
        }
        return $all;
    }

    static function getHandlerContactMsg($type)
    {
        $handler = null;
        if($type == self::TYPE_ALONE){
            $handler = new AloneContactMsgs();
        }elseif($type == self::TYPE_GROUP){
            $handler = new GroupContactMsgs();
        }elseif($type== self::TYPE_SUPPORT){
            $handler = new SupportContactMsgs();
        }elseif($type == self::TYPE_NEW){
            $handler = new NewContactMsgs();
        }
        return $handler;
    }

    public static function processMsg($userId,$accountId,$receivers,$msg,$attach=array(),$issupport,$isinternal)
    {
        $result = [];
        foreach($receivers as $receiver)
        {
            $handler = self::getHandlerContactMsg($receiver->type);

            $tmp = $handler->processMsg($accountId,$receiver,$msg,$attach,$issupport,$isinternal);
            if(!$tmp['error']){
                $tmp['entity']['support'] = $issupport ?
                    self::setSupportMsg($accountId,$userId,$tmp['entity']['id']) : [];
                self::setStatusAnsweredMsg($accountId,$tmp['entity']['id'],$tmp['entity']['thread_hash']);
            }
            $result[] = $tmp;
        }
        $report = self::reportSaveMsg($result);
        return array('objs'=>$result,'report'=>$report);
    }

    /*
     * проставить всем сообщением, у которых получателем является юзер статус отвеченные
     * если указано id сообщения то нужно найти все сообщения которые были созданы ранее этого сообщения
     */
    public static function setStatusAnsweredMsg($userId,$msgId,$contactId)
    {
        $msgId = (int)$msgId;
        $contactId = (int)$contactId;
        $where = $msgId == 0 ? "receiver_id = $userId" :" id < $msgId AND receiver_id = $userId";
        $sql = <<<SQL
UPDATE modules_client_messages
SET is_answered = 1
WHERE $where AND thread_hash = $contactId
SQL;
        return Yii::app()->db->createCommand($sql)->execute();
    }

    public static function createServiceMsg($userId,$contact,$msg)
    {
//        $error = false;
//        if($userId && empty($contact) && $msg) $error = true;
//
//        $handler = self::getHandlerContactMsg($contact->type);
//        return array('error'=>$error,'service_msg'=>$data);
    }

    protected static function reportSaveMsg($data)
    {
        $errorText = '';
        $countError = 0;
        $countSuccess = 0;
        foreach($data as $dt)
        {
            if($dt['error']){
                $errorText    = $dt['error'];
                $countError++;
            }
            $countSuccess++;
        }
        $report = array('errors'=>false,'info'=>'');
        if($countError){
            $report['errors'] = true;
            $report['info'] .= 'Количество не отправленных сообщений: '.$countError.'. ';
        }
        if($countSuccess){
            $report['info'] .= 'Количество отправленных сообщений: '.$countSuccess.'.';
        }
        return $report;
    }


    //если сушествует контакт с сапортом то по дефолту саппорт-контакт не добавляется
    private static function cleanDefaultSupports($contactId, &$supports)
    {
        foreach($supports as $k=>$v)
            if($v->id == $contactId)
                unset($supports[$k]);
    }

    public static function getStatusConstants($clasName)
    {
        $reflect = new ReflectionClass( $clasName);
        $all = $reflect->getConstants();
        $constants = array();
        foreach($all as $key=>$val)
        {
            preg_match('/^STATUS/',$key, $match);
            if(!empty($match))
                $constants[] = $val;
        }
        return $constants;
    }

    protected  static  function setSupportMsg($accountId,$managerId,$msgId)
    {
        if(Yii::app()->db->createCommand()->insert('modules_chat_support_msg',
            array(
                'account_id'=>$accountId,
                'manager_id'=>$managerId,
                'msg_id'=>$msgId
            ))){
            $minfo = ManagerContacts::getInfoUser($managerId);
            return array('id'=>$minfo->id,'name'=>$minfo->name,'logo'=>$minfo->logo);
        }else{
            return [];
        }
    }
    /*
     * отметить сообщения которые были написаны менеджерами саппорта
     */
    public static function markSupportMsgs($contactId,$msgs)
    {
        $msgIds = [];
        foreach($msgs as $msg){
            $msgIds[] = (int)$msg['id'] ? : 0;
        }
        $supports = Yii::app()->db->createCommand()
            ->select('msg_id,manager_id')
            ->from('modules_chat_support_msg')
            ->where(array('in', 'msg_id', $msgIds))
            ->where('account_id=:accid', array(':accid'=>$contactId))
            ->queryAll();

        $managers = [];
        $infomanagers = [];
        foreach($msgs as &$msg){
            foreach($supports as $suppkey=>$support){
                if($msg['id'] == $support['msg_id']){
                    if(!empty($managers) && in_array($support['manager_id'],$managers)){
                        $msg['support'] = $infomanagers[$support['manager_id']];
                    }else{
                        $managers[] = $support['manager_id'];
                        $msg['support'] = $infomanagers[$support['manager_id']] = self::getInfoUser($support['manager_id']);
                    }
                }
            }
        }
        return $msgs;
    }
}