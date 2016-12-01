<?php
namespace Phate;

/**
 * JSRendererクラス
 *
 * JavaScriptのヘッダを付け描画をするレンダラ
 *
 * @package PhateFramework
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2014/11/13
 **/
class JsRenderer
{
    public function __construct()
    {
    }
    
    /**
     * 描画
     *
     * @param mixed $value
     */
    public function render($value)
    {
        Response::setContentType('text/javascript');
        print_r($value);
    }
}
