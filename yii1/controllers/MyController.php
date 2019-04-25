<?php
/**
 * User: mike
 * Date: 01.12.14
 * Time: 17:30
 */
Yii::import('application.controllers.IPageTitle');
Yii::import('application.modules.profile.components.ClientChat');
Yii::import('application.modules.profile.components.ShortUrls');

class MyController extends ProfileAccessController implements IPageTitle {

    use ClientChat;
    use ShortUrls;

	const PROFILE_TIME_FORMAT = 'd.m.Y H:i';
    const AUTH_PORTAL         = '/profile/auth/';


	function beforeAction($action)
	{
		// Для пользователей с типом team(команда портала) доступна только одна страница
		if ( $this->client->type === PModulesClient::TYPE_TEAM && $this->action->id !== 'index' && !$this->isOwner )
		{
			Yii::app()->request->redirect("/profile/{$this->client->id}/");
		}

		if ( $this->client )
		{
			$this->client->lastvisitText = $this->client->lastvisit ? Date::getWithDayName($this->client->lastvisit) : null;
			$this->client->status   = PModulesClient::clientStatus($this->clientId);
			$this->client->addedText     = $this->client->getPeriodAdded();
			$this->client->services      = PModulesClientServices::getUserServices($this->clientId);
			if ( Yii::app()->params['enabled_chat']  )
			{
				$this->client->countContacts['all']           = ManagerContacts::getCountContacts($this->clientId, Contact::CONTACTS_WHICH_ALL);// PModulesClientRelations::getCount($this->clientId, PModulesClientRelations::CONTACTS_ALL);
				$this->client->countContacts['own_request']   = ManagerContacts::getCountContacts($this->clientId, Contact::CONTACTS_WHICH_OWN_REQUESTS); //PModulesClientRelations::getCount($this->clientId, PModulesClientRelations::CONTACTS_OWN_REQUESTS);
				$this->client->countContacts['other_request'] = ManagerContacts::getCountContacts($this->clientId, Contact::CONTACTS_WHICH_OTHER_REQUESTS); //PModulesClientRelations::getCount($this->clientId, PModulesClientRelations::CONTACTS_OTHER_REQUESTS);
				$this->client->countContacts['notviewed_msg'] = PModulesClientMessages::getCountNotViewedMsg($this->clientId);
			}
			$this->client->isIE9andLower = isIE9andLower();
		}
		return parent::beforeAction($action);
	}

	function setTitle($msg)
	{
		Yii::app()->siteConfig->metatitle = $msg;
	}

	function setDescription($msg)
	{
		Yii::app()->siteConfig->metadesc = $msg;
	}

	function actionForbidden()
	{
		$pageTitle = 'У Вас нет доступа к запрашиваемой странице';
		$this->setTitle($pageTitle);
		$this->render('forbidden', compact('pageTitle'));
	}

	function actionNotFound()
	{
		$pageTitle = 'Профиль пользователя не найден';
		$this->setTitle($pageTitle);
		$this->render('notFound', compact('pageTitle'));
	}

	function actionIndex()
	{
		if ( $this->client->type === PModulesClient::TYPE_TEAM && !$this->isOwner )
		{
			$this->indexTeam();
			Yii::app()->end();
		}
       
        //main setting info
        $mktime = mktime(0, 0, 0);
        $siteId = self::$portalId;
        $userId = $clientId = !Yii::app()->client->isGuest ? Yii::app()->client->id : 0;
        $isNoFollow = false;

		// Comment block
		$event   = Yii::app()->request->getParam('event', null);
		$orderId = (int) Yii::app()->request->getParam('orderid', null);
		$entity  = (int) Yii::app()->request->getParam('entity', null);

		$feedForms = '';
		if ( $event && in_array($event, ['comment_price', 'comment_shop']) && $orderId )
		{
			// TODO check is owner
			$feedForms = $this->getFeedForms($orderId, $entity, $event, $userId);
		}

        $pageTitle = $this->isOwner ? 'Добро пожаловать, ' . Yii::app()->client->info->name : 'Профиль пользователя ' . $this->client->name;
        $this->setTitle($pageTitle);
        $this->setDescription(!$this->isOwner ? "Профиль пользователя {$this->client->name} на портале " . Yii::app()->siteConfig->title : '');

        //User rating statistic
        $lastPlace = Yii::app()->db->createCommand("SELECT MAX(position_rating) FROM modules_client")->queryScalar();
        $userRating = PModulesClient::model()->findByPk($userId)->position_rating;
        $todayNum = PModulesClient::model()->findByPk($userId)->old_position_rating;
        $todayRating = $todayNum == 0 ? 0 : $todayNum - $userRating;
        if($todayRating < 0)
             $minusRatingFlag = 'minus';
        $mainStatRating = [];
        if($userRating > 100) {
            $mainStatRating['top'] = 100;
            $mainStatRating['leftTop'] = $userRating - 100;
        }elseif($userRating < 100 && $userRating > 50){
            $mainStatRating['top'] = 50;
            $mainStatRating['leftTop'] = $userRating - 50;
        }elseif($userRating < 50 && $userRating > 10){
            $mainStatRating['top'] = 10;
            $mainStatRating['leftTop'] = $userRating - 10;
        }elseif($userRating < 10){
            $mainStatRating['top'] = 1;
            $mainStatRating['leftTop'] = $userRating - 1;
        }
        if($userRating === 1){
            $mainStatRating['top1'] = 1;
        }

        //get limit list (3 users)
        $userStat = self::getUserStatistic($userRating, $lastPlace);

        //generate rating for user
            $partsum = floor($userRating * 360 / $lastPlace / 3);
            if($partsum <= 1) {
                $step = 3;
                if($userRating == 1) $step = 0;
            }else{
                $step = $partsum * 3;
            }
            $partStat = 360 - $step;
            $totalStat = 360;


        //Bonuses statistic
        $totalBonuses = PModulesClientBonuses::getSumById($userId);
        $allBonuses = PModulesBonusesItem::getList(self::$portalId);
        $bonusesToday = Yii::app()->db->createCommand("SELECT sum(amount) FROM modules_client_bonuses WHERE user_id = {$userId} AND date_added > {$mktime}")->queryScalar();
        $listBonuses = self::getLimitBonusesList($siteId, $totalBonuses);
        $mainStatBonuses = self::getBonusStatistic($listBonuses, $allBonuses, $totalBonuses);

		$latest = [];
		if ( !$this->isOwner )
		{
			if ( $latestReview = PModulesComment::getReviewsToUser($this->clientId, self::$portalId, !$this->isOwner, 1) )
				$latest['review'] = $latestReview[0];

			// Get last feed
			if ( $latestFeed = PModulesComment::getLatestByUser(self::$portalId, PModulesComment::TYPE_PRICE, $this->clientId, !$this->isOwner) )
				$latest['feedPrice'] = $latestFeed;

			// Get user's activity
			if ( $latestFeed = PModulesComment::getLatestByUser(self::$portalId, PModulesComment::TYPE_SHOP, $this->clientId, !$this->isOwner) )
				$latest['feedShop'] = $latestFeed;

            $activitiesCounts = PModulesComment::activitiesCounts($this->clientId, self::$portalId);
		}

        if( $activitiesCounts['reviews'] == 0 && $activitiesCounts['feedPrice'] ==0 && $activitiesCounts['feedShop'] == 0 && $activitiesCounts['favorites'] == 0 )
            $isNoFollow = true;

		$this->render('index', compact('feedForms', 'pageTitle', 'latest', 'isNoFollow', 'userRating', 'lastPlace', 'partStat', 'totalStat',
            'totalBonuses', 'bonusesToday', 'userStat', 'mainStatBonuses', 'mainStatRating', 'todayRating', 'minusRatingFlag'));
	}

    public function getUserStatistic($userRating, $lastPlace){

        if($userRating <= 1){
            $betweenStart = 1;
            $betweenEnd = $userRating + 2;
        }elseif($userRating == $lastPlace){
            $betweenStart = $userRating - 2;
            $betweenEnd = $userRating;
        }else{
            $betweenStart = $userRating - 1;
            $betweenEnd = $userRating + 1;
        }

        $userStat = Yii::app()->db->createCommand("SELECT tt.id, tt.position_rating, us.name
                                                    FROM modules_client tt
                                                    LEFT JOIN modules_client_activity mca ON mca.client_id = tt.id
                                                    LEFT JOIN modules_client us ON us.id = tt.id
                                                    WHERE tt.type = 'customer' AND tt.is_active = 1 AND tt.is_public = 1 AND tt.position_rating BETWEEN {$betweenStart} AND {$betweenEnd}
                                                    ORDER BY position_rating LIMIT 3")
            ->queryAll();
        return $userStat;
    }

    public function getLimitBonusesList($siteId, $totalBonuses){
        $listBonuses = Yii::app()->db->createCommand("
                              SELECT it.price, nm.name
                              FROM modules_bonuses_item it
                              LEFT JOIN sc_item nm ON nm.id = it.item_id
                              WHERE it.active = 1 AND it.site_id = {$siteId} AND it.price <= {$totalBonuses}")
            ->queryAll();
        return $listBonuses;
    }

    public function getBonusStatistic($listBonuses, $allBonuses, $totalBonuses){
        $mainStatBonuses = [];
        $mainStatBonuses['nameBonus'] = 'Бонусы пока не доступны';
        $mainStatBonuses['sumPart'] = 0;
        $currentPriceBonus = array_reverse($listBonuses, true);

        foreach($currentPriceBonus as $currPrice) {
            if ($currPrice['price'] < $totalBonuses) {
                $mainStatBonuses['promBonus'] = $currPrice['price'];
                $mainStatBonuses['nameBonus'] = $currPrice['name'];
                $mainStatBonuses['partBonus'] = $totalBonuses;
                break;
            }
        }

        foreach($allBonuses as $allList){
            if($totalBonuses < $allList['price_bonuses']){
                $mainStatBonuses['lastMaxBonusPrice'] = $allList['price_bonuses'];
                $mainStatBonuses['sumPart'] = $allList['price_bonuses'] - $totalBonuses;
                $mainStatBonuses['partBonus'] = $allList['price_bonuses'] - $mainStatBonuses['sumPart'];
                break;
            }
            else{
                $mainStatBonuses['lastMaxBonusPrice'] = $mainStatBonuses['promBonus'] ;
            }
        }
        return $mainStatBonuses;
    }

	private function indexTeam()
	{
		$pageTitle = $this->client->name;
		$this->setTitle($pageTitle);
		$this->setDescription("Профиль пользователя {$this->client->name} на портале " . Yii::app()->siteConfig->title);

		$this->render('indexTeam', compact('pageTitle'));
	}

	private function getFeedForms($orderId, $entity, $event, $userId)
	{
		$shop   = (int) Yii::app()->dbShop->createCommand("SELECT shopid FROM sc_orders WHERE id = $orderId")->queryScalar();
		$pricesRaw = Yii::app()->dbShop->createCommand("SELECT GROUP_CONCAT(iid) FROM sc_orderitems WHERE orderid = $orderId")->queryScalar();
		$pricesRaw = explode(',', $pricesRaw);

		if ( !$shop || !$pricesRaw )
			return '';

		$prices = [];
		foreach( $pricesRaw as $id )
		{
			$info = (new ScItem)->get($id);
			$prices[] = [
				'id'   => $id,
				'name' => $info->name,
				'link' => Link::hrefPrice($id, $info->alias),
				'img'  => Link::hrefImageThumb($info->shopid, ( new ScItem )->getFirstImage($id)),
			];
		}

		$shop               = ( new ScConfigs )->getShopInfo($shop);
		$typeShow           = preg_match('`comment_(\w+)`', $event, $m) ? $m[1] : null;
		$showVideoBonusHint = self::$portalId == PORTAL_UA;
		$userId             = (int) $userId;
		$countUserMailing   = PModulesMailLog::countByClient((int) $userId);
		$name               = Yii::app()->client->info->name;

		return $this->renderPartial('addFeed', compact('shop', 'prices', 'entity', 'typeShow', 'showVideoBonusHint',
			'countUserMailing', 'name', 'userId'), true);
	}

    public function ListBreadcrumbs()
    {
        return [
            ['link' => '/profiles/', 'name' => 'Активные пользователи']
        ];

    }

	function actionBonuses()
	{
		$userId = Yii::app()->client->id;
        $bonusesRequest =  Yii::app()->db->createCommand('SELECT * FROM modules_bonuses_request WHERE user_id = :uId ORDER BY id DESC')->bindParam(":uId",$userId,PDO::PARAM_STR)->queryAll();
        foreach($bonusesRequest as &$items){
                $amount = PModulesBonusesItem::model()->findByAttributes(array('item_id' => $items['bonuses_item_id']));
                $bonusName = ScItem::model()->findByAttributes(array('id' => $items['bonuses_item_id']));
                $items['nameBonus'] = $bonusName['name'];
                $items['amount'] = $amount['price_bonuses'];
            }

		// Get messages info
		$typesBonuses = PModulesClientBonusesTypes::getAll();

		foreach ( $typesBonuses as $_typesBonuses )
		{
			$typesBonusesAll[$_typesBonuses['id']] = $_typesBonuses['title'];
		}

		if ( $bonuses = PModulesClientBonuses::getAllById($userId) )
			foreach ( $bonuses as &$bonus )
				$this->addBonusInfo($bonus);

        $totalBonuses = PModulesClientBonuses::getSumById($userId);

		if ( $notEnrolledOrders = PModulesClientBonuses::getNotEnrolledOrders($userId) )
			foreach ( $notEnrolledOrders as &$order )
				$this->preparePurchaseBonus($order);

		$this->setTitle('Мои бонусы. ' . Yii::app()->client->info->name);

		$this->render('bonuses', compact('bonuses', 'totalBonuses', 'links', 'notEnrolledOrders', 'bonusesRequest', 'amount'));
	}

	private function addBonusInfo(&$bonus)
    {
		$bonus['link'] = "";

		if ( in_array($bonus['type'], ['comment_price', 'comment_shop', 'item_review']) )
		{
			if ( $comment = ModulesComment::model()->findByPk((int) $bonus['id_entity']) )
			{
				if ( $comment['type'] == 'price' )
				{
					$priceInfo     = ScItem::model()->findByPk($comment['entity_id']);
					$bonus['link'] = Link::hrefPrice($comment['entity_id'], $priceInfo ? $priceInfo->alias : null) . '#comment_' . $comment['id'];
					$bonus['name'] = "Отзыв на товаре {$priceInfo->name}";

				}
				elseif ( $comment['type'] == 'price_review' )
				{
					$priceInfo     = ScItem::model()->findByPk($comment['entity_id']);
					// TODO set link to review
					$bonus['link'] = Link::hrefPrice($comment['entity_id'], $priceInfo ? $priceInfo->alias : null);
					$bonus['name'] = "Обзор товара {$priceInfo->name}";
				}
				elseif ( $comment['type'] == 'shop' )
				{
					$shopInfo = ( new ScConfigs )->getShopInfo($comment['entity_id']);
					$bonus['link'] = Link::hrefShop($comment['entity_id'], $shopInfo ? $shopInfo->alias : null);
					$bonus['name'] = "Отзыв о магазине {$shopInfo->name}";
				}
			}
		}
		elseif ( $bonus['type'] == 'purchase' )
		{
			$this->preparePurchaseBonus($bonus);
		}
		elseif ( $bonus['type'] == 'bug_report' )
		{
			$bonus['name'] = "Сообщение об ошибке";
		}
		elseif ( $bonus['type'] == 'auth_bonus' )
		{
			$bonus['name'] = "Бонус за регистрацию";
		}
	}

	private function preparePurchaseBonus(&$bonus)
	{
		$orderInfo    = PScOrders::model()->findByPk($bonus['id_entity']);
		$innerOrderId = $orderInfo ? "#{$orderInfo->shopindex}" : '';
		$shopInfo     = ( new ScConfigs )->getShopInfo($orderInfo->shopid);

		$bonus['link'] = "/profile/my/orders/";
		$bonus['name'] = "Заказ $innerOrderId в магазине {$shopInfo->name}";
	}

	function actionBonusesItems()
	{
        $listBonusesById = [];
        $listBonuses = PModulesBonusesItem::getList(self::$portalId);
        $bonusesIds = ArrayHelper::get_array_column($listBonuses, 'item_id');

        $productsList = PriceEntity::getPreparedList($bonusesIds, self::$portalId, self::$portalLocale);
        $countryCode = ModulesSites::model()->findByPk(self::$portalId)->code;

        foreach($productsList as $key => $product)
        {
            if ( !ScItem::isActive($product["id"], $countryCode)
				|| !(new ScConfigs)->isActive($product["shopid"], $countryCode, self::$siteRegion) ) {
                unset($productsList[$key]);
            }
        }
        foreach($listBonuses as $bonus)
        {

            $bonus['date_end'] = Date::format($bonus['date_end']);
            $listBonusesById[$bonus['item_id']] = $bonus;
        }

        $this->setTitle('Товары, участвующие в бонусной программе. ');

		$this->render('bonusesItems', ['productsList' => $productsList, 'listBonusesById' => $listBonusesById]);
	}

	function actionOrders()
	{
		$clientId = Yii::app()->client->id;
		$orders   = $this->getOrdersList($clientId, self::$portalId);

		Request::getBackURL();
		$currency = self::getCurrency();
		$this->setTitle('Список заказов. ' . Yii::app()->client->info->name);

		$this->render('orders', compact('orders', 'currency', 'clientId'));
	}

    function actionMessages()
{
    $this->setTitle('Мои Сообщения. ' . Yii::app()->client->info->name);

    $this->render('messages', compact('messages', 'currency'));

}

    function actionNew_message()
    {
        $this->setTitle('Новое сообщение. ' . Yii::app()->client->info->name);

        $this->render('new_message', compact('new_message', 'currency'));

    }

    function actionDialogs()
{
    $this->setTitle('Мои Сообщения. ' . Yii::app()->client->info->name);

    $this->render('dialogs', compact('dialogs', 'currency'));

}

    function actionAdd_contact()
    {
        $this->setTitle('Добавить контакт. ' . Yii::app()->client->info->name);

        $this->render('add_contact', compact('add_contact', 'currency'));

    }

    function actionContacts()
    {
        $this->setTitle('Мои Контакты. ' . Yii::app()->client->info->name);

        $this->render('contacts', compact('contacts', 'currency'));

    }

    function actionRequests()
    {
        $this->setTitle('Мои Контакты. ' . Yii::app()->client->info->name);

        $this->render('requests', compact('requests', 'currency'));

    }



	const LIMIT_PRODUCTS_HISTORY = 100;
    const LIMIT_PRODUCTS_FAVORITE = 100;

	function actionProductsHistory()
	{
		$clientId = Yii::app()->client->id;
		$ids      = [];

		// TODO set DISTINCT
		$list = PModulesClientProducts::model()->findAllByAttributes(["client_id" => $clientId, 'site_id' => self::$portalId],
			['order' => 'id DESC', 'limit' => self::LIMIT_PRODUCTS_HISTORY]);

		$time = [];
		foreach ( $list as $_ )
		{
			$ids[$_->entity_id] = $_->entity_id;
			$time[$_->entity_id] = Date::format($_->date_created);
		}

		if ( $ids )
			$list = PriceEntity::getPreparedList($ids, self::$portalId, self::$portalLocale, false, false);

        $activeItemsIds = $this->_getItemIdsActive($list);

		foreach($list as &$item)
			$item['date'] = $time[$item['id']];

		$this->setTitle('История просмотра товаров');

		$this->render('productsHistory', ['productsList' => $list, 'activeProductsIds' => $activeItemsIds]);
	}

	public function actionSettings()
	{
		$clientId = Yii::app()->client->id;

        $this->setTitle('Настройки');
		$resultSave = null;

		if ( Yii::app()->request->isPostRequest )
		{
            // $gender = array('-'=>'-','male'=>'Мужской','female'=>'Женский');
			$name            = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
			$lastName        = $this->sanitizeName('last_name', EntityClient::LIMIT_LAST_NAME);
			$firstName       = $this->sanitizeName('first_name', EntityClient::LIMIT_FIRST_NAME);
			$middleName      = $this->sanitizeName('middle_name', EntityClient::LIMIT_MIDDLE_NAME);
			$isNotifyComment = $_POST['is_notify'] ? 1 : 0;
            $isNotifyNewContact = $_POST['is_notify_chat_contact'] ? 1 : 0;
            $isNotifyNewMsg = $_POST['is_notify_chat_msg'] ? 1 : 0;
			$dateBirth       = $_POST['date_birth'] ? explode('/', strip_tags($_POST['date_birth'])) : [];

			$gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
			$gender = $gender ? : '';

            $phone = mb_substr($_POST['phone'], 0, EntityClient::LIMIT_PHONE, "UTF-8");
			$phone = preg_replace('`[^тел\.\-\+\(\)0-9]`iu', '', $phone);

			$model                    = ModulesClient::model()->findByPk($clientId);
			$model->last_name         = $lastName ? $lastName : '';
			$model->first_name        = $firstName ? $firstName : '';
			$model->middle_name       = $middleName ? $middleName : '';
			$model->phone             = $phone ? $phone : '';
			$model->is_notify_comment = $isNotifyComment;

			if ( $model->name !== $name )
			{
				// Log name change
				PModulesClientNameLog::saveInfo($clientId, $model->name);
			}

			$model->name = $name;

			if($model->save())
            {
                if($gender || !empty($dateBirth))
                {
                    $gender = $_POST['sex'];
                    if(PModulesClientInfo::model()->saveInfoClient($model->id, array('gender'=>$gender, 'dateBirth'=>$dateBirth,'notify_chat_contact'=>$isNotifyNewContact,'notify_chat_msg' => $isNotifyNewMsg)))
                        $resultSave = ['class' => 'alert-danger',  'msg' => "При сохранении произошла ошибка. Попробуйте, пожалуйста, позже"];
                }

                $resultSave = ['class' => 'alert-success', 'msg' => "Данные успешно сохранены. <br>Ваши контактные данные будут использоваться при оформлении заказа."];
            }
            else
            {
                $resultSave = ['class' => 'alert-danger',  'msg' => "При сохранении произошла ошибка. Попробуйте, пожалуйста, позже"];
            }
        }

        $data = PModulesClientInfo::model()->findByAttributes(array('user_id'=>$clientId));

		$this->render('settings', compact('data', 'resultSave', 'gender'));
	}

	private function sanitizeName($varName, $limit)
	{
		if ( !isset($_POST[$varName]) )
			return null;

		$lastName = mb_substr($_POST[$varName], 0, $limit, "UTF-8");
		return trim(preg_replace('`[^a-zа-я\'\"їіє"-\s]`iu', '', $lastName));
	}


	const IMG_CLIENT_PREFIX = 'prof-';

	/**
	 * TODO создать отдельный хелпер для загрузки картинок; склеить с аналогичными
	 * @param     $id
	 * @param int $index
	 */
	public function actionUpload($id, $index = 0)
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

		$name = self::IMG_CLIENT_PREFIX . $id . '-' . crc32($imgName) . '.' . $imgExtension;
		$path = Yii::app()->getBasePath() . "/../uploads/portal/client/";

		if ( !is_dir($path) )
		{
			mkdir($path, 0777, true);
			chmod($path, 0777);
		}

		$width  = EntityClient::IMG_WIDTH_LIMIT;
		$height = EntityClient::IMG_HEIGHT_LIMIT;

		$ih->load($folder . $result['filename'])->thumb($width, $height)->save($path . $name, false, 99);
		@unlink($folder . $result['filename']);

		$result['filename'] = $name;
		$result['cid']      = $id;
		$result             = htmlspecialchars(json_encode($result), ENT_NOQUOTES);

		if ( $id > 0 )
		{
			$item       = ModulesClient::model()->findByPk($id);
			$item->logo = $name;
			$item->save(true, ['logo']);
		}
		echo $result;
	}


    function actionProductsFavorite()
	{
		$ids      = [];
		$list     = PModulesClientProducts::model()->findAllByAttributes(["client_id" => $this->clientId, 'site_id' => self::$portalId],
			['condition' => "type = 'favourite'", 'order' => 'id DESC', 'limit' => self::LIMIT_PRODUCTS_FAVORITE]);

		foreach ( $list as $_ )
		{
			$ids[$_->entity_id] = $_->entity_id;
		}

		if ( $ids )
		{
			$list = PriceEntity::getPreparedList($ids, self::$portalId, self::$portalLocale, false, false);
		}

        $activeItemsIds = $this->_getItemIdsActive($list);

		$this->setTitle(!$this->isOwner ? "Избранные товары пользователя {$this->client->name}" : 'Избранные товары');
		$this->setDescription(!$this->isOwner ? "Избранные товары пользователя {$this->client->name} на портале " . Yii::app()->siteConfig->title
			: 'Избранные товары');

		$this->render('favoriteProducts', ['productsList' => $list, 'activeProductsIds' => $activeItemsIds]);
	}

	private
	function getOrdersList($clientId, $siteId)
	{
		$criteria = new CDbCriteria();
		$criteria->order = 'id DESC';

		$clientOrders = PScOrders::model()->findAllByAttributes(['client_id' => $clientId, 'site_id' => $siteId], $criteria);
		$orders       = [];

		foreach ( $clientOrders as $orderInfo )
			$orders[] = $this->getOrderInfo((int) $orderInfo['id']);

//		$ids = [54258, 54223];
//		foreach ( $ids as $id )
//		{
//			$orders[] = $this->getOrderInfo($id);
//		}

		return $orders;
	}

	// TODO вынести
	private function getOrderInfo($orderId)
	{
		$sql = "SELECT o.id, o.number, o.shopid, remote_ip, o.name, o.status,
				o.phone, delivery_address,
				dateadded, totalsum , o.deliverysum, GROUP_CONCAT(i.iid) items
		 	FROM sc_orders o
		 	LEFT JOIN sc_orderitems i ON i.orderid =  o.id
		 	WHERE o.id = $orderId
		 	GROUP BY o.id DESC";

		$orderItemsSql = "SELECT `sum`, qty, iid FROM sc_orderitems WHERE orderid = $orderId";
		$orderItems    = Yii::app()->dbShop->createCommand($orderItemsSql)->queryAll();

		$item = Yii::app()->dbShop->createCommand($sql)->queryRow();

		// Statuses
		$orderStatusesRaw = Yii::app()->dbShop->createCommand("SELECT * FROM sc_orderstatuses")->queryAll();
		$orderStatuses    = [];
		$statuses         = [];

        $shopCountry  = Yii::app()->db->createCommand("SELECT country FROM shops_targeting WHERE shop_id = {$item['shopid']}")->queryScalar();


        $mainCurrency = EntityCurrency::getCountryMainCurrency($shopCountry);
        $mCurrencies  = new ModulesCurrencies();
        $currency['main'] 	  = $mCurrencies->get($mainCurrency);

		foreach ( $orderStatusesRaw as $status )
			$orderStatuses[$status['id']] = $status;

		$item['shop']     = ( new ScConfigs )->getShopInfo((int) $item['shopid']);
		$item['status']   = $orderStatuses[$item['status']]['name'];

		$item['totalsum'] = Price::convertPriceToNaturalGen2(
			array('price' => $item['totalsum'] + $item['deliverysum'], 'shopid' => $item['shopid']),
			$currency['main'],
			$shopCountry
		);

		if ( $item['deliverysum'] )
		{
			$item['deliverysum'] = Price::convertPriceToNaturalGen2(
				array('price' => $item['deliverysum'], 'shopid' => $item['shopid']),
				$currency['main'],
				$shopCountry
			);
			$item['deliverysum'] = Price::preparePrice($item['deliverysum'], $currency['main']);
		}

        $item['totalsum'] = Price::preparePrice($item['totalsum'], $currency['main']);
        $userId = Yii::app()->client->id;

		if ( !empty($orderItems) )
		{
			$itemsPrepared = [];
			foreach ( $orderItems as $_item )

			{
				$itemInfo = ScItem::model()->findByPk($_item['iid']);

				if ( $itemInfo )
				{
                    $currency['main2'] = Price::convertPriceToNaturalGen2(array('price' => $_item['sum'], 'shopid' => $item['shopid']), $currency['main'], $shopCountry);

					$itemInfo = [
						'id'     => $itemInfo['id'],
						'name'   => $itemInfo['name'],
						'link'   => Link::hrefPrice($itemInfo['id'], $itemInfo['alias']),
						'images' => ( new ScItem )->getImages($itemInfo['id']),
						'sum'	 =>  Price::preparePrice($currency['main2'], $currency['main']),
						'qty'	 => $_item['qty'],
					];

                    $priceComment = PriceEntity::priceCommentForOrders($itemInfo['id'], $userId);
				}
				$itemsPrepared[] = $itemInfo;
			}

			$item['items'] = $itemsPrepared;
		}

		$item['dateadded'] = date(self::PROFILE_TIME_FORMAT, strtotime($item['dateadded']));

        if( $priceComment != false )
            $item['commentOrder'] = $priceComment;
        else
            $item['commentOrder'] = false;

        if(strtotime($item['dateadded']) < strtotime('-3 days'))
            $item['3daysOrder'] = true;
        else
            $item['3daysOrder'] = false;

		$statuses[$item['status']]++;

		return $item;
	}



	function actionFeed()
	{
		preg_match('`/type/(\w+)/?$`', Yii::app()->request->getRequestUri(), $m);

		$type = $m[1];

		$moderateTypeText = PModulesComment::getModerateTypeText($type);

		if ( !$moderateTypeText )
			throw new CHttpException(404);

		$siteId            = Controller::$portalId;
		$list              = PModulesComment::getByUserList($siteId, $type, $this->clientId, !$this->isOwner);
		$bonusesArticleUrl = ModulesContent::getBonusesArticleUrl(self::$portalId);

		switch( $type )
		{
			case 'price' :
				$this->setTitle(!$this->isOwner ? "Отзывы пользователя {$this->client->name} о товарах" : 'Мои отзывы о товарах');
				$this->setDescription(!$this->isOwner ? "Отзывы пользователя {$this->client->name} о товарах на портале " . Yii::app()->siteConfig->title : '');
				break;

			case 'shop'  :
				$this->setTitle(!$this->isOwner ? "Отзывы пользователя {$this->client->name} о магазинах" : 'Мои отзывы о магазинах');
				$this->setDescription(!$this->isOwner ? "Отзывы пользователя {$this->client->name} о магазинах на портале " . Yii::app()->siteConfig->title : '');
				break;
		}

		$this->render('feed', compact('list', 'moderateTypeText', 'type', 'bonusesArticleUrl'));
	}

	function actionCategories()
	{
		$clientId = (int) Yii::app()->client->id;
		$list     = PModulesClientProducts::getUserCategories($clientId, self::$portalId, self::$portalLocale);

		$this->setTitle('Список посещаемых категорий');
		$this->render('categories', compact('list'));
	}

	function actionReplies()
	{
		$clientId     = (int) Yii::app()->client->id;
		$listOnPrices = PModulesComment::getNewRepliesToUser(self::$portalId, PModulesComment::TYPE_PRICE, $clientId);
		$listOnShops  = PModulesComment::getNewRepliesToUser(self::$portalId, PModulesComment::TYPE_SHOP, $clientId);

		$this->setTitle('Комментарии на Ваши отзывы');
		$highlightNew = 1;

		$this->render('feedReplies', compact('listOnPrices', 'listOnShops', 'highlightNew'));

	}

    function actionReviews()
    {
		$list              = PModulesComment::getReviewsToUser($this->clientId, self::$portalId, !$this->isOwner);
		$bonusesArticleUrl = ModulesContent::getBonusesArticleUrl(self::$portalId);
		$pageTitle		   = $this->isOwner ? 'Мои обзоры' : 'Обзоры пользователя';

		$this->setTitle($pageTitle . ' ' . $this->client->name);
		$this->setDescription(!$this->isOwner ? "Обзоры товаров пользователя {$this->client->name} на портале " . Yii::app()->siteConfig->title : '');

		$this->render('userReviews', compact('list', 'bonusesArticleUrl', 'pageTitle'));
    }

    function actionVideoReviews()
    {
        $list  = '';
        $pageTitle = 'Мои видеообзоры';
        $this->setTitle($pageTitle);
        $this->setDescription("Видеообзоры пользователя {$this->client->name} на портале ");

        $this->render('userVideoReviews', compact('pageTitle','list'));
    }

    function actionAddVideoReview()
    {
        $pageTitle = 'Добавить видеообзор';
		$this->setTitle($pageTitle);
        $this->render('addVideoReview', compact('pageTitle'));
    }
    function actionSaveVideoReview()
    {
		$response  = array('error' => false, 'msg' => 'Видеообзор успешно добавлен.');
		$id        = (int) Yii::app()->request->getPost('id', 0);
		$video_id  = 0;
		$entity_id = 0;

		$entityUrl        = strip_tags(trim(Yii::app()->request->getPost('entity_url', '')));
		$entityName       = strip_tags(trim(Yii::app()->request->getPost('entity_name', '')));
		$videoTitle       = strip_tags(trim(Yii::app()->request->getPost('title', '')));
		$videoDescription = strip_tags(trim(Yii::app()->request->getPost('description', '')));


		// TODO get contest from db
		$contestAlias = 'fipro';
		$contestId    = 3;

		if ( isset( $_POST['video_url'] ) && Link::is_valid(Link::YOUTUBE, array('url' => trim($_POST['video_url']))) )
		{
            $video_id = Link::getIdFromUrl(trim($_POST['video_url']), Link::YOUTUBE);
			if ( !$video_id )
				$this->exitWithError($response, "Неверно указана ссылка на видео Youtube", 'video_url');

			if ( PModulesVideoReviews::model()->find('id_video_youtube = :id_video_youtube', [':id_video_youtube' => $video_id]) )
				$this->exitWithError($response, "Видео уже участвует в конкурсе", 'video_url');
		}
		else
			$this->exitWithError($response, "Неверно указана ссылка на видео Youtube", 'video_url');

		if ( !$videoTitle )
			$this->exitWithError($response, "Необходимо указать название видео", 'title');

		if ( !$videoDescription )
			$this->exitWithError($response, "Необходимо указать описание видео", 'description');

		if ( !$entityUrl || !$this->isAllowedContestLink($entityUrl) )
			$this->exitWithError($response, "Неверно указана ссылка на товар или группу товаров", 'entity_url');

		if ( !$entityName )
			$this->exitWithError($response, "Необходимо указать название товара", 'entity_name');

		$model       = PModulesVideoReviews::model()->findAll();
		$count       = count($model) + 1;
		$url_vreview = Yii::app()->request->hostInfo . "/$contestAlias/" . $count;

		/**
		 * @var PModulesVideoReviews $model
		 */

		if ( $id )
		{
			$model = PModulesVideoReviews::model()->findByPk($id);
			$model->id_video_youtube = $video_id;
			$model->moderator_id     = null;
			$model->date_resolution  = null;
		}
		else
		{
			$model = new PModulesVideoReviews();
			$model->site_id          = self::$portalId;
			$model->promo_id         = $contestId;
			$model->user_id          = Yii::app()->client->id;
			$model->id_video_youtube = $video_id;
			$model->date_created     = time();
		}

		$model->title       = $videoTitle;
		$model->description = $videoDescription;
		$model->entity_url  = $entityUrl;
		$model->entity_name = $entityName;
		$model->entity_id   = $entity_id;
		$model->url         = $url_vreview;
		$model->url_float   = $url_vreview;

		if ( !$model->save() )
			$this->exitWithError($response, "Произошла ошибка сохранения данных, попробуйте поже.", 'submit');

		$response['link'] = Yii::app()->request->hostInfo .  '/videoReviews/' . $contestAlias . '/' . $model->id;

		// mark user
		/**
		 * @var PModulesClientPromo $modelClientPromo
		 */
		$modelClientPromo             = new PModulesClientPromo();
		$modelClientPromo->user_id    = Yii::app()->client->id;
		$modelClientPromo->promo_id   = $contestId;
		$modelClientPromo->date_added = time();
		$modelClientPromo->save();
		//

		$this->returnJSON($response);
        //Yii::app()->controller->redirect(Yii::app()->request->urlReferrer);
    }

	// TODO remove to helper
	private function isAllowedContestLink($link)
	{
		$pattern = '`^
			https?://
			(?:test(?:2|3|4)?\.rixetka\.com|tatet\.(?:ua|ru)|ava\.ua|toriava\.ru|m\.ava\.ua|rozetka\.com|bt\.rozetka\.com)
		`x';
		return preg_match($pattern, $link, $match);
	}

	// TODO remove to separate class
	private function exitWithError(&$response, $msg, $errorsNode)
	{
		$response['error']      = true;
		$response['msg']        = $msg;
		$response['error_node'] = $errorsNode;
		$this->returnJSON($response);
	}

    private function _getItemIdsActive($list)
    {
        if (empty($list)) {
            return array();
        }
        $listIds     = ArrayHelper::get_array_column($list, 'id');
        $countryCode = ModulesSites::model()->findByPk(self::$portalId)->code;

        $activeItems = ScItem::getActiveByIdsAndCountry($listIds, $countryCode);
        $shopsIds    = ArrayHelper::get_array_column($activeItems, 'shopid');

        $activeShopsIds = [];
        if ( $shopsIds )
        {
            $activeShops    = ( new ScConfigs )->getActiveByShopIdCountryRegion($shopsIds, $countryCode, self::$siteRegion);
            $activeShopsIds = ArrayHelper::get_array_column($activeShops, 'shopid');
        }

        $activeItemsIds = [];
        foreach ( $activeItems as $item )
        {
            if ( !in_array($item['shopid'], $activeShopsIds) )
            {
                continue;
            }
            $activeItemsIds[] = $item['id'];
        }

        return $activeItemsIds;
    }

    function actionAddNewReview()
    {
        if ( Yii::app()->client->isGuest )
        {
//			$ref = Yii::app()->request->getUrlReferrer();
            //Yii::app()->session->add('reviewFrom', $ref);
            Yii::app()->request->redirect(self::AUTH_PORTAL);
        }
        $errors = 0;

        $resultSave = null;
        $data       = [];

        $entity_id  = Yii::app()->request->getParam('entityId');

        $nameEntity = ScItem::model()->findByPk($entity_id)->name;
        if ( !$nameEntity )
        {
            $errors = 1;
        }

        $isNoFollow = true;
        $title = 'Обзоры';
        $pageTitle = 'Обзор на товар '.$nameEntity;
        $this->setTitle('Добавить обзор на товар');
        $this->render('addNewReview', compact('pageTitle', 'data', 'resultSave', 'errors', 'isNoFollow', 'nameEntity', 'title', 'entity_id'));
    }

    function actionEditReview()
    {
        $entity_id  = Yii::app()->request->getParam('entityId');

        $nameEntity = ScItem::model()->findByPk($entity_id)->name;
        if ( !$nameEntity )
        {
            $errors = 1;
        }

        $title = 'Редактирование обзора';
        $pageTitle = 'Обзор на товар '.$nameEntity;
        $this->setTitle('Редактирование обзора');
        $this->render('editReview', compact('pageTitle', 'resultSave', 'errors', 'isNoFollow', 'nameEntity', 'title', 'entity_id'));
    }

    function actionUnsubscribe()
    {
        if(Yii::app()->request->isPostRequest)
        {
            $error = true;
            if(isset($_POST['unsc_reason']) && PModulesUnsubscribeReasons::model()->findByPk($_POST['unsc_reason']))
            {
                if(PModulesUnsubscribe::model()->findByAttributes(
                    array('user_id'=>Yii::app()->client->id,'reason_id'=>$_POST['unsc_reason'])))
                {
                    $error = false;
                }else{
                    $model = new PModulesUnsubscribe();
                    $model->user_id = Yii::app()->client->id;
                    $model->reason_id = $_POST['unsc_reason'];
                    $model->comment = isset($_POST['unsc_comment']) && $_POST['unsc_comment'] ? $_POST['unsc_comment'] : '';
                    $model->date = time();

                    if($model->save()){
                        $client = PModulesClient::model()->findByPk($model->user_id);
                        if($client->is_notify){
                            $client->is_notify = 0;
                            $error = $client->save();
                        }else{
                            $error = false;
                        }
                    }
                }

            }
            $this->render('success_unsubscribe',compact('error'));

        }else{
            $clientInfo = Yii::app()->client->getInfo();
            $reasons = PModulesUnsubscribeReasons::model()->findAll();
            $this->render('unsubscribe',compact('clientInfo','reasons'));
        }

    }

    function actionListSubscription()
    {
        $productList = null;

        $subscribeList = Yii::app()->db->createCommand("
                                                        SELECT item_id
                                                        FROM modules_item_subscription
                                                        WHERE client_id = :userId AND is_active = 1")
                                                        ->bindParam(':userId', $this->clientId, PDO::PARAM_INT)
                                                        ->queryAll();

        $listIds = [];
        if( !empty( $subscribeList ) )
        {
            foreach($subscribeList as $subscribe)
                $listIds[] = $subscribe['item_id'];

            $productList = PriceEntity::getPreparedList($listIds, self::$portalId, self::$portalLocale, false, false);
        }

        $this->render('listSubscription', ['productsList' => $productList]);
    }

    function actionGoshop()
    {
        GoShopHelper::run();
    }

}