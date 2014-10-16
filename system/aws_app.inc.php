<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package		WeCenter Framework
 * @author		WeCenter Dev Team
 * @copyright	Copyright (c) 20011 - 2013, WeCenter, Inc.
 * @license		http://www.wecenter.com/license/
 * @link		http://www.wecenter.com/
 * @since		Version 1.0
 * @filesource
 */


/**
 * WeCenter 系统初始化文件
 *
 * 处理基本类库与请求
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Front-controller
 * @author		WeCenter Dev Team
 */
class AWS_APP
{
	private static $config; //run()---init()初始化成为core/config.php中class的对象，获得系统的基础配置数组
	private static $db;//run()---init()初始化成为core/db.php中class的对象，获得系统的基础配置数组
	private static $form;//run()---init()初始化成为core/form.php中class的对象，获得系统的基础配置数组
	private static $upload;//run()---init()初始化成为core/upload.php中class的对象，获得上传的基础配置数组
	private static $image;//run()---init()初始化成为core/image.php中class的对象，获得图像的基础配置数组
	private static $pagination;//run()---init()初始化成为core/pagination.php中class的对象，获得分页的基础配置数组
	private static $cache;//run()---init()初始化成为core/cache.php中class的对象，获得缓存机制的基础配置数组
	private static $lang;//run()---init()初始化成为core/lang.php中class的对象，获得语言的基础配置数组
	private static $session;//self::$session = new Zend_Session_Namespace(G_COOKIE_PREFIX . '_Anwsion');
	private static $captcha;//run()---init()初始化成为core/captcha.php中class的对象，获得系统的基础配置数组
	private static $mail;//run()---init()初始化成为core/mail.php中class的对象，获得邮件的基础配置数组
	
	public static $session_type = 'file';
	
	private static $models = array();
	private static $plugins = array();
	
	public static $settings = array();
	public static $_debug = array();
	
	/**
	 * 系统运行
	 */
	public static function run()
	{
		//系统初始化步骤
		self::init();
		
		load_class('core_uri')->set_rewrite();
		
		if (!$app_dir = load_class('core_uri')->app_dir)
		{
			$app_dir = ROOT_PATH . 'app/home/';
		}

		// 传入应用目录,返回控制器对象
		$handle_controller = self::create_controller(load_class('core_uri')->controller, $app_dir);//感觉应该是目录对象
		$action_method = load_class('core_uri')->action . '_action';//解析url请求的动作
		// 判断
		if (! is_object($handle_controller) OR ! method_exists($handle_controller, $action_method))
		{
			HTTP::error_404();
		}
		
		if (method_exists($handle_controller, 'get_access_rule'))
		{
			$access_rule = $handle_controller->get_access_rule();
		}
		
		// 判断访问规则使用白名单还是黑名单, 默认使用黑名单
		if ($access_rule)
		{			
			// 黑名单, 黑名单中的检查 'white' 白名单,白名单以外的检查 (默认是黑名单检查)
			if (isset($access_rule['rule_type']) AND $access_rule['rule_type'] == 'white')
			{
				if ((! $access_rule['actions']) OR (! in_array(load_class('core_uri')->action, $access_rule['actions'])))
				{
					self::login();
				}
			}
			else if (isset($access_rule['actions']) AND in_array(load_class('core_uri')->action, $access_rule['actions']))	// 非白就是黑名单
			{
				self::login();
			}
		
		}
		else
		{
			self::login();
		}
		
		// 执行
		$handle_controller->$action_method();
	}
	
	/**
	 * 系统初始化
	 */
	private static function init()
	{
		//set_exception_handler参数中描述的函数必须在调用 set_exception_handler() 函数之前定义。
		set_exception_handler(array('AWS_APP', 'exception_handle'));
		self::$config = load_class('core_config');
		self::$db = load_class('core_db');
		self::$plugins = load_class('core_plugins');
		self::$settings = self::model('setting')->get_settings();//获取系统设置
		
		if ((!defined('G_SESSION_SAVE') OR G_SESSION_SAVE == 'db') AND get_setting('db_version') > 20121123)//获取系统设置
		//get_setting()在index.php中被require载入，所以在此可以直接调用
		//get_setting()最后还是调用AWS_APP下的$setting的初始化方法self::$settings = self::model('setting')->get_settings();
		///models/setting.php中get_settings()是查询数据库，system_settings字段
		//未定义SESSION的存储方式，或者定义为存储在数据库 并且数据库版本一定要大于20121123
		{
			Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable(array(
			//Zend库,设置用户自定义会话存储函数
			    'name' 					=> get_table('sessions'),//functions.inc.php->get_table获取带表前缀的数据库表名
			    'primary'				=> 'id',
			    'modifiedColumn'		=> 'modified',
			    'dataColumn'			=> 'data',
			    'lifetimeColumn'		=> 'lifetime',
				//'authIdentityColumn'	=> 'uid'
			)));
			
			self::$session_type = 'db';
		}
		
		Zend_Session::setOptions(array(
			'name' => G_COOKIE_PREFIX . '_Session',
			'cookie_domain' => G_COOKIE_DOMAIN
		));
		
		if (G_SESSION_SAVE == 'file' AND G_SESSION_SAVE_PATH) //config.dist.php
		{
			Zend_Session::setOptions(array(
				'save_path' => G_SESSION_SAVE_PATH
			));
		}
		
		Zend_Session::start(); //session开始
		
		self::$session = new Zend_Session_Namespace(G_COOKIE_PREFIX . '_Anwsion');
		
		if ($default_timezone = get_setting('default_timezone'))//存在
		{
			date_default_timezone_set($default_timezone); //php原生函数
		}

		$img_url = get_setting('img_url');
		$base_url = get_setting('base_url');
		 
		! empty($img_url) ? define('G_STATIC_URL', $img_url) : define('G_STATIC_URL', $base_url . '/static');
		//需要看一下：这边为什么要用到crond
		if (self::config()->get('system')->debug)
		{
			if ($cornd_timer = self::cache()->getGroup('crond'))//crond可定义计划任务； 任务调度守护进程 
			{				
				foreach ($cornd_timer AS $cornd_tag)
				{
					if ($cornd_runtime = self::cache()->get($cornd_tag))
					{
						AWS_APP::debug_log('crond', 0, 'Tag: ' . str_replace('crond_timer_', '', $cornd_tag) . ', Last run time: ' . date('Y-m-d H:i:s', $cornd_runtime));
					}
				}
			}
		}
	}
	
	/**
	 * 创建 Controller
	 *
	 * 根据传入的控制器名称与 app_dir 载入 Controller 相关文件
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	object
	 */
	public static function create_controller($controller, $app_dir)
	{
		if (trim($app_dir) == '' OR trim($controller, '/') === '')
		{
			return false;
		}
		
		$class_file = $app_dir . $controller . '.php';
		
		if (! file_exists($class_file))
		{
			return false;
		}
		
		if (! class_exists($controller, false))
		{
			require_once ($class_file);
		}
		
		if (class_exists($controller, false))
		{
			return new $controller();
		}
		
		return false;
	}
	
	/**
	 * 异常处理
	 *
	 * 获取系统异常 & 处理
	 *
	 * @access	public
	 * @param	object
	 */
	public static function exception_handle(Exception $exception)
    {
		$exception_message = "Application error\n------\nMessage: " . $exception->getMessage() . "\n------\nBuild: " . G_VERSION . " " . G_VERSION_BUILD . "\nPHP Version: " . PHP_VERSION . "\nUser Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n------\n" . $exception->__toString();
				
        show_error($exception_message, $exception->getMessage());
    }
    
   	/**
	 * 格式化系统返回消息
	 *
	 * 格式化系统返回的消息 json 数据包给前端进行处理
	 *
	 * @access	public
	 * @param	array
	 * @param	integer
	 * @return	string
	 */
	public static function RSM($rsm, $errno = 0, $err = '')
	{
		return array(
			'rsm' => $rsm, 
			'errno' => (int)$errno, 
			'err' => $err,
		);
	}
	
	/**
	 * 检查用户登录状态
	 *
	 * 检查用户登录状态并带领用户进入相关操作
	 */	
	public static function login()
	{
		if (! USER::get_client_uid())
		{
			if ($_POST['_post_type'] == 'ajax')
			{
				H::ajax_json_output(self::RSM(null, -1, AWS_APP::lang()->_t('会话超时, 请重新登录')));
			}
			else
			{
				HTTP::redirect('/account/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
			}
		}
	}
	
	/**
	 * 获取系统配置
	 *
	 * 调用 core/config.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function config()
	{
		return self::$config;
	}
	
	/**
	 * 获取系统上传类
	 *
	 * 调用 core/upload.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function upload()
	{
		if (!self::$upload)
		{
			self::$upload = load_class('core_upload');
		}
		
		return self::$upload;
	}
	
	/**
	 * 获取系统图像处理类
	 *
	 * 调用 core/image.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function image()
	{
		if (!self::$image)
		{
			self::$image = load_class('core_image');
		}
		
		return self::$image;
	}
	
	/**
	 * 获取系统语言处理类
	 *
	 * 调用 core/lang.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function lang()
	{
		if (!self::$lang)
		{
			self::$lang = load_class('core_lang');
		}
		
		return self::$lang;
	}
	
	/**
	 * 获取系统验证码处理类
	 *
	 * 调用 core/captcha.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function captcha()
	{
		if (!self::$captcha)
		{
			self::$captcha = load_class('core_captcha');
		}
		
		return self::$captcha;
	}
	
	/**
	 * 获取系统缓存处理类
	 *
	 * 调用 core/cache.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function cache()
	{
		if (!self::$cache)
		{
			self::$cache = load_class('core_cache');
		}
		
		return self::$cache;
	}
	
	/**
	 * 获取系统表单提交验证处理类
	 *
	 * 调用 core/form.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function form()
	{
		if (!self::$form)
		{
			self::$form = load_class('core_form');
		}
		
		return self::$form;
	}
	
	/**
	 * 获取系统邮件处理类
	 *
	 * 调用 core/mail.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function mail()
	{
		if (!self::$mail)
		{
			self::$mail = load_class('core_mail');
		}
		
		return self::$mail;
	}
	
	/**
	 * 获取系统插件处理类
	 *
	 * 调用 core/plugins.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function plugins()
	{
		if (!self::$plugins)
		{
			self::$plugins = load_class('core_plugins');
		}
		
		return self::$plugins;
	}
	
	/**
	 * 获取系统分页处理类
	 *
	 * 调用 core/pagination.php
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function pagination()
	{
		if (!self::$pagination)
		{
			self::$pagination = load_class('core_pagination');
		}
		
		return self::$pagination;
	}
	
	/**
	 * 调用系统 Session
	 *
	 * 此功能基于 Zend_Session 类库
	 * 
	 * @access	public
	 * @return	object
	 */	
	public static function session()
	{
		return self::$session;
	}
	
	/**
	 * 调用系统数据库
	 *
	 * 此功能基于 Zend_DB 类库
	 * 
	 * @access	public
	 * @param	string
	 * @return	object
	 */	
	public static function db($db_object_name = 'master')
	{
		if (!self::$db) //若没有设置database
		{
			return false;
		}
		
		return self::$db->setObject($db_object_name);
	}
	
	/**
	 * 记录系统 Debug 事件
	 *
	 * 打开 debug 功能后相应事件会在页脚输出
	 * 
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 */	
	public static function debug_log($type, $expend_time, $message)
	{
		self::$_debug[$type][] = array(
			'expend_time' => $expend_time,
			'log_time' => microtime(true),
			'message' => $message
		);
	}

	/**
	 * 调用系统 Model
	 *
	 * 根据命名规则调用相应的 Model 并初始化类库保存于 self::$models 数组, 防止重复初始化
	 * 
	 * @access	public
	 * @param	string
	 * @return	object
	 */	
	public static function model($model_class = null)
	{
		if (!$model_class)
		{
			$model_class = 'AWS_MODEL';
		}
		else if (! strstr($model_class, '_class'))
		{
			$model_class .= '_class';
		}
		
		if (! isset(self::$models[$model_class]))
		{
			$model = new $model_class();
			self::$models[$model_class] = $model;
		}
		
		return self::$models[$model_class];
	}
}