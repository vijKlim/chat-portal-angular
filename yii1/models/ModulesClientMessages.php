<?php
/**
 * User: mike
 * Date: 08.09.15
 * Time: 17:45
 * Customized Yii CRUD gen
 * This is the model class for table "modules_client_messages".
 *
 * The followings are the available columns in table 'modules_client_messages':
 * @property string $id
 * @property string $thread_hash
 * @property string $sender_id
 * @property string $receiver_id
 * @property string $msg
 * @property integer $is_answered
 * @property integer $is_viewed
 * @property string $date_created
 * @property string $ip
 * @property string $status_sender
 * @property string $status_receiver
 */
class ModulesClientMessages extends MyActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesClientMessages the static model class
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
        return 'modules_client_messages';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('thread_hash, sender_id, receiver_id, msg, date_created, ip', 'required'),
            array('is_answered, is_viewed', 'numerical', 'integerOnly'=>true),
            array('thread_hash', 'length', 'max'=>20),
            array('sender_id, receiver_id, date_created, ip', 'length', 'max'=>10),
            array('msg', 'length', 'max'=>7000),
            array('status_sender, status_receiver', 'length', 'max'=>6),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, thread_hash, sender_id, receiver_id, msg, is_answered, is_viewed, date_created, ip, status_sender, status_receiver', 'safe', 'on'=>'search'),
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
            'thread_hash' => 'Thread Hash',
            'sender_id' => 'Sender',
            'receiver_id' => 'Receiver',
            'msg' => 'Msg',
            'is_answered' => 'Is Answered',
            'is_viewed' => 'Is Viewed',
            'date_created' => 'Date Created',
            'ip' => 'Ip',
            'status_sender' => 'Status Sender',
            'status_receiver' => 'Status Receiver',
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
        $criteria->compare('thread_hash',$this->thread_hash,true);
        $criteria->compare('sender_id',$this->sender_id,true);
        $criteria->compare('receiver_id',$this->receiver_id,true);
        $criteria->compare('msg',$this->msg,true);
        $criteria->compare('is_answered',$this->is_answered);
        $criteria->compare('is_viewed',$this->is_viewed);
        $criteria->compare('date_created',$this->date_created,true);
        $criteria->compare('ip',$this->ip,true);
        $criteria->compare('status_sender',$this->status_sender,true);
        $criteria->compare('status_receiver',$this->status_receiver,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}