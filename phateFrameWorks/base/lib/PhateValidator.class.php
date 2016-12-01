<?php
/**
 * PhateValidatorクラスファイル
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * Validatorクラス
 *
 * 値検証用のクラス
 *
 * @category Framework
 * @package  BaseLibrary
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class Validator
{

    private static $_instance;

    private $_validatorList = [
        'noblank'          => 'typeNoBlank',           // 必須項目
        'number'           => 'typeNumber',            // 数字項目
        'numberminmax'     => 'typeNumberMinMax',      // 数字項目（上限下限）
        'alphabet'         => 'typeAlphabet',          // アルファベットのみ
        'alphabetornumber' => 'typeAlphabetOrNumber',  // 英数アルファベットのみ
        'lenminmax'        => 'typeLenMinMax',         // byte数（上限下限）
        'widthminmax'      => 'typeWidthMinMax',       // 文字数（上限下限）
        'bool'             => 'typeBool',              // ブーリアンか
        'enum'             => 'typeEnum',              // 配列列挙にマッチするか
        'array'            => 'typeArray',             // 配列
        'notarray'         => 'typeNotArray',          // 配列以外
        'arraycountminmax' => 'typeArrayCountMinMax',  // 配列の大きさ（上限下限）
        'preg'             => 'typePreg',              // preg_match
        'mbEreg'           => 'typeMbEreg',            // mb_ereg_match
    ];

    private $_registeredValidators = [];
    private $_resultValidation = [];

    /**
     * コンストラクタ
     **/
    private function __construct()
    {
    }
    
    /**
     * シングルトン生成
     *
     * @return Validator class instance
     **/
    public static function getInstance()
    {
        if (!is_object(self::$_instance)) {
            self::$_instance = new Validator();
        }
        return self::$_instance;
    }

    /**
     * Validatorにルールをセット
     *
     * @param string  $paramName     パラメータ名
     * @param string  $validatorName validator名
     * @param array   $param         (範囲指定・具体的なデータ等)
     * @param boolean $isChain       違反時処理継続するか
     *
     * @return void
     * @throws CommonException
     **/
    public function setValidator($paramName, $validatorName, $param = [], $isChain = false)
    {
        if (!in_array($validatorName, array_keys($this->_validatorList))) {
            throw new CommonException('validator error');
        }
        if (!array_key_exists($paramName, $this->_registeredValidators)) {
            $this->_registeredValidators[$paramName] = [];
        }
        $this->_registeredValidators[$paramName][] = ['name' => $validatorName, 'param' => $param, 'isChain' => $isChain];
    }

    /**
     * Validatorを実行
     *
     * @return array 結果セット
     **/
    public function execute()
    {
        foreach ($this->_registeredValidators as $paramName => $validationArray) {
            $result = true;
            $requestParam = Request::getRequestParam($paramName) === "" ? null : Request::getRequestParam($paramName);
            foreach ($validationArray as $validation) {
                if (!$result && !$validation['isChain']) {
                    break;
                }
                $function = $this->_validatorList[$validation['name']];
                $result = $this->$function($requestParam, $validation['param']);
                $this->_resultValidation[$paramName][] = ['name' => $validation['name'], 'param' => $validation['param'], 'result' => $result];
            }
        }
        return $this->_resultValidation;
    }
    
    /**
     * 空白(null)は許さない
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeNoBlank($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return false;
        }
        if (is_string($requestParam) && ($requestParam === "")) {
            return false;
        }
        return true;
    }
    /**
     * 数字のみ許す
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeNumber($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        return is_numeric($requestParam);
    }
    /**
     * 数値、ある数字以上ある数字以下
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeNumberMinMax($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_numeric($requestParam)) {
            return false;
        }
        if (!is_array($validationParam) || (count($validationParam) != 2)) {
            return false;
        }
        if (!is_numeric($min = array_shift($validationParam))) {
            return false;
        }
        if (!is_numeric($max = array_shift($validationParam))) {
            return false;
        }
        if (($min > $requestParam) || ($max < $requestParam)) {
            return false;
        }
        return true;
    }

    /**
     * アルファベットのみ
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeAlphabet($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_string($requestParam)) {
            return false;
        }
        return ctype_alpha($requestParam);
    }
    
    /**
     * アルファベットか数字によって構成された文字列
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeAlphabetOrNumber($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_string($requestParam)) {
            return false;
        }
        return ctype_alnum($requestParam);
    }
    
    /**
     * バイト数の長さをチェック
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeLenMinMax($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_string($requestParam)) {
            return false;
        }
        if (strlen($requestParam) == 0) {
            return true;
        }
        if (!is_array($validationParam) || (count($validationParam) != 2)) {
            return false;
        }
        if (!is_numeric($min = array_shift($validationParam))) {
            return false;
        }
        if (!is_numeric($max = array_shift($validationParam))) {
            return false;
        }
        if (($min > strlen($requestParam)) || ($max < strlen($requestParam))) {
            return false;
        }
        return true;
    }
    
    /**
     * 文字数の長さチェック（全角も一文字）
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeWidthMinMax($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_string($requestParam)) {
            return false;
        }
        if (strlen($requestParam) == 0) {
            return true;
        }
        if (!is_array($validationParam) || (count($validationParam) != 2)) {
            return false;
        }
        if (!is_numeric($min = array_shift($validationParam))) {
            return false;
        }
        if (!is_numeric($max = array_shift($validationParam))) {
            return false;
        }
        if (($min > mb_strlen($requestParam)) || ($max < mb_strlen($requestParam))) {
            return false;
        }
        return true;
    }
    
    /**
     * 入力されたものが指定された配列の要素か
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeEnum($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_array($validationParam)) {
            return false;
        }
        return in_array($requestParam, $validationParam);
    }
    
    /**
     * 入力されたものがbooleanか
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeBool($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        return is_bool($requestParam);
    }
    
    /**
     * 配列か
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeArray($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        return is_array($requestParam);
    }
    
    /**
     * 配列ではないか
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeNotArray($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        return !is_array($requestParam);
    }
    
    /**
     * 配列の要素数が範囲内か
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeArrayCountMinMax($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_array($requestParam)) {
            return false;
        }
        if (!is_array($validationParam) || (count($validationParam) != 2)) {
            return false;
        }
        if (!is_numeric($min = array_shift($validationParam))) {
            return false;
        }
        if (!is_numeric($max = array_shift($validationParam))) {
            return false;
        }
        $cnt = count($requestParam);
        if (($min > $cnt) || ($max < $cnt)) {
            return false;
        }
        return true;
    }
    /**
     * 正規表現にマッチするか
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam regular expression string
     *
     * @return boolean
     **/
    protected function typePreg($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_string($requestParam) || !is_string($validationParam)) {
            return false;
        }
        return preg_match($validationParam, $requestParam) !== false;
    }

    /**
     * 全角使用正規表現にマッチするか
     *
     * @param mixed $requestParam    request parameter
     * @param mixed $validationParam paramter for validate
     *
     * @return boolean
     **/
    protected function typeMbEreg($requestParam, $validationParam)
    {
        if (is_null($requestParam)) {
            return true;
        }
        if (!is_string($requestParam) || !is_string($validationParam)) {
            return false;
        }
        return mb_ereg($validationParam, $requestParam) !== false;
    }
}
