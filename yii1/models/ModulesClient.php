<?php
/**
 * User: mike
 * Date: 19.07.16
 * Time: 15:07
 * Customized Yii CRUD gen
 * This is the model class for table "modules_client".
 *
 * The followings are the available columns in table 'modules_client':
 * @property string $id
 * @property string $type
 * @property integer $is_active
 * @property integer $email_verified
 * @property integer $is_banned
 * @property string $name
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $pwd
 * @property string $phone
 * @property string $email
 * @property string $id_external
 * @property string $logo
 * @property string $comment
 * @property integer $status
 * @property string $dateadded
 * @property string $lastlogin
 * @property string $lastip
 * @property string $lastproxy
 * @property string $tmphash
 * @property string $lastvisit
 * @property integer $is_notify_comment
 * @property integer $site_id
 * @property integer $is_public
 * @property integer $is_enabled_chat
 * @property integer $is_notify
 * @property string $position_rating
 * @property string $old_position_rating
 * @property string $email_buffer
 */
class ModulesClient extends MyActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesClient the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'modules_client';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, email', 'required'),
            array('is_active, email_verified, is_banned, status, is_notify_comment, site_id, is_public, is_enabled_chat, is_notify', 'numerical', 'integerOnly'=>true),
            array('type', 'length', 'max'=>13),
            array('name, first_name, middle_name, last_name, logo', 'length', 'max'=>255),
            array('pwd', 'length', 'max'=>33),
            array('phone, email, email_buffer', 'length', 'max'=>100),
            array('id_external, position_rating, old_position_rating', 'length', 'max'=>10),
            array('dateadded, lastlogin, lastip, lastproxy, lastvisit', 'length', 'max'=>11),
            array('tmphash', 'length', 'max'=>50),
            array('comment', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, type, is_active, email_verified, is_banned, name, first_name, middle_name, last_name, pwd, phone, email, id_external, logo, comment, status, dateadded, lastlogin, lastip, lastproxy, tmphash, lastvisit, is_notify_comment, site_id, is_public, is_enabled_chat, is_notify, position_rating, old_position_rating, email_buffer', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'type' => 'Type',
            'is_active' => 'Is Active',
            'email_verified' => 'Email Verified',
            'is_banned' => 'Is Banned',
            'name' => 'Name',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'pwd' => 'Pwd',
            'phone' => 'Phone',
            'email' => 'Email',
            'id_external' => 'Id External',
            'logo' => 'Logo',
            'comment' => 'Comment',
            'status' => 'Status',
            'dateadded' => 'Dateadded',
            'lastlogin' => 'Lastlogin',
            'lastip' => 'Lastip',
            'lastproxy' => 'Lastproxy',
            'tmphash' => 'Tmphash',
            'lastvisit' => 'Lastvisit',
            'is_notify_comment' => 'Is Notify Comment',
            'site_id' => 'Site',
            'is_public' => 'Is Public',
            'is_enabled_chat' => 'Is Enabled Chat',
            'is_notify' => 'Is Notify',
            'position_rating' => 'Position Rating',
            'old_position_rating' => 'Old Position Rating',
            'email_buffer' => 'Email Buffer',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id,true);
        $criteria->compare('type',$this->type,true);
        $criteria->compare('is_active',$this->is_active);
        $criteria->compare('email_verified',$this->email_verified);
        $criteria->compare('is_banned',$this->is_banned);
        $criteria->compare('name',$this->name,true);
        $criteria->compare('first_name',$this->first_name,true);
        $criteria->compare('middle_name',$this->middle_name,true);
        $criteria->compare('last_name',$this->last_name,true);
        $criteria->compare('pwd',$this->pwd,true);
        $criteria->compare('phone',$this->phone,true);
        $criteria->compare('email',$this->email,true);
        $criteria->compare('id_external',$this->id_external,true);
        $criteria->compare('logo',$this->logo,true);
        $criteria->compare('comment',$this->comment,true);
        $criteria->compare('status',$this->status);
        $criteria->compare('dateadded',$this->dateadded,true);
        $criteria->compare('lastlogin',$this->lastlogin,true);
        $criteria->compare('lastip',$this->lastip,true);
        $criteria->compare('lastproxy',$this->lastproxy,true);
        $criteria->compare('tmphash',$this->tmphash,true);
        $criteria->compare('lastvisit',$this->lastvisit,true);
        $criteria->compare('is_notify_comment',$this->is_notify_comment);
        $criteria->compare('site_id',$this->site_id);
        $criteria->compare('is_public',$this->is_public);
        $criteria->compare('is_enabled_chat',$this->is_enabled_chat);
        $criteria->compare('is_notify',$this->is_notify);
        $criteria->compare('position_rating',$this->position_rating,true);
        $criteria->compare('old_position_rating',$this->old_position_rating,true);
        $criteria->compare('email_buffer',$this->email_buffer,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}