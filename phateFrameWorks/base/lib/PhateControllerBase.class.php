<?php
/**
 * PhateControllerBaseクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * ControllerBaseクラス
 *
 * コントローラファイル作る際の継承元クラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
abstract class ControllerBase
{
    /**
     * 一番初めに呼ばれる、メソッド
     *
     * @return boolean falseを返すと、そこで処理が止まります。
     *
     * @abstract
     */
    abstract public function initialize();

    /**
     * 実処理
     *
     * @return   void
     * @see      validate()
     * @abstract
     */
    abstract public function action();

    /**
     * バリデートする
     *
     * @return   true|false boolean
     * @abstract
     */
    abstract public function validate();

    /**
     * 上のvalidate()でfalseが返った場合の処理。
     *
     * @param array $resultArray validate result array
     *
     * @return   void
     * @see      validate()
     * @abstract
     */
    abstract public function validatorError($resultArray);
}
