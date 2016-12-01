<?php
/**
 * PhateRedisクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Redisクラス
 *
 * 設定ファイルより接続済みRedisクラス取得クラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Redis
{
    private static $_config = [];
    private static $_shardConfig = [];
    private static $_realInstancePool = [];
    
    /**
     * 設定ファイルよりredisの設定を取得
     *
     * @return void
     */
    public static function setConfig()
    {
        // 必ずここは通るのでRedisモジュールの確認
        if (!class_exists('Redis')) {
            throw new CommonException('no redis module');
        }
        if (!($fileName = Core::getConfigure('redis_config_file'))) {
            throw new CommonException('no redis configure');
        }
        if (!($config = Common::parseConfigYaml(PHATE_CONFIG_DIR . $fileName))) {
            throw new CommonException('no redis configure');
        }
        foreach ($config as $key => $arr) {
            if (array_key_exists('servers', $arr)) {
                self::$_shardConfig[$key] = array_keys($arr['servers']);
                foreach ($arr['servers'] as $servername => $conf) {
                    $conf['database'] = isset($conf['database']) ? $conf['database'] : 0;
                    self::$_config[$servername] = $conf;
                }
            } else {
                $arr['database'] = isset($arr['database']) ? $arr['database'] : 0;
                self::$_config[$key] = $arr;
            }
        }
    }
    
    /**
     * 接続先別のインスタンスを生成
     *
     * @param string  $host             host url
     * @param integer $port             port
     * @param int     $database         database(0-15)
     * @param integer $readWriteTimeout timeout seconds
     * @param boolean $serialize        use serializer
     * @param boolean $persistent       use persistent connect
     *
     * @return Redis
     */
    private static function _getRealInstance($host, $port, $database, $readWriteTimeout = null, $serialize = true, $persistent = false)
    {
        if (!isset(self::$_realInstancePool[$host][$port])) {
            $redis = new \Redis();
            $method = $persistent ? "pconnect" : "connect";
            if (is_null($port)) {
                if (!is_null($readWriteTimeout)) {
                    $redis->$method($host, $readWriteTimeout);
                } else {
                    $redis->$method($host);
                }
            } else {
                if (!is_null($readWriteTimeout)) {
                    $redis->$method($host, $port, $readWriteTimeout);
                } else {
                    $redis->$method($host, $port);
                }
            }
            // 初期設定(falseでも無視)
            self::$_realInstancePool[$host][$port] = $redis;
        }
        self::$_realInstancePool[$host][$port]->select($database);
        if ($serialize) {
            if (function_exists('igbinary_serialize')) {
                self::$_realInstancePool[$host][$port]->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
            } else {
                self::$_realInstancePool[$host][$port]->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            }
        } else {
            self::$_realInstancePool[$host][$port]->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        }
        return self::$_realInstancePool[$host][$port];
    }
    
    /**
     * 接続名のインスタンスを返す
     *
     * @param string $namespace 接続先名前空間
     *
     * @return \Redis
     */
    public static function getInstance($namespace)
    {
        // config読み込み
        if (!self::$_config) {
            self::setConfig();
        }
        if (!isset(self::$_config[$namespace]) && !isset(self::$_shardConfig[$namespace])) {
            throw new RedisConnectFailException('cant resolve namespace on redis');
        }
        // レプリケーションしている時は接続分散and死活判定用に設定展開
        if (isset(self::$_config[$namespace])) {
            $configures = [$namespace => self::$_config[$namespace]];
        } else {
            $configures = [];
            shuffle(self::$_shardConfig[$namespace]);
            foreach (self::$_shardConfig[$namespace] as $servername) {
                if (!isset(self::$_config[$servername])) {
                    throw new RedisConnectFailException('illegal config on redis');
                }
                $configures[$servername] = self::$_config[$servername];
            }
        }
        $instance = null;
        // インスタンス接続（sentinel対応）
        foreach ($configures as $servername => $serverConfig) {
            try {
                if (array_key_exists('domain', $serverConfig)) {
                    $host = $serverConfig['domain'];
                    $port = null;
                    $readWriteTimeout = null;
                } else {
                    $host = $serverConfig['host'];
                    $port = $serverConfig['port'];
                    $readWriteTimeout = array_key_exists('read_write_timeout', $serverConfig) ? $serverConfig['read_write_timeout'] : null;
                }
                $database = array_key_exists('database', $serverConfig) ? $serverConfig['database'] : 0;
                $serialize = array_key_exists('serialize', $serverConfig) ? $serverConfig['serialize'] : true;
                $persistent = array_key_exists('persistent', $serverConfig) ? $serverConfig['persistent'] : false;
                $instance = self::_getRealInstance($host, $port, $database, $readWriteTimeout, $serialize, $persistent);
                break;
            } catch (\RedisException $e) {
                Logger::info($host);
                Logger::info($e->getMessage());
                Logger::info($e->getCode());
                $instance = null;
                continue;
            }
        }
        // 全部に接続確立できてない
        if (is_null($instance)) {
            throw new RedisConnectFailException('namespace : ' . $namespace);
        }
        return $instance;
    }
    
    /**
     * 接続中のインスタンスを全て明示的に切断する
     *
     * @return void
     */
    public static function disconnect()
    {
        if (!self::$_realInstancePool || !is_array(self::$_realInstancePool)) {
            return;
        }
        foreach (self::$_realInstancePool as $k1 => $t1) {
            foreach ($t1 as $k2 => $t2) {
                unset(self::$_realInstancePool[$k1][$k2]);
            }
        }
        self::$_realInstancePool = [];
        return;
    }
}

/**
 * RedisConnectFailException
 *
 * Redis接続失敗例外
 *
 * @category Framework
 * @package  BaseException
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class RedisConnectFailException extends CommonException
{
    
}
