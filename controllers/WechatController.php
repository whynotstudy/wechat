<?php

namespace app\controllers;

use yii;
use yii\web\Controller;
use yii\web\Response;
use EasyWeChat\Factory;
use app\models\WechatUser;

class WechatController extends Controller
{
    private $token = 'cuiyihang';

    public function actionIndex()
    {
        $this->actionToken();
        // 获取微信公众号的配置
        $config = [
            'app_id' => 'wx68405f962d2fa1f2',
            'secret' => '0d6fc772454582728a4df1 b4a95ce1a0',
            'token' => 'cuiyihang',
            'response_type' => 'array',
        ];

        // 创建EasyWeChat实例
        //$app = Factory::officialAccount($config);

        $content = yii::$app->request->post('content');
        $mm = yii::$app->getResponse();
        $a = $mm['type'];


        $logObj = fopen('../logsByC/message.log','a+');
        fwrite($logObj,$a.date("Y-m-d H:i:s", time()).$mm.'\n');
        fclose($logObj);


        // 处理微信服务器的请求
        /*$app->server->push(function ($message) {
            // 获取用户发送的消息内容
            $content = $message['Content'];

            $logObj = fopen('../logsByC/message.log','a+');
            fwrite($logObj,date("Y-m-d H:i:s", time()).$content.'\n');
            fclose($logObj);

            // 查询数据库，这里以查询User表为例
            $user = WechatUser::findOne(['name' => $content]);

            if ($user) {
                return '用户存在，姓名：' . $user->name . '，年龄：' . $user->age;
            } else {
                return '用户不存在';
            }
        });*/

        // 响应请求
        //return Yii::$app->response->content = $app->server->serve();
    }

    //验证token，另加的
    public function actionToken()
    {
// $this->request是我封装了一层，可以用原生Yii::$app->request->get()替代
        $signature = $this->request->get('signature');
        $timestamp = $this->request->get('timestamp');
        $nonce = $this->request->get('nonce');
        $token = $this->token;// token需要跟公众平台的token保持一致

        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if ($tmpStr == $signature) {
            $echostr = $this->request->get('echostr');
            echo $echostr;
        }
    }
}
