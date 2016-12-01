<?php
/**
 * Phate例外クラスファイル
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 **/
namespace Phate;

/**
 * KillException例外
 *
 * 処理を中断する際の例外。exitの代わりにthrowしてください。
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class KillException extends \Exception
{
}

/**
 * RedirectException例外
 *
 * 他のURLへリダイレクトをする際にthrowする例外。
 * 標準出力は全て破棄されます。
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class RedirectException extends \Exception
{
}

/**
 * CommonException例外
 *
 * 汎用例外。エラーとして処理されます。
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class CommonException extends \Exception
{
}

/**
 * NotFoundException例外
 *
 * 実行対象コントローラ等不明例外。処理が中断しエラーとして処理されます。
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class NotFoundException extends \Exception
{
}

/**
 * UnauthorizedException例外
 *
 * 認証失敗例外。リクエストに対する処理を行わずエラーとして処理されます。
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class UnauthorizedException extends \Exception
{
}

/**
 * DatabaseException例外
 *
 * データベース関連の例外
 *
 * @category Framework
 * @package  BaseExceptions
 * @author   Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @license  http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link     https://github.com/Tsuchiy/Phate
 * @create   2014/11/13
 **/
class DatabaseException extends \PDOException
{
}
