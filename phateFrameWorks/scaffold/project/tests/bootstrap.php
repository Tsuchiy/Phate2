<?php
/**
 * bootstrap
 *
 * APIテスト用基底コード
 **/
ini_set('display_errors', 1);
set_time_limit(0);
mb_internal_encoding('UTF-8');

// アプリ名
define('PROJECT_NAME', '%%project_name%%');
// デバッグOnOff
$debug = true;
// Coreの読み込みしてlib周りが使えるようにする
include(realpath(dirname(__FILE__) . '/../../phateFrameWorks/base') . '/PhateCore.class.php');
$instance = \Phate\Core::getInstance(PROJECT_NAME, $debug);
define('URL_BASE', \Phate\Core::getConfigure('base_uri'));

// 各ツールをinclude
include(realpath(dirname(__FILE__)) . '/TestHttpRequester.php');
