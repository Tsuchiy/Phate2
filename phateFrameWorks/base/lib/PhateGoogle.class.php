<?php
/**
 * PhateGoogleクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Googleクラス
 *
 * 設定ファイル読んで、
 * Googleに関する処理を行うクラス(未完)
 * 未実装
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Google
{
    private static $_config;
    private static $_applicationKey;
    private static $_secret;
    
    /**
     * 設定ファイルよりgoogleの設定を取得
     *
     * @return void
     */
    private static function _setConfig()
    {
        $sysConf = Core::getConfigure();
        if (!isset($sysConf['GOOGLE']['load_yaml_file'])) {
            throw new CommonException('no mbga configure');
        }
        $filename = PHATE_CONFIG_DIR . $sysConf['GOOGLE']['load_yaml_file'];
        self::$_config = Common::parseConfigYaml($filename);
    }
    /* GCMとかは使うようになるんじゃないだろうか */
    /* paymentの正当性は署名ファイルからできるらしい openssl_verify */
}
