<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 07.12.2015
 * Time: 10:12
 */

class AloneContact extends Contact{

    public $accountId = 0;
    public $contactId = 0;
    public $type;

    private function __construct(){}

    static function getInstance($accountId=0,$contactId=0)
    {
        $obj = null;
        $res = Yii::app()->db->createCommand()
            ->select('*')
            ->from('modules_client_relations')
            ->where('hash=:hash', array(':hash'=>$contactId))
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'AloneContact')
            ->queryAll();
        if(empty($res)){
            $obj = new AloneContact();
            $obj->contactId = $contactId;
            $obj->accountId = $accountId;
            $obj->type = self::TYPE_NEW;
        }else{
            $obj = $res[0];
            $obj->contactId = $obj->hash;
            $obj->accountId = $accountId;
            $obj->type = self::TYPE_ALONE;
        }
        return $obj;
    }

    function isSimple()
    {
        return $this->type == self::TYPE_NEW ? true : false;
    }

    function checkOwner()
    {
        if((int)$this->accountId == $this->client_offer_id ||
            (int)$this->accountId == $this->client_invited_id)
            return true;
        else
            return false;

    }
    function getMember()
    {
        if ($this->client_offer_id == $this->accountId)
            return $this->client_invited_id;
        else
            return $this->client_offer_id;
    }

    function create($offerId, $invitedId, $status=self::STATUS_NEW, $is_approved = false)
    {
        $model = new PModulesClientRelations();
        $model->client_offer_id   = $offerId;
        $model->client_invited_id = $invitedId;
        $model->hash              = $hash = createHash($offerId,$invitedId);
        $model->date_invitation   = time();
        $model->status            = $status;
        if($is_approved){
            $model->is_approved = 1;
            $model->date_resolution = time();
        }
        if($model->save()){
            $this->client_offer_id   = $model->client_offer_id;
            $this->client_invited_id = $model->client_invited_id;
            $this->hash              =  $model->hash;
            $this->date_invitation   = $model->date_invitation;
            $this->status            = $model->status;

            $this->type = self::TYPE_ALONE;
        }
    }

    //only contacts which exists db
    function format()
    {
        $userInfo = self::getInfoUser($this->getMember());
        return array(
            'id'         =>$this->contactId,
            'inOnline'   => false,
            'count_msgs' => 0,
            'type'       => $this->type,
            'status'     => $this->status,
            'name'          => $userInfo->name,
            'id_external'=> $userInfo->id_external,
            'is_default_logo'=> $userInfo->is_default_logo,
            'logo'           => $userInfo->logo,
            'members'     => [$this->getMember()]
        );

    }
    function formatForSimple()
    {
        return array(
            'id'         =>$this->contactId,
            'inOnline'   => false,
            'count_msgs' => 0,
            'type'       => $this->type
        );
    }

    function getBlankContact($memberId)
    {
        $userInfo = self::getInfoUser($memberId);
        return array(
            'id'         =>$this->contactId,
            'inOnline'   => false,
            'count_msgs' => 0,
            'type'       => self::TYPE_NEW,
            'status'     => '',
            'name'          => $userInfo->name,
            'id_external'=> $userInfo->id_external,
            'is_default_logo'=> $userInfo->is_default_logo,
            'logo'           => $userInfo->logo
        );
    }


    function setStatus($status)
    {
        return Yii::app()->db->createCommand()
            ->update('modules_client_relations', array(
                        'date_resolution'=>time(),
                        'status'=>$status,
                        'is_approved'=> ($status === self::STATUS_ACCEPTED) ? 1 : 0
                    ), 'hash=:hash', array(':hash'=>$this->contactId)) ? true : false;
    }

} 