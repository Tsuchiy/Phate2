<?php
namespace Phate;

/**
 * CsvRendererクラス
 *
 * csvとして出力するレンダラ
 *
 * @package PhateFramework
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2014/11/13
 **/
class CsvRenderer
{
    
    private $_columnNames = [];
    
    public function __construct()
    {
    }

    public function setColumnNames(array $columnNameArray)
    {
        $this->_columnNames = $columnNameArray;
    }
    
    /**
     * 描画
     *
     * @param mixed $value
     */
    public function render(array $listArray, $filename = "")
    {
        Response::setContentType('text/csv');
        if (!is_null($filename)) {
            if ($filename === "") {
                $filename = str_replace(' ', '_', Timer::getDateTime());
            }
            if (!preg_match('/^.*\.csv$/', $filename)) {
                $filename .= '.csv';
            }
            Response::setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }
        ob_start();
        $fp = fopen('php://output', 'w');
        if ($this->_columnNames) {
            fputcsv($fp, $this->_columnNames);
        }
        foreach ($listArray as $row) {
            fputcsv($fp, $row);
        }
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }
}
