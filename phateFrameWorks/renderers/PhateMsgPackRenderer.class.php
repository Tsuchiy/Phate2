<?php
namespace Phate;

/**
 * MsgPackRendererクラス
 *
 * MsgPackでシリアライズしたバイナリの出力を行うレンダラ
 *
 * @package PhateFramework
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2014/11/13
 **/
class MsgPackRenderer
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
        Response::setContentType('application/x-msgpack');
        echo msgpack_serialize($value);
    }
}
