<?php
namespace Phate;

/**
 * JsonRendererクラス
 *
 * json_encodeしたtextの出力を行うレンダラ
 *
 * @package PhateFramework
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2014/11/13
 **/
class JsonRenderer
{
    public function __construct()
    {
    }
    /**
     * 描画
     *
     * @param mixed $value
     */
    public function render(array $value)
    {
        if (!($rtn = json_encode($value))) {
            throw new CommonException('cant json encode parameter');
        }
        Response::setContentType('application/json');
        echo $rtn;
    }
}
