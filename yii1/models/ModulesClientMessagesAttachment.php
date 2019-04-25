<?php
/**
 * User: mike
 * Date: 15.10.15
 * Time: 09:56
 * Customized Yii CRUD gen
 * This is the model class for table "modules_client_messages_attachment".
 *
 * The followings are the available columns in table 'modules_client_messages_attachment':
 * @property string $id
 * @property string $type
 * @property string $msg_id
 * @property string $type_msg
 * @property string $site
 * @property string $image
 * @property string $url
 * @property string $title
 * @property string $description
 * @property string $dateadded
 * @property string $ordernum
 * @property string $additional_info
 */
class ModulesClientMessagesAttachment extends MyActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesClientMessagesAttachment the static model class
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
        return 'modules_client_messages_attachment';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('type, msg_id, type_msg, dateadded', 'required'),
            array('type', 'length', 'max'=>5),
            array('msg_id, dateadded, ordernum', 'length', 'max'=>10),
            array('type_msg, site', 'length', 'max'=>7),
            array('image, url, description', 'length', 'max'=>255),
            array('title', 'length', 'max'=>120),
            array('additional_info', 'length', 'max'=>700),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, type, msg_id, type_msg, site, image, url, title, description, dateadded, ordernum, additional_info', 'safe', 'on'=>'search'),
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
            'msg_id' => 'Msg',
            'type_msg' => 'Type Msg',
            'site' => 'Site',
            'image' => 'Image',
            'url' => 'Url',
            'title' => 'Title',
            'description' => 'Description',
            'dateadded' => 'Dateadded',
            'ordernum' => 'Ordernum',
            'additional_info' => 'Additional Info',
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
        $criteria->compare('msg_id',$this->msg_id,true);
        $criteria->compare('type_msg',$this->type_msg,true);
        $criteria->compare('site',$this->site,true);
        $criteria->compare('image',$this->image,true);
        $criteria->compare('url',$this->url,true);
        $criteria->compare('title',$this->title,true);
        $criteria->compare('description',$this->description,true);
        $criteria->compare('dateadded',$this->dateadded,true);
        $criteria->compare('ordernum',$this->ordernum,true);
        $criteria->compare('additional_info',$this->additional_info,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}