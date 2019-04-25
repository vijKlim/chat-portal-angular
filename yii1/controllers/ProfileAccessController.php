<?php

Yii::import('application.modules.profile.filters.ProfileAccessControl');

class ProfileAccessController extends DefaultController
{
	public $clientId = null;
	public $client = null;
	protected $isOwner = false;

	const LOGIN_URL = '/profile/auth/';

//	public $lngCategory = 'common';
//	public $layout = 'main';
//	public $breadcrumbs = [];

	public function filters()
    {

		if ( preg_match('`^/profile/(\d+)`', Yii::app()->request->getRequestUri(), $m) )
		{
			$this->clientId = intval($m[1]);
			$this->isOwner  =
				!Yii::app()->client->isGuest
				&& ( intval(Yii::app()->client->id) === $this->clientId );

			// TODO check if profile not deleted

			if ( $this->clientId )
			{
				if ( !$this->isOwner )
					$this->checkIsPublicProfile($this->clientId);

				$this->client = (new PModulesClient())->getInfo($this->clientId);

				if ( !$this->client )
					Yii::app()->request->redirect('/profile/my/notFound/');
			}
		}
		elseif ( preg_match('`^/profile/my`', Yii::app()->request->getRequestUri()) )
		{
			// Redirect for /profile/my/ old urls
			if ( !Yii::app()->client->isGuest && !$this->clientId &&
				!preg_match('`/profile/my/(?:notFound|forbidden)`', Yii::app()->request->getRequestUri())
			)
			{
				$newUrl = preg_replace('`^/profile/my`', '/profile/' . Yii::app()->client->id, Yii::app()->request->getRequestUri());
				Yii::app()->request->redirect($newUrl);
			}
			elseif ( Yii::app()->client->isGuest && !$this->clientId )
			{
				Yii::app()->request->redirect( ProfileAccessController::LOGIN_URL );
			}
		}

        return [
            array('application.modules.profile.filters.ProfileAccessControl', 'clientId' => $this->clientId, 'isOwner' => $this->isOwner),
            array('application.modules.profile.filters.YXssFilter', 'clean'   => 'all', 'actions' => 'sendMsg','tags' => 'strict'),//exemple 'actions' => 'admin,manage' — фильтровать только экшены admin и manage
		];
    }

    public function ListBreadcrumbs()
    {
		// TODO set
		$list = [];
//		$list = [
//			[
//				'class'  => '',
//				'link'   => '/padmin/dashboard/',
//				'name' => Yii::t('common', 'dashboard')
//			]
//		];
//		if( count($this->breadcrumbs) )
//		{
//            $list = array_merge($list, $this->breadcrumbs);
//      }

        return $list;
    }

	private $_nav;

	private function setNav()
	{
		// TODO check access if required
		$this->_nav = require_once( CONFIG_FOLDER . DIRECTORY_SEPARATOR .
			( !$this->isOwner ? 'public_profile_nav.php' : 'profile_nav.php') );
		$requestUri = Yii::app()->request->getRequestUri();

		if ( $this->_nav )
		{
			foreach ( $this->_nav as &$_nav )
			{
				if ( $_nav['items'] && is_array($_nav['items']) )
				{
					foreach ( $_nav['items'] as &$_subNav )
					{
						$_subNav['href'] = preg_replace('`^/profile/my`', '/profile/' . $this->clientId, $_subNav['href']);
						if ( $_subNav['href'] == $requestUri )
						{
							$_subNav['selected'] = true;
						}
					}
				}
			}
		}

//		$this->checkNavAccess();
	}

	private function checkIsPublicProfile($clientId)
	{
		if ( !PModulesClient::isPublic($clientId) )
		{
			Yii::app()->request->redirect('/profile/my/notFound/');
		}
	}

	public function render($view, $data = null, $return = false)
	{
		$this->setNav();
		parent::render($view, array_merge(
			$data,
			[
				'nav'      => $this->_nav,
				'isOwner'  => $this->isOwner,
				'client'   => $this->client,
				'clientId' => $this->clientId,
				'portalId' => self::$portalId
			]
		), $return);
	}

//	/**
//	 * TODO склеить с аналогичным в padmin и вынести в компонент
//	 * Проверка прав доступа к пунктам меню
//	 */
//	private function checkNavAccess()
//	{
//		foreach ( self::$nav as $groupIndex => $group )
//		{
//			foreach ( $group['items'] as $key => $item )
//			{
//				// Удаляются пункты меню с пустой ссылкой
//				if ( empty($item['href']) )
//					unset(self::$nav[$groupIndex]['items'][$key]);
//
//				if ( preg_match('`^(?:javascript\:;?|#)$`', $item['href']) )
//					continue;
//
//				$urlComponents             = $this->parseHref($item['href']);
//				$controllerNameCapitalized = strtoupper(substr($urlComponents['controller'], 0, 1)) . substr($urlComponents['controller'], 1);
//
//				// Удаляется пункт меню, к которому нет прав доступа
//				if ( !ProfileAccessControl::hasAccess($controllerNameCapitalized, $urlComponents['action']) )
//				{
//					unset(self::$nav[$groupIndex]['items'][$key]);
//				}
//			}
//
//			// Удаление группы меню, если к её пунктам нет доступа
//			if ( empty(self::$nav[$groupIndex]['items']) )
//				unset(self::$nav[$groupIndex]);
//		}
//	}
//
//	const DEFAULT_ACTION = 'index';
//
//	/**
//	 * @param $href
//	 * @return array
//	 */
//	private function parseHref($href)
//	{
//		preg_match('`^/(?P<module>\w+)/(?P<controller>\w+)/?(?P<action>\w+)?`', $href, $m);
//		if ( is_null($m['action']) )
//		 	$m['action'] = self::DEFAULT_ACTION;
//
//		return $m;
//	}
//
//
//	// Log in - out
//	public
//	function actionLogin()
//	{
//
//		echo 'test'; die;
//		/*
//			  'application.modules.scfront.components.*',
//			 'application.modules.scfront.models.*',
//		 */
//
//		$service = Yii::app()->request->getQuery('service');
//		if ( isset( $service ) )
//		{
//			$authIdentity              = Yii::app()->eauth->getIdentity($service);
//			$authIdentity->redirectUrl = Yii::app()->client->returnUrl;
//			$authIdentity->cancelUrl   = $this->createAbsoluteUrl('profile/login');
//
//			if ( $authIdentity->authenticate() )
//			{
//				$identity = new EAuthUserIdentity( $authIdentity );
//
//				// successful authentication
//				if ( $identity->authenticate() )
//				{
//					Yii::app()->client->login($identity);
//
//					// special redirect with closing popup window
//					$authIdentity->redirect();
//				}
//				else
//				{
//					// close popup window and redirect to cancelUrl
//					$authIdentity->cancel();
//				}
//			}
//
//			// Something went wrong, redirect to login page
//			$this->redirect(array('profile/login'));
//		}
//
//		echo 'test'; die;
//
////		$this->render('login_auth', array());
//		// default authorization code through login/password ..
//	}
//
//	/**
//	 * Logs out the current user and redirect to homepage.
//	 */
//	public
//	function actionLogout()
//	{
//		Yii::app()->client->logout();
//		// TODO set redirect ot referrer
//		$this->redirect('/');
//	}
}