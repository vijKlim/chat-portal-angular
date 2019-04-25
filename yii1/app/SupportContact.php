<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 07.12.2015
 * Time: 10:12
 */

class SupportContact extends FormatAloneContact{

    const ONE_DAY = 86400;
    const DEFAULT_SHOP_MANAGER_ID_EXTERNAL = 22;
    const TATET_SHOP_SUPPORT_ID   = 1963;
    public $isclosed = 1;
    protected function __construct()
    {
        $this->type = self::TYPE_SUPPORT;
    }


    public static function getAll($userId, $which = self::CONTACTS_WHICH_ALL)
    {
        $supports = array();
        if(Yii::app()->client->isShop()){
            $supports =  self::getShopSupports();
        }else{
            //контакты администрации для добавления обычному пользователю
            $supports = self::getPortalSupports();
        }
        return $supports;
    }

    private static function getSupportContacts($ids = array(), $type='team', $column='id')
    {
        foreach($ids as &$id){
            $id = (int)$id;
        }
        $where = !empty($ids) ? 'AND '.$column.' IN ('.implode(',', $ids).')' : '';

        $admins = Yii::app()->db->cache(self::ONE_DAY)
            ->createCommand("SELECT id, type as type_user, id_external,name,logo FROM modules_client WHERE type=:type $where")
            ->bindParam(':type',$type,PDO::PARAM_STR)
            ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'SupportContact')->queryAll();


        return $admins;
    }

    public static function getPortalSupports()
    {
        return self::getSupportContacts();
    }

    public static function getShopSupports()
    {
        $contacts = array();
        $portal   = array();
        $shop     = array();
        $shopid = self::getShopId();
        if(self::isShopInPortal($shopid))
            $portal = self::getSupportContacts(array(self::TATET_SHOP_SUPPORT_ID));
        if($managerId = self::getShopManager($shopid)){
            $shop = self::getSupportContacts(array($managerId),'shops_support','id_external');
        }else{
            $shop = self::getSupportContacts(array(self::DEFAULT_SHOP_MANAGER_ID_EXTERNAL),'shops_support','id');
        }

        $contacts = array_merge($portal,$shop);
        return $contacts;
    }



    private static function isShopInPortal($shopid)
    {
        $inPortal = (int) Yii::app()->db->cache(self::ONE_DAY)->createCommand()
            ->select('count(shopid)')
            ->from('sc_configs')
            ->where('shopid = :shopid and region IN ("ua", "ru") and is_portal = 1', [':shopid' => $shopid])
            ->queryScalar();

        if ( $inPortal )
        {
            $inPortal = $inPortal &&
                Yii::app()->dbShop->cache(self::ONE_DAY)->createCommand("SELECT items FROM sc_portal_stat WHERE shopid = :shopid")
                    ->bindValue(':shopid', $shopid)
                    ->queryScalar();

            $inPortal = $inPortal &&
                Yii::app()->dbShop->cache(self::ONE_DAY)->createCommand("SELECT wantportal FROM sc_configs WHERE shopid = :shopid")
                    ->bindValue(':shopid', $shopid)
                    ->queryScalar();
        }
        return $inPortal;


    }
    private static  function getShopManager($shopid)
    {
        return Yii::app()->dbBilling->cache(self::ONE_DAY)->createCommand()
            ->select('m.id')->from('sc_managers m')
            ->join('sc_billing_orders o', 'm.id = o.managerid')
            ->where('o.shopid = :shopid', array(':shopid'=>$shopid))->queryScalar();
    }

    private static function getShopId()
    {
        return (int)Yii::app()->db->cache(self::ONE_DAY)
            ->createCommand("SELECT id_external FROM modules_client WHERE id = ".Yii::app()->client->id)->queryScalar();
    }

} 