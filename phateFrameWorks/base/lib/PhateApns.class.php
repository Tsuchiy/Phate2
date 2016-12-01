<?php
/**
 * PhateApnsクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Apnsクラス
 *
 * 設定ファイル読んで、
 * Apnsするクラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2015/03/26
 **/
class Apns
{
    const TRY_CONECTION_TIMES = 3;
    const TRY_CONECTION_TIMEOUT = 3;
    const STREAM_CONECTION_TIMEOUT = 86400;
    
    private $_config;
    private $_fp;
    private $_sendSize;
    private $_connectTimes = 0;
    
    /**
     * 設定ファイルよりappleの設定を取得
     *
     * @return void
     */
    public function __construct()
    {
        // config取得
        $sysConf = Core::getConfigure();
        if (!isset($sysConf['apple_config_file'])) {
            throw new CommonException('no apple configure');
        }
        $filename = PHATE_CONFIG_DIR . $sysConf['apple_config_file'];
        $this->_config = Common::parseConfigYaml($filename);
    }
    
    /**
     * 設定のホストにコネクトする
     *
     * @return void
     *
     * @throws CommonException
     */
    private function _connect()
    {
        $this->_sendSize = 0;
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->_config['push_ssl_pem']);
        if (array_key_exists('push_pem_passphrase', $this->_config) && $this->_config['push_pem_passphrase']) {
            stream_context_set_option($ctx, 'ssl', 'passphrase', $this->_config['push_pem_passphrase']);
        }
        $i = 0;
        do {
            ++$i;
            if (++$this->_connectTimes > 0) {
                sleep(1);
            }
            $this->_fp = stream_socket_client(
                $this->_config['push_ssl_host'],
                $errno,
                $errstr,
                self::TRY_CONECTION_TIMEOUT,
                STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT,
                $ctx
            );
        } while ((!$this->_isConnected()) && ($i <= self::TRY_CONECTION_TIMES));
        if (!$this->_fp) {
            throw new CommonException('APNS connect error : ' . $errno . ' : ' . $errstr);
        }
        stream_set_timeout($this->_fp, self::STREAM_CONECTION_TIMEOUT);
    }
    
    /**
     * 現在コネクト中かを返す
     *
     * @return type
     */
    private function _isConnected()
    {
        return (bool)$this->_fp;
    }
    
    /**
     * Push通知を送信する
     *
     * @param string      $deviceToken device token
     * @param ApnsMessage $apnsMessage message object
     * @param string      $identifier  id
     *
     * @return boolean
     */
    public function sendNotification($deviceToken, ApnsMessage $apnsMessage, $identifier = null)
    {
        if (!$this->_isConnected()) {
            $this->connect();
        }
        // PUSH内容を作成
        $payload = $apnsMessage->getPayload();
        $frameData = '';
        
        // 1 Device token               32 bytes
        $frameData .= chr(1) . pack('n', 32) . pack('H*', $deviceToken);
        
        // 2 Payload                    variable length, less than or equal to 2 kilobytes
        $payloadLength = strlen($payload);
        $frameData .= chr(2) . pack('n', $payloadLength) . $payload;
        
        // 3 Notification identifier    4 bytes
        if (!is_null($identifier) && strlen($identifier) <= 4) {
            $frameData .= chr(3) . pack('n', 4) . $identifier;
        }
        
        // 4 Expiration date            4 bytes
        $frameData .= chr(4) . pack('n', 4) . pack('N', time() + 600);
        
        // 5 Priority                   1 byte
        //      10 The push message is sent immediately.
        //      5 The push message is sent at a time that conserves power on the device receiving it.
        $frameData .= chr(5) . pack('n', 1) . chr(10);
        
        $frameLength = strlen($frameData);
        // メッセージ文字列完成
        $msg = chr(2) .  pack('N', $frameLength) . $frameData;
        // と、メッセージ長を取得
        $msgSize = strlen($msg);
        try {
            fwrite($this->_fp, $msg, $msgSize);
            echo fgets($this->_fp, 7);
            /*
             * $result = fread($this->_fp, 7);
            if (!$result){
                Logger::error("Apns failure: deviceToken : " . $deviceToken);
                Logger::error('APNSResLog : ' . strlen($result));
                $this->_disconnect();
                return false;
            }
             * 
             */
            $this->_sendSize += $msgSize;
            if ($this->_sendSize >= 5120) {
                $this->_disconnect();
            }
        } catch (Exception $e) {
            $this->_disconnect();
            Logger::error("Apns : " . $e->getCode() . " : " . $e->getMessage());
        }
        return true;
    }
    
    /**
     * 切断する
     *
     * @return void
     */
    private function _disconnect()
    {
        Logger::debug("Apns connection times : " . $this->_connectTimes);
        if (!$this->_isConnected()) {
            fclose($this->_fp);
        }
    }
    
    /**
     * Destruct時も切断を仕込んでおく
     *
     * @return void
     */
    public function __destruct()
    {
        $this->_disconnect();
    }
}

/**
 * ApnsMessageクラス
 * Apnsする際に、メッセージの内容を設定するクラス構造体
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2015/03/26
 **/

class ApnsMessage
{
    private $_title = null;
    private $_body = null;
    private $_badge = null;
    private $_sound = null;
    private $_contentAvailable = null;
    private $_category = null;
    private $_customProperty =[];
    private $_launchImage = null;
    private $_titleLocKey = null;
    private $_titleLocArgs = null;
    private $_actionLocKey = null;
    private $_locKey = null;
    private $_locArgs = null;

    /**
     * Title 設定
     *
     * @param string $title title
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }
    
    /**
     * Body 設定
     *
     * @param string $body body
     *
     * @return void
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }
    
    /**
     * Badge 設定
     *
     * @param integer $badge badge number
     *
     * @return void
     */
    public function setBadge($badge)
    {
        $this->_badge = $badge;
    }
    
    /**
     * Sound 設定
     *
     * @param string $sound sound filename
     *
     * @return void
     */
    public function setSound($sound = 'default')
    {
        $this->_sound = $sound;
    }

    /**
     * Category 設定
     *
     * @param string $category category
     *
     * @return void
     */
    public function setCategory($category = '')
    {
        $this->_category = $category;
    }
    
    /**
     * Content avlable 設定
     *
     * @param string $contentAvailable content avlable
     *
     * @return void
     */
    public function setContentAvailable($contentAvailable = true)
    {
        $this->_contentAvailable = $contentAvailable;
    }
    
    /**
     * Custom property 設定
     *
     * @param string $key   key
     * @param string $value value
     *
     * @return void
     */
    public function addCustomProperty($key, $value)
    {
        $this->_customProperty[$key]= $value;
    }

    /**
     * Launch Image 設定
     *
     * @param string $imageFileName file name
     *
     * @return void
     */
    public function setLaunchImage($imageFileName)
    {
        $this->_launchImage = $imageFileName;
    }
    
    /**
     * Title location 設定
     *
     * @param string $key  lockey
     * @param string $args locArg
     *
     * @return void
     */
    public function setTitleLoc($key, $args = null)
    {
        $this->_titleLocKey = $key;
        $this->_titleLocArgs = $args;
    }
    
    /**
     * Action location 設定
     *
     * @param string $key lockey
     *
     * @return void
     */
    public function setActionLocKey($key)
    {
        $this->_actionLocKey = $key;
    }
    
    /**
     * Message location 設定
     *
     * @param string $key  lockey
     * @param string $args locArg
     *
     * @return void
     */
    public function setmessageLoc($key, $args = null)
    {
        $this->_locKey = $key;
        $this->_locArgs = $args;
    }
    
    
    /**
     * Payload 取得
     *
     * @return string payloadJson
     */
    public function getPayload()
    {
        if (is_null($this->_body)) {
            throw new Exception('push message is null');
        }

        $arr = ["aps" => []];
        if (is_null($this->_title)
            && is_null($this->_titleLocKey)
            && is_null($this->_actionLocKey)
            && is_null($this->_locKey)
            && is_null($this->_launchImage)
        ) {
            $arr["aps"]["alert"] = $this->_body;
        } else {
            $arr["aps"]["alert"] = ["body" => $this->_body];
            if (!is_null($this->_title)) {
                $arr["aps"]["alert"]["title"] = $this->_title;
            }
            if (!is_null($this->_actionLocKey)) {
                $arr["aps"]["alert"]["action-loc-key"] = $this->_actionLocKey;
            }
            if (!is_null($this->_titleLocKey)) {
                $arr["aps"]["alert"]["title-loc-key"] = $this->_titleLocKey;
                $arr["aps"]["alert"]["title-loc-args"] = $this->_titleLocArgs;
            }
            if (!is_null($this->_locKey)) {
                $arr["aps"]["alert"]["loc-key"] = $this->_locKey;
                $arr["aps"]["alert"]["loc-args"] = $this->_locArgs;
            }
            if (!is_null($this->_launchImage)) {
                $arr["aps"]["alert"]["launch-image"] = $this->_launchImage;
            }
        }
        if (!is_null($this->_badge)) {
            $arr["aps"]["badge"] = $this->_badge;
        }
        if (!is_null($this->_sound)) {
            $arr["aps"]["sound"] = $this->_sound;
        }
        if (!is_null($this->_contentAvailable)) {
            $arr["aps"]["content-available"] = $this->_contentAvailable;
        }
        if (!is_null($this->_category)) {
            $arr["aps"]["category"] = $this->_category;
        }
        
        if ($this->_customProperty) {
            foreach ($this->_customProperty as $key => $value) {
                $arr[$key] = $value;
            }
        }
        $json = json_encode($arr);
        return $json;
    }
}
