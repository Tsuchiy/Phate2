<?php
/**
 * PhateFrameworkディスパッチャファイル
 *
 * @category Framework
 * @package  dispatcher
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * PhateFrameworkディスパッチャ
 *
 * @category Framework
 * @package  dispatcher
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create  2014/11/13
 **/
// UTF-8限定にしておく
mb_language("Ja");
mb_internal_encoding('UTF-8');

// アプリ名
define('PROJECT_NAME', '%%projectName%%');

// デバッグモード取得
if (getenv('DEBUG_MODE')) {
    ini_set('display_errors', 1);
    set_time_limit(30);
    $debug = true;
    if (function_exists('xdebug_enable')) {
        ini_set('xdebug.default_enable', 1);
    }
} else {
    ini_set('display_errors', 0);
    set_time_limit(0);
    $debug = false;
    if (function_exists('xdebug_enable')) {
        ini_set('xdebug.default_enable', 0);
    }
}

// サーバ環境取得
if (!($serverEnv = getenv('SERVER_ENV'))) {
    throw new Exception('Server environment is empty');
}
define('SERVER_ENV', $serverEnv);


/*
 * コード開始
 */


try {
    // opcachecode対策
    if ($debug && function_exists('opcache_invalidate')) {
        opcache_invalidate(realpath(dirname(__FILE__) . '/../..') . '/project/' . PROJECT_NAME);
    }
    // apcu対策
    if ($debug && function_exists('apcu_store')) {
        ini_set('apcu.enabled', 0);
    }
    // Coreの読み込み
    include(realpath(dirname(__FILE__) . '/../../phateFrameWorks/base') . '/PhateCore.class.php');
    $instance = Core::getInstance(PROJECT_NAME, $debug);
    $instance->dispatch();
} catch (Exception $e) {
    if ($debug) {
        var_dump($e);
    }
}
