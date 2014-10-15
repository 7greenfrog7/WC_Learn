<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_ANWSION', TRUE);
define('ENVIRONMENT_PHP_VERSION', '5.2.2');
//define('SYSTEM_LANG', 'en_US');

if (version_compare(PHP_VERSION, ENVIRONMENT_PHP_VERSION, '<'))
{
	die('Error: WeCenter require PHP version ' . ENVIRONMENT_PHP_VERSION . ' or newer');
}

if (version_compare(PHP_VERSION, '6.0', '>='))
{
	die('Error: WeCenter not support PHP version 6 currently');
}

define('START_TIME', microtime(TRUE));

if (function_exists('memory_get_usage'))
{
	define('MEMORY_USAGE_START', memory_get_usage());
}

if (! defined('AWS_PATH'))
{
	define('AWS_PATH', dirname(__FILE__) . '/');
}

if (defined('SAE_TMP_PATH'))
{
	define('IN_SAE', true);
}

define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');

if (defined('IN_SAE'))
{
	error_reporting(0);
	define('TEMP_PATH', rtrim(SAE_TMP_PATH, '/') . '/');
}
else
{
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
	define('TEMP_PATH', dirname(dirname(__FILE__)) . '/tmp/');
}

if (function_exists('get_magic_quotes_gpc'))
/*
magic_quotes_gpc函数在php中的作用是判断解析用户提示的数据
 如包括有:post、get、cookie过来的数据增加转义字符“\”，以确保这些数据不会引起程序，特别是数据库语句因为特殊字符引起的污染而出现致命的错误
 在magic_quotes_gpc=On的情况下，如果输入的数据有
单引号（’）、双引号（”）、反斜线（）与 NUL（NULL 字符）等字符都会被加上反斜线。这些转义是必须的，如果这个选项为off，那么我们就必须调用addslashes这个函数来为字符串增加转义。
正是因为这个选项必须为On，但是又让用户进行配置的矛盾，在PHP6中删除了这个选项，一切的编程都需要在magic_quotes_gpc=Off下进行了。在这样的环境下如果不对用户的数据进行转义，后果不仅仅是程序错误而已了。同样的会引起数据库被注入攻击的危险。所以从现在开始大家都不要再依赖这个设置为On了，以免有一天你的服务器需要更新到PHP6而导致你的程序不能正常工作。  
 */
{
	if (@get_magic_quotes_gpc()) // GPC 进行反向处理
	{
		if (! function_exists('stripslashes_gpc'))
		{
			function stripslashes_gpc(&$value)
			{
				$value = stripslashes($value);
			}
		}

		array_walk_recursive($_GET, 'stripslashes_gpc');
		array_walk_recursive($_POST, 'stripslashes_gpc');
		array_walk_recursive($_COOKIE, 'stripslashes_gpc');
		array_walk_recursive($_REQUEST, 'stripslashes_gpc');
	}
}

require_once(ROOT_PATH . 'version.php');
require_once(AWS_PATH . 'functions.inc.php');
//与 array_walk() 函数 类似，array_walk_recursive() 函数对数组中的每个元素应用回调函数。不一样的是，如果原数组中的元素也是数组，就会递归地调用回调函数，也就是说，会递归到更深层的数组中去。
array_walk_recursive($_GET, 'remove_invisible_characters');//此函数在functions_inc.php
array_walk_recursive($_POST, 'remove_invisible_characters');
array_walk_recursive($_COOKIE, 'remove_invisible_characters');
array_walk_recursive($_REQUEST, 'remove_invisible_characters');

if (@ini_get('register_globals'))
{	
//	ini_get是获取php.ini里的环境变量的值.
//语法
//string ini_get ( string varname )
//返回值如果为布尔型则为0或1
//例：
///*
//我们的 php.ini 包含了以下的设置：
//display_errors = On
//register_globals = Off
//post_max_size = 8M
//*/
//<?php
//echo 'display_errors = ' . ini_get('display_errors') . "\n";
//echo 'register_globals = ' . ini_get('register_globals') . "\n";
//echo 'post_max_size = ' . ini_get('post_max_size') . "\n";
//echo 'post_max_size+1 = ' . (ini_get('post_max_size')+1) . "\n";
//echo 'post_max_size in bytes = ' . return_bytes(ini_get('post_max_size'));
	if ($_REQUEST)
	{
		foreach ($_REQUEST AS $name => $value)
		{
			unset($$name);
		}
	}

	if ($_COOKIE)
	{
		foreach ($_COOKIE AS $name => $value)
		{
			unset($$name);
		}
	}
}

require_once(AWS_PATH . 'functions.app.php');

if (file_exists(AWS_PATH . 'config.inc.php'))
{
	require_once(AWS_PATH . 'config.inc.php');
}

load_class('core_autoload');

date_default_timezone_set('Etc/GMT-8');