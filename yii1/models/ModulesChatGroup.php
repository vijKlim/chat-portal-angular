<?php
/**
 * User: mike
 * Date: 02.12.15
 * Time: 12:50
 * Customized Yii CRUD gen
 * This is the model class for table "modules_chat_group".
 *
 * The followings are the available columns in table 'modules_chat_group':
 * @property string $id
 * @property string $name
 * @property string $admin_id
 * @property string $tilte
 * @property string $description
 * @property string $url
 * @property string $logo
 */
class ModulesChatGroup extends MyActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ModulesChatGroup the static model class
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
        return 'modules_chat_group';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, admin_id', 'required'),
            array('name', 'length', 'max'=>50),
            array('admin_id', 'length', 'max'=>10),
            array('tilte', 'length', 'max'=>100),
            array('description, url, logo', 'length', 'max'=>255),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, admin_id, tilte, description, url, logo', 'safe', 'on'=>'search'),
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
            'name' => 'Name',
            'admin_id' => 'Admin',
            'tilte' => 'Tilte',
            'description' => 'Description',
            'url' => 'Url',
            'logo' => 'Logo',
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
        $criteria->compare('name',$this->name,true);
        $criteria->compare('admin_id',$this->admin_id,true);
        $criteria->compare('tilte',$this->tilte,true);
        $criteria->compare('description',$this->description,true);
        $criteria->compare('url',$this->url,true);
        $criteria->compare('logo',$this->logo,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}