<?php
/**
 * PhateVendorAutoLoaderクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * VendorAutoLoaderクラス
 *
 * 他社提供ライブラリを無理やりオートロードするためのクラス
 * 最初にspl_registerに登録してもらう
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
class VendorAutoLoader
{
    /**
     * オートローダー
     *
     * @param type $className
     *
     * @retrun void
     */
    public static function registLoader($className)
    {
    }
}
