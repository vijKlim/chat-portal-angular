<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 04.08.2015
 * Time: 13:01
 */

trait ClientChat {

protected $support_type = ['team','shops_support'];
//OK
    final function actionAddMembersInGroup(){
        $accountId = (int)Yii::app()->request->getParam('accountId') ? : 0;
        $members = json_decode(Yii::app()->request->getParam('members'));

        $groupId = (int)Yii::app()->request->getParam('groupId') ? : 0;

        $mcontacts = ManagerContacts::getInstance($accountId,ManagerContacts::TYPE_GROUP);
        echo json_encode($mcontacts->addMembers($groupId,$members));
        Yii::app()->end();
    }
//OK
    final public function actionCreateGroup()
    {
        $accountId = (int)Yii::app()->request->getParam('accountId') ? : 0;
        $nameGroup = Yii::app()->request->getParam('nameGroup') ? : '';
        $logoGroup = Yii::app()->request->getParam('logoGroup') ? : '';
        $members = json_decode(Yii::app()->request->getParam('members'));
        $thread_hash = (int)Yii::app()->request->getParam('thread_hash') ? : 0;

        $mcontacts = ManagerContacts::getInstance($accountId ,ManagerContacts::TYPE_GROUP);

        $group = $mcontacts->createGroup($nameGroup, $logoGroup, $thread_hash);

        if(!$group['error'] && $group['group']['groupId'] && !empty($members)){
            $result = [];
            foreach($members as $member){
                $result[] = $mcontacts->addContact($group['group']['groupId'],$member->id);
            }
        }
        echo json_encode($group);
        Yii::app()->end();
    }


//    final public function actionGetCommonGroups($mid,$fid)
//    {
//        $manager = new ManagerGroupContacts(array('userId'=>$mid, 'groupId'=>ManagerContacts::NOT_USED));
//        echo json_encode($manager->findCommonGroups($fid));
//        Yii::app()->end();
//    }

    final public function actionTatetChat()
    {
        if(Yii::app()->client->id){

            $basePath = $_SERVER['SERVER_NAME'] != 'blog.rixetka.com' ? Yii::app()->params['protocol'].'://'.$_SERVER['SERVER_NAME'] : Yii::app()->params['protocol'].'://tatet.ua';

            $count =Yii::app()->db->createCommand()
                ->select('count(account_id)')->from('modules_chat_multi_accounts')
                ->where('user_id=:id', array(':id'=>Yii::app()->client->id)
                )->queryColumn()[0];
            if($count){
                $sp = 1; //support портала и магазинов
                $multi_accaunt = 1;
            }else{
                $sp = 0;
                $multi_accaunt = 0;
            }
            $pageURL = $_SERVER["REQUEST_URI"];
            $chatProfile = preg_match('/tatetChat/',$pageURL) ? 1 : 0;
            $script = '
            var tatetChat={ myId: '.Yii::app()->client->id.',
                            muacc: '.$multi_accaunt.',
                            sp: '.$sp.',
                            domenApp: "'.$basePath.'",
                            chatProfile: '.$chatProfile.',
                            isShop: 0
                          };
             ';
            Yii::app()->clientScript->registerScript("varApp", $script, CClientScript::POS_BEGIN);
            Yii::app()->clientScript->registerCssFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.css.scrollbar').'/jquery.jscrollpane.css'));
            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.scrollbar').'/jquery.jscrollpane.min.js'), CClientScript::POS_END );
            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.scrollbar').'/jquery.mousewheel.js'), CClientScript::POS_END );

            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/1.4.3/angular.min.js'), CClientScript::POS_END );
            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/1.4.3/i18n/angular-locale_ru.js'), CClientScript::POS_END );
            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular').'/1.4.3/angular-route.min.js'), CClientScript::POS_END );
            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular.angular-ui-bootstrap').'/0.13.3/ui-bootstrap-tpls.min.js'), CClientScript::POS_END );

            Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish( Yii::getPathOfAlias('webroot.lib.js.angular.apps.chat.scripts').'/chatApp.min.js'), CClientScript::POS_END );

            $this->render('chat', array());
        }else{
            throw new CHttpException(404);
        }
    }

    final public function actionUpdateStatusMsgs($accountId,$ids,$status)
    {
        $result = array('error'=>true, 'status'=>0);
        $mids = json_decode($ids);
        if($this->checkAccess($accountId)){
            if($r = ContactMsgs::updateStatusMsgs($accountId,$mids,$status)){
                $result['error'] = false;
                $result['status'] = 1;
                echo json_encode($result);
                Yii::app()->end();
            }

            echo json_encode($result);
            Yii::app()->end();
        }

    }

    final function actionSearchChatContact($search,$searchGroups = 0,$type = 0)
    {
        $data = null;
        switch($type){
            case 0://поиск юзеров в чате
                $data = $this->searchUserChat($search,$searchGroups);
                break;
            case 1: //поиск магазинов в чате
                $data = $this->searchShopChat($search);
                break;
        }

        echo json_encode($data);
        Yii::app()->end();

    }

    final function searchUserChat($search,$searchGroups)
    {
        $ids = [];
        $mSearchClients = new SearchClients();


        if( is_numeric($search) ) {
            $ids[] = $search;
        }elseif( !is_numeric($search) && preg_match('/@/', $search, $matches, PREG_OFFSET_CAPTURE) == FALSE){

            $result              = $mSearchClients->select(0, 20, $search);
            $ids                 = $result->getResult();
        }
//    elseif ( preg_match('/@/', $search, $matches, PREG_OFFSET_CAPTURE) == TRUE )
//        {
//            $main = Yii::app()->db->createCommand("SELECT name, id FROM modules_client WHERE email LIKE '{$search}%'")->queryAll();
//
//        }
        $criteria = new CDbCriteria();
        $criteria->condition = 'is_active = 1 AND is_public = 1';
        $criteria->addInCondition('id',$ids, 'AND');

        $sql = 'SELECT id,name,logo FROM modules_client WHERE '.$criteria->condition;
        $dataAlone = Yii::app()->db->createCommand($sql)->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')->queryAll(true, $criteria->params);
        foreach($dataAlone as $val){
            $val->type = 'alone';
        }
        $dataGroup = (int)$searchGroups ? GroupContact::search($search) : [];

        return array_merge($dataAlone,$dataGroup);
    }
    final function searchShopChat($search)
    {
        // IF(alias = '', domen, alias) as name

        if( is_numeric($search) ) {
            $data = Yii::app()->db->createCommand()
                ->select("shopid as id, IF(alias = '', domen, alias) as name")->from('sc_configs')
                ->where('shopid=:id', array(':id'=>$search))
                ->queryAll();
        }elseif( !is_numeric($search)){
            if(strlen($search) > 4){
                $data = Yii::app()->db->createCommand()
                    ->select("shopid as id, IF(alias = '', domen, alias) as name")->from('sc_configs')
                    ->where(array('or',array('like', 'domen', '%'.$search.'%'),array('like', 'alias', '%'.$search.'%')))
                    ->queryAll();
            }else{
                $data = [];
            }
        }
        foreach($data as &$val){
            $val['type'] = 'shop';
        }
        return $data;
    }

    //OK
    final function actionGetInfoChatContact($accountId,$contactId, $typeContact)
    {
        $data = null;
        $mcontacts = ManagerContacts::getInstance($accountId,$typeContact);
        echo json_encode($mcontacts->getInfoContact($contactId));
        Yii::app()->end();
    }

    //OK
    final function actionGetInfoChatContactMembers($members)
    {
        $members =json_decode($members);
        $data = Yii::app()->db->createCommand()
            ->select('id, name, logo ')
            ->from('modules_client')
            ->where(array('in', 'id', $members))
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')
            ->queryAll();
        echo json_encode($data);
        Yii::app()->end();
    }
//устарел, пока не используется нужно переделывать
    final function actionGetIdManagerShop($shopId)
    {
        $sql = <<<SQL
SELECT portal.id,portal.logo,portal.name FROM shops.sc_managers as shops
LEFT JOIN shops_portal.modules_client as portal
ON shops.shopid = portal.id_external AND shops.email = portal.email
WHERE shops.shopid = :shopid AND portal.is_active
ORDER BY portal.lastlogin DESC
SQL;
        $list =Yii::app()->db->createCommand($sql)
            ->bindParam(':shopid',$shopId,PDO::PARAM_INT)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')->queryAll();

        echo json_encode(array('list'=>$list,'error'=>false));
        Yii::app()->end();
    }

    final  function actionFindNewShopsInChat($accountId,$ids)
    {
        $ids = json_decode($ids);

        if($this->isExistsAccount($accountId))
        {
            $data = Yii::app()->db->createCommand()
                ->select('id, id_external,name, logo ')
                ->from('modules_client')
                ->where(array('AND', 'type=:type', array('in', 'id', $ids)), array(':type' => 'shop'))
                ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')
                ->queryAll();
            echo json_encode(array('list'=>$data));
            Yii::app()->end();
        }
    }

    public function actionUploadImgChat($id)
    {
        Yii::import("ext.EAjaxUpload.qqFileUploader");
        $folder = Yii::app()->getBasePath() . "/../uploads/portal/tmp/";
        if ( !is_dir($folder) )
        {
            mkdir($folder, 0777, true);
            chmod($folder, 0777);
        }
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
        $sizeLimit         = 2 * 1024 * 1024;
        $uploader          = new qqFileUploader( $allowedExtensions, $sizeLimit );
        $result            = $uploader->handleUpload($folder, true);

        $ih   = new CImageHandler();

        if ( preg_match('`^(.+)\.(.+)$`', $result['filename'], $m) )
        {
            $imgName      = $m[1];
            $imgExtension = $m[2];
        }
        else
        {
            $imgName      = $result['filename'];
            $imgExtension = 'jpg';
        }

        $name =  $id . '-' . crc32($imgName) . '.' . $imgExtension;
        $path = Yii::app()->getBasePath() . "/../uploads/portal/chat/";

        if ( !is_dir($path) )
        {
            mkdir($path, 0777, true);
            chmod($path, 0777);
        }

        $width  = 1500;
        $height = 1500;

        if($imgExtension != 'gif')
            $ih->load($folder . $result['filename'])->thumb($width, $height)->save($path . $name, false, 75);
        else{
//            $ih->load($folder . $result['filename'])->save($path . $name, false, 99);
            $tmpName = $folder . $result['filename'];
            $file = $path . $name;
            `\cp $tmpName $file`;
            unlink($tmpName);
        }


        @unlink($folder . $result['filename']);
        if($imgExtension != 'gif')
            ImageUploaderHelper::convert($path . $name,75);

        $result['filename'] = $name;
        $result['cid']      = $id;
        $result             = htmlspecialchars(json_encode($result), ENT_NOQUOTES);

        echo $result;
    }

    function actionGetMediaContent($url)
    {
        if($url)
        {
            $url = explode(' ',HTMLHelper::stripDefault(trim($url)))[0];
            $parser = new Parser($url);
            $media = $parser->getMeta(array('title','description','image','site','url','category'));
            $str = trim(preg_replace('`[^a-zа-я\d\s:/\-\.]+`usi', "\\1", $media['description']));
            $str = mb_strlen($str, 'UTF-8') <= 180 ? $str : mb_substr($str, 0, 180, 'UTF-8') . '...';
            $media['description'] = $str;
            echo json_encode($media);
            Yii::app()->end();
        }
    }

    function actionGetToolsChat()
    {
        echo json_encode(PModulesChatUserSettings::getSettings(Yii::app()->client->id));
        Yii::app()->end();
    }
    function actionChangeToolChat($idTool,$attributes)
    {
        $attr = json_decode($attributes,true);
        if(PModulesChatUserSettings::updateSetting($idTool,$attr, Yii::app()->client->id)){
            echo json_encode(array('error'=>false));
        }else{
            echo json_encode(array('error'=>true));
        }
        Yii::app()->end();
    }


    /*
     * если аакаунт существует значит он саппорт аакаунт
     * проверка закреплен ли под юзером этот аккаунт
     */
    //OK
    function isExistsAccount($accountId)
    {
        return Yii::app()->db->createCommand()
            ->select('account_id')->from('modules_chat_multi_accounts')
            ->where('user_id=:id AND account_id=:accId', array(':id'=>Yii::app()->client->id,':accId'=>$accountId)
            )->queryScalar() ? true : false;
    }
    //OK
    function checkAccess($accountId)
    {
        if($accountId == Yii::app()->client->id || $this->isExistsAccount($accountId))
//            Yii::app()->user->inRole(UserRole::ROLE_SHOPS_SUPPORT) ||
//            Yii::app()->user->inRole(UserRole::ROLE_ADMIN))
            return true;
        else
            return false;
    }
    function isSupport()
    {
        $clientInfo = (new PModulesClient())->getInfo(Yii::app()->client->id);
        return in_array($clientInfo->type, $this->support_type) ? true : false;

    }
    //OK
    function actionGetChatAccounts()
    {
        $data = array();
        $accounts = Yii::app()->db->createCommand()
            ->select('account_id')->from('modules_chat_multi_accounts')
            ->where('user_id=:id', array(':id'=>Yii::app()->client->id)
            )->queryAll();

        if(!empty($accounts)){
            foreach($accounts as $acc){
                $data[] = (new PModulesClient())->getInfo($acc['account_id'],true);
            }
        }else{
            $data[] = (new PModulesClient())->getInfo(Yii::app()->client->id,true);
        }

        echo json_encode($data);
        Yii::app()->end();
    }

    //OK
    final  function actionGetChatThreads($accountId)
    {
        $accountId = (int)$accountId ? : 0;
        if($accountId && $this->checkAccess($accountId)){
            $issupport = $this->isSupport();
            $threads = ManagerContacts::fetchThreads($accountId,$issupport);
            echo json_encode($threads);
            Yii::app()->end();
        }

    }
//OK
    function actionGetCountContacts($accountId,$which='all')
    {
        $accountId     = (int)$accountId ? : 0;
        if($this->checkAccess($accountId)){
            $count = ManagerContacts::getCountContacts($accountId,$which);
            echo json_encode(array('count'=>$count));
            Yii::app()->end();
        }
    }
    //OK
    function actionGetChatAccountContacts($accountId,$which='all')
    {
        $accountId     = (int)$accountId ? : 0;
        if($this->checkAccess($accountId)){
            $contacts = ManagerContacts::fetchContacts($accountId,$which);
            echo json_encode($contacts);
            Yii::app()->end();
        }

    }

    //OK
    function actionGetChatOneContact()
    {
        $accountId     = (int)Yii::app()->request->getParam('accountId', 0);
        $contactId     = (int)Yii::app()->request->getParam('contactId', 0);
        $typeContact = Yii::app()->request->getParam('contactType') ? : '';
        if($this->checkAccess($accountId)){
            $result = null;
            if($typeContact == ManagerContacts::TYPE_ALONE){
                $contact = AloneContact::getInstance($accountId,$contactId);
                if(!$contact->isSimple())
                    $result = $contact->format();
                else
                    $result = $contact->formatForSimple();
            }elseif($typeContact == ManagerContacts::TYPE_GROUP){
                $contact = new GroupContact();
                $result = $contact->getFormatContact($accountId,$contactId);
            }
            echo json_encode($result);
            Yii::app()->end();
        }

    }

    //OK
    final public function actionCreateAloneContact()
    {
        $invitedId = (int)Yii::app()->request->getParam('invitedId');
        $accountId = (int)Yii::app()->request->getParam('accountId');
        if($this->checkAccess($accountId)) {
            $mcontacts = new ManagerAloneContacts($accountId);
            echo json_encode($mcontacts->createContact($invitedId));
            Yii::app()->end();
        }

    }
    //OK получить контакт по id-шникам участников контакта, если такого контакта не существует создать болванку
    final public function actionGetContactByMembers()
    {
        $invitedId = (int)Yii::app()->request->getParam('invitedId');
        $accountId = (int)Yii::app()->request->getParam('accountId');

        $mcontacts = new ManagerAloneContacts($accountId);
        echo json_encode($mcontacts->getContactByMember($invitedId));
        Yii::app()->end();
    }

    //OK получить контакт(ы) по id-магазина и id-аккаунта нашего (for support), если контакта еще нет создать балванку
    final public function actionGetContactByShop()
    {
        $shopId = (int)Yii::app()->request->getParam('shopId');
        $accountId = (int)Yii::app()->request->getParam('accountId');
        $sql = <<<SQL
SELECT pmc.id FROM shops_portal.modules_client pmc
LEFT JOIN shops.sc_managers sm ON sm.shopid = pmc.id_external AND sm.status =1
WHERE pmc.id_external = :shopid
SQL;

        $menagers = Yii::app()->db->createCommand($sql)
            ->bindParam(':shopid',$shopId,PDO::PARAM_INT)->queryAll();
        if(!empty($menagers)){
            $mcontacts = new ManagerAloneContacts($accountId);
            $shopContacts = [];
            foreach($menagers as $m){
                $shopContacts[] = $mcontacts->getContactByMember($m['id']);
            }
            echo json_encode(array('error'=>false,'shopContacts'=>$shopContacts));

        }else{

            echo json_encode(array('error'=>true,'shopContacts'=>null));
        }
        Yii::app()->end();
    }

    //OK
    final public function actionUpdateStatusChatContact()
    {
        $contactId     = (int)Yii::app()->request->getParam('contactId', 0);
        $accountId     = (int)Yii::app()->request->getParam('accountId', 0);
        $status = Yii::app()->request->getParam('status','');
        $typeContact = Yii::app()->request->getParam('typeContact') ? : '';

        if($this->checkAccess($accountId)){
            $mcontacts = ManagerContacts::getInstance($accountId,$typeContact);
            echo json_encode($mcontacts->setStatusContact($contactId,$status));
            Yii::app()->end();
        }

    }

    //OK
    function actionGetAccountManagers()
    {
        $accountId     = (int)Yii::app()->request->getParam('accountId', 0);
        $issupport = $this->isExistsAccount($accountId);
        if( $issupport) {
            //AND ma.user_id <> :user
            $sql = <<<SQL
SELECT mc.id, mc.name
FROM modules_chat_multi_accounts ma
LEFT JOIN modules_client mc ON mc.id = ma.user_id
WHERE ma.account_id = :id
SQL;

            echo json_encode(Yii::app()->db->createCommand($sql)
                ->bindParam(':id',$accountId,PDO::PARAM_INT)
                ->queryAll());
            Yii::app()->end();
        }
    }
    //OK
    function actionGetSupports()
    {
        $supportAccounts = Yii::app()->db->cache(86400)->createCommand()
            ->select('id,id_external,logo,name')
            ->from('modules_client')
            ->where('type = :type',array(':type'=>'team'))
            //->where(array('in', 'type', array('team', 'shops_support')))
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')
            ->queryAll();
        foreach($supportAccounts as $k=>$acc)
        {
            if($managers = Yii::app()->db->cache(86400)->createCommand()
                ->select('m.id,m.id_external,m.logo,m.name')
                ->from('modules_chat_multi_accounts ma')
                ->join('modules_client m', 'm.id=ma.user_id')
                ->where('ma.account_id = :accId',array(':accId'=>$acc->id))
                ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'EntityClient')
                ->queryAll())
            {
                $acc->managers = $managers;
            }
            else
            {
                unset($supportAccounts[$k]);
            }
        }
        echo json_encode($supportAccounts);
        Yii::app()->end();

    }
//OK
    function actionGetChatContactMsgs()
    {
        $accountId     = (int)Yii::app()->request->getParam('accountId', 0);
        $contactId     = (int)Yii::app()->request->getParam('contactId', 0);
        $type_contact = Yii::app()->request->getParam('typeContact') ? : '';
        $period = Yii::app()->request->getParam('period') ? : 'default';

        $issupport = $this->isExistsAccount($accountId);
        if($accountId == Yii::app()->client->id || $issupport) {
            $mcontacts = ManagerContacts::getInstance($accountId,$type_contact);
            $result = $mcontacts->getContactMsgs($contactId, $period,$issupport);
            //если клиент чата из саппорта, то добавить информацию: кто из менеджеров отвечал на сообщения
            if($issupport && !$result['isblank'])
                $result['msgs'] = ManagerContacts::markSupportMsgs($accountId,$result['msgs']);
            echo json_encode($result);
            Yii::app()->end();
        }

    }

    /*
     * OK
     * $receivers is array = [ [id=>'1','type'=>'alone','isOnline'=>true],] информация о контактах получателях
     *
     */
    final function actionSendMsg($accountId,$receivers,$msg,$attach,$isinternal=0)
    {
        $receivers = json_decode($receivers);
        $attach = json_decode($attach,true);
        //является ли сообщение от саппорта
        $issupport = $this->isExistsAccount($accountId);
        if($accountId == Yii::app()->client->id || $issupport) {

            //является ли сообщение внутреним (только для саппорта)
            $isinternal = (int)$isinternal == 1 && $issupport ? true : false;
            echo json_encode(ManagerContacts::processMsg(Yii::app()->client->id,$accountId, $receivers, $msg, $attach,$issupport,$isinternal));
            Yii::app()->end();
        }

    }

    final function actionSetAnswered()
    {
        $accountId     = (int)Yii::app()->request->getParam('accountId', 0);
        $contactId     = (int)Yii::app()->request->getParam('contactId', 0);
        $type_contact = Yii::app()->request->getParam('contactType') ? : '';

        $issupport = $this->isExistsAccount($accountId);
        if($accountId == Yii::app()->client->id || $issupport) {

            json_encode(array('error' => ManagerContacts::setStatusAnsweredMsg($accountId,0,$contactId)));
            Yii::app()->end();
        }
    }

    /*
     * Копирование сообщений с одного контакта в другой (не для групповых контактов)
     * $msgIds - сообщения которые нужно скопировать (эти сообщения взяты с одного контакта)
     * $accountId - аккаунт с которого произведенно копирование (на случай если мультиаккаунтинг)
     * $copyForAccountId - аккаунт в который нужно скопировать (на основании этого аккаунта и $memberId будет сформирован контакт куда будут скопированны сообщения)
     * $copyForManagerId - манеджер для которого было произведенно это действие
     */
    final function actionCopyMsgs()
    {
        $msgIds     = json_decode(Yii::app()->request->getParam('msgIds'));
        $accountId = (int)Yii::app()->request->getParam('accountId', 0);
        $copyForAccountId     = (int)Yii::app()->request->getParam('copyForAccountId', 0);
        $copyForManagerId = (int)Yii::app()->request->getParam('copyForManagerId', 0);
        $error = false;
        if($this->isExistsAccount($accountId)) {

            if (!empty($msgIds) && $copyForAccountId && $copyForManagerId) {
                $info = ['error' => 0, 'info' => '','copies'=>[]];
                foreach ($msgIds as $k => &$v) {
                    $v = (int)$v;
                }
                $msgs = Yii::app()->db->createCommand()
                    ->select('*')
                    ->from('modules_client_messages')
                    ->where(array('in', 'id', $msgIds))
                    ->queryAll();

                if (!empty($msgs)) {
                    /*
                 * выделяем с сообщения id участника диалога($memberId) (сообщения скопированны с одного диалога поэтому берем с первого элемена массива)
                 * если $memberId больше нуля (если сообщения из группы то может быть нулем) создаем id контакта из $memberId и $copyForAccountId
                 * если id контакта не существует создаем новый контакт
                 * копируем сообщения в новый созданный контакт
                 */
                    $memberId = $msgs[0]['sender_id'] == $accountId ? (int)$msgs[0]['receiver_id'] : (int)$msgs[0]['sender_id'];
                    if ($memberId > 0) {
                        $copyToContactId = createHash($memberId, $copyForAccountId);
                        $copyToContact = AloneContact::getInstance($accountId, $copyToContactId);
                        if ($copyToContact->isSimple())
                            $copyToContact->create($copyForAccountId, $memberId, Contact::STATUS_ACCEPTED, true);
                        if (!$copyToContact->isSimple()) {
                            $copies = [];
                            $coder = new MessagesCoder();
                            foreach ($msgs as &$msg) {
                                unset($msg['id']);
                                $model = new PModulesClientMessages();
                                $model->attributes = $msg;
                                $model->thread_hash  = $copyToContactId;
                                $model->is_internal = 1;
                                $model->is_copy = 1;
                                $model->date_created = time();
                                //в скопированом сообщении меняем участника, чтоб скопированные сообщения относились к другому аккаунту
                                if((int)$model->sender_id == $accountId){
                                    $model->sender_id = $copyForAccountId;
                                }else{
                                    $model->receiver_id = $copyForAccountId;
                                }

                                if($model->save()){
                                    Yii::app()->db->createCommand()->insert('modules_chat_support_msg', array(
                                        'account_id'=>$copyForAccountId,
                                        'manager_id'=>$copyForManagerId,
                                        'msg_id'    =>$model->id
                                    ));
                                    $model->msg = $coder->decodeUserData($model->msg);
                                    $copies[] = $model->attributes;
                                }else{
                                    $error = true;
                                }
                            }
                            if (!$error){
                                //$supportMsgs[] = "({$msg['id']},$copyForAccountId,$copyForManagerId,{$msg['id']})";
                                $info['info'] = "Cообщения успешно скопированны";
                                $info['copies'] = $copies;
                $info['infoToCopy'] = array('accountCopy'=>$copyForAccountId,'managerCopy'=>$copyForManagerId,'memberCopy'=>$memberId,'contactCopy'=>createHash($memberId, $copyForAccountId));
                            }else{
                                $info['error'] = 1;
                                $info['info'] = "Ошибка 6.Обратитесть к администратору.";
                            }

                            $info['info'] = 'Ошибка 5.Обратитесть к администратору.';
                        } else {
                            $info['error'] = 1;
                            $info['info'] = 'Ошибка 4.Обратитесть к администратору.';
                        }

                    } else {
                        $info['error'] = 1;
                        $info['info'] = 'Ошибка 3.Обратитесть к администратору.';
                    }
                } else {
                    $info['error'] = 1;
                    $info['info'] = 'Ошибка 2.Обратитесть к администратору.';
                }

            } else {
                $info['error'] = 1;
                $info['info'] = 'Ошибка 1.Обратитесть к администратору.';
            }

            echo json_encode($info);
            Yii::app()->end();
        }

    }


    final public function actionGetNewMsgs()
    {
        $info = [];
        $accounts = Yii::app()->db->createCommand()
            ->select('account_id')->from('modules_chat_multi_accounts')
            ->where('user_id=:id', array(':id'=>Yii::app()->client->id)
            )->queryAll();
        if(!empty($accounts)){

            foreach($accounts as $acc){
                $info[] = ['accountId'=>$acc['account_id'], 'newmsgs'=>ManagerContacts::getNewMsgs($acc['account_id'])];
            }
        }else{
            $info[] = ['accountId'=>Yii::app()->client->id, 'newmsgs'=>ManagerContacts::getNewMsgs(Yii::app()->client->id)];
        }
        echo json_encode($info);
        Yii::app()->end();
    }


} 