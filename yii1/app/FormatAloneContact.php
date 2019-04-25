<?php
/**
 * Created by PhpStorm.
 * User: proger
 * Date: 12.02.2016
 * Time: 15:53
 */

class FormatAloneContact extends Contact{


    protected function __construct()
    {
        $this->type = self::TYPE_ALONE;
    }

    function __set($key, $val)
    {
        switch($key)
        {
            //чтоб лого нормально обрабатывалось, в mysql выборке должно быть указанно последним (после id_external, type_user, name)
            case 'logo':
                $this->logo = Link::hrefClientImage($val);
                $this->is_default_logo = $this->logo == Link::hrefDefaultClientImage();

                $idExternal = $this->id_external;
                if ( $idExternal && $this->type_user == 'shop' )
                {
                    // Магазинам присваивается порядковый индекс менеджера
                    $order = (int) PModulesClientOrder::getShopManagerOrder($idExternal);
                    if ( $order )
                        $this->name = $this->name . ' ' . $order;
                    //

                    // Формируется кастомное лого магазинов
                    if ( $logo = ScConfigs::getLogo($idExternal) )
                        $this->logo = $logo;
                    //
                }

                $this->isclosed = ($this->type_user == 'team' || $this->type_user == 'shops_support') ? 1 : 0;


                break;
            case 'memberId':
                $this->members = array($val);
                unset($key);
                break;
            default:
                $this->$key = $val;
        }

    }

    public static function _all($userId, $which,$onlyCount = false)
    {
        switch($which)
        {
            case self::CONTACTS_WHICH_OWN_REQUESTS:
                $select = $onlyCount ? 'count(mcr.id) as count' : 'mcr.hash as id, mcr.client_invited_id as memberId, mcr.status as status, mc.name as name,
					 mc.id_external as id_external, mc.type as type_user,mc.logo as logo';
                $sql = <<<SQL
					SELECT  $select
					FROM modules_client_relations mcr
					LEFT JOIN modules_client mc ON mc.id = mcr.client_invited_id
					WHERE mcr.client_offer_id = :id AND mcr.date_resolution IS NULL
SQL;
                break;
            case self::CONTACTS_WHICH_OTHER_REQUESTS:
                $select = $onlyCount ? 'count(mcr.id) as count' : 'mcr.hash as id,  mcr.client_offer_id as memberId, mcr.status as status, mc.name as name,
					 mc.id_external as id_external, mc.type as type_user,mc.logo as logo';
                $sql = <<<SQL
					SELECT $select
					FROM modules_client_relations mcr
					LEFT JOIN modules_client mc ON mc.id = mcr.client_offer_id
					WHERE mcr.client_invited_id = :id AND mcr.date_resolution IS NULL
SQL;
                break;
            case self::CONTACTS_WHICH_ALL:
            default:
                $select1 = $onlyCount ? 'count(mcr1.id) as count' : 'mcr1.hash as id, mcr1.client_invited_id as memberId, mcr1.status as status, mc1.name as name,
					mc1.id_external as id_external, mc1.type as type_user,mc1.logo as logo';
                $select2 = $onlyCount ? 'count(mcr2.id) as count' : 'mcr2.hash as id, mcr2.client_offer_id as memberId, mcr2.status as status, mc2.name as name,
					mc2.id_external as id_external, mc2.type as type_user,mc2.logo as logo';
                $order = $onlyCount ? '' : 'ORDER BY status';
                $sql = <<<SQL
					SELECT  $select1
					FROM modules_client_relations mcr1
					LEFT JOIN modules_client mc1 ON mc1.id = mcr1.client_invited_id
					WHERE mcr1.client_offer_id = :id  AND mcr1.status <> 'delete'
					UNION
					SELECT  $select2
					FROM modules_client_relations mcr2
					LEFT JOIN modules_client mc2 ON mc2.id = mcr2.client_offer_id
					WHERE mcr2.client_invited_id = :id  AND mcr2.status <> 'delete'
					$order
SQL;
                break;
        }

        if($onlyCount){
            $counts = Yii::app()->db->createCommand($sql)
                ->bindParam(':id',$userId, PDO::PARAM_INT)
                ->queryAll();
            $sum = 0;
            foreach($counts as $c){
                $sum += (int)$c['count'];
            }
            return $sum;
        }else{
            return Yii::app()->db->createCommand($sql)
                ->bindParam(':id',$userId, PDO::PARAM_INT)
                ->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'FormatAloneContact')
                ->queryAll();
        }

    }

    public static function getAll($userId, $which,$onlyCount = false)
    {
        return self::_all($userId,$which,$onlyCount);
    }

    static function getCountAll($userId,  $which = self::CONTACTS_WHICH_ALL)
    {
        return self::_all($userId, $which, true);
    }
} 