<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 07.12.2015
 * Time: 10:11
 */

class GroupContact extends Contact{

    function __construct()
    {
        $this->type = self::TYPE_GROUP;
    }

    function createGroup($adminId,$nameGroup,$logoGroup='')
    {
        $group = new PModulesChatGroup();
        $group->name = $nameGroup;
        $group->admin_id = $adminId;
        $group->logo = $logoGroup;

        if($group->save()){
            $this->group_id = $group->id;
            $this->add($adminId, $adminId, $group->id, $status=self::STATUS_ACCEPTED);
        }
    }

    function getContact($memberId, $groupId)
    {
        return PModulesChatGroupContain::model()
            ->find('group_id = :groupId AND member_id = :memberId',
                array(':groupId'=>$groupId,':memberId'=>$memberId));

    }
    function getExtraContact($memberId,$groupId)
    {
        return Yii::app()->db->createCommand()
            ->select('g.id,g.name,g.logo,cn.status')
            ->from('modules_chat_group_contain cn')
            ->leftJoin('modules_chat_group g','g.id=cn.group_id')
            ->where('cn.group_id = :group AND cn.member_id = :member',
                array(':group'=>$groupId,':member'=>$memberId))
            ->queryRow();
    }
    function getFormatContact($memberId,$groupId)
    {
        $contact = $this->getExtraContact($memberId,$groupId);
        return $this->format($contact);
    }

    function format($data)
    {
        return array(
            'id'         =>$data['id'],
            'inOnline'   => false,
            'count_msgs' => 0,
            'type'       => $this->type,
            'status'     => $data['status'],
            'name'       => $data['name'],
            'logo'       => $data['logo'],
            'members'    => FormatGroupContact::getMembersGroups($data['id'])
        );

    }


    function getAdminGroup($groupId)
    {
        return Yii::app()->db->createCommand()
            ->select('admin_id')
            ->where('id = :groupId ', array(':groupId'=>$groupId,))
            ->from('modules_chat_group')
            ->queryScalar();

    }

    function add($adminId, $memberId, $groupId, $status=self::STATUS_NEW)
    {
        $model = new PModulesChatGroupContain();
        $model->group_id    = $groupId;
        $model->member_id   = $memberId;
        $model->status      = $status;
        $model->last_viewed = time();
        $model->who_added   = $adminId;
        return $model->save() ? $model : null;
    }

    function setStatus($accountId, $contactId, $status)
    {
        if($model = $this->getContact($accountId, $contactId))
        {
            $model->status            = $status;
            $model->date_resolution = time();
            return $model->save();
        }
        return false;
    }

    public static function search($match){

        return  Yii::app()->db->createCommand()
            ->select('id,name,logo')
            ->from('modules_chat_group')
            ->where(array('like', 'name', "%$match%"))
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'GroupContact')
            ->queryAll();

    }
} 