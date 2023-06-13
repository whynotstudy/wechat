<?php

namespace app\models;

use yii\db\ActiveRecord;

class WechatUser extends ActiveRecord
{
    public static function tableName()
    {
        return 'wechat';
    }
}
