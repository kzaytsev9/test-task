<?php

namespace app\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $email
 * @property string $password
 * @property string $_password
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $logged_at
 *
 */

class User extends ActiveRecord implements IdentityInterface
{

    public $_password;
    const STATUS_NOT_ACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
            'auth_key' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'auth_key'
                ],
                'value' => Yii::$app->getSecurity()->generateRandomString()
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', '_password'], 'required'],
            [['email'], 'email', 'checkDNS' => true],
            [['email'], 'unique'],
            [['email', 'auth_key'], 'string', 'max' => 255],
            [['_password'], 'string', 'min'=>2, 'max'=>18],
            [['_password', 'email'], 'filter', 'filter'=>'trim'],
            [['created_at', 'logged_at', 'status'], 'integer'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => array_keys(self::statuses())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            '_password' => 'Password',
            'created_at' => 'Created',
            'logged_at' => 'Last visit',
            'auth_key' => 'Auth Key',
            'status' => 'Status',
        ];
    }



    /**
     * Returns user statuses list
     * @return array|mixed
     */
    public static function statuses()
    {
        return [
            self::STATUS_NOT_ACTIVE => 'Not Active',
            self::STATUS_ACTIVE => 'Active',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne([
            'id'=>$id,
            'status'=>self::STATUS_ACTIVE
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {

    }

    /**
     * Finds user by email
     *
     * @param string $email
     * @return static
     */
    public static function findByEmail($email)
    {
        return static::findOne([
            'email' => $email,
            'status' =>self::STATUS_ACTIVE,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Generate password
     *
     * @param string $password password to generate
     * @return string password hash
     */
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }
}
