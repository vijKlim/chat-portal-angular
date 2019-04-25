<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 12.02.2016
 * Time: 15:56
 */

class FormatGroupContact extends Contact{

    function __construct()
    {
        $this->type = self::TYPE_GROUP;
    }
    function __set($key, $val)
    {
        switch($key)
        {
            //чтоб лого нормально обрабатывалось, в mysql выборке должно быть указанно последним (после id_external, type_user, name)
            case 'id':
                $this->$key = $val;
                $this->members = self::getMembersGroups($val);
                break;
            default:
                $this->$key = $val;
        }

    }

    public static function getMembersGroups($groupId)
    {
        $members = [];
        $res = Yii::app()->db->createCommand("SELECT member_id FROM modules_chat_group_contain WHERE group_id=$groupId")->queryAll();
        foreach($res as $item){
            $members[] = $item['member_id'];
        }
        return $members;
    }

    private static function _all($userId, $which, $onlyCount = false)
    {
        $where = '';
        switch($which)
        {
            case Contact::CONTACTS_WHICH_ALL:
                $where .= '';
                break;
            case Contact::CONTACTS_WHICH_OWN_REQUESTS:
                $where .= ' AND mcgc.who_added = :id AND mcgc.date_resolution IS NULL';
                break;
            case Contact::CONTACTS_WHICH_OTHER_REQUESTS:
                $where .= ' AND mcgc.who_added <> :id AND mcgc.date_resolution IS NULL';
                break;
        }

        $select = '';
        if(!$onlyCount)
            $select .= " mgroup.id,  mgroup.name, mgroup.logo, mcgc.status, mcgc.last_viewed, mgroup.admin_id ";
        else
            $select .= " count(mgroup.id) as count";

        $sql = <<<SQL
SELECT  $select FROM modules_chat_group_contain mcgc
LEFT JOIN modules_chat_group mgroup ON mgroup.id = mcgc.group_id
WHERE mcgc.member_id = :id AND mcgc.status NOT IN ('delete','cancel','block')  $where
SQL;
        if(!$onlyCount){
            return Yii::app()->db->createCommand($sql)
                ->bindParam(':id',$userId, PDO::PARAM_INT)
                ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'FormatGroupContact')
                ->queryAll();
        }else{
            return Yii::app()->db->createCommand($sql)
                ->bindParam(':id',$userId, PDO::PARAM_INT)
                ->queryScalar();
        }

    }

    static function getCountAll($userId,  $which = self::CONTACTS_WHICH_ALL)
    {
        return self::_all($userId, $which, true);
    }

    public static function getAll($userId, $which = self::CONTACTS_WHICH_ALL)
    {
        $contacts = self::_all($userId, $which);
        //not used
//        $counts = GroupContactMsgs::countContactsMsgs($userId);
//        foreach($contacts as $contact)
//        {
//            if(!empty($contact))
//                $contact->count_msgs = (int)$counts[$contact->id];
//            else
//                $contact->count_msgs = 0;
//        }
        return $contacts;

    }

} 