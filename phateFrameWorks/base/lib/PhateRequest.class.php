<?php
/**
 * PhateHttpRequestクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * HttpRequestクラス
 *
 * Httpリクエストで取得できる値を格納しておくクラス
 * コード内部から直接グローバル変数へアクセスすることを防ぐ
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Request
{
    private static $_server;
    private static $_remoteAddr;
    private static $_method = null;
    private static $_requestParam = null;
    private static $_getParam = null;
    private static $_postParam = null;
    private static $_queryParam = [];
    private static $_rawPostData = null;
    
    private static $_deviceCode;
    
    const DEVICE_PC_PLAIN = 0;
    const DEVICE_FP_DOCOMO = 1;
    const DEVICE_FP_AU = 2;
    const DEVICE_FP_SOFTBANK = 3;
    const DEVICE_FP_WILLCOM = 4;
    const DEVICE_FP_EMOBILE = 5;
    const DEVICE_SP_IOS = 11;
    const DEVICE_SP_ANDROID = 12;
    const DEVICE_SP_WINDOWS = 13;
    const DEVICE_APPLI_IOS = 21;
    const DEVICE_APPLI_ANDROID = 22;
    const DEVICE_APPLI_SWF = 23;
    const DEVICE_APPLI_PC = 24;
    const DEVICE_UNKNOWN = 99;
    
    private static $_headerParam;
    
    private static $_calledModuleName;
    private static $_calledControllerName;
    
    private static $_userId = null;
    
    /**
     * HTTPリクエストからリクエスト情報をオブジェクトにセットする
     *
     * @return void
     *
     * @throws NotFoundException
     **/
    public static function init()
    {
        // スーパーグローバルの退避
        self::$_server = $_SERVER;
        self::$_requestParam = $_REQUEST ? $_REQUEST : [];
        self::$_getParam = $_GET ? $_GET : [];
        self::$_postParam = $_POST ? $_POST : [];
        self::$_queryParam = [];
        // リクエストメソッド
        self::$_method = array_key_exists('REQUEST_METHOD', self::$_server) ? self::$_server['REQUEST_METHOD'] : 'GET';
        // クライアントからのヘッダ情報
        self::$_headerParam = self::getallheaders();
        
        // request_uri処理
        $tmpArray = explode('/', self::$_server['REQUEST_URI']);
        foreach ($tmpArray as $v) {
            if (strlen(trim($v)) > 0) {
                self::$_queryParam[] = trim($v);
            }
        }
        // コントローラ情報
        self::$_calledModuleName = count(self::$_queryParam) >= 1 ? self::$_queryParam[0] :'index';
        self::$_calledControllerName = count(self::$_queryParam) >= 2 ? self::$_queryParam[1] :'Index';
    }
    
    /**
     * 相手のRemoteAddressを取得する
     *
     * @return string
     **/
    public static function getRemoteAddr()
    {
        if (is_null(self::$_remoteAddr)) {
            // リモートアドレス取得
            self::$_remoteAddr = array_key_exists('REMOTE_ADDR', self::$_server) ? self::$_server['REMOTE_ADDR'] : null;
        }
        return self::$_remoteAddr;
    }
    
    /**
     * RemoteAddressを上書きする(LB越しの時など)
     *
     * @param string $str 文字列
     *
     * @return void
     **/
    public static function setRemoteAddr($str)
    {
        self::$_remoteAddr = $str;
    }
    
    /**
     * リクエストメソッドを取得する
     *
     * @return string
     **/
    public static function getMethod()
    {
        return self::$_method;
    }

    /**
     * サーバーパラメータ($_SERVER)を取得する
     *
     * @param string $key     (null時は全配列)
     * @param string $default 設定されていなかった場合のreturn
     *
     * @return mixed|array
     */
    public static function getServerParam($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$_server;
        } else {
            return array_key_exists($key, self::$_server) ? self::$_server[$key] : $default;
        }
    }

    /**
     * リクエストパラメータ(GET/POST)を取得する
     *
     * @param string $key     (null時は全配列)
     * @param string $default 設定されていなかった場合のreturn
     *
     * @return mixed|array
     */
    public static function getRequestParam($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$_requestParam;
        } else {
            return array_key_exists($key, self::$_requestParam) ? self::$_requestParam[$key] : $default;
        }
    }

    /**
     * リクエストパラメータ(GET/POST)を設定する
     *
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return void
     */
    public static function setRequestParam($key, $value)
    {
        self::$_requestParam[$key] = $value;
    }

    /**
     * GETパラメータを取得する
     *
     * @param string $key     (null時は全配列)
     * @param string $default 設定されていなかった場合のreturn
     *
     * @return string|array
     */
    public static function getGetParam($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$_getParam;
        } else {
            return array_key_exists($key, self::$_getParam) ? self::$_getParam[$key] : $default;
        }
    }

    /**
     * POSTパラメータを取得する
     *
     * @param string $key     (null時は全配列)
     * @param string $default 設定されていなかった場合のreturn
     *
     * @return string|array
     */
    public static function getPostParam($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$_postParam;
        } else {
            return array_key_exists($key, self::$_postParam) ? self::$_postParam[$key] : $default;
        }
    }

    /**
     * URIパラメータ(/xx/yy/zzz/)を取得する
     *
     * @param int    $key     (null時は全配列)
     * @param string $default 設定されていなかった場合のreturn
     *
     * @return string|array
     */
    public static function getQueryParam($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$_queryParam;
        } else {
            return array_key_exists($key, self::$_queryParam) ? self::$_queryParam[$key] : $default;
        }
    }
    
    /**
     * 生のPOSTデータを取得する
     *
     * @return string
     */
    public static function getRawPostData()
    {
        if (!self::$_rawPostData) {
            self::$_rawPostData = file_get_contents("php://input");
        }
        return self::$_rawPostData;
    }

    /**
     * リクエストヘッダパラメータを取得する
     *
     * @param string $key     null時は全配列
     * @param string $default 設定されていなかった場合のreturn
     *
     * @return mixed
     */
    public static function getHeaderParam($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$_headerParam;
        } else {
            return array_key_exists($key, self::$_headerParam) ? self::$_headerParam[$key] : $default;
        }
    }
    
    /**
     * リクエスト時にコールされたModule名を取得する
     *
     * @return string
     */
    public static function getCalledModule()
    {
        return self::$_calledModuleName;
    }
    
    /**
     * リクエスト時にコールされたController名を取得する
     *
     * @return string
     */
    public static function getController()
    {
        return self::$_calledControllerName . 'Controller';
    }
    
    /**
     * コールされたリクエストのDeviceコードを設定する
     * (アプリなどロジックによる判別時に上書きする目的)
     *
     * @param int $code 任意のコード
     *
     * @return void
     */
    public static function setDeviceCode($code)
    {
        self::$_deviceCode = $code;
    }
    
    /**
     * Deviceコードを取得する
     *
     * @return string
     */
    public static function getDeviceCode()
    {
        if (is_null(self::$_deviceCode)) {
            // ユーザエージェントからdevice判定
            self::$_deviceCode = self::checkUserAgent();
        }
        return self::$_deviceCode;
    }
    
    /**
     * ユーザIDをセットする
     *
     * @param string $userId ユーザID
     *
     * @return void
     */
    public static function setUserId($userId)
    {
        self::$_userId = $userId;
    }
    
    /**
     * ユーザIDを取得する
     *
     * @return string
     */
    public static function getUserId()
    {
        return self::$_userId;
    }
    
    /**
     * FastCGIでもgetallheadersを使うために拡張
     *
     * @return array
     */
    public static function getallheaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        $headers = [];
        foreach (self::$_server as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
    /**
     * ユーザーエージェントからデバイスを判定する
     *
     * @return int
     */
    public static function checkUserAgent()
    {
        $rtn = self::DEVICE_UNKNOWN;
        if (!array_key_exists('HTTP_USER_AGENT', self::$_server) || !($userAgent = self::$_server['HTTP_USER_AGENT'])) {
            return $rtn;
        }
        
        // キャリアチェック
        if (strpos($userAgent, 'DoCoMo') !== false) {
            // DoCoMo
            $rtn = self::DEVICE_FP_DOCOMO;
        } elseif (strpos($userAgent, 'UP.Browser') !== false) {
            // au
            $rtn = self::DEVICE_FP_AU;
        } elseif ((strpos($userAgent, 'SoftBank') !== false) || (strpos($userAgent, 'Vodafone') !== false) || (strpos($userAgent, 'J-PHONE') !== false) || (strpos($userAgent, 'SMOT') !== false)) {
            // SoftBank
            $rtn = self::DEVICE_FP_SOFTBANK;
        } elseif (strpos($userAgent, 'WILLCOM') !== false) {
            // WILLCOM
            $rtn = self::DEVICE_FP_WILLCOM;
        } elseif (strpos($userAgent, 'emobile') !== false) {
            // e-mobile
            $rtn = self::DEVICE_FP_EMOBILE;
        }
        
        // スマホチェック
        if (strpos($userAgent, 'iPhone') !== false) {
            // iPhone
            return self::DEVICE_SP_IOS;
        } elseif (strpos($userAgent, 'iPad') !== false) {
            // iPad
            return self::DEVICE_SP_IOS;
        } elseif ((strpos($userAgent, 'Android') !== false) && (strpos($userAgent, 'Mobile') !== false)) {
            // Android
            return self::DEVICE_SP_ANDROID;
        } elseif (strpos($userAgent, 'Android') !== false) {
            // Android(tablet)
            return self::DEVICE_SP_ANDROID;
        } elseif (strpos($userAgent, 'Windows Phone') !== false) {
            // Windows Phone
            return self::DEVICE_SP_WINDOWS;
        } elseif ((strpos($userAgent, 'Windows') !== false) && (strpos($userAgent, 'ARM') !== false)) {
            // Windows RT
            return self::DEVICE_SP_WINDOWS;
        } elseif ($rtn != self::DEVICE_UNKNOWN) {
            // フィーチャフォン
            return $rtn;
        }
        
        return self::DEVICE_UNKNOWN;
    }
}
