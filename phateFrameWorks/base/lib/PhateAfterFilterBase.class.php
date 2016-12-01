<?php
/**
 * PhateAfterFilterBaseクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 *  PhateAfterFilterBaseクラス
 *
 *  AfterFilterを作る際の継承元クラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @abstract
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create   2014/11/13
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
abstract class PhateAfterFilterBase
{

    /**
     * フィルタの実行
     *
     * @param mixed $contents 出力
     *
     * @return   void
     * @abstract
     */
    abstract public function execute(&$contents);
}
