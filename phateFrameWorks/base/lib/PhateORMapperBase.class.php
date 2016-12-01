<?php
/**
 * PhateORMapperBaseクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * ORMapperBaseクラス
 *
 * O-RMapperの先祖クラス。基礎パラメータと基礎メソッド群。
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class ORMapperBase
{
    protected $tableName;
    
    protected $pkey = [];
    
    protected $pkeyIsRowId = false;
    
    protected $value = [];
    
    protected $type = [];
    
    protected $toSave = [];
    
    protected $changeFlg = true;
    
    protected $fromHydrateFlg = false;
    
    /**
     * プロパティ取得用汎用メソッド(予備用)
     *
     * @param string $name カラム名
     *
     * @return string
     **/
    public function __get($name)
    {
        if (!array_key_exists($name, $this->toSave)) {
            throw new DatabaseException('column not found');
        }
        return $this->toSave[$name];
    }
    
    /**
     * プロパティ設定用汎用メソッド(予備用)
     *
     * @param string $name  カラム名
     * @param string $value 値
     *
     * @return void
     **/
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->value)) {
            throw new DatabaseException('column not found');
        }
        if ($this->value[$name] != $value) {
            $this->changeFlg = true;
        }
        $this->toSave[$name] = $value;
    }

    /**
     * 行配列をオブジェクトに設定する
     *
     * @param array $row データ行の配列
     *
     * @return void
     **/
    public function hydrate(array $row)
    {
        $this->changeFlg = false;
        $this->fromHydrateFlg = true;
        foreach ($this->value as $column => $value) {
            if (array_key_exists($column, $row)) {
                $this->value[$column] = $row[$column];
                $this->toSave[$column] = $row[$column];
            }
        }
    }

    /**
     * オブジェクトのプロパティを行配列の形にする
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->toSave;
    }
    
    /**
     * オブジェクトの状態をDBサーバに反映させるためにInsert/Update文を発行する
     *
     * @param DBO $dbh DBハンドラ
     *
     * @return boolean
     **/
    public function save(DBO $dbh)
    {
        // hydrate後に変更がない場合はなにもしない
        if ($this->fromHydrateFlg && !$this->changeFlg) {
            return false;
        }
        // "modified"カラムは特別扱い
        if (array_key_exists('modified', $this->toSave) && ($this->toSave['modified'] === $this->value['modified'])) {
            $this->toSave['modified'] = Timer::getDateTime();
        }
        // "updated"カラムは特別扱い
        if (array_key_exists('updated', $this->toSave) && ($this->toSave['updated'] === $this->value['updated'])) {
            $this->toSave['updated'] = Timer::getDateTime();
        }
        // "updated_at"カラムは特別扱い
        if (array_key_exists('updated_at', $this->toSave) && ($this->toSave['updated_at'] === $this->value['updated_at'])) {
            $this->toSave['updated_at'] = Timer::getDateTime();
        }
        // insertの場合
        if (!$this->fromHydrateFlg) {
            // "created"カラムは特別扱い
            if (array_key_exists('created', $this->toSave) && is_null($this->toSave['created'])) {
                $this->toSave['created'] = Timer::getDateTime();
            }
            // "inserted"カラムは特別扱い
            if (array_key_exists('inserted', $this->toSave) && is_null($this->toSave['inserted'])) {
                $this->toSave['inserted'] = Timer::getDateTime();
            }
            // "created_at"カラムは特別扱い
            if (array_key_exists('created_at', $this->toSave) && is_null($this->toSave['created_at'])) {
                $this->toSave['created_at'] = Timer::getDateTime();
            }
            // "inserted"カラムは特別扱い
            if (array_key_exists('inserted_at', $this->toSave) && is_null($this->toSave['inserted_at'])) {
                $this->toSave['inserted_at'] = Timer::getDateTime();
            }
            // autoincrementに新規行を追加するとき
            $toSave = $this->toSave;
            if ($this->pkeyIsRowId && is_null($this->toSave[$this->pkey[0]])) {
                $pkey = $this->pkey[0];
                unset($toSave[$pkey]);
            }
            $columns = array_keys($toSave);
            $columnClause = '(' . implode(',', $columns) . ')';
            $placeClause = str_repeat('?,', count($toSave));
            $placeClause = '(' . substr($placeClause, 0, -1) . ')';
            $sql = 'INSERT INTO ' .$this->tableName . ' ' . $columnClause . ' VALUES ' . $placeClause;
            $sth = $dbh->prepare($sql);
            $i = 0;
            foreach ($columns as $column) {
                $value = $this->toSave[$column];
                if (isset($this->type[$column])) {
                    $sth->bindValue(++$i, $value, $this->type[$column]);
                } else {
                    $sth->bindValue(++$i, $value, \PDO::PARAM_STR);
                }
            }
            if ($sth->execute() === false) {
                return false;
            }
            if ($this->pkeyIsRowId && is_null($this->toSave[$this->pkey[0]])) {
                $this->toSave[$pkey] = $dbh->lastInsertId();
            }
        } else {
            // updateの場合
            $setClause = '';
            $setParam = [];
            foreach ($this->toSave as $key => $value) {
                $setClause .= $setClause == '' ? ' SET ' : ' , ';
                $setClause .= $key .' = ? ';
                $setParam[$key] = $value;
            }
            $whereClause = '';
            $whereParam = [];
            foreach ($this->pkey as $key) {
                $whereClause .= $whereClause == '' ? ' WHERE ' : ' AND ';
                $whereClause .= $key . ' = ? ';
                $whereParam[$key] = $this->value[$key];
            }
            $sql = 'UPDATE ' . $this->tableName . $setClause . $whereClause;
            $sth = $dbh->prepare($sql);
            $i = 0;
            foreach ($setParam as $column => $value) {
                if (isset($this->type[$column])) {
                    $sth->bindValue(++$i, $value, $this->type[$column]);
                } else {
                    $sth->bindValue(++$i, $value, \PDO::PARAM_STR);
                }
            }
            foreach ($whereParam as $column => $value) {
                if (isset($this->type[$column])) {
                    $sth->bindValue(++$i, $value, $this->type[$column]);
                } else {
                    $sth->bindValue(++$i, $value, \PDO::PARAM_STR);
                }
            }
            if ($sth->execute() === false) {
                return false;
            }
        }
        $this->value = $this->toSave;
        $this->changeFlg = false;
        $this->fromHydrateFlg = true;
        return true;
    }
    
    /**
     * オブジェクトに対応する行をDatabaseから削除する
     *
     * @param DBO $dbh DBハンドラ
     *
     * @return boolean
     **/
    public function delete(DBO $dbh)
    {
        // readonlyDBは処理禁止
        if ($dbh->isReadOnly()) {
            throw new DatabaseException('this database is readonly');
        }
        // hydrate済みか確認
        if (!$this->fromHydrateFlg) {
            return false;
        }
        $whereClause = '';
        foreach ($this->pkey as $key) {
            $whereClause .= $whereClause == '' ? ' WHERE ' : ' AND ';
            $whereClause .= $key . ' = ?';
        }
        $sql = 'DELETE FROM ' . $this->tableName . $whereClause;
        $sth = $dbh->prepare($sql);
        $i = 0;
        foreach ($this->pkey as $column) {
            if (isset($this->type[$column])) {
                $sth->bindValue(++$i, $this->value[$column], $this->type[$column]);
            } else {
                $sth->bindValue(++$i, $this->value[$column], \PDO::PARAM_STR);
            }
        }
        if ($sth->execute() === false) {
            return false;
        }
        $this->changeFlg = false;
        $this->fromHydrateFlg = true;
        return true;
    }
    
    /**
     * オブジェクトに対応する行に論理削除的updateを発行する
     *
     * @param DBO $dbh DBハンドラ
     *
     * @return boolean
     **/
    public function logicDelete(DBO $dbh)
    {
        // readonlyDBは処理禁止
        if ($dbh->isReadOnly()) {
            throw new DatabaseException('this database is readonly');
        }
        // deleted,delete_flgカラムの確認
        if (!array_key_exists('delete_flg', $this->value) && !array_key_exists('deleted', $this->value)) {
            return false;
        }
        if ((array_key_exists('delete_flg', $this->value) && $this->value['delete_flg'] == 1)
            && (array_key_exists('deleted', $this->value) && $this->value['deleted'] == 1)
        ) {
            return false;
        }
                
        // hydrate済みか確認
        if (!$this->fromHydrateFlg) {
            return false;
        }
        $whereClause = '';
        foreach ($this->pkey as $key) {
            $whereClause .= $whereClause == '' ? ' WHERE ' : ' AND ';
            $whereClause .= $key . ' = ?';
        }
        $modifiedClause = '';

        // "modified"カラムは特別扱い
        if (array_key_exists('modified', $this->value)) {
            $modifiedClause = ",modified = '" . Timer::getDateTime() . "' ";
        }
        // "updated"カラムは特別扱い
        if (array_key_exists('updated', $this->value)) {
            $modifiedClause = ",updated = '" . Timer::getDateTime() . "' ";
        }
        // "updated_at"カラムは特別扱い
        if (array_key_exists('updated_at', $this->value)) {
            $modifiedClause = ",updated_at = '" . Timer::getDateTime() . "' ";
        }
        // delete部分SQL
        $tmpArr=[];
        if (array_key_exists('deleted', $this->value)) {
            $tmpArr[] = 'deleted';
        }
        if (array_key_exists('delete_flg', $this->value)) {
            $tmpArr[] = 'delete_flg';
        }
        $deleteClause = implode('= 1 AND ', $tmpArr) . ' = 1 ';
        
        $sql = 'UPDATE ' . $this->tableName . ' SET ' . $deleteClause . $modifiedClause . $whereClause;
        Logger::info($sql);
        $sth = $dbh->prepare($sql);
        $i = 0;
        foreach ($this->pkey as $column) {
            if (isset($this->type[$column])) {
                $sth->bindValue(++$i, $this->value[$column], $this->type[$column]);
            } else {
                $sth->bindValue(++$i, $this->value[$column], \PDO::PARAM_STR);
            }
        }
        if ($sth->execute() === false) {
            return false;
        }

        // 更新後処理
        $this->value['deleted'] = 1;
        // "modified"カラムは特別扱い
        if (array_key_exists('modified', $this->value)) {
            $this->value['modified'] = Timer::getDateTime();
        }
        // "updated"カラムは特別扱い
        if (array_key_exists('updated', $this->value)) {
            $this->value['updated'] = Timer::getDateTime();
        }
        // "updated_at"カラムは特別扱い
        if (array_key_exists('updated_at', $this->value)) {
            $this->value['updated_at'] = Timer::getDateTime();
        }
        
        $this->changeFlg = false;
        $this->fromHydrateFlg = true;
        $this->toSave = $this->value;
        return true;
    }
}
