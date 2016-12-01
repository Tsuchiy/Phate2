<?php
/**
 * PhateLoggerクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Loggerクラス
 *
 * Logに記録するクラス。記録レベルや対象ファイルは設定ファイルにて設定されます。
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Logger
{
    
    const LEVEL_DEBUG = 1;
    const LEVEL_INFO = 2;
    const LEVEL_WARNING = 4;
    const LEVEL_ERROR = 8;
    const LEVEL_CRITICAL = 16;
    const LEVEL_FATAL = 128;
    
    const DEFAULT_PREFIX = '%s [%s] ';
    
    private static $_config;
    
    /**
     * ロガーの初期化
     *
     * @return void
     */
    public static function init()
    {
        if (!($fileName = Core::getConfigure('logger_config_file'))) {
            throw new CommonException('no logger configure');
        }
        if (!(self::$_config = Common::parseConfigYaml(PHATE_CONFIG_DIR . $fileName))) {
            throw new CommonException('no logger configure');
        }
    }
    
    /**
     * Debugレベルログ出力
     *
     * @param string $string writing string
     *
     * @return void
     */
    public static function debug($string)
    {
        $name = __FUNCTION__;
        $loggingLevel = Core::isDebug() ? self::$_config['debug_logging_level'] : self::$_config['normal_logging_level'];
        if (!(self::LEVEL_DEBUG & $loggingLevel)) {
            return false;
        }
        $outputPath = self::$_config[$name]['log_file_path'];
        $outputFilename = self::$_config[$name]['log_file_name'];
        $message  = sprintf(self::DEFAULT_PREFIX, Timer::getDateTime(), strtoupper($name));
        $message .= $string . "\n";
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }

    /**
     * Infoレベルログ出力
     *
     * @param string $string writing string
     *
     * @return void
     */
    public static function info($string)
    {
        $name = __FUNCTION__;
        $loggingLevel = Core::isDebug() ? self::$_config['debug_logging_level'] : self::$_config['normal_logging_level'];
        if (!(self::LEVEL_INFO & $loggingLevel)) {
            return false;
        }
        $outputPath = self::$_config[$name]['log_file_path'];
        $outputFilename = self::$_config[$name]['log_file_name'];
        $message  = sprintf(self::DEFAULT_PREFIX, Timer::getDateTime(), strtoupper($name));
        $message .= $string . "\n";
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }

    /**
     * Warningレベルログ出力
     *
     * @param string $string writing string
     *
     * @return void
     */
    public static function warning($string)
    {
        $name = __FUNCTION__;
        $loggingLevel = Core::isDebug() ? self::$_config['debug_logging_level'] : self::$_config['normal_logging_level'];
        if (!(self::LEVEL_WARNING & $loggingLevel)) {
            return false;
        }
        $outputPath = self::$_config[$name]['log_file_path'];
        $outputFilename = self::$_config[$name]['log_file_name'];
        $message  = sprintf(self::DEFAULT_PREFIX, Timer::getDateTime(), strtoupper($name));
        $message .= $string . "\n";
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }
    
    /**
     * Errorレベルログ出力
     *
     * @param string $string writing string
     *
     * @return void
     */
    public static function error($string)
    {
        $name = __FUNCTION__;
        $loggingLevel = Core::isDebug() ? self::$_config['debug_logging_level'] : self::$_config['normal_logging_level'];
        if (!(self::LEVEL_ERROR & $loggingLevel)) {
            return false;
        }
        $outputPath = self::$_config[$name]['log_file_path'];
        $outputFilename = self::$_config[$name]['log_file_name'];
        $message  = sprintf(self::DEFAULT_PREFIX, Timer::getDateTime(), strtoupper($name));
        $message .= $string . "\n";
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }
    
    /**
     * Criticalレベルログ出力
     *
     * @param string $string writing string
     *
     * @return void
     */
    public static function critical($string)
    {
        $name = __FUNCTION__;
        $loggingLevel = Core::isDebug() ? self::$_config['debug_logging_level'] : self::$_config['normal_logging_level'];
        if (!(self::LEVEL_CRITICAL & $loggingLevel)) {
            return false;
        }
        $outputPath = self::$_config[$name]['log_file_path'];
        $outputFilename = self::$_config[$name]['log_file_name'];
        $message  = sprintf(self::DEFAULT_PREFIX, Timer::getDateTime(), strtoupper($name));
        $message .= $string . "\n";
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }
    
    /**
     * Fatalレベルログ出力(PHP fatal error handler)
     *
     * @param string $errno   error code no
     * @param string $errstr  error message
     * @param string $errfile error ditect file name
     * @param string $errline error ditect line
     *
     * @return void
     */
    public static function fatal($errno, $errstr, $errfile, $errline)
    {
        $name = __FUNCTION__;
        $outputPath = self::$_config[$name]['log_file_path'];
        $outputFilename = self::$_config[$name]['log_file_name'];
        $message  = sprintf(self::DEFAULT_PREFIX, Timer::getDateTime(), strtoupper($name));
        $message .= "error_no:" . $errno . " " . $errstr ." ";
        $message .= "(" . $errfile ." , line:" . $errline . ")\n";
        
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }

    /**
     * Fluentd拡張
     *
     * @param string $tag  tag name
     * @param array  $data toJsonArray
     *
     * @return void
     */
    public static function fluentdPost($tag, array $data)
    {
        if (!class_exists('\Phate\Fluentd')) {
            return false;
        }
        Fluentd::post($tag, $data);
    }
    
    /**
     * カスタムログ出力(マジックメソッド)
     * 適宜の名前のログ出力を行う
     *
     * @param string $name      custom log level
     * @param array  $arguments [message, filename]
     *
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        if (!isset(self::$_config[$name]['log_file_path'])) {
            return false;
        }
        $outputPath = self::$_config[$name]['log_file_path'];
        $message = array_shift($arguments) . "\n";
        if (($filename = array_shift($arguments))) {
            $outputFilename = $filename;
        } else {
            if (!isset(self::$_config[$name]['log_file_name'])) {
                return false;
            }
            $outputFilename = self::$_config[$name]['log_file_name'];
        }
        
        error_log($message, 3, $outputPath . $outputFilename);
        if (substr(sprintf('%o', fileperms($outputPath . $outputFilename)), -4) !=='0666') {
            chmod($outputPath . $outputFilename, 0666);
        }
        return true;
    }
}
