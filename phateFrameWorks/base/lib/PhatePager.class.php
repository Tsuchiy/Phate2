<?php
/**
 * PhatePagerクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Pagerクラス
 *
 * ページャ処理クラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/01/01
 **/
class Pager
{
    protected $items;
    protected $pageSize = 10;
    protected $nowPage = 1;
    
    /**
     * コンストラクタ
     *
     * @param array $items 全アイテム
     *
     * @return void
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }
    
    /**
     * 1ページあたりの件数を設定する
     *
     * @param integer $pageSize ページサイズ
     *
     * @return void
     */
    public function setPageSize($pageSize)
    {
        if (!is_numeric($pageSize) || ($pageSize < 1)) {
            throw new CommonException('illegal page size');
        }
        $this->pageSize = $pageSize;
    }
    
    /**
     * 最初のページ番号を取得する
     *
     * @return integer
     */
    public function getFirstPage()
    {
        return count($this->items) ? 1 : 0;
    }
    
    /**
     * 最後のページ数を取得する
     *
     * @return integer
     */
    public function getLastPage()
    {
        return count($this->items) ? ceil(count($this->items) / $this->pageSize) : 0;
    }
    
    /**
     * 現在のページのデータを抽出する
     *
     * @param integer $pageNo page number
     *
     * @return array
     */
    public function getPageData($pageNo = null)
    {
        if (is_null($pageNo)) {
            $pageNo = $this->nowPage;
        } else {
            $this->nowPage = $pageNo;
        }
        
        $rtn = [];
        if (count($this->items) == 0) {
            return $rtn;
        }
        $i = 0;
        foreach ($this->items as $key => $value) {
            ++$i;
            if (ceil($i / $this->pageSize) == $pageNo) {
                $rtn[$key] = $value;
            } elseif (ceil($i / $this->pageSize) > $pageNo) {
                break;
            }
        }
        return $rtn;
    }
    
    /**
     * 現在のページ番号を設定
     *
     * @param integer $pageNo page number
     *
     * @return void
     */
    public function setNowPage($pageNo)
    {
        if (!is_numeric($pageNo) || ($pageNo < $this->getFirstPage())) {
            throw new CommonException('illegal page number');
        }
        if ($pageNo > $this->getLastPage()) {
            $pageNo = $this->getLastPage();
        }
        $this->nowPage = $pageNo;
    }

    /**
     * 現在のページ番号を取得する
     *
     * @return integer
     */
    public function getNowPage()
    {
        return count($this->items) ? $this->nowPage : 0;
    }

    /**
     * 現在の次のページ番号を取得する
     *
     * @return integer
     */
    public function getNextPage()
    {
        if (($this->getNowPage() + 1) > $this->getLastPage()) {
            return 0;
        }
        return $this->getNowPage() + 1;
    }
    
    /**
     * 現在の前のページ番号を取得する
     *
     * @return integer
     */
    public function getPrevPage()
    {
        if ($this->getNowPage() <= 1) {
            return 0;
        }
        return $this->getNowPage() - 1;
    }
    
    /**
     * 総アイテム数を返す
     *
     * @return integer
     */
    public function getAllCount()
    {
        return count($this->items);
    }
}
