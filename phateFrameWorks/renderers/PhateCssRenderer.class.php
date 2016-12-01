<?php
namespace Phate;

/**
 * CssRendererクラス
 *
 * CSSのヘッダを付け描画をするレンダラ
 *
 * @package PhateFramework
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2014/11/13
 **/
class CssRenderer
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
        Response::setContentType('text/css');
        print_r($value);
    }
}
