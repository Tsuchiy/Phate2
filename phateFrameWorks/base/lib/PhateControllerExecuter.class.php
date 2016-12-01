<?php
/**
 * PhateControllerExecuterクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * ControllerExecuterクラス
 *
 * Controllerを実行する手順について記載してあります。
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class ControllerExecuter
{
    /**
     * コントローラを実行する側
     *
     * @param ControllerBase $controllerClass 実行するcontroller
     *
     * @return void
     */
    public static function execute(ControllerBase $controllerClass)
    {
        if (($controllerClass->initialize()) === false) {
            throw new KillException();
        }
        $validateResult = $controllerClass->validate();
        if (is_array($validateResult)) {
            $result = true;
            foreach ($validateResult as $line) {
                foreach ($line as $v) {
                    if ($v['result'] == false) {
                        $result = false;
                        break 2;
                    }
                }
            }
            if (!$result) {
                $controllerClass->validatorError($validateResult);
                return;
            }
        }
        $controllerClass->action();
        return;
    }
}
