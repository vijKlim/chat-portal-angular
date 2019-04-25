<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 09.12.2015
 * Time: 9:27
 */

class ManagerAloneContacts extends ManagerContacts{

    public function __construct($accountId)
    {
        $accountId = (int)$accountId;
        if($accountId)
            $this->accountId = $accountId;
        else
            throw new Exception('Неправильная конфигурация переданна в класс '.__CLASS__);
    }

    //получить контакт по id-шникам участников контакта, если такого контакта не существует создать болванку
    function getContactByMember($invitedId)
    {
        $invitedId = (int)$invitedId;
        $contactId = createHash($invitedId,$this->accountId);
        $contact = AloneContact::getInstance($this->accountId,$contactId);

        if($contact->isSimple())
            return $contact->getBlankContact($invitedId);
        else
            return $contact->format();
    }

    function createContact($invitedId)
    {
        if($invitedId == $this->accountId){
            return array('model'=>null,'isnew'=>0,'msg'=>self::ERROR_ADD_OWN_CONTACT,'error'=>self::FLAG_ERROR_TRUE);
        }
        if((int)$invitedId){
            $contactId = createHash($invitedId,$this->accountId);
            $contact = AloneContact::getInstance($this->accountId,$contactId);
            //если контакта с базы мы не получили то создаем и заносим в базу
            if($contact->isSimple()){
                $contact->create($this->accountId,$invitedId);
                if(!$contact->isSimple()){
                    $new = $contact->format();
                    $this->informAddContact($invitedId);

                    return array('model'=>$new,'isnew'=>1,'msg'=>self::SUCCESS_ADD_CONTACT,'error'=>self::FLAG_ERROR_FALSE);
                }else{
                    return array('model'=>null,'isnew'=>1,'msg'=>self::ERROR_GENERAL,'error'=>self::FLAG_ERROR_TRUE);
                }
            }else{
                return array('model'=>null,'isnew'=>0,'msg'=>self::WARNING_EXISTS_CONTACT,'error'=>self::FLAG_ERROR_FALSE);
            }
        }else{
            return array('model'=>null,'isnew'=>0,'msg'=>self::ERROR_GENERAL,'error'=>self::FLAG_ERROR_TRUE);
        }
    }

    function setStatusContact($contactId, $status)
    {
        $statuses = self::getStatusConstants('Contact');
        if(in_array($status,$statuses))
        {
            $contact = AloneContact::getInstance($this->accountId,$contactId);

            if(!$contact->isSimple() && $contact->checkOwner()){
                if($contact->setStatus( $status)){
                    return array('error'=>self::FLAG_ERROR_FALSE, 'type'=>$status);
                }else{
                    return array('error'=>self::FLAG_ERROR_TRUE, 'type'=>'');
                }
            }
        }
        return array('error'=>self::FLAG_ERROR_TRUE, 'type'=>'');
    }



    function informAddContact($invitedId)
    {
        $replaceTable = [];
        $infoOffer = Yii::app()->client->info;
        $replaceTable = array_merge($replaceTable, [
            '{$user_name}' => $infoOffer->name,
            '{$user_logo}' => preg_match('`http://`',$infoOffer->logo) ? $infoOffer->logo : 'http://tatet.ua'.$infoOffer->logo
        ]);
        EmailClient::sendByUser($invitedId, PModulesMailTpl::ALIAS_INFO_CHAT_CONTACT, Controller::$portalId, Controller::$portalLocale, $replaceTable);
    }

    function getContactMsgs($contactId, $period,$issupport)
    {
        $contact = AloneContact::getInstance($this->accountId,$contactId);
        list($m1,$m2) = decodeHash($contactId);
        $memberId = (int)$this->accountId == (int)$m1 ? $m2 : $m1;
        if(!$contact->isSimple() && $contact->checkOwner())
        {
            $amsgs = new AloneContactMsgs();
            $result = $amsgs->getMsgsThread($this->accountId, $contactId,$period,$issupport);

            if(!empty($result)){

                return array('msgs'=>$result,
                    'personalInfoContact'=>self::getInfoUser($memberId),
                    'countViewed'=>$amsgs->setViewed($this->accountId, $contactId),
                    'isblank'=>false);
            }else{
                $data = $amsgs->createNullMsgsThread($this->accountId,$contactId);
                if($data){
                    return array('msgs'=>$data,
                        'personalInfoContact'=>self::getInfoUser((int)$data[0]['receiver_id']),
                        'countViewed'=>0,
                        'isblank'=>true);
                }
                return array('msgs'=>$result,'personalInfoContact'=>null,'countViewed'=>0,'isblank'=>true);


            }
        }else{
            return array('msgs'=>null,'personalInfoContact'=>self::getInfoUser((int)$memberId),'countViewed'=>0,'isblank'=>true);
        }

    }

    function getInfoContact($contactId)
    {
        $contact = AloneContact::getInstance($this->accountId,$contactId);
        //если контакт существеут в базе данных
        if(!$contact->isSimple()){
            $memberId = $contact->getMember();
            $data = (new PModulesClient())->getInfo($memberId ,true);
            $data->id = $contactId;
            $data->type = Contact::TYPE_ALONE;
            return $data;
        }

    }


}