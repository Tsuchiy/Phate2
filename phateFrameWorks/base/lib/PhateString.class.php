<?php
/**
 * PhateStringクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Stringクラス
 *
 * 文字列処理共通メソッド格納クラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class String
{
    /**
     * Trimの拡張関数
     *
     * @param string $str 対象文字列
     *
     * @return string
     */
    public static function mbTrim($str)
    {
        if (preg_match('/^(\s*)$/us', $str)) {
            return '';
        }
        return preg_replace('/^(.*[^\s])(\s*)$/us', '$1', preg_replace('/^(\s*)([^\s].*)$/us', '$2', $str));
    }

    /**
     * 再帰的に配列内をmb_convert_encodingする
     *
     * @param array|string $arg           対象
     * @param string       $to_encoding   変換先文字コード
     * @param string       $from_encoding 変換元文字コード
     *
     * @return array|string
     */
    public static function mbConvertEncodingArray($arg, $to_encoding, $from_encoding = null)
    {
        if (!is_array($arg)) {
            return mb_convert_encoding($arg, $to_encoding, $from_encoding);
        }
        
        foreach ($arg as &$v) {
            $v = self::mb_convert_encoding_array($v, $to_encoding, $from_encoding);
        }
        return $arg;
    }
    
    /**
     * Unicode外字を持つかを判定
     *
     * @param string $str 対象文字列
     *
     * @return boolean
     */
    public static function hasUnicodeEmoji($str)
    {
        $len = mb_strlen($str);
        for ($i=0; $i < $len; ++$i) {
            $chr = mb_substr($str, $i, 1);
            if (preg_match('/^(\xEE[\x80-\xBF])|(\xEF[\x80-\xA3])|(\xF3[\xB0-\xBF])|(\xF4[\x80-\x8F])/', $chr)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 4byte以上の文字を持つかを判定
     *
     * @param string $str 対象文字列
     *
     * @return boolean
     */
    public static function has4byteMoreCharacter($str)
    {
        $len = mb_strlen($str);
        for ($i=0; $i < $len; ++$i) {
            if (strlen(mb_substr($str, $i, 1)) > 3) {
                return true;
            }
        }
        return false;
    }
}
