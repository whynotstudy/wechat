<?php

namespace yiisoft\easywechat\driver\wechat;

use yii\base\Controller;
use yii\web\Controller as WebController;
use yiisoft\easywechat\driver\wechat\message\AbstractMessage;
use yiisoft\easywechat\driver\wechat\service\ChatService;

class MessageController extends WebController
{
    public function actionReceive()
    {
        $chatService = new ChatService($this->container);
        $message = $chatService->receive();
        // 处理接收到的消息
        return $this->render('receive', [
            'message' => $message,
        ]);
    }
}