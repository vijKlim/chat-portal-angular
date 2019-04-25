<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 09.12.2015
 * Time: 9:29
 */

class ManagerGroupContacts extends ManagerContacts{

    protected $groupId;
    public function __construct($accountId)
    {
        $accountId = (int)$accountId;
        if($accountId)
            $this->accountId = $accountId;
        else
            throw new Exception('Неправильная конфигурация переданна в класс '.__CLASS__);

    }

    function createGroup($nameGroup,$logoGroup, $thread_hash)
    {
        if($nameGroup){
            $nameGroup = strlen($nameGroup) > 25 ? substr($nameGroup,0,25) : $nameGroup;
        }else{
            $nameGroup = 'defaultGroup'.FormatGroupContact::getCountAll($this->accountId);
        }

        $group = new GroupContact();
        $group->createGroup($this->accountId,$nameGroup, $logoGroup);

        if($group->group_id){
            $groupInfo = array('groupId'=>$group->group_id,'name'=>$nameGroup,'logo'=>$logoGroup,'adminId'=>$this->accountId);
            //Если thread_hash = 0 то значит мы группу создаем с нуля,
            // а если больше нуля тогда мы диалог модифицируем в группу
            if($thread_hash){
                if($this->modifyContact($thread_hash, $group->group_id))
                    return array('group'=>$groupInfo,'error'=>false,'info'=>self::SUCCESS_CREATE_GROUP);
                else
                    return array('group'=>$groupInfo,'error'=>true,'info'=>self::ERROR_MORFY_CONTACT);
            }

            return array('group'=>$groupInfo,'error'=>false,'info'=>self::SUCCESS_CREATE_GROUP);

        }else{
            return array('group'=>0,'error'=>true,'info'=>self::ERROR_GENERAL);
        }

    }

    private function modifyContact($thread_hash, $groupId)
    {
        Yii::app()->db->createCommand()
            ->update('modules_client_messages', array(
                'group_id' => $groupId,
            ), 'thread_hash = :thr AND NOT group_id', array(':thr'=>$thread_hash));
        return true;
    }
//formatContact???
    function addContact($groupId,$memberId)
    {
        if((int)$memberId){
            $contact = new GroupContact();
            if(!$contact->getContact($memberId,$groupId)){
                $new = null;
                if($this->accountId == $contact->getAdminGroup($groupId)){
                    $new = $contact->add($this->accountId,$memberId,$groupId);
                }else{
                    return array('model'=>null,'isnew'=>1,'msg'=>self::WARNING_ONLY_ADMIN_ADD_GROUP,'error'=>self::FLAG_ERROR_TRUE);
                }
                if($new){
                    $new = $this->formatContact($groupId,$new->member_id, $new->status,self::TYPE_GROUP);
                    $this->informAddContact($this->accountId, $memberId);

                    return array('model'=>$new,'isnew'=>1,'msg'=>self::SUCCESS_ADD_CONTACT,'error'=>self::FLAG_ERROR_FALSE);
                }else{
                    return array('model'=>null,'isnew'=>1,'msg'=>self::ERROR_GENERAL,'error'=>self::FLAG_ERROR_TRUE);
                }
            }else{
                return array('model'=>null,'isnew'=>0,'msg'=>self::WARNING_EXISTS_CONTACT,'error'=>self::FLAG_ERROR_TRUE);
            }
        }else{
            return array('model'=>null,'isnew'=>0,'msg'=>self::ERROR_GENERAL,'error'=>self::FLAG_ERROR_TRUE);
        }
    }

    function addMembers($groupId,$members)
    {
        $tmp = [];
        $result = [];
        if(!empty($members)){
            foreach($members as $member){
                $tmp[] = $this->addContact($groupId,$member->id);
            }
            foreach($tmp as $t){
                $result[(int)$t['error']]['error'] = $t['error'];
                $member = $t['model'] ? $t['model']['member_id']." - " : '';
                $result[(int)$t['error']]['msg'][] = $member.$t['msg'];
            }
        }else{
            $result[0]['error'] = self::FLAG_ERROR_TRUE;
            $result[0]['msg']  = 'Укажите контакты для добавления в группу';
        }
        return $result;
    }

    function setStatusContact($contactId, $status)
    {
        $statuses = self::getStatusConstants('Contact');

        if(in_array($status,$statuses))
        {
            $contact = new GroupContact();
//            if($this->accountId == $contact->getAdminGroup($contactId))
            if($contact->setStatus($this->accountId,$contactId, $status)){
                return array('error'=>self::FLAG_ERROR_FALSE, 'type'=>$status);
            }else{
                return array('error'=>self::FLAG_ERROR_TRUE, 'type'=>'error set status');
            }

        }
        return array('error'=>self::FLAG_ERROR_TRUE, 'type'=>'error set status 2');

    }

    function informAddContact($offerId,$invitedId)
    {
        return;
    }

    function getContactMsgs($contactId,$period,$issupport)
    {
        $gmsgs = new GroupContactMsgs();
        $result = $gmsgs->getMsgsThread($this->accountId,$contactId,$period,$issupport);
        if(!empty($result)){
            return array('msgs'=>$result,
                'personalInfoContact'=>PModulesChatGroup::model()->findByPk($contactId)->attributes,
                'countViewed'=>$gmsgs->setViewed($this->accountId, $contactId),
                'isblank'=>false);
        }else{
            return array('msgs'=>$gmsgs->createNullMsgsThread($this->accountId,$contactId),
                'personalInfoContact'=>PModulesChatGroup::model()->findByPk($contactId)->attributes,
                'countViewed'=>0,
                'isblank'=>true);
        }
    }

    function informNewMsg($receiverId)
    {

    }

    function findCommonGroups($friendId)
    {
        $sql = <<<SQL
SELECT group_id FROM modules_chat_group_contain
WHERE group_id IN
(SELECT group_id FROM modules_chat_group_contain  WHERE member_id=:idm AND status NOT IN ('delete','cancel','block'))
 AND member_id=:idf AND status <> 'delete'
SQL;
        $data = Yii::app()->db->createCommand($sql)
            ->bindParam(':idm',$this->accountId,PDO::PARAM_INT)
            ->bindParam(':idf',$friendId,PDO::PARAM_INT)
            ->queryAll();
        $result = [];
        foreach($data as $item)
            $result[] = $item['group_id'];
        return $result;
    }

    function getInfoContact($contactId)
    {
        $data = Yii::app()->db->createCommand()
            ->select('id, name, logo ')
            ->from('modules_chat_group')
            ->where("id=:id",array( ':id'=>$contactId))
            ->queryRow();
        $data['type'] = Contact::TYPE_GROUP;
        return $data;
    }

} 