#!/usr/bin/php
<?php
/**
 * PhateFrameworkバッチ参考用
 *
 * @package PhateFramework 
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2013/08/01
 **/
ini_set('display_errors', 0);

// application name
define('PROJECT_NAME', 'sample');
// debug OnOff
$debug = false;

/**
 * real code
 */
try {
    // Include Core
    include(dirname(__FILE__) . '/../phate/base/PhateCore.class.php');
    $instance = PhateCore::getInstance(PROJECT_NAME, $debug);
    $instance->doBatch('SampleBatch');
} catch (Exception $e) {
    if ($debug) {
        var_dump($e);
    }
}