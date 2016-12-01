<?php
namespace Phate;

define('PHATE_ROOT_DIR', realpath(dirname(__FILE__).'/../../') . DIRECTORY_SEPARATOR);
define('PHATE_CONFIG_DIR', PHATE_ROOT_DIR . 'configs/');
define('PHATE_PROJECT_DIR', PHATE_ROOT_DIR . 'projects/');

/**
 * scaffoldingProjectクラス
 *
 * projectのscaffolfolding機能実装クラス
 *
 * @package PhateFramework scaffolding
 * @access  public
 * @author  Nobuo Tsuchiya <develop@m.tsuchi99.net>
 * @create  2014/11/13
 **/
class ScaffoldingProject
{
    /**
     * scaffolding実行
     *
     * @param string $name プロジェクト名
     */
    public function execute($name)
    {
        $scaffoldDir = PHATE_SCAFFOLD_DIR . 'project/';
        
        // put dispatcher
        $dir = PHATE_HTTPROOT_DIR . $name;
        mkdir($dir);
        chmod($dir, 0755);
        $dir .= DIRECTORY_SEPARATOR;
        mkdir($dir . 'css');
        chmod($dir . 'css', 0777);
        mkdir($dir . 'img');
        chmod($dir . 'img', 0777);
        mkdir($dir . 'js');
        chmod($dir . 'js', 0777);
        copy($scaffoldDir . 'htdocs/.htaccess', $dir . '.htaccess');
        copy($scaffoldDir . 'htdocs/robots.txt', $dir . 'robots.txt');
        $str = file_get_contents($scaffoldDir . 'htdocs/index.php');
        $str = str_replace('%%projectName%%', $name, $str);
        file_put_contents($dir . 'index.php', $str);

        // put main config
        $str = file_get_contents($scaffoldDir . 'configs/mainConfig.yml');
        $str = str_replace('%%projectName%%', $name, $str);
        file_put_contents(PHATE_CONFIG_DIR . $name . '.yml', $str);
        $str = file_get_contents($scaffoldDir . 'configs/loggerConfig.yml');
        $str = str_replace('%%projectName%%', $name, $str);
        file_put_contents(PHATE_CONFIG_DIR . $name . '_logger.yml', $str);
        copy($scaffoldDir . 'configs/filterConfig.yml', PHATE_CONFIG_DIR . $name . '_filter.yml');

        // make project directory
        $dir = PHATE_PROJECT_DIR . $name;
        mkdir($dir);
        $dir .= DIRECTORY_SEPARATOR;
        mkdir($dir . 'batches');
        $tmp = "<?php\n" . str_replace('%%projectName%%', $name, file_get_contents($scaffoldDir . 'projects/batches/CommonBatch'));
        file_put_contents($dir . 'batches/CommonBatch.class.php', $tmp);
        mkdir($dir . 'controllers');
        $tmp = "<?php\n" . str_replace('%%projectName%%', $name, file_get_contents($scaffoldDir . 'projects/controllers/CommonController'));
        file_put_contents($dir . 'controllers/CommonController.class.php', $tmp);
        mkdir($dir . 'controllers/index');
        $tmp = "<?php\n" . str_replace('%%projectName%%', $name, file_get_contents($scaffoldDir . 'projects/controllers/index/IndexController'));
        file_put_contents($dir . 'controllers/index/IndexController.class.php', $tmp);
        mkdir($dir . 'data');
        chmod($dir . 'data', 0777);
        mkdir($dir . 'filters');
        $tmp = "<?php\n" . str_replace('%%projectName%%', $name, file_get_contents($scaffoldDir . 'projects/filters/MaintenanceFilter'));
        file_put_contents($dir . 'filters/MaintenanceFilter.class.php', $tmp);
        mkdir($dir . 'maintenance');
        copy($scaffoldDir . 'projects/maintenance/toRename.yml', $dir . 'maintenance/toRename.yml');
        mkdir($dir . 'models');
        mkdir($dir . 'database');
        mkdir($dir . 'exception');
        $tmp = "<?php\n" . str_replace('%%projectName%%', $name, file_get_contents($scaffoldDir . 'projects/exception/ThrownException'));
        file_put_contents($dir . 'exception/ThrownException.class.php', $tmp);
        mkdir($dir . 'views');
        
        // make testing directory
        $dir = PHATE_ROOT_DIR . 'tests/' . $name;
        mkdir($dir);
        $dir .= DIRECTORY_SEPARATOR;
        copy($scaffoldDir . 'tests/phpunit.xml', $dir . 'phpunit.xml');
        $str = file_get_contents($scaffoldDir . 'tests/bootstrap.php');
        $str = str_replace('%%project_name%%', $name, $str);
        file_put_contents($dir . 'bootstrap.php', $str);
        copy($scaffoldDir . 'tests/TestHttpRequester.php', $dir . 'TestHttpRequester.php');
        mkdir($dir . 'developmentTool');
        copy($scaffoldDir . 'tests/developmentTool/DevelopmentTool.php', $dir . 'developmentTool/DevelopmentTool.php');
        mkdir($dir . 'controllers');
        mkdir($dir . 'controllers/Index');
        copy($scaffoldDir . 'tests/controllers/index/IndexControllerTest.php', $dir . 'controllers/Index/IndexControllerTest.php');
    }
}
