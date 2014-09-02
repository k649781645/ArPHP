<?php
/**
 * ArPHP A Strong Performence PHP FrameWork ! You Should Have.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Core.Component.List
 * @author   yc <ycassnr@gmail.com>
 * @license  http://www.arphp.net/licence BSD Licence
 * @version  GIT: 1: coding-standard-tutorial.xml,v 1.0 2014-5-01 18:16:25 cweiske Exp $
 * @link     http://www.arphp.net
 */

/**
 * Core.Component.Weixin
 *
 * default hash comment :
 *
 * config
 *'ext' => array(
    'lazy' => true,
    'weixin' => array(
        'config' => array(
            'APPID' => 'wx37b8059cb2bf452e',
            'APPSECRET' => 'a732c465fb149c4937e012b60081f677',
            'menu' => array(
                'button' => array(
                    array(
                        'name' => 'test1',
                        'type' => 'click',
                        'key' => 'test1',
                    ),
                    array(
                        'name' => 'test2',
                        'type' => 'click',
                        'key' => 'test2',
                    ),
                    array(
                        'name' => 'test3',
                        'type' => 'click',
                        'key' => 'test3',
                    ),
                ),
            ),
        ),
    ),
),
 * <code>
 *  # This is a hash comment, which is prohibited.
 *  $hello = 'hello';
 * </code>
 *
 * @category ArPHP
 * @package  Core.base
 * @author   yc <ycassnr@gmail.com>
 * @license  http://www.arphp.net/licence BSD Licence
 * @version  Release: @package_version@
 * @link     http://www.arphp.net
 */
class ArWeixin extends ArComponent
{
    // 微信 AppId
    protected $appId;
    // 微信 AppSecret
    protected $appSecret;
    // 微信 token
    protected $token;
    // 微信 请求数据
    protected $rawDataArray;

    /**
     * initialization function.
     *
     * @param mixed  $config config.
     * @param string $class  hold class.
     *
     * @return Object
     */
    static public function init($config = array(), $class = __CLASS__)
    {
        $obj = parent::init($config, $class);

        if (empty($obj->config['APPID'])) :
            throw new ArException("wx config mission error : " . "'APPID' required !");
        else :
            $obj->appId = $obj->config['APPID'];
        endif;

        if (empty($obj->config['APPSECRET'])) :
            throw new ArException("wx config mission error : " . "'APPSECRET' required !");
        else :
            $obj->appSecret = $obj->config['APPSECRET'];
        endif;

        if (empty($obj->config['TOKEN'])) :
            throw new ArException("wx config mission error : " . "'TOKEN' required !");
        else :
            $obj->token = $obj->config['TOKEN'];
        endif;

        // 设置curl ssl 请求参数
        arComp('rpc.api')->curlOptions = array(
            CURLOPT_SSL_VERIFYPEER => false
        );

        arComp('rpc.api')->method = 'post';

        return $obj;

    }

    /**
     * get Access Token
     *
     * @return string
     */
    public function getAccessToken()
    {
        if (!arComp('cache.file')->get('wx_token')) :
            $result = arComp('rpc.api')->remoteCall('https://api.weixin.qq.com/cgi-bin/token', array('grant_type' => 'client_credential', 'appid' => $this->appId, 'secret' => $this->appSecret));
            $resultArray = $this->handlerRemoteData($result);
            arComp('cache.file')->set('wx_token', $resultArray['access_token'], '7200');
        endif;

        return arComp('cache.file')->get('wx_token');

    }

    /**
     * 创建菜单
     *
     * @return void
     */
    public function createMenu()
    {
        if (empty($this->config['menu'])) :
            throw new ArException("wx config mission error : " . "'menu' required !");
        endif;

        $result = arComp('rpc.api')->remoteCall('https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken(), json_encode($this->config['menu']));

        $resultArray = $this->handlerRemoteData($result);

        return $resultArray;

    }

    /**
     * check data.
     *
     * @return mixed
     */
    public function handlerRemoteData($data = '')
    {
        if ($data = json_decode($data, true)) :
            if (!empty($data['errcode'])) :
                throw new ArException("wx request error : " . $data['errmsg'] . ', code : ' . $data['errcode']);
            else :
                return $data;
            endif;
        else :
            throw new ArException("wx data parse error , data : " . $data);
        endif;

    }

    /**
     * 检查是否来自微信.
     *
     * @return boolean
     */
    private function checkSignature()
    {
        $signature = arGet('signature');
        $timestamp = arGet('timestamp');
        $nonce = arGet('nonce');

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        arComp('list.log')->record($tmpStr);
        if ($tmpStr == $signature) :
            return true;
        else :
            return false;
        endif;

    }

    /**
     * 微信回复.
     *
     * @return void
     */
    public function response($type = 'text', $data = array())
    {
        if ($this->checkSignature()) :
            $result = call_user_func_array(array($this, 'process' . ucfirst($type)), array($data));
            arComp('list.log')->record($result);
            echo $result;
        endif;

    }

    protected function processText($data)
    {
        $tplXmlArray = array(
            'ToUserName' => $this->rawurlencode['FromUserName'],
            'FromUserName' => $this->rawurlencode['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => $data,
        );
        arComp('list.log')->record($tplXmlArray);
        return arComp('ext.out')->array2xml($tplXmlArray, false, 'xml');

    }

    /**
     * 处理微信拉取数据.
     *
     * @return void
     */
    public function processRequest()
    {
        $rawData = file_get_contents('php://input');
        if ($rawData) :
            $xmlArray = arComp('ext.out')->xml2array();
            arComp('list.log')->record(array('xml' => $xmlArray));
            $this->rawDataArray = $xmlArray['xml'];
        else :
            echo '';
            exit;
        endif;

    }

    /**
     * 第一次验证.
     *
     * @return void
     */
    public function weixinFirstCheck()
    {
        $echostr = arGet('echostr');
        if ($this->checkSignature() && !empty($echostr)) :
            echo $echostr;
            exit;
        endif;

    }

}
