<?php
/**
 * PhatescaffoldingDatabaseクラスファイル
 *
 * @category Framework
 * @package  scafolding
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * scaffoldingDatabaseクラス
 *
 * o-rmapperのscaffolfolding機能実装クラス
 *
 * @category Framework
 * @package  scafolding
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class ScaffoldingDatabase
{
    /**
     * o-rmapper自動生成実行
     *
     * @param type $config
     */
    public function execute($config)
    {
        $projectName = array_shift($config);
        $dbModelDirectory = PROJECT_ROOT . '/database/';
        $peerDirectory = $dbModelDirectory . 'peer/';
        $ormDirectory = $dbModelDirectory . 'orm/';
        $ormBaseDirectory = $ormDirectory . 'ormBase/';
        if (!file_exists($dbModelDirectory)) {
            mkdir($dbModelDirectory);
        }
        if (!file_exists($peerDirectory)) {
            mkdir($peerDirectory);
        }
        if (!file_exists($ormDirectory)) {
            mkdir($ormDirectory);
        }
        if (!file_exists($ormBaseDirectory)) {
            mkdir($ormBaseDirectory);
        }
        foreach ($config as $databaseName => $tmp) {
            $slaveDatabaseName = $tmp['slave_name'];
            $tableArray = $tmp['tables'];
            $isSharding = array_key_exists('sharding', $tmp) && (bool)$tmp['sharding'];
            echo "main  : " . $databaseName . " : \n";
            echo "slave : " . $slaveDatabaseName . " : \n";
            if ($isSharding) {
                echo "database constructed with sharding \n";
            }
            $dbh = \Phate\DB::getInstance($databaseName);
            foreach ($tableArray as $table) {
                // テーブル情報取得
                $tableName = $table['table_name'];
                echo $tableName . " exporting ...";
                $isMaster = false;
                $readOnly = false;
                if (array_key_exists('read_only', $table)) {
                    $readOnly = (bool)$table['read_only'];
                }
                if (array_key_exists('is_master', $table)) {
                    $isMaster = (bool)$table['is_master'];
                    $readOnly = $isMaster ? true : $readOnly;
                }
                $className = 'DB' . \Phate\Common::pascalizeString($tableName);
                if (preg_match('/^.+M$/', $className)) {
                    $className = substr($className, 0, -1) . 'Master';
                }
                if (preg_match('/^.+C$/', $className)) {
                    $className = substr($className, 0, -1) . 'Control';
                }
                if (preg_match('/^.+U$/', $className)) {
                    $className = substr($className, 0, -1) . 'User';
                }
                $sql = 'SHOW COLUMNS FROM ' . $tableName;
                if (!($columnStatus = $dbh->getAll($sql))) {
                    echo 'check your yaml (table_name:' . $tableName . ")\n";
                    exit();
                }
                $pkIsRowId = 'false';
                $pkeys = [];
                $pkeysCamel = [];
                $values = [];
                $types = [];
                $pkeyBindStatement = '';
                foreach ($columnStatus as $column) {
                    if (strstr($column['Extra'], 'auto_increment') !== false) {
                        $pkIsRowId = 'true';
                    }
                    if (strstr($column['Key'], 'PRI') !== false) {
                        $pkeys[] = $column['Field'];
                        $pkeysCamel[] = \Phate\Common::camelizeString($column['Field']);
                        $pkeyBindStatement .= '\'' . $column['Field'] . '\' => $' . \Phate\Common::camelizeString($column['Field']) . ', ';
                    }
                    $values[$column['Field']] = $column['Default'];
                    if ((strpos(strtolower($column['Type']), 'int') !== false)
                        || (strpos(strtolower($column['Type']), 'bit') !== false)
                        || (strpos(strtolower($column['Type']), 'float') !== false)
                        || (strpos(strtolower($column['Type']), 'double') !== false)
                        || (strpos(strtolower($column['Type']), 'decimal') !== false)) {
                        $types[$column['Field']] = '\PDO::PARAM_INT';
                    } elseif ((strpos(strtolower($column['Type']), 'binary') !== false)
                            || (strpos(strtolower($column['Type']), 'blob') !== false)) {
                        $types[$column['Field']] = '\PDO::PARAM_LOB';
                    } else {
                        $types[$column['Field']] = '\PDO::PARAM_STR';
                    }
                }
                $whereClause = implode(' = ? AND ', $pkeys) . ' = ? ';
                $pkeysList = $pkeysCamel ? '$' . implode(', $', $pkeysCamel) : '';
                $pkeysArgList = $pkeysCamel ? '$' . implode(', $', $pkeysCamel) . ',' : '';
                $memkeyPkeys = '$' . implode(" . '_' . $", $pkeysCamel);
                // ormapperBaseClass
                if (!file_exists($ormBaseDirectory . $className . 'OrmBase.class.php')) {
                    touch($ormBaseDirectory . $className . 'OrmBase.class.php');
                }
                $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/OrMapperBaseDesignBase');
                $str = str_replace('%%projectName%%', $projectName, $str);
                $str = str_replace('%%className%%', $className, $str);
                $str = str_replace('%%tableName%%', $tableName, $str);
                $pkeyStatement = '';
                foreach ($pkeys as $pkey) {
                    $pkeyStatement .= "        '" . $pkey . "',\n";
                }
                $str = str_replace('%%pkey%%', $pkeyStatement, $str);
                $str = str_replace('%%pkeys%%', $pkeysList, $str);
                $str = str_replace('%%pkeysArg%%', $pkeysArgList, $str);
                $str = str_replace('%%pkeyBindStatement%%', $pkeyBindStatement, $str);
                $str = str_replace('%%pkIsRowId%%', $pkIsRowId, $str);
                $str = str_replace('%%slaveDatabaseName%%', $slaveDatabaseName, $str);
                $str = str_replace('%%pureTableName%%', $tableName, $str);
                $str = str_replace('%%pkeyWhere%%', $whereClause, $str);
                $valueStatement = '';
                $methodStatement = '';
                $typeStatement = '';
                foreach ($values as $columnName => $defaultValue) {
                    $valueStatement .= "        '" . $columnName . "' => ";
                    if ((string)$defaultValue === '') {
                        $valueStatement .= "null,\n";
                    } else {
                        $valueStatement .= $types[$columnName] == '\PDO::PARAM_INT' ? $defaultValue . ",\n" : "'" . $defaultValue . "',\n";
                    }
                    
                    $methodStatement .= '    public function get' . \Phate\Common::pascalizeString($columnName) ."()\n";
                    $methodStatement .= '    {' . "\n";
                    $methodStatement .= '        return $this->toSave[\'' . $columnName . '\'];' . "\n";
                    $methodStatement .= '    }' . "\n";
                    $methodStatement .= '    ' . "\n";
                    $methodStatement .= '    public function set' . \Phate\Common::pascalizeString($columnName) .'($value)' . "\n";
                    $methodStatement .= '    {' . "\n";
                    $methodStatement .= '        if ($this->value[\'' . $columnName . '\'] != $value) {' . "\n";
                    $methodStatement .= '            $this->changeFlg = true;' . "\n";
                    $methodStatement .= '        }' . "\n";
                    $methodStatement .= '        $this->toSave[\'' . $columnName . '\'] = $value;' . "\n";
                    $methodStatement .= '    }' . "\n";
                    $methodStatement .= '    ' . "\n";
                    
                    $typeStatement .= "        '" . $columnName . "' => " .$types[$columnName] . ",\n";
                }
                $str = str_replace('%%value%%', $valueStatement, $str);
                $str = str_replace('%%type%%', $typeStatement, $str);
                if ($readOnly) {
                    $methodStatement .= '' . "\n";
                    $methodStatement .= '    public function save(\Phate\DBO $dbh) ' . "\n";
                    $methodStatement .= '    {' . "\n";
                    $methodStatement .= '        throw new \Phate\DatabaseException("cant save readOnly data o/r");' . "\n";
                    $methodStatement .= '    }' . "\n";
                    $methodStatement .= '    public function delete(\Phate\DBO $dbh) ' . "\n";
                    $methodStatement .= '    {' . "\n";
                    $methodStatement .= '        throw new \Phate\DatabaseException("cant delete readOnly data o/r");' . "\n";
                    $methodStatement .= '    }' . "\n";
                    $methodStatement .= '    public function logicDelete(\Phate\DBO $dbh) ' . "\n";
                    $methodStatement .= '    {' . "\n";
                    $methodStatement .= '        throw new \Phate\DatabaseException("cant update readOnly data o/r");' . "\n";
                    $methodStatement .= '    }' . "\n";
                    $methodStatement .= '' . "\n";
                }
                
                $str = "<?php\n" . $str . $methodStatement . '}' . "\n";
                file_put_contents($ormBaseDirectory . $className . 'OrmBase.class.php', $str);
                // ormapperClass
                if (!file_exists($ormDirectory . $className . 'Orm.class.php')) {
                    $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/OrMapperDesignBase');
                    $str = str_replace('%%projectName%%', $projectName, $str);
                    $str = str_replace('%%className%%', $className, $str);
                    $str = str_replace('%%tableName%%', $tableName, $str);
                    $oRMapperMethod = '    // this class will be used for override';
                    $str = "<?php\n" . str_replace('%%ORMapperMethod%%', $oRMapperMethod, $str);
                    file_put_contents($ormDirectory . $className . 'Orm.class.php', $str);
                }
                // peerClass
                if (!file_exists($peerDirectory . $className . 'Peer.class.php')) {
                    if ($isSharding) {
                        if ($isMaster) {
                            $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/ShardPeerMasterDesignBase');
                        } elseif ($readOnly) {
                            $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/ShardPeerRODesignBase');
                        } else {
                            $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/ShardPeerDesignBase');
                        }
                    } else {
                        if ($isMaster) {
                            $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/PeerMasterDesignBase');
                        } elseif ($readOnly) {
                            $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/PeerRODesignBase');
                        } else {
                            $str = file_get_contents(PHATE_SCAFFOLD_DIR . 'database/PeerDesignBase');
                        }
                    }
                    $str = str_replace('%%projectName%%', $projectName, $str);
                    $str = str_replace('%%tableName%%', $tableName, $str);
                    $str = str_replace('%%className%%', $className, $str);
                    $str = str_replace('%%pkeys%%', $pkeysList, $str);
                    $str = str_replace('%%pkeysArg%%', $pkeysArgList, $str);
                    $str = str_replace('%%databaseName%%', $databaseName, $str);
                    $str = str_replace('%%slaveDatabaseName%%', $slaveDatabaseName, $str);
                    $str = str_replace('%%pureTableName%%', $tableName, $str);
                    $str = str_replace('%%pkeyWhere%%', $whereClause, $str);
                    $str = str_replace('%%memkeyPkeys%%', $memkeyPkeys, $str);
                    $str = "<?php\n" . $str;
                    file_put_contents($peerDirectory . $className . 'Peer.class.php', $str);
                }
                echo " done \n";
            }
        }
    }
}
