<?php
/**
 * User: mike
 * Date: 18.11.15
 * Time: 17:47
 * Customized Yii CRUD gen
 * This is the model class for table "modules_chat_user_settings".
 *
 * The followings are the available columns in table 'modules_chat_user_settings':
 * @property string $id
 * @property string $setting_id
 * @property string $user_id
 * @property string $value
 */
class ModulesChatUserSettings extends MyActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesChatUserSettings the static model class
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
        return 'modules_chat_user_settings';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('setting_id, user_id, value', 'required'),
            array('setting_id, user_id', 'length', 'max'=>10),
            array('value', 'length', 'max'=>150),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, setting_id, user_id, value', 'safe', 'on'=>'search'),
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
            'setting_id' => 'Setting',
            'user_id' => 'User',
            'value' => 'Value',
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
        $criteria->compare('setting_id',$this->setting_id,true);
        $criteria->compare('user_id',$this->user_id,true);
        $criteria->compare('value',$this->value,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}