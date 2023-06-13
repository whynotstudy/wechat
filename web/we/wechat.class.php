<?php
/**
 * 微信公众平台操作类
 */
class WeChat
{
    private $_appid;
    private $_appsecret;
    //微信公众平台请求开发者的服务器需要token
    private $_token;

    //标识qrcodeticket的类型，是永久还是临时
    const QRCODE_TYPE_TEMP = 1;
    const QRCODE_TYPE_TEMP_STR = 2;
    const QRCODE_TYPE_LIMIT = 3;
    const QRCODE_TYPE_LIMIT_STR = 4;

    /**
     * 构造函数
     * @param string $id     appid
     * @param string $secret app秘钥
     */
    public function __construct($id,$secret,$token){
        $this->_appid=$id;
        $this->_appsecret=$secret;
        $this->_token=$token;
    }

    /**
     * 用于第一次验证URl合法性
     */
    public function firstValid(){
        //校验签名的合法性
        if($this->_checkSignature()){
            //签名合法，告知微信服务器
            echo $_GET['echostr'];
        }
    }
    /**
     * 验证签名
     * @return [type] [description]
     */
    private function _checkSignature(){
        //获取由微信服务器发过来的数据
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        //开始验证数据
        $tmp_arr =  array($this->_token,$timestamp,$nonce);
        sort($tmp_arr,SORT_STRING);
        $tmp_str = implode($tmp_arr);
        $tmp_str = sha1($tmp_str);
        //对比数据
        if ($signature == $tmp_str) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 消息类型判断
     * @return array
     */
    public function responseMsg()
    {
        //因为很多都设置了register_globals禁止,不能用$GLOBALS["HTTP_RAW_POST_DATA"] 改用file_get_contents("php://input")即可
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            //用户发送的消息类型判断
            switch ($RX_TYPE)
            {
                case "text":    //文本消息
                    return array('type'=>'text','msg'=>'文本','obj'=>$postObj);
                    break;
                case "image":   //图片消息
                    return array('type'=>'image','msg'=>'图片','obj'=>$postObj);
                    break;

                case "voice":   //语音消息
                    return array('type'=>'voice','msg'=>'语音','obj'=>$postObj);
                    break;
                case "video":   //视频消息
                    return array('type'=>'video','msg'=>'视频','obj'=>$postObj);
                    break;
                case "location"://位置消息
                    return array('type'=>'location','msg'=>'位置','obj'=>$postObj);
                    break;
                case "link":    //链接消息
                    return array('type'=>'link','msg'=>'链接','obj'=>$postObj);
                    break;
                default:
                    return array('type'=>'unknow msg type','msg'=>'未知','obj'=>$postObj);
                    break;
            }
        }else {
            echo '??';
            exit;
        }
    }


    /**
     * 获取 access_tonken值
     * @param string $token_file 用来存储的文件
     * @return access_token
     */
    public function getAccessToken($token_file='./access_token'){
        //处理是否过期问题，将access_token存储到文件
        $life_time = 7200;
        if (file_exists($token_file) && time() - filemtime($token_file) < $life_time) {
            // 存在有效的access_token 直接返回文件内容
            return file_get_contents($token_file);
        }
        //接口URL
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->_appid."&secret=".$this->_appsecret;
        //发送GET请求
        $result = $this->_request('get',$url);
        if (!$result) {
            return false;
        }
        //处理数据
        $result_obj = json_decode($result);
        //写入到文件
        file_put_contents($token_file, $result_obj->access_token);
        return $result_obj->access_token;
    }

    /**
     * 获取Ticket
     * @param string $content 二维码内容
     * @param int $type 二维码类型 1 临时整形 2临时字符串 3永久整形 4永久字符串
     * @param int $expire 有效时间
     * @return ticket
     */
    public function getQRCodeTicket($content,$type=2,$expire=604800){
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
        $type_list = array(
            self::QRCODE_TYPE_TEMP => 'QR_SCENE',
            self::QRCODE_TYPE_TEMP_STR => 'QR_STR_SCENE',
            self::QRCODE_TYPE_LIMIT=>'QR_LIMIT_SCENE',
            self::QRCODE_TYPE_LIMIT_STR=>'QR_LIMIT_STR_SCENE'
        );
        $action_name = $type_list[$type];
        //post发送的数据
        switch ($type){
            case self::QRCODE_TYPE_TEMP:
                $data_arr['expire_seconds']=$expire;
                $data_arr['action_name'] = $action_name;
                $data_arr['action_info']['scene']['scene_id'] = $content;
                break;
            case self::QRCODE_TYPE_TEMP_STR:
                $data_arr['expire_seconds']=$expire;
                $data_arr['action_name'] = $action_name;
                $data_arr['action_info']['scene']['scene_str'] = $content;
                break;
            case self::QRCODE_TYPE_LIMIT:
                $data_arr['action_name'] = $action_name;
                $data_arr['action_info']['scene']['scene_id'] = $content;
                break;
            case self::QRCODE_TYPE_LIMIT_STR:
                $data_arr['action_name'] = $action_name;
                $data_arr['action_info']['scene']['scene_str'] = $content;
                break;
        }
        $data = json_encode($data_arr);
        $result = $this->_request('post',$url,$data);
        if(!$result){
            return false;
        }
        $result_obj = json_decode($result);
        return $result_obj->ticket;
    }

    /**
     * 根据ticket获取二维码
     * @param int|string $content qrcode内容标识
     * @param [type] $file 存储为文件的地址，如果null直接输出
     * @param integer $type 类型
     * @param integer $expire 如果是临时，标识有效期
     * @return  [type]
     */
    public function getQRCode($content,$file=NULL,$type=2,$expire=604800){
        //获取ticket
        $ticket = $this->getQRCodeTicket($content,$type=2,$expire=604800);
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
        //发送，取得图片数据
        $result = $this->_request('get',$url);
        if($file){
            file_put_contents($file,$result);
        }else{
            header('Content-Type:image/jpeg');
            echo $result;
        }
    }


    /**
     * 封装发送http请求
     * @param string $method 请求方式 get/post
     * @param string $url 请求目标的url
     * @param array $data post发送的数据
     * @param bool $ssl 是否为https协议
     * @return 响应主体
     */
    private function _request($method='get',$url,$data=array(),$ssl=true){
        //curl完成，先开启curl模块
        //初始化一个curl资源
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl,CURLOPT_URL,$url);//url
        //请求的代理信息
        $user_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']: 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        curl_setopt($curl,CURLOPT_USERAGENT,$user_agent);
        //referer头，请求来源
        curl_setopt($curl,CURLOPT_AUTOREFERER,true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时时间
        //SSL相关
        if($ssl){
            //禁用后，curl将终止从服务端进行验证;
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
            //检查服务器SSL证书是否存在一个公用名
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,2);
        }
        //判断请求方式post还是get
        if(strtolower($method)=='post') {
            /**************处理post相关选项******************/
            //是否为post请求 ,处理请求数据
            curl_setopt($curl,CURLOPT_POST,true);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        }
        //是否处理响应头
        curl_setopt($curl,CURLOPT_HEADER,false);
        //是否返回响应结果
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

        //发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            echo '<br>', curl_error($curl), '<br>';
            return false;
        }
        //关闭curl
        curl_close($curl);
        return $response;
    }

}

