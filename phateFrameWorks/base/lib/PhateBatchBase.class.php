<?php
/**
 * PhateBatchBaseクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * BatchBaseクラス
 *
 * バッチファイル作る際の継承元クラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
abstract class BatchBase
{
    /**
     * 初期化メソッド
     *
     * @return void
     */
    abstract public function initialize();

    /**
     * 実行メソッド
     *
     * @return void
     */
    abstract public function execute();
}
