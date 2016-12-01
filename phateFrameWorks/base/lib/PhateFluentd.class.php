<?php
/**
 * PhateFluentdクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

// Fluentをインクルード
require_once PHATE_LIB_VENDOR_DIR . 'Fluent/Autoloader.php';
use Fluent\Logger;

/**
 * Fluentdクラス
 * (https://github.com/fluent/fluent-logger-php)
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Fluentd
{
    private static $_fluent;
    
    /**
     * ロガーの初期化
     *
     * @return void
     */
    private static function _init()
    {
        if (!($fileName = Core::getConfigure('logger_config_file'))) {
            throw new CommonException('no logger configure');
        }
        if (!($config = Common::parseConfigYaml(PHATE_CONFIG_DIR . $fileName))) {
            throw new CommonException('no logger configure');
        }
        if (!isset($config['fluentd'])) {
            throw new CommonException('no config for fluentd');
        }
        Fluent\Autoloader::register();
        if (isset($config['fluentd']['socket'])) {
            self::$_fluent = new Logger\FluentLogger($config['fluentd']['socket']);
        } elseif (isset($config['fluentd']['host']) && isset($config['fluentd']['port'])) {
            self::$_fluent = new Logger\HttpLogger($config['fluentd']['host'], $config['fluentd']['port']);
        } else {
            throw new CommonException('no config for fluentd');
        }
    }
    /**
     * Fluentロガーに出力
     *
     * @param string $tag  tag
     * @param array  $data data
     *
     * @return void
     */
    public static function post($tag, array $data)
    {
        if (!self::$_fluent) {
            self::_init();
        }
        self::$_fluent->post($tag, $data);
    }
}
