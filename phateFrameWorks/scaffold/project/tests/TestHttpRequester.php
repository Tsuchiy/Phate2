<?php

class TestHttpRequester
{
    const HTTP_STATUS_OK = 200;
    
    protected $_urlBase = '';
    protected $_userId = 1000000;
    protected $_uuid = 'uuid';
    protected $_password = 'password';
    protected $_version = '0000.0000.00000000000000';
    protected $_devicetypeId = 10;
    protected $_authToken = '';
    protected $_checkDigit = 'aaa';
    
    public $rtnStatus;
    public $rtnHeader;
    public $rtnBody;
    
    public function __construct()
    {
        $this->_urlBase = URL_BASE;
    }
    
    public function setUrlBase($urlBase)
    {
        $this->_urlBase = $urlBase;
    }
    
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }
    
    public function setUuid($uuid)
    {
        $this->_uuid = $uuid;
    }
    
    public function getUuid()
    {
        return $this->_uuid;
    }
    
    public function setPassword($password)
    {
        $this->_password = $password;
    }
    
    public function setVersion($version)
    {
        $this->_version = $version;
    }
    
    public function setDeviceTypeId($deviceTypeId)
    {
        $this->_devicetypeId = $deviceTypeId;
    }
    
    public function setCheckDigit($checkDigit)
    {
        $this->_checkDigit = $checkDigit;
    }
    /**
     * Httpリクエスト
     *
     * @access public
     * @param  string $url
     * @param  mixed $post
     * @return void
     */
    public function access($url, $headers = [], $post = [])
    {
        $headers = array_merge(
            [
                'User-Id: ' . $this->_userId,
                'Devicetype-Id: ' . $this->_devicetypeId,
                'Request-Id: ' . $this->_userId . microtime(true),
                'Version: ' . $this->_version,
                'Check-Disit: ' . $this->_checkDigit,
            ],
            $headers
        );
        if ($this->_authToken) {
            $headers[] = 'Auth-Token: ' . $this->_authToken;
        }

        $url = $this->_urlBase . $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        // URI
        curl_setopt($ch, CURLOPT_URL, $url);
        // header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // post
        $postField = '';
        if ($post) {
            /** normal post */
            foreach ($post as $k => $v) {
                $postField .= urlencode($k) . '=' . urlencode($v) . '&';
            }
            $postField = substr($postField, 0, -1);
            /** json post
            $postField = json_encode($post);
            */
            /** msgpack post
            $postField = msgpack_serialize($post);
            */
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postField);
        }
        // URL を取得する
        $r = curl_exec($ch);
        $this->rtnStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $rtnHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // cURL リソースを閉じる
        curl_close($ch);
        $this->rtnHeader = substr($r, 0, $rtnHeaderSize);
        $this->rtnBody = substr($r, $rtnHeaderSize);
        if ($this->rtnStatus != self::HTTP_STATUS_OK) {
            return false;
        }
        return true;
    }
    
    /**
     * ログインリクエスト
     *
     * @access public
     * @return void
     */
    public function login()
    {
        $post = [
            'uuid' => $this->_uuid,
            'password' => $this->_password,
        ];
        
        if (!($this->access('/login/Index/', [], $post))) {
            return false;
        }
        $result = json_decode($this->rtnBody, true);
        $this->_authToken = $result['auth_token'];
        return true;
    }
}
