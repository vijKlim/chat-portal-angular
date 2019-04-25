<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 17.12.2015
 * Time: 12:33
 */

class ManagerSupportContacts extends ManagerContacts{

    public function __construct($accountId)
    {
        $accountId = (int)$accountId;
        if($accountId)
            $this->accountId = $accountId;
        else
            throw new Exception('Неправильная конфигурация переданна в класс '.__CLASS__);
    }

    public function addContact($contactId){

    }
    public function setStatusContact($contactId, $status){

    }

    /*
     * Контакты Support добавлены в чаты по дефолту по этому они еще не имеют сообщения,
     *  как только установлена переписка контакт Support трансформируется в Alone. о мне нужно вытянуть информацию о контакте
     */
    function getContactMsgs($contactId,$period,$issupport)
    {
        $smsgs = new SupportContactMsgs();
        return array('msgs'=>$smsgs->createNullMsgsThread($this->accountId,$contactId),
            'personalInfoContact'=>self::getInfoUser($contactId),
            'countViewed'=>0,'isblank'=>true);
    }
} 