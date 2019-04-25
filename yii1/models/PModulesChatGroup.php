<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 02.12.2015
 * Time: 12:51
 */

class PModulesChatGroup extends ModulesChatGroup{

    public static  function createGroup($name, $adminId)
    {
        if(!PModulesChatGroup::existsGroup($name)){
            $model = new PModulesChatGroup();
            $model->name = $name;
            $model->admin_id = $adminId;
            $model->logo = 'default';
            if($model->save())
                return true;
            else
                return false;
        }
        return false;
    }

    public static  function existsGroup($name)
    {
        if(PModulesChatGroup::model()->exists('name=:name', array(':name', $name)))
            return true;
        else
            return false;
    }

    public static  function addInGroup($groupId,$memberId)
    {
        if(Yii::app()->db->createCommand()
            ->insert('modules_chat_group_contain',
                array(
                    'member_id'=>$memberId,
                    'status'=>PModulesChatGroupContain::STATUS_NEW,
                ),
                'group_id=:groupId', array(':groupId'=>$groupId)))
            return true;
        else
            return false;
    }

    public static  function setStatusMemberInGroup($groupId, $userId, $status)
    {
        if(Yii::app()->db->createCommand()
            ->update('modules_chat_group_contain', array('status'=>$status,),
                'group_id=:groupId AND member_id=:userId',
                array(':groupId'=>$groupId,':userId'=>$userId)))
            return true;
        else
            return false;
    }

    public static  function getMembersGroup($groupId,$adminId, $active_status = true)
    {
        $statusCondition = '';
        if($active_status)
            $statusCondition .= 'AND status IN ("'.PModulesChatGroupContain::STATUS_NEW.'","'.PModulesChatGroupContain::STATUS_ACCEPTED.'")';
        $users = Yii::app()->db->createCommand()
            ->select('member_id as clientId, status')
            ->where('group_id = :groupId AND member_id <> :adminId '.$statusCondition,
                array(':groupId'=>$groupId,":adminId"=>$adminId))
            ->from('modules_chat_group_contain')
            ->queryAll();

        foreach($users as &$user)
        {
            $user['client'] = (new PModulesClient())->getInfo($user['clientId'],true);
        }

        return $users;
    }

    public static function findGroupsUser($id)
    {
        $sql = <<<SQL
SELECT  mgroup.*, mcgc.status, mcgc.last_viewed FROM modules_chat_group_contain mcgc
LEFT JOIN modules_chat_group mgroup ON mgroup.id = mcgc.group_id
WHERE mcgc.member_id = :id
SQL;
        $groups =  Yii::app()->db->createCommand($sql)->bindParam(':id',$id, PDO::PARAM_INT)->queryAll();

        foreach($groups as &$group)
        {
            $group['inOnline'] = false;
            self::addCountNewMsgs($id, $group);
        }

        return $groups;
    }

    public static
    function addCountNewMsgs($userId,&$group)
    {
        $group['count_msgs'] = PModulesClientMessages::model()
            ->count('group_id = :groupId AND date_created > :lastViewedDate',
                array(':groupId'=>$group['id'],':lastViewedDate'=>$group['last_viewed']));
    }

} 