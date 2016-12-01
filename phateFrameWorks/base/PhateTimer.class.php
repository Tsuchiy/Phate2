<?php
/**
 * PhateTimerクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Timerクラス
 *
 * 実行開始時刻の記録・取得と、時間に対する各メソッド群
 *
 * @category Framework
 * @package  BaseLibrary
 * @access   public
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create   2014/11/13
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
class Timer
{
    private static $_now;
    private static $_mnow;
    private static $_timezone;
    private static $_applicationResetTime;
    
    const DEFAULT_TIMEZONE = 'Asia/Tokyo';
    const DEFAULT_RESET_TIME = '00:00:00';
    
    /**
     * 初期化
     *
     * @return void
     */
    public static function init()
    {
        $timeStamp = microtime(true);
        self::$_now = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : floor($timeStamp);
        self::$_mnow = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : $timeStamp;
        $sysConfig = Core::getConfigure('timer');
        self::$_timezone =  isset($sysConfig['timezone']) ? new \DateTimeZone($sysConfig['timezone']) : new \DateTimeZone(self::DEFAULT_TIMEZONE);
        self::$_applicationResetTime = isset($sysConfig['application_reset_time']) ? $sysConfig['application_reset_time'] : self::DEFAULT_RESET_TIME;
        ini_set('date.timezone', self::$_timezone->getName());
    }

    /**
     * TimeZone設定済みDateTimeクラスを取得する
     *
     * @param integer|float $timestamp UnixTimeStamp(省略は生成時刻)
     *
     * @return DateTime
     */
    private static function _getDateTimeClass($timestamp = null)
    {
        $ts = is_null($timestamp) ? self::$_now : $timestamp;
        if (is_int($ts)) {
            return \DateTime::createFromFormat('U', $ts, self::$_timezone);
        } else {
            return \DateTime::createFromFormat('U.u', sprintf('%6F', $ts), self::$_timezone);
        }
    }
    
    /**
     * TimeZone設定済みDateTimeクラスを取得する
     *
     * @param string $string "Y-m-d H:i:s"型|"Y-m-d H:i:s.u"型
     *
     * @return DateTime
     */
    private static function _getDateTimeClassByString($string = null)
    {
        if (is_null($string)) {
            return self::_getDateTimeClass();
        }
        $arr = [];
        if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s([0-9]+):([0-9]+):([0-9]+)$/', $string, $arr)) {
            $arr[6] = '000000';
        } elseif (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s([0-9]+):([0-9]+):([0-9]+)\.([0-9]+)$/', $string, $arr)) {
            $arr[6] = str_pad($arr[6], 6, '0', STR_PAD_RIGHT);
        } else {
            return self::_getDateTimeClass();
        }
        return \DateTime::createFromFormat(
            'Y-m-d H:i:s.u',
            sprintf('%04d-%02d-%02d %02d:%02d:%02d.%6s', $arr[0], $arr[1], $arr[2], $arr[3], $arr[4], $arr[5], $arr[6]),
            self::$_timezone
        );
    }
    
    /**
     * 生成時のUnixTimeStampを得る
     *
     * @param string $dateString "Y-m-d H:i:s"型|"Y-m-d H:i:s.u"型|省略は生成時刻
     *
     * @return integer
     */
    public static function getUnixTimeStamp($dateString = null)
    {
        return self::_getDateTimeClassByString($dateString)->getTimestamp();
    }

    /**
     * 生成時のUnixTimeStampをマイクロ秒単位で得る
     *
     * @param string $dateString "Y-m-d H:i:s"型(省略は生成時刻)
     *
     * @return float
     */
    public static function getMicroTimeStamp($dateString = null)
    {
        if (is_null($dateString)) {
            return self::$_mnow;
        }
        $datetimeClass = self::_getDateTimeClassByString($dateString);
        return $datetimeClass->format('U.u');
    }
    
    
    /**
     * フォーマットされた日時を得る
     *
     * @param integer $timestamp UnixTimeStamp(省略は生成時刻)
     *
     * @return string
     */
    public static function getDateTime($timestamp = null)
    {
        return self::format('Y-m-d H:i:s', $timestamp);
    }
    /**
     * フォーマットされた日時(マイクロ秒)を得る
     *
     * @param float $timestamp UnixTimeStamp(省略は生成時刻)
     *
     * @return string
     */
    public static function getMicroDateTime($timestamp = null)
    {
        $ts = is_null($timestamp) ? self::$_mnow : $timestamp;
        return self::format('Y-m-d H:i:s.u', $timestamp);
    }
    
    
    /**
     * フォーマットされた時刻を得る
     *
     * @param integer $timestamp UnixTimeStamp(省略は生成時刻)
     *
     * @return string
     */
    public static function getTimeFormat($timestamp = null)
    {
        return self::format('H:i:s', $timestamp);
    }
    
    
    /**
     * フォーマットされた日を得る
     *
     * @param integer $timestamp UnixTimeStamp(省略は生成時刻)
     *
     * @return string
     */
    public static function getDateFormat($timestamp = null)
    {
        return self::format('Y-m-d', $timestamp);
    }

    /**
     * 曜日を得る
     *
     * @param integer $timestamp UnixTimeStamp(省略は生成時刻)
     *
     * @return string 0(Sunday)-6(Saturday)
     */
    public static function getWeekDate($timestamp = null)
    {
        return self::format('w', $timestamp);
    }

    /**
     * DateTimeフォーマットに従った文字列を返す
     *
     * @param string    $format    DateTimeフォーマット文字列
     * @param int|float $timestamp UnixTimeStamp
     *
     * @return string
     */
    public static function format($format, $timestamp = null)
    {
        return self::_getDateTimeClass($timestamp)->format($format);
    }

    
    /**
     * アプリ内リセット時間を考慮したフォーマットされた日を得る
     *
     * @param integer $timestamp UnixTimeStamp(省略は生成時)
     *
     * @return string
     */
    public static function getApplicationDate($timestamp = null)
    {
        $datetimeClass = self::_getDateTimeClass($timestamp);
        if ($datetimeClass->format('H:i:s') < self::$_applicationResetTime) {
            $datetimeClass->add(new DateInterval('P-1D'));
        }
        return $datetimeClass->format('Y-m-d');
    }
    
    /**
     * String形式の日付の間隔を取得する
     *
     * @param string $toTimeString   目的の"Y-m-d H:i:s"型
     * @param string $fromTimeString "Y-m-d H:i:s"型(省略は生成時刻)
     *
     * @return array  ('day','hour,'minute','second')
     */
    public static function getDateTimeDiff($toTimeString, $fromTimeString = null)
    {
        $fromDateTimeClass = self::_getDateTimeClassByString($fromTimeString);
        $toDateTimeClass = self::_getDateTimeClassByString($toTimeString);
        $dateInterval = $fromDateTimeClass->diff($toDateTimeClass);
        $rtn['day'] = $dateInterval->format('%a');
        $rtn['hour'] = $dateInterval->format('%h');
        $rtn['minute'] = $dateInterval->format('%i');
        $rtn['second'] = $dateInterval->format('%s');
        return $rtn;
    }
    
    /**
     * String形式の日付の間隔を秒単位で取得する
     *
     * @param string $toTimeString   目的の"Y-m-d H:i:s"型
     * @param string $fromTimeString "Y-m-d H:i:s"型(省略は生成時刻)
     *
     * @return int
     */
    public static function getDateTimeDiffSecond($toTimeString, $fromTimeString = null)
    {
        $arr = self::getDateTimeDiff($toTimeString, $fromTimeString);
        return  ($arr['day'] * 24 * 60 * 60) +
                ($arr['hour'] * 60 * 60) +
                ($arr['minute'] * 60) +
                ($arr['second']);
    }
    
    /**
     * 現在時刻をセットする（主に仮想時刻用）
     *
     * @param int $unixtimestamp UnixTimeStamp
     *
     * @return void
     */
    public static function setTimeStamp($unixtimestamp)
    {
        self::$_now = $unixtimestamp;
    }
}
