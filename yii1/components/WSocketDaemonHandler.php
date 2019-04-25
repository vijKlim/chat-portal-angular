<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 10.09.2015
 * Time: 15:53
 */
//Yii::import('ext.websocket.Daemon');
require_once(__DIR__.'/../extensions/websocket/Daemon.php');
require_once(__DIR__.'/../helpers/SessionUnserialize.php');
class WSocketDeamonHandler extends Daemon{

    //хранятся  id-шники юзеров портала
    public $userIds = [];
    //хранится список поддерживаемых юзером аккаунтов
    public $multiaccs = [];
    //назначается отношение(контакт - менеджер контакта). Кто из менеджеров в данный момент сапортит этот контакт (только для поддержки портала)
    protected $supportContactManagement = [];


    private function getSessionUserIds($sessKey){
        $out = $this->curlQuery("id=" . $sessKey."&is_shop=0");
        return $out ? $out : $this->curlQuery("id=" . $sessKey."&is_shop=1");
    }
    private function curlQuery($paramsStr)
    {
        if( $curl = curl_init() ) {
            curl_setopt($curl, CURLOPT_URL, 'https://tatet.ua/getSessionInfo.php');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $paramsStr);
            $out = curl_exec($curl);
            curl_close($curl);
            return $out;
        }
    }

    protected function verifyUser($sessKey, $userId)
    {
        print_r($sessKey);
        $flag = false;
        echo ' =  = ';
        print_r($userId);
        $result = $this->getSessionUserIds($sessKey);
        if($result){
            $result = explode(',',$result);
            print_r($result);
            foreach($result as $id)
            {
                if($id == $userId)
                    $flag = true;
            }
        }
        if(!$flag){
            $muserId = ContactsManager::getManagerShopForPortalUser($userId);
            echo "m user\r\n";
            print_r($muserId);
            echo "m user sess\r\n";
            print_r($result);
            foreach($result as $id){
                if($muserId == $id)
                    $flag = true;
            }
        }
        return $flag;
    }

    function clearing($id)
    {
        foreach ($this->userIds as $connectId => $userId) {
            if ($id == $userId) {
                unset($this->userIds[$connectId]);
            }
        }
    }
    /*
     * сообщить о новом онлайн соединении юзера его контактам
     */
    function userInOnlineNotify($userId,ContactsManager $contacts, $checkShop = true)
    {
        $isShop=false;
        if($checkShop && ContactsManager::isManagerShop($userId))
            $isShop = true;

        $msg = json_encode(['event'=>'open','userId'=>$userId,'isShop'=>$isShop]);
        echo "onlineConnectIds\r\n";print_r($contacts->onlineConnectIds);
        foreach($contacts->onlineConnectIds as $connectId)
        {
            $this->sendToClient($connectId, $msg);
        }
    }
    /*
     * получить информацию о состоянии сообщества  юзера
     */
    function listOnlineUsersNotify($userId,$connectId,ContactsManager $contacts)
    {
        echo "onlineNow:\r\n";
        print_r($contacts->onlineUserIds);

        $online = $contacts->onlineUserIds;
        //если юзер есть саппортом то добавляем информацию о контактах которые на данный момент саппортятся
        $internalInfo = $this->supportContactManagement ;
        if(!empty($online)){
            $this->sendToClient($connectId, json_encode(array(
                'accountId'=>$userId,
                'onlineUsers'=>$contacts->onlineUserIds,
                'countShopManagers'=>$contacts->countManagersShops,
                'supportInfo'=>$internalInfo,
                'event'=>'online')));
            echo "connect\r\n";
            print_r($this->userIds);
        }

    }

    protected function processOpen($connectId, $userId)
    {
        $contacts = ContactsManager::summary($userId,$this->userIds, $this->multiaccsWithoutCurrentConnect($connectId));
        $this->userInOnlineNotify($userId,$contacts);//отправляем сообщения юзерам о новом конекте из списка контактов нового конекта
        $this->listOnlineUsersNotify($userId,$connectId,$contacts);//отправляем информацию о том кто в онлайне новому конекту из его списка контактов
    }

    /*
     * открыть соединения (1 сооединение к 1 юзеру)
     */
    protected function openAccountConnect($connectId, $userId)
    {
        $this->clearing($userId);
        $this->userIds[$connectId] = $userId;// в мозиле иногда происходит переподключения(для того чтоб не создавалось много коннектов для одного пользователя делаем чистку)
        $this->processOpen($connectId,$userId);
    }


    /*
     * открыть соединения (1 сооединение к много юзеров) (только для саппорта)
     * добавляем id соединения и привязываем к нему id-шники аккаунтов мультиаккаунта
     * оповещаем всех онлайн пользователей
     */
    protected function openMultiAccountConnect($connectId, $userId)
    {
        foreach($this->multiaccs as $connId=>$data){
            if($data['userId'] == $userId){
                unset($this->multiaccs[$connId]);
            }
        }
        if(!in_array($connectId,$this->multiaccs)){

            $this->multiaccs[$connectId] =  array('userId'=>$userId,
                'accIds'=>ContactsManager::getUserAccounts($userId));

            foreach($this->multiaccs[$connectId]['accIds'] as $accId)
                $this->processOpen($connectId,$accId);

            echo "MULTI_ACCAUNT_CONNECT_ID: ".$connectId."\r\n";

        }
    }
    protected function multiaccsWithoutCurrentConnect($connectId)
    {
        $multiaccs = [];
        foreach($this->multiaccs as $kconId=>$data){
            if($kconId != $connectId)
                $multiaccs[$kconId] = $data;
        }
        echo "clear multi\r\n";
        print_r($multiaccs);
        return $multiaccs;
    }

    /*
     * Отправка информацию о чатах саппорту
     * а теперь и всем
     */
    protected function sendReportChat()
    {
        $data = array('event'=>'report-chat','onlines'=>$this->userIds);
        foreach($this->multiaccs as $connectId => $elem) {
            echo "send Report Chat  \r\n";
            $this->sendToClient($connectId, json_encode($data));
        }
        foreach($this->userIds as $connectId => $elem) {
            echo "send Report Chat  \r\n";
            $this->sendToClient($connectId, json_encode($data));
        }
    }
    /*
     * обрабатывает запросы на подключение к вебсокет серверу
     */
    protected function onOpen($connectionId, $info) {//вызывается при соединении с новым клиентом
        $info['GET'];//or use $info['Cookie'] for use PHPSESSID or $info['X-Real-IP'] if you use proxy-server like nginx
        parse_str(substr($info['GET'], 1), $_GET);//parse get-query
        echo "open connect \r\n";
        if($this->verifyUser($_GET['hash'], $_GET['userId'])){
            //делаем проверку является ли коннект мультиаккаунтом
            if(isset($_GET['muacc'])&& (int)$_GET['muacc'] > 0){
                $this->openMultiAccountConnect($connectionId, $_GET['userId']);
            }else{
                $this->openAccountConnect($connectionId, $_GET['userId']);
            }
            $this->sendReportChat();

        }else{
            echo "denied access\r\n";
        }

    }

    protected function closeAccountConnect($connectionId)
    {
        $message = json_encode(array('event'=>'close','userId'=>$this->userIds[$connectionId]));
        $countBefore = count($this->userIds);
        unset($this->userIds[$connectionId]);
        $countAfter = count($this->userIds);
        echo "Close connect";
        print_r($this->userIds);
        if($countAfter != $countBefore)
            foreach ($this->clients as $clientId => $client) {
                $this->sendToClient($clientId, $message);
            }
    }
    protected function closeMultiAccountConnect($connectionId)
    {
        if(isset($this->multiaccs[$connectionId])){
            foreach($this->supportContactManagement as $k=>$manager){
                if($manager->id == $this->multiaccs[$connectionId]['userId']){
                    $hash = $k;
                }
            }
            unset($this->supportContactManagement[$hash]);
            foreach($this->multiaccs[$connectionId]['accIds'] as $accId){
                $msg = json_encode(array('event'=>'close','userId'=>$accId));
                foreach ($this->userIds as $connectionId => $userId) {
                    $this->sendToClient($connectionId, $msg);
                }
            }

            unset($this->multiaccs[$connectionId]);
            echo "Close connect multiaccs";
            print_r($this->multiaccs);
        }
    }
    /*
     * обрабатывает запросы на отключение от вебсокет сервера
     */
    protected function onClose($connectionId) {
        $this->closeAccountConnect($connectionId);
        $this->closeMultiAccountConnect($connectionId);
        $this->sendReportChat();
    }


    /*
     * обрабатывает посылаемые сообщения на  вебсокет сервер
     */
    protected function onMessage($connectionId, $data, $type) {//вызывается при получении сообщения от клиента
        $data = json_decode($data);
        switch($data->event){
            //передача текстового сообщения между диалогами и группами
            case 'message':
                $this->processMsg($connectionId,$data);
                break;
            case 'info-writing-msg':
                $this->informWritingMsg($connectionId,$data);
                break;
            case 'msg-support':
                $this->processMsgSupport($connectionId,$data);
                break;
            //сообщение что один из членов support принял сообщение и будет вести диалог
            case 'support-accepted-chat':
                $this->processAcceptedChat($connectionId,$data);
                break;
            //сообщение что один из членов support не принял сообщение
            case 'support-not-accepted-msg':
                $this->processNotAcceptedChat($connectionId,$data);
                break;
            //сообщение что диалог передан другому члену саппорта внутри этого контакта
            case 'support-forward-manager':
                $this->forwardManager($connectionId,$data);
                break;
            //сообщение что диалог передан другому аккаунту сапорта
            case 'support-forward-account':
                $this->forwardAccount($connectionId,$data);
                break;
            case 'inform-user-forward-account':
                $this->informUserForwardAccount($connectionId,$data);

        };

    }

    private function processMsg($connectionId,$data)
    {
        $receivers = [];
        $handler = null;
        switch($data->typeContact){
            case ContactsManager::TYPE_ALONE:
                $receivers[] = $data->receiver;
                break;
            case ContactsManager::TYPE_GROUP:
                $receivers = ContactsManager::getUsersInGroup($data->contactId,$this->userIds[$connectionId]);
                break;
        }

        echo "START USER SEND\r\n";

        echo "ids:\r\n";print_r($receivers);
        foreach ($this->userIds as $connectId => $userId) {
            foreach($receivers as $id){
                if ($id == $userId) {
                    $data->accountId = $id;
                    echo "SEND userId = ".$id."\r\n";
                    $this->sendToClient($connectId, json_encode($data));
                }
            }
        }
        //проверяем нет ли среди получателей сообщение мультиаккаунты,
        //а так же проверяем кто из менеджеров на данный момент соопровождает аккаунт (функционал для сапорта)
        foreach($receivers as $id){

            foreach($this->multiaccs as $connectId => $elem) {
                if (in_array($id,$elem['accIds'])) {
                    $data->accountId = $id;
                    echo "SEND userId = ".$id."\r\n";
                    $this->sendToClient($connectId, json_encode($data));
                }
            }
        }
    }

    private function informWritingMsg($connectId,$data)
    {
        echo "SEND inform writing msg\r\n";
        foreach ($this->userIds as $connectId => $userId) {
            if ($data->companion == $userId) {
                echo "SEND inform writing msg\r\n";
                $this->sendToClient($connectId, json_encode($data));
            }
        }
    }

    /*
     * контакту закрепляем менеджера, который будет вести беседу (support)
     * и уведомляем всех остальных менеджеров этого контакта о закреплении беседы за этим менеджером
     */
    protected function processAcceptedChat($connectId,$data)
    {
        print_r($this->multiaccs);
        if(isset($this->multiaccs[$connectId])){
            if(in_array($data->accountId,$this->multiaccs[$connectId]['accIds'])){
                //записываем в ключе контакт а в значении юзера который согласился вести беседу = закрепить менеджера за контактом
                $this->supportContactManagement[$data->contact->id.$data->contact->type.$data->accountId] = $data->manager;
                //уведомить остальных менеджеров контакта о согласии менеджера вести беседу
                foreach($this->multiaccs as $muconId=>$elem){
                    if (in_array($data->accountId,$elem['accIds'])) {
                        $msg = array('event'=>'support-inform-accepted-chat','manager'=>$data->manager,'accountId'=>$data->accountId,'contact'=>array('id'=>$data->contact->id,'type'=>$data->contact->type),'msgdata'=>$data->msg);
                        echo "send event assign manager = ".var_dump($data)."\r\n";
                        $this->sendToClient($muconId, json_encode($msg));
                    }
                }
            }
        }
    }
    protected function processNotAcceptedChat($connectId,$data)
    {
        if(isset($this->multiaccs[$connectId])){
            if(in_array($data->accountId,$this->multiaccs[$connectId]['accIds'])){
                //открепить менеджера от закрепленного за ним ранне контакта
                if(isset($this->supportContactManagement[$data->contact->id.$data->contact->type.$data->accountId]))
                    unset($this->supportContactManagement[$data->contact->id.$data->contact->type.$data->accountId]);
                //уведомить остальных менеджеров контакта о снятии закрепленности контакта
                foreach($this->multiaccs as $muconId=>$elem){
                    if (in_array($data->accountId,$elem['accIds'])) {
                        $msg = array('event'=>'support-inform-not-accepted-chat','manager'=>$data->manager,'accountId'=>$data->accountId,'contact'=>array('id'=>$data->contact->id,'type'=>$data->contact->type),'msgdata'=>$data->msg);
                        echo "send event not assign manager = ".var_dump($data)."\r\n";
                        $this->sendToClient($muconId, json_encode($msg));
                    }
                }
            }
        }
    }
    /*
     * сообщить о передаче диалога менеджером другому менеджеру
     */
    protected function forwardManager($connectId,$data)
    {

        if(in_array($data->accountId,$this->multiaccs[$connectId]['accIds'])){
            $this->supportContactManagement[$data->contact->id.$data->contact->type.$data->accountId] = $data->nextManager;

//уведомить остальных менеджеров контакта о снятии закрепленности контакта
            foreach($this->multiaccs as $muconId=>$elem){
                if (in_array($data->accountId,$elem['accIds'])) {
                    $msg = array('event'=>'support-forward-manager','prevManager'=>$data->currentManager,
                        'nextManager'=>$data->nextManager,'accountId'=>$data->accountId,
                        'contact'=>array('id'=>$data->contact->id,'type'=>$data->contact->type),'msgdata'=>$data->msg);
                    echo "send event forward manager = ".var_dump($data)."\r\n";
                    $this->sendToClient($muconId, json_encode($msg));
                }
            }
            $this->informStateSupportContactsManagement();
        }


    }
    protected function forwardAccount($connectId,$data)
    {
        echo "start event forward account\r\n";
        if(in_array($data->prevAccountId,$this->multiaccs[$connectId]['accIds'])){
            if($this->supportContactManagement[$data->prevContact->id.$data->prevContact->type.$data->prevAccountId])
                unset($this->supportContactManagement[$data->prevContact->id.$data->prevContact->type.$data->prevAccountId]);

            $this->supportContactManagement[$data->nextContact->id.$data->nextContact->type.$data->nextAccountId] = $data->nextManager;

            foreach($this->multiaccs as $muconId=>$elem){
                if ((int)$data->nextManager->id == (int)$elem['userId']) {

                    echo "send event forward accountId = ".$elem['userId']." \r\n";
//                    var_dump($data);
                    $this->sendToClient($muconId, json_encode($data));
                }
            }
            $this->informStateSupportContactsManagement();
            //$this->sendToClient($connectId, json_encode($data));
        }

    }
    public function informStateSupportContactsManagement()
    {
        $data = ['event' => 'inform-state-support-contacts-management','scm'=>$this->supportContactManagement];
        foreach($this->multiaccs as $muconId=>$elem){
            echo "inform State Support Contacts Management = ".$elem['userId']." \r\n";
            $this->sendToClient($muconId, json_encode($data));

        }
    }
    public function informUserForwardAccount($connectId,$data){
        if(in_array($data->currentAccountId,$this->multiaccs[$connectId]['accIds'])){

            foreach ($this->userIds as $connectId => $userId) {
                if ((int)$data->receiverId == (int)$userId) {

                    echo "SEND informUserForwardAccount = ".$data->receiverId."\r\n";
                    $this->sendToClient($connectId, json_encode($data));
                }
            }
        }
    }
    /*
    * Менеджер принял передачу диалога
    */
//    protected function acceptedTransferMsgInside($connectionId,$data)
//    {
//
//    }


}


class ContactsManager{
    protected $onlineConnectIds;
    protected $onlineUserIds;
    protected $countManagersShops;

    const TYPE_ALONE = 'alone';
    const TYPE_GROUP = 'group';
    const TYPE_SUPPORT = 'support';

    private function __construct()
    {
        $this->onlineConnectIds = [];
        $this->onlineUserIds = [];
        $this->countManagersShops = 0;
    }

    function __get($name)
    {
        return $this->$name;
    }

//    function delMyAccounts($accounts)
//    {
//        print_r($accounts);
//        print_r($this->onlineUserIds);
//        $keys = [];
//        if(!empty($this->onlineUserIds )){
//            foreach($this->onlineUserIds as $key=>$onlineId){
//                if(in_array($onlineId,$accounts)){
//                    $keys[]= $key;
//                }
//            }
//        }
//
//        print_r($keys);
//    }

    static function getUserAccounts($userId)
    {
        $sql = <<<SQL
SELECT account_id
 FROM modules_chat_multi_accounts
 WHERE user_id= :userId
SQL;
        $db = new DB();
        $accounts = $db->column($sql,array('userId'=>$userId));
        $db->close();
        return $accounts;

    }

    static  function getManagerShopForPortalUser($user_id)
    {
        $sql = <<<SQL
SELECT mshops.id
FROM shops.sc_managers as mshops
LEFT JOIN shops_portal.modules_client as portal
ON mshops.shopid = portal.id_external AND mshops.email = portal.email
LEFT JOIN shops.sc_configs sc ON sc.shopid = mshops.shopid
WHERE portal.id = :user_id AND portal.is_active
SQL;

        $db = new DB();
        $managers = $db->column($sql,array('user_id'=>$user_id));
        $db->close();
        return $managers[0];
    }

    static  function isManagerShop($manager_id)
    {
//        $sql = <<<SQL
//SELECT portal.id
// FROM shops_portal.modules_client as portal
// LEFT JOIN shops.sc_managers as sm ON sm.id = portal.id
// LEFT JOIN shops_portal.sc_configs c ON c.shopid = sm.shopid
// WHERE portal.id= :manager_id AND portal.type='shop' AND portal.id_external AND portal.is_active
//SQL;
        $sql = <<<SQL
SELECT mshops.id
FROM shops.sc_managers as mshops
LEFT JOIN shops_portal.modules_client as portal
ON mshops.shopid = portal.id_external AND mshops.email = portal.email AND mshops.shopid = portal.id_external
LEFT JOIN shops.sc_configs sc ON sc.shopid = mshops.shopid
WHERE mshops.id = :manager_id AND portal.is_active
SQL;

        $db = new DB();
        $managers = $db->column($sql,array('manager_id'=>$manager_id));
        $db->close();
        return !empty($managers) ? true : false;
    }

    static function summary($wsuser,$wsdata, $multi_wsdata){

        $db = new DB();
        $ugroups = self::findGroupContacts($db,$wsuser);
        $ualones = self::findAloneContacts($db,$wsuser);
        $db->close();

        $auids = self::selectUserIds($ualones);
        $guids = self::selectUserIds($ugroups);
        $uids = array_unique(array_merge($auids,$guids));
        $manager = new ContactsManager();

        foreach($wsdata as $connectId => $userId){
            if(in_array($userId,$uids)){
                $manager->onlineConnectIds[] = $connectId;

            }
            if(in_array($userId,$uids)){
                $manager->onlineUserIds[] = $userId;
            }
            //делаем выборку менеджеров магазинов (это нужно для "спросить у магазина онлайн" там высвечивать онлайн магазин или нет)
            if(ContactsManager::isManagerShop($userId)){
                if(!in_array($userId,$manager->onlineUserIds)){
                    $manager->onlineUserIds[] = $userId;
                    $manager->countManagersShops++;
                }
            }

//            foreach($ugroups as $item){
//                if((int)$userId == (int)$item['userId']){
//                    $manager->onlineUsersInGroups[$item['id']]['group'] = $item['id'];
//                    $manager->onlineUsersInGroups[$item['id']]['online'][] = $item['userId'];
//                }
//            }
        }
        if(!empty($multi_wsdata) && !empty($uids)){
            foreach($multi_wsdata as $connectId => $data){
                foreach($data['accIds'] as $accId){
                    if(in_array($accId,$uids)){
                        if(!in_array($connectId,$manager->onlineConnectIds))
                            $manager->onlineConnectIds[] = $connectId;

                    }
                    if(in_array($accId,$uids) && !in_array($accId,$manager->onlineUserIds)){
                        $manager->onlineUserIds[] = $accId;
                        $manager->countManagersShops++;
                    }
                }
            }
        }


        return $manager;
    }

    static function findGroupContacts($db,$userId)
    {
        $sqlUserGroups = <<<SQL
SELECT c1.group_id as id, c1.member_id as userId
FROM `modules_chat_group_contain` as c1
INNER JOIN modules_chat_group_contain as c2 ON c2.group_id = c1.group_id
WHERE c2.member_id = :userId AND c1.member_id <> :userId AND c2.status NOT IN ('delete','cancel','block') AND c1.status NOT IN ('delete','cancel','block')
SQL;
        return $db->query($sqlUserGroups,array('userId'=>$userId)) ? : [];
    }

    static function findAloneContacts($db,$userId)
    {
        $sqlAloneUsers = <<<SQL
SELECT hash as id, client_invited_id as userId
FROM modules_client_relations
WHERE client_offer_id = :userId  AND status <> 'delete'
UNION
SELECT hash as id, client_offer_id as userId
FROM modules_client_relations
WHERE client_invited_id = :userId  AND status <> 'delete'
SQL;

        return $db->query($sqlAloneUsers,array('userId'=>$userId)) ? : [];
    }


    static function selectUserIds($contacts)
    {
        $ids = [];
        foreach($contacts as $item)
            $ids[] = (int)$item['userId'];
        return array_unique($ids);
    }

    static function selectUserGroupIds($groups)
    {
        $ids = [];
        foreach($groups as $item)
            $ids[] = (int)$item['id'];
        return array_unique($ids);
    }

    static function getUsersInGroup($groupId,$userId)
    {
        $sqlUserGroup = <<<SQL
SELECT member_id
FROM `modules_chat_group_contain`
WHERE group_id = :groupId AND member_id <> :userId AND status <> 'delete';
SQL;
        $db = new DB();
        $res = $db->column($sqlUserGroup,array('groupId'=>$groupId,'userId'=>$userId));
        $db->close();
        return $res ? : [];
    }
}


class DB{
    private $pdo,$sQuery,$parameters;
    private $bConnected = false;

    public function __construct(){
        $this->connect();
    }

    private function connect()
    {
        $dsn = 'mysql:host=db.tatet.net;dbname=shops_portal';
        try{
            $this->pdo = new PDO($dsn, 'shops', 'vfufpbyxbr', array(PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->bConnected = true;
        }catch (PDOException $e){
            echo $e->getMessage();
            die();
        }
    }
    public function close()
    {
        $this->bConnected = false;
        $this->pdo = null;
    }
    private function Init($query,$parameters = "")
    {
        # Connect to database
        if(!$this->bConnected) { $this->connect(); }
        try {
            # Prepare query
            $this->sQuery = $this->pdo->prepare($query);

            # Add parameters to the parameter array
            $this->bindMore($parameters);
            # Bind parameters
            if(!empty($this->parameters)) {
                foreach($this->parameters as $param)
                {
                    $parameters = explode("\x7F",$param);
                    $this->sQuery->bindParam($parameters[0],$parameters[1]);
                }
            }
            # Execute SQL
            $this->succes 	= $this->sQuery->execute();
        }
        catch(PDOException $e)
        {
            # Write into log and display Exception
            echo $e->getMessage();
            die();
        }
        # Reset the parameters
        $this->parameters = array();
    }

    public function query($query,$params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        $query = trim($query);
        $this->Init($query,$params);
        $rawStatement = explode(" ", $query);

        # Which SQL statement is used
        $statement = strtolower($rawStatement[0]);

        if ($statement === 'select' || $statement === 'show') {
            return $this->sQuery->fetchAll($fetchmode);
        }
        elseif ( $statement === 'insert' ||  $statement === 'update' || $statement === 'delete' ) {
            return $this->sQuery->rowCount();
        }
        else {
            return NULL;
        }
    }

    public function bindMore($parray)
    {
        if(empty($this->parameters) && is_array($parray)) {
            $columns = array_keys($parray);
            foreach($columns as $i => &$column)	{
                $this->bind($column, $parray[$column]);
            }
        }
    }

    public function bind($para, $value)
    {
        $this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . utf8_encode($value);
    }

    public function column($query,$params = null)
    {
        $this->Init($query,$params);
        $Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);

        $column = null;
        foreach($Columns as $cells) {
            $column[] = $cells[0];
        }
        return $column;

    }

    public function row($query,$params = null,$fetchmode = PDO::FETCH_ASSOC)
    {
        $this->Init($query,$params);
        return $this->sQuery->fetch($fetchmode);
    }
}

//class debugInfo{
//    const WS = '.ws.';
//    const CHANNEL_ZERO = '.channel_not_zero.';
//    const CHANNEL_NOT_ZERO = '.channel_not_zero.';
//    const ALONE_CONTACT = '.alone.';
//    const GROUP_CONTACT = '.group.';
//
//    const OPEN = '.open connect.';
//    const CLOSE = '.close connect.';
//    const SEND  = '.send massage.';
//
//    const SUCCESS = '.success.';
//    const ERROR   = '.error.';
//    const WARNING = '.warning.';
//
//   static function write($msg, $params = array())
//   {
//       $strparam = '';
//       if(!empty($params)){
//           foreach($params as $k=>$v){
//               $strparam .= ' | '.$k.' = '.$v." | ";
//           }
//       }
//       echo $msg." (".$strparam." )\r\n";
//   }
//}