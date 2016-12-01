<?php
/**
 * PhateCoreクラス及び共通処理ファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/*
 * 最低限の共通処理部
 * 
 */
// フレームワーク情報
define('PHATE_FRAMEWORK_VERSION', 'v2.0rc');

// 各ディレクトリ定数宣言
define('PHATE_ROOT_DIR', realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR);
define('PHATE_FRAMEWORK_DIR', PHATE_ROOT_DIR.'phateFrameWorks/');
define('PHATE_BASE_DIR', PHATE_FRAMEWORK_DIR . 'base/');
define('PHATE_LIB_VENDOR_DIR', PHATE_FRAMEWORK_DIR . 'vendor/');
define('PHATE_CONFIG_DIR', PHATE_ROOT_DIR . 'configs/');
define('PHATE_CACHE_DIR', PHATE_ROOT_DIR . 'cache/');
define('PHATE_PROJECT_DIR', PHATE_ROOT_DIR . 'projects/');

// フレームワーク基底部の読み込み
$dh = opendir(PHATE_BASE_DIR);
while (($file = readdir($dh)) !== false) {
    if (is_file(PHATE_BASE_DIR . $file) && preg_match('/^.*\.class\.php$/', $file)) {
        if ($file == basename(__FILE__)) {
            continue;
        }
        include PHATE_BASE_DIR . $file;
    }
}
closedir($dh);
//----------------------------------------------------------

/**
 * Coreクラス
 *
 * Frameworkを実行する中心部分となります。
 * web経由での展開はdispatch、バッチの実行はdoBatchを用いてください。
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Core
{
    private static $_instance;
    private static $_appName;
    private static $_isDebug;
    private static $_conf;
    private static $_includeClassList;
    
    /**
     * コンストラクタ
     *
     * @param string  $appName projectName
     * @param boolean $isDebug isDebugMode?
     *
     * @return void
     */
    private function __construct($appName, $isDebug)
    {
        // プロジェクト定数宣言
        define('PROJECT_ROOT', PHATE_PROJECT_DIR . $appName . DIRECTORY_SEPARATOR);
        define('PROJECT_MODELS_DIR', PROJECT_ROOT .'models' . DIRECTORY_SEPARATOR);
        define('PROJECT_LIBS_DIR', PROJECT_ROOT .'libs' . DIRECTORY_SEPARATOR);
        define('PROJECT_DATABASE_DIR', PROJECT_ROOT .'database' . DIRECTORY_SEPARATOR);
        define('PROJECT_CONTROLLERS_DIR', PROJECT_ROOT .'controllers' . DIRECTORY_SEPARATOR);
        define('PROJECT_VIEWS_DIR', PROJECT_ROOT .'views' . DIRECTORY_SEPARATOR);
        
        // 初期値
        self::$_appName = $appName;
        self::$_isDebug = $isDebug;
        // 基礎configの読み込み
        self::$_conf = Common::parseConfigYaml(PHATE_CONFIG_DIR . $appName . '.yml');
        // 実行時間の初期化
        Timer::init();
        // ロガーを初期化
        Logger::init();
        // エラーハンドルにロガーをセット
        set_error_handler(['\Phate\Logger', 'fatal']);
        // autoloaderの設定
        spl_autoload_register('\Phate\Core::classLoader');
        if (file_exists(PHATE_LIB_VENDOR_DIR . 'PhateVendorAutoLoader.class.php')) {
            include PHATE_LIB_VENDOR_DIR . 'PhateVendorAutoLoader.class.php';
            if (method_exists('\Phate\VendorAutoLoader', 'registLoader')) {
                spl_autoload_register(['\Phate\VendorAutoLoader', 'registLoader']);
            }
        }
    }
    
    /**
     * Singleton取得
     *
     * @param string  $appName projectName
     * @param boolean $isDebug is DebugMode?
     *
     * @return Core
     */
    public static function getInstance($appName = null, $isDebug = false)
    {
        
        if (!isset(self::$_instance)) {
            if (is_null($appName)) {
                throw new Exception('no appName');
            }
            self::$_instance = new Core($appName, $isDebug);
        }
        return self::$_instance;
    }
    
    /**
     * オートロード対象リスト取得
     *
     * @return array libraryArray[Name=>Path]
     */
    private static function _getIncludeClassList()
    {
        // 取得済み確認
        if (self::$_includeClassList) {
            return self::$_includeClassList;
        }
        // キャッシュ確認
        $apcuCacheName = self::$_appName . '_autoload_' . SERVER_ENV . '.cache';
        if (function_exists('apcu_store') && !self::$_isDebug) {
            if ($rtn = apcu_fetch($apcuCacheName)) {
                self::$_includeClassList = msgpack_unserialize($rtn);
                return self::$_includeClassList;
            }
        }
        $cacheFileName = PHATE_CACHE_DIR . self::$_appName . '_autoload_' . SERVER_ENV . '.cache';
        if (file_exists($cacheFileName) && !self::$_isDebug) {
            self::$_includeClassList = Common::unserialize(file_get_contents($cacheFileName));
            if (function_exists('apcu_store')) {
                apcu_store($apcuCacheName, msgpack_serialize(self::$_includeClassList), 0);
            }
            return self::$_includeClassList;
        }
        $rtn = [
            'Phate' => [],
            PROJECT_NAME => [],
        ];
        // オートロードロジック展開
        // フレームワークライブラリ
        // 対象ディレクトリ生成
        // ディレクトリ展開
        $fileNames = array_merge(
            [],
            Common::getFileNameRecursive(PHATE_BASE_DIR . 'lib'),
            Common::getFileNameRecursive(PHATE_FRAMEWORK_DIR . 'renderers')
        );
        // 配列保存
        foreach ($fileNames as $value) {
            if (preg_match('/^.*\.class\.php$/', $value)) {
                $rtn['Phate'][substr(substr(basename($value), 0, -10), 5)] = $value;
            }
        }
        // プロジェクトデータベースライブラリ
        // 対象ディレクトリ生成
        // config設定ディレクトリ
        $dirArray = [PROJECT_MODELS_DIR, PROJECT_LIBS_DIR, PROJECT_DATABASE_DIR];
        if (array_key_exists('autoload', self::$_conf) && is_array(self::$_conf['autoload'])) {
            $dirArray = array_merge($dirArray, self::$_conf['autoload']);
        }
        // ディレクトリ展開
        $pjFileNames = [];
        foreach ($dirArray as $line) {
            if (file_exists($line)) {
                $pjFileNames = array_merge($pjFileNames, Common::getFileNameRecursive($line));
            }
        }
        foreach ($pjFileNames as $value) {
            if (preg_match('/^.*\.class\.php$/', $value)) {
                $rtn[PROJECT_NAME][substr(basename($value), 0, -10)] = $value;
            }
        }
        // キャッシュ保存
        file_put_contents($cacheFileName, Common::serialize($rtn), LOCK_EX);
        if (substr(sprintf('%o', fileperms($cacheFileName)), -4) !=='0777') {
            chmod($cacheFileName, 0777);
        }
        if (function_exists('apcu_store') && !self::$_isDebug) {
            apcu_store($apcuCacheName, msgpack_serialize($rtn), 0);
        }
        self::$_includeClassList = $rtn;
        return self::$_includeClassList;
    }
    
    /**
     * オートローダ用メソッド
     *
     * @param string $className className
     *
     * @return void
     */
    public static function classLoader($className)
    {
        $classList = self::_getIncludeClassList();
        // namespace対応
        $names = [];
        if (preg_match('/^(.*)\\\\(.+)$/', $className, $names)) {
            if ($names[1] == '\\Phate' || $names[1] == 'Phate') {
                if (array_key_exists($names[2], $classList['Phate'])) {
                    include_once $classList['Phate'][$names[2]];
                    return;
                }
            }
            if ($names[1] == '\\' . PROJECT_NAME || $names[1] == PROJECT_NAME) {
                if (array_key_exists($names[2], $classList[PROJECT_NAME])) {
                    include_once $classList[PROJECT_NAME][$names[2]];
                    return;
                }
            }
        }
    }
    
    /**
     * バージョン取得
     *
     * @return string version string
     */
    public static function getVersion()
    {
        return PHATE_FRAMEWORK_VERSION;
    }
    
    /**
     * アプリ名取得
     *
     * @return string processingProjectName
     */
    public static function getAppName()
    {
        return self::$_appName;
    }
    
    /**
     * デバッグモード取得
     *
     * @access public
     *
     * @return boolean is debug mode?
     */
    public static function isDebug()
    {
        return self::$_isDebug;
    }
    
    /**
     * メイン設定取得
     *
     * @param string $key configKey
     *
     * @return array configure by environment
     */
    public static function getConfigure($key = null)
    {
        if (is_null($key)) {
            return self::$_conf;
        }
        if (array_key_exists($key, self::$_conf)) {
            return self::$_conf[$key];
        } else {
            return null;
        }
    }
    
    /**
     * HTTPリクエスト展開実行
     *
     * @return void
     */
    public function dispatch()
    {
        // httpリクエストの初期化・整理
        Request::init();
        // 対象の存在確認
        $controllerFile = PROJECT_ROOT . 'controllers/' . Request::getCalledModule() . DIRECTORY_SEPARATOR . Request::getController() . '.class.php';
        if (!file_exists($controllerFile)) {
            Response::setHttpStatus(Response::HTTP_NOT_FOUND);
            Response::sendHeader();
            exit();
        }
        try {
            // load filter config
            $beforeFilters = [];
            $afterFilters = [];
            if (array_key_exists('filter_config_file', self::$_conf)) {
                $filterConfig = Common::parseConfigYaml(PHATE_CONFIG_DIR . self::$_conf['filter_config_file']);
                if (array_key_exists('before', $filterConfig) && is_array($filterConfig['before'])) {
                    $beforeFilters = $filterConfig['before'];
                }
                if (array_key_exists('after', $filterConfig) && is_array($filterConfig['after'])) {
                    $afterFilters = $filterConfig['after'];
                }
            }
            // beforeFilter
            if ($beforeFilters) {
                foreach ($beforeFilters as $filter) {
                    $fileName = PROJECT_ROOT . 'filters/' . $filter . '.class.php';
                    if (file_exists($fileName)) {
                        include $fileName;
                        $filterName = '\\' . PROJECT_NAME . '\\' . $filter;
                        $filterClass = new $filterName;
                        $filterClass->execute();
                    }
                }
            }
            ob_start();
            // Controller実行
            if (!file_exists(PROJECT_ROOT . 'controllers/CommonController.class.php')) {
                throw new CommonException('CommonController file not found');
            }
            include PROJECT_ROOT . 'controllers/CommonController.class.php';
            include $controllerFile;
            $className = '\\' . PROJECT_NAME . '\\' . Request::getController();
            $controller = new $className;
            ControllerExecuter::execute($controller);
            $content = ob_get_contents();
            ob_end_clean();
            ob_start();
            // afterFilter
            if ($afterFilters) {
                foreach ($afterFilters as $filter) {
                    $fileName = PROJECT_ROOT . 'filters/' . $filter . '.class.php';
                    if (file_exists($fileName)) {
                        include $fileName;
                        $filterName = '\\' . PROJECT_NAME . '\\' . $filter;
                        $filterClass = new $filterName;
                        $filterClass->execute($content);
                    }
                }
            }
        } catch (NotFoundException $e) {
            ob_end_clean();
            Response::setHttpStatus(Response::HTTP_NOT_FOUND);
            Response::sendHeader();
            if (self::$_isDebug) {
                var_dump($e);
            }
            exit();
        } catch (KillException $e) {
            ob_end_flush();
            exit();
        } catch (RedirectException $e) {
            ob_end_clean();
            try {
                Response::sendHeader();
            } catch (KillException $e) {
                exit();
            } catch (\Exception $e) {
                Response::setHttpStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
                Response::sendHeader();
                var_dump($e);
                exit();
            }
        } catch (\Exception $e) {
            $body = ob_get_contents();
            ob_end_clean();
            if (file_exists(PROJECT_ROOT . 'exception/ThrownException.class.php')) {
                include PROJECT_ROOT . 'exception/ThrownException.class.php';
                $className = '\\' . PROJECT_NAME . '\\ThrownException';
                $thrownExceptionClass = new $className;
                $thrownExceptionClass->execute($e);
                exit();
            }
            if ($e instanceof UnauthorizedException) {
                Response::setHttpStatus(Response::HTTP_UNAUTHORIZED);
            } else {
                Response::setHttpStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            Response::sendHeader();
            if (self::$_isDebug) {
                echo $body;
                var_dump($e);
            }
            exit();
        }
        // 一応Content-Lengthの設定もしておく
        Response::setHeader('Content-Length', strlen($content));
        // レスポンスヘッダ設定
        Response::sendHeader();
        // 画面出力
        echo $content;
        ob_end_flush();
        return;
    }

    /**
     * バッチ実行
     *
     * @param string $classname execute class name
     *
     * @return void
     */
    public function doBatch($classname)
    {
        try {
            // batch実行
            include PROJECT_ROOT . 'batches/CommonBatch.class.php';
            $batchFile = PROJECT_ROOT . 'batches/' . $classname . '.class.php';
            if (!file_exists($batchFile)) {
                throw new NotFoundException('batch file not found');
            }
            include $batchFile;
            $classNameWithSpace = '\\' . PROJECT_NAME . '\\' . $classname;
            $controller = new $classNameWithSpace;
            $controller->initialize();
            $controller->execute();
            return;
        } catch (KillException $e) {
            exit();
        } catch (Exception $e) {
            Logger::error('batch throw exception');
            ob_start();
            var_dump($e);
            $dump = ob_get_contents();
            ob_end_clean();
            Logger::error("exception dump : \n" . $dump);
            if (self::$_isDebug) {
                echo $dump;
            }
            exit();
        }
        return;
    }
    
    /**
     * デストラクタ
     *
     * @return void
     */
    public function __destruct()
    {
        if (class_exists('\Phate\Redis')) {
            Redis::disconnect();
        }
        if (class_exists('\Phate\Memcached')) {
            Memcached::disconnect();
        }
        if (class_exists('\Phate\DB')) {
            DB::disconnect();
        }
    }
}
