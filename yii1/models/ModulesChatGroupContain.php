<?php
/**
 * User: mike
 * Date: 07.12.15
 * Time: 17:17
 * Customized Yii CRUD gen
 * This is the model class for table "modules_chat_group_contain".
 *
 * The followings are the available columns in table 'modules_chat_group_contain':
 * @property string $id
 * @property string $group_id
 * @property string $user_id
 * @property string $status
 * @property string $last_viewed
 * @property string $who_added
 */
class ModulesChatGroupContain extends MyActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesChatGroupContain the static model class
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
        return 'modules_chat_group_contain';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('group_id, member_id, status, last_viewed, who_added', 'required'),
            array('group_id, member_id, last_viewed, who_added', 'length', 'max'=>10),
            array('status', 'length', 'max'=>11),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, group_id, member_id, status, last_viewed, who_added', 'safe', 'on'=>'search'),
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
            'group_id' => 'Group',
            'member_id' => 'Member',
            'status' => 'Status',
            'last_viewed' => 'Last Viewed',
            'who_added' => 'Who Added',
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
        $criteria->compare('group_id',$this->group_id,true);
        $criteria->compare('member_id',$this->user_id,true);
        $criteria->compare('status',$this->status,true);
        $criteria->compare('last_viewed',$this->last_viewed,true);
        $criteria->compare('who_added',$this->who_added,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}