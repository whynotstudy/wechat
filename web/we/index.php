<?php
header('Content-type:text/html;charset=utf8');
require 'wechat.class.php';

//需要配置
$token = "cuiyihang";
$appid = "wx68405f962d2fa1f2";
$appsecret = "0d6fc772454582728a4df1b4a95ce1a0";


$wx = new WeChat($appid, $appsecret, $token);

//验证url 这个不开启在基本配置里就无法正确验证通过token，在这里验证了token之后会告诉微信服务器没错，才能验证通过----------------------
$wx->firstValid();

//判断消息类型
$result = $wx->responseMsg();


if ($result['type'] == 'text') {
    //文本消息
    //$backMessage = transmitText($result['obj'], '你发送的是文本，内容为：'.$result['obj']->Content);
    /*
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        */
    /*
        $redis = new Redis();
        $redis->connect('localhost', 6379);
    //redis生成自增长的id
        $bid = $redis->incr('bid');
        $str = $result['obj']->Content;//这里需要获取到文本，但是总是获取到对象
        $redis->zAdd(cyh,$bid,$str);
        $resSet = $redis->zRange('key1', 0, -1);//读出来的不像是数组
        $resString = implode("|",$resSet);
        */
    /*
        $result = sprintf($textTpl, $result['obj']->FromUserName, $result['obj']->ToUserName, time(), '【调试中】你发送的是文本，内容为：'.$result['obj']->Content);
        file_put_contents('test.txt',$result);
    */


    $dsn = 'mysql:host=localhost;dbname=tang_poetry';
    $pdo = new PDO($dsn, 'root', '666666');
    $pdo->query('set names utf8');
    $bid = rand(1, 43030);
    $sql = 'select a.name,b.title,b.content from poets as a inner join poetries as b where b.id=' . $bid . ' and a.id=b.poet_id';
    //$st = $pdo->prepare($sql);
    //$line = $st->execute($bid);
    //$a = $st->fetch();
    $arr = $pdo->query($sql);
    $line = $arr->fetch();

    if (count($line) != 0) {
        $contentArr = explode("。", $line['content']);
        $contentStr = "";
        foreach ($contentArr as $item) {
            $contentStr = $contentStr . $item . "\n";
        }
        $contentStr = trim($contentStr, "\n");
        $backMessage = transmitText($result['obj'], $line['title'] . "\n" . $line['name'] . "\n" . $contentStr);
    } else {
        $backMessage = transmitText($result['obj'], '输入1~43030范围内数字。');
    }

} elseif ($result['type'] == 'image') {//图片消息
    $backMessage = transmitText($result['obj'], '【调试中】你发送的是图片消息，暂时不会输出。');
} elseif ($result['type'] == 'voice') {//语音消息   打算在这里做一些事情
    //$backMessage = transmitText($result['obj'], '【调试中】你发送的是语音消息，暂时不会输出。');
} elseif ($result['type'] == 'video') {//视频消息
    $backMessage = transmitText($result['obj'], '【调试中】你发送的是视频消息，暂时不会输出。');
} elseif ($result['type'] == 'location') {//位置消息
    $backMessage = transmitText($result['obj'], '【调试中】你发送的是位置消息，暂时不会输出。');
} elseif ($result['type'] == 'link') {//链接消息
    $backMessage = transmitText($result['obj'], '【调试中】你发送的是链接消息，内容为：' . $result['obj']->Content);
} else {
    $backMessage = transmitText($result['obj'], '【调试中】不知道这是啥。');
}

//输出接受处理后的消息
echo $backMessage;
//echo $result;

/*
 * 操作Redis数据库
 */


/*
 * 回复文本消息
 */
function transmitText($object, $content)
{
    $textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        </xml>";
    $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
    file_put_contents('test.txt', $result);
    //echo $result;
    //die;
    return $result;
}


/*
 * 接收文本消息
 */
/*
function receiveText($object)
{
    //$content = "你发送的是文本，内容为：".$object->Content;
    $resMessage = $this->transmitText($object, $object->Content);
    return $resMessage;
}*/
