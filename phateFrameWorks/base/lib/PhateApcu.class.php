<?php
/**
 * PhateApcuクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Apcuクラス
 *
 * 設定ファイル読んで、Apcuのストアを操作するクラス
 * 名前空間毎にprefixの処理なんかもする
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Apcu
{
    
    private static $_config;
    private static $_getDisable = false;
    
    /**
     * 設定ファイルよりapcuの設定を取得
     *
     * @return void
     */
    private static function _setConfig()
    {
        if (!function_exists('apcu_store')) {
            throw new CommonException('no apcu module');
        }
        if (!($fileName = Core::getConfigure('apcu_config_file'))) {
            throw new CommonException('no apcu configure');
        }
        if (!(self::$_config = Common::parseConfigYaml(PHATE_CONFIG_DIR . $fileName))) {
            throw new CommonException('no apcu configure');
        }
    }
    
    /**
     * Apcuに値を格納
     *
     * @param string $key       key
     * @param mixed  $value     value
     * @param int    $ttl       time to live
     * @param string $namespace connection namespace
     *
     * @return boolean
     */
    public static function set($key, $value, $ttl = null, $namespace = 'default')
    {
        $realTtl = is_null($ttl) ? self::$_config[$namespace]['default_ttl'] : $ttl;
        $realKey = self::$_config[$namespace]['default_prefix'] . $key;
        
        return apcu_store($realKey, msgpack_serialize($value), $realTtl);
    }
    
    /**
     * Apcuより値を取得
     *
     * @param string $key       key
     * @param string $namespace connection namespace
     *
     * @return mixed/false
     */
    public static function get($key, $namespace = 'default')
    {
        if (self::$_getDisable) {
            return false;
        }
        $realKey = self::$_config[$namespace]['default_prefix'] . $key;
        return msgpack_unserialize(apcu_fetch($realKey));
    }
    
    /**
     * Apcuより値を消去
     *
     * @param string $key       key
     * @param string $namespace connection namespace
     *
     * @return boolean
     */
    public static function delete($key, $namespace = 'default')
    {
        $realKey = self::$_config[$namespace]['default_prefix'] . $key;
        return apcu_delete($realKey);
    }
    
    
    /**
     * Apcuより全てのキー一覧を取得（ただし保証はされない）
     *
     * @param string $namespace connection namespace
     *
     * @return array
     */
    public static function getAllKeys($namespace = null)
    {
        if (!is_null($namespace)) {
            $pattern = '/^' . preg_quote(self::$_config[$namespace]['default_prefix']) . '(.*)$/';
            $apcuIterator = new \APCUIterator($pattern);
        } else {
            $apcuIterator = new \APCUIterator();
        }
        
        $rtn = [];
        $apcuIterator->rewind();
        if ($apcuIterator->key() === false) {
            return [];
        }
        while ($key = $apcuIterator->key()) {
            $rtn[] = $key;
            $apcuIterator->next();
        }
        
        return $rtn;
    }
    
    /**
     * Apcu機能の無効化を行う
     * debug時用
     *
     * @param boolean $disable disable
     *
     * @return integer
     */
    public static function setGetDisable($disable = true)
    {
        self::$_getDisable = $disable;
    }
}
