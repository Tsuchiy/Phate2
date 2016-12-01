<?php
/**
 * PhateMemcachedクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Memcachedクラス
 *
 * 設定ファイル読んで、Memcacheに接続したmemcachedのインスタンスを操作するクラス
 * 名前空間毎にprefixの処理なんかもする
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Memcached
{
    
    private static $_config;
    private static $_realInstancePool;
    private static $_instancePool;
    private static $_instancePool4Set;
    private static $_getDisable = false;
    
    /**
     * 設定ファイルよりmemcacheの設定を取得
     *
     * @return void
     */
    private static function _setConfig()
    {
        if (!class_exists('\Memcached')) {
            throw new CommonException('no memcached class(use pecl install)');
        }
        if (defined("\Memcached::HAVE_MSGPACK") && (\Memcached::HAVE_MSGPACK == 1)) {
            ini_set('memcached.serializer', 'msgpack');
        } elseif (defined("\Memcached::HAVE_IGBINARY") && (\Memcached::HAVE_IGBINARY == 1)) {
            ini_set('memcached.serializer', 'igbinary');
        }
        if (!($fileName = Core::getConfigure('memcache_config_file'))) {
            throw new CommonException('no memcache configure');
        }
        if (!(self::$_config = Common::parseConfigYaml(PHATE_CONFIG_DIR . $fileName))) {
            throw new CommonException('no memcache configure');
        }
    }
    
    /**
     * 接続先別のインスタンスを生成
     *
     * @param string  $host host url
     * @param integer $port port
     *
     * @return \Memcached
     */
    private static function _getRealInstance($host, $port)
    {
        if (!isset(self::$_realInstancePool[$host][$port])) {
            $m = new \Memcached;
            $m->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000);
            $m->setOption(\Memcached::OPT_SEND_TIMEOUT, 1000);
            $m->setOption(\Memcached::OPT_RECV_TIMEOUT, 1000);
            $m->addServer($host, $port);
            // 疎通確認
            $m->getVersion();
            if ($m->getResultCode() !== \Memcached::RES_SUCCESS) {
                throw new MemcachedConnectFailException();
            }
            self::$_realInstancePool[$host][$port] = $m;
        }
        return self::$_realInstancePool[$host][$port];
    }
    
    /**
     * 接続名のインスタンスを返す
     *
     * @param string $namespace namespace
     *
     * @return \Memcached
     */
    private static function _getInstance($namespace)
    {
        if (!isset(self::$_instancePool[$namespace])) {
            if (!isset(self::$_config)) {
                self::_setConfig();
            }
            if (!isset(self::$_config[$namespace])) {
                throw new MemcachedConnectFailException('cant resolve namespace on memcache');
            }
            $instance = null;
            $instance4Set = [];
            // レプリケーション対応
            foreach (self::$_config[$namespace]['servers'] as $serverConfig) {
                try {
                    $tmpInstance = self::_getRealInstance($serverConfig['host'], $serverConfig['port']);
                    if (is_null($instance)) {
                        $instance = $tmpInstance;
                    }
                    $instance4Set[] = $tmpInstance;
                } catch (MemcachedConnectFailException $e) {
                    continue;
                }
            }
            // 全部に接続確立できてない
            if (is_null($instance)) {
                throw new MemcachedConnectFailException();
            }
            self::$_instancePool[$namespace] = $instance;
            self::$_instancePool4Set[$namespace] = $instance4Set;
        }
        return self::$_instancePool[$namespace];
    }
    
    /**
     * 接続名の全てのインスタンスを返す
     *
     * @param string $namespace namespace
     *
     * @return array
     */
    private static function _getInstance4Set($namespace)
    {
        if (!isset(self::$_instancePool4Set[$namespace])) {
            self::_getInstance($namespace);
        }
        return self::$_instancePool4Set[$namespace];
    }
    
    /**
     * インスタンスプールにあるmemcachedオブジェクトを全て明示的に切断する
     *
     * @return void
     */
    public static function disconnect()
    {
        if (!is_array(self::$_realInstancePool)) {
            return;
        }
        // 存在するインスタンスを全部切断する
        foreach (self::$_realInstancePool as $host => $tmp) {
            foreach ($tmp as $port => $v) {
                self::$_realInstancePool[$host][$port]->quit();
                unset(self::$_realInstancePool[$host][$port]);
            }
        }
    }

    /**
     * Memcacheに値を格納
     * バックアップサーバ対策？のためにキーを全サーバに保存しに行く
     *
     * @param string  $key        key
     * @param mixed   $value      value
     * @param integer $expiration expiration
     * @param string  $namespace  connection namespace
     *
     * @return boolean
     */
    public static function set($key, $value, $expiration = null, $namespace = 'default')
    {
        $memcachedList = self::_getInstance4Set($namespace);
        if (is_null($expiration)) {
            $expiration = self::$_config[$namespace]['default_expire'];
        }
        $rtn = true;
        foreach ($memcachedList as $memcached) {
            if (($memcached->set(self::$_config[$namespace]['default_prefix'] . $key, $value, $expiration)) === false) {
                $rtn = false;
            }
        }
        return $rtn;
    }
    
    /**
     * Memcacheに値を複数格納
     * バックアップサーバ対策？のためにキーを全サーバに保存しに行く
     *
     * @param array   $items      [key=>value]
     * @param integer $expiration expiration
     * @param string  $namespace  connection namespace
     *
     * @return boolean
     */
    public static function setMulti(array $items, $expiration = null, $namespace = 'default')
    {
        if (!isset(self::$_config)) {
            self::_setConfig();
        }
        if (is_null($expiration)) {
            $expiration = self::$_config[$namespace]['default_expire'];
        }
        $realItems = [];
        foreach ($items as $key => $value) {
            $realItems[self::$_config[$namespace]['default_prefix'] . $key] = $value;
        }
        $memcachedList = self::_getInstance4Set($namespace);
        $rtn = true;
        foreach ($memcachedList as $memcached) {
            if (($memcached->setMulti($realItems, $expiration)) === false) {
                $rtn = false;
            }
        }
        return $rtn;
    }
    /**
     * Memcacheより値を取得
     *
     * @param string   $key       key
     * @param string   $namespace connection namespace
     * @param function $cache_cb  cache callback
     * @param string   $cas_token CAS Token
     *
     * @return mixed/false
     */
    public static function get($key, $namespace = 'default', $cache_cb = null, float &$cas_token = null)
    {
        if (self::$_getDisable) {
            if (!is_null($cache_cb)) {
                $rtn = false;
                if (call_user_func($cache_cb, self::_getInstance($namespace), $key, $rtn) === true) {
                    return $rtn;
                }
            }
            return false;
        }
        $rtn = self::_getInstance($namespace)->get(self::$_config[$namespace]['default_prefix'] . $key, null, $cas_token);
        if (($rtn === false) && !is_null($cache_cb)) {
            $funcRtn = false;
            if (call_user_func($cache_cb, self::_getInstance($namespace), $key, $funcRtn) === true) {
                return $funcRtn;
            }
        }
        return $rtn;
    }
    /**
     * Memcacheより値を配列で取得
     *
     * @param string $keys      key array
     * @param string $namespace connection namespace
     *
     * @return mixed/false
     */
    public static function getMulti(array $keys, $namespace = 'default')
    {
        if (self::$_getDisable) {
            $rtn = [];
            foreach ($keys as $key) {
                $rtn[$key] = false;
            }
            return $rtn;
        }
        if (!isset(self::$_config)) {
            self::_setConfig();
        }
        foreach ($keys as &$key) {
            $key = self::$_config[$namespace]['default_prefix'] . $key;
        }
        if (!($res = self::_getInstance($namespace)->getMulti($keys))) {
            return $res;
        }
        $rtn = [];
        $pattern = '/^' . preg_quote(self::$_config[$namespace]['default_prefix']) . '(.*)$/';
        foreach ($res as $key => $value) {
            $newKey = preg_replace($pattern, '$1', $key);
            $rtn[$newKey] = $value;
        }
        return $rtn;
    }

    /**
     * Memcacheより値を消去
     * バックアップ対策？のためにキーを全サーバに削除しに行く
     *
     * @param string $key       key
     * @param string $namespace connection namespace
     *
     * @return boolean
     */
    public static function delete($key, $namespace = 'default')
    {
        $memcachedList = self::_getInstance4Set($namespace);
        $rtn = true;
        foreach ($memcachedList as $memcached) {
            if (($memcached->delete(self::$_config[$namespace]['default_prefix'] . $key)) === false) {
                if ($memcached->getResultCode() != \Memcached::RES_NOTFOUND) {
                    $rtn = false;
                }
            }
        }
        return $rtn;
    }
    /**
     * Memcacheより値を配列で消去
     * バックアップ対策？のためにキーを全サーバに削除しに行く
     *
     * @param array  $keys      key array
     * @param string $namespace connection namespace
     *
     * @return boolean
     */
    public static function deleteMulti(array $keys, $namespace = 'default')
    {
        if (!isset(self::$_config)) {
            self::_setConfig();
        }
        foreach ($keys as &$key) {
            $key = self::$_config[$namespace]['default_prefix'] . $key;
        }
        $memcachedList = self::_getInstance4Set($namespace);
        $rtn = true;
        foreach ($memcachedList as $memcached) {
            if (($memcached->deleteMulti($keys)) === false) {
                if ($memcached->getResultCode() != \Memcached::RES_NOTFOUND) {
                    $rtn = false;
                }
            }
        }
        return $rtn;
    }
    
    /**
     * Memcacheより全てのキー一覧を取得（ただし保証はされない）
     *
     * @param string $namespace connection namespace
     *
     * @return array
     */
    public static function getAllKeys($namespace = 'default')
    {
        $realKeys = self::_getInstance($namespace)->getAllKeys();
        $rtn = [];
        $pattern = '/^' . preg_quote(self::$_config[$namespace]['default_prefix']) . '(.*)$/';
        foreach ($realKeys as $realKey) {
            if (preg_match($pattern, $realKey)) {
                $rtn[] = preg_replace($pattern, '$1', $realKey);
            }
        }
        return $rtn;
    }
    /**
     * 直前のmemcached結果コードを取得
     * バックアップ対策？のために全サーバに更新処理をかけているので、
     * 必ずしも意図した値は保証されないかもしれない
     *
     * @param string $namespace connection namespace
     *
     * @return integer
     */
    public static function getResultCode($namespace = 'default')
    {
        return self::_getInstance($namespace)->getResultCode();
    }
    
    /**
     * Memcache機能の無効化を行う
     * debug時用
     *
     * @param bool $disable setting
     *
     * @return integer
     */
    public static function setGetDisable($disable = true)
    {
        self::$_getDisable = $disable;
    }
    
    /**
     * 名前空間にある全てのキーを削除する
     *
     * @param string $namespace connection namespace
     * @param int    $delay     delay time
     *
     * @return type
     */
    public static function flush($namespace = 'default', $delay = 0)
    {
        if ($delay == 0) {
            return Memcached::deleteMulti(Memcached::getAllKeys($namespace), $namespace);
        }
        $items = Memcached::getMulti(Memcached::getAllKeys($namespace), $namespace);
        return Memcached::setMulti($items, $delay);
    }
}

/**
 * MemcacheConnectFailException
 *
 * Memcache接続失敗例外
 *
 * @category Framework
 * @package  BaseException
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class MemcachedConnectFailException extends CommonException
{
}
