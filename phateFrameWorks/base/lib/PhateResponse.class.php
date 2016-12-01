<?php
/**
 * PhateResponseクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Responseクラス
 *
 * Httpレスポンスを行う際、必要な処理を格納しておくクラス
 * 主にはレスポンスヘッダ・HTTPステータスの設定
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Response
{
    const HTTP_OK = 200;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    
    private static $_httpStatus = self::HTTP_OK;
    private static $_contentType = '';
    private static $_redirectUrl;
    private static $_logicalErrorCode = 0;
    private static $_headerParam = [];
    
    /**
     * レスポンスのヘッダを送信する
     *
     * @return void
     *
     * @throws KillException
     **/
    public static function sendHeader()
    {
        // リダイレクト設定がある場合はリダイレクトして終了
        if (isset(self::$_redirectUrl)) {
            header("Location: " . self::$_redirectUrl);
            throw new KillException();
            return;
        }
        header("Response-Code: " . self::$_logicalErrorCode);
        foreach (self::$_headerParam as $key => $value) {
            header($key . ": " . $value);
        }
        // それ以外のレスポンス
        http_response_code(self::$_httpStatus);
        if (self::$_httpStatus != self::HTTP_OK) {
            return;
        }
        if (isset(self::$_contentType)) {
            header("Content-Type: " . self::$_contentType);
        }
        
        return;
    }
    
    /**
     * レスポンス時に返すHTTPStatusをセットする
     *
     * @param string $statusCode HTTPステータスコード
     *
     * @return void
     **/
    public static function setHttpStatus($statusCode)
    {
        self::$_httpStatus = $statusCode;
        return;
    }
    /**
     * ロジックエラーコード取得
     *
     * @return string 設定済み論理エラーコード
     **/
    public static function getLogicalErrorCode()
    {
        return self::$_logicalErrorCode;
    }
    
    /**
     * ロジックエラーコード設定
     *
     * @param string $statusCode 論理エラーコード
     *
     * @return void
     **/
    public static function setLogicalErrorCode($statusCode)
    {
        self::$_logicalErrorCode = $statusCode;
        return;
    }
    
    /**
     * Content-Typeヘッダの設定
     *
     * @param string $contentType content-type
     *
     * @return void
     **/
    public static function setContentType($contentType)
    {
        self::$_contentType = $contentType;
        return;
    }

    /**
     * リダイレクトヘッダの設定(設定されているとリダイレクトが優先される)
     *
     * @param string $url 遷移先URL
     *
     * @return void
     **/
    public static function setRedirectUrl($url)
    {
        self::$_redirectUrl = $url;
        return;
    }
    
    /**
     * レスポンス時のHTTPヘッダの設定
     *
     * @param string $key   項目名
     * @param string $value 値
     *
     * @return void
     **/
    public static function setHeader($key, $value)
    {
        self::$_headerParam[$key] = $value;
        return;
    }
    
    /**
     * HTTPヘッダ情報を取得
     *
     * @return array
     **/
    public static function getReponseInfo()
    {
        return [
            'http_status' => self::$_httpStatus,
            'content_type' => self::$_contentType,
            'redirect_url' => self::$_redirectUrl,
            'logical_error_code' => self::$_logicalErrorCode,
            'header_param' => self::$_headerParam,
            ];
    }
}
