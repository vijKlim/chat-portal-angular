<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 07.12.2015
 * Time: 10:11
 */

abstract class Contact {


    public $count_msgs = 0; // count not viewed msgs
    public $inOnline = false; // user is online

    const TYPE_ALONE = 'alone';
    const TYPE_GROUP = 'group';
    const TYPE_SUPPORT = 'support';
    const TYPE_NEW    = 'new';

    const CONTACTS_WHICH_ALL                = 'all';
    const CONTACTS_WHICH_OWN_REQUESTS       = 'own';
    const CONTACTS_WHICH_OTHER_REQUESTS     = 'other';

    const STATUS_NEW         = 'new';
    const STATUS_ACCEPTED    = 'accepted';
    const STATUS_NO_ACCEPTED = 'no_accepted';
    const STATUS_CANCEL      = 'cancel';
    const STATUS_BLOCK       = 'block';
    const STATUS_DELETE      = 'delete';

    static function getInfoUser($userId)
    {
        return (new PModulesClient())->getInfo($userId,true);
    }

}