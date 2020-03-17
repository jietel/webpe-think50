<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\view\driver;

use think\App;
use think\exception\TemplateNotFoundException;
use think\Loader;
use think\Log;
use think\Request;
use think\Template;
use think\Cache;
/**
 * 优化的视图类(按需初始化模板引擎)
 * @author Jayter2ff
 */
class Think
{
	// 模板引擎实例
	private $template;
	// 模板引擎参数
	protected $config = [
			// 视图基础目录（集中式）
			'view_base'   => '',
			// 模板起始路径
			'view_path'   => '',
			// 模板文件后缀
			'view_suffix' => 'html',
			// 模板文件名分隔符
			'view_depr'   => DS,
			// 是否开启模板编译缓存,设为false则每次都会重新编译
			'tpl_cache'   => true,
			// 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
			'auto_rule'   => 1,
			//@jayter 增加
			'cache_time'	=> 0, // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
			'cache_prefix'	=> '', //模板缓存前缀标识，可以动态改变
			'cache_suffix'	=> 'php',  //缓存后缀
			'layout_name'   => 'layout', // 布局模板入口文件
			'tpl_replace_string' => [], //该项少了View取得会变为0
	];
	
	public function __construct($config = [])
	{
		$this->config = array_merge($this->config, $config);
		if (empty($this->config['view_path'])) {
			$this->config['view_path'] = App::$modulePath . 'view' . DS;
		}
		
	}
	/**
	 * 配置或者获取模板引擎参数
	 * @access private
	 * @param string|array  $name 参数名
	 * @param mixed         $value 参数值
	 * @return mixed
	 */
	public function config($name, $value = null)
	{
		if (is_array($name)) {
			$this->config = array_merge($this->config, $name);
		} elseif (is_null($value)) {
			return isset($this->config[$name]) ? $this->config[$name] : '';
		} else {
			$this->config[$name]   = $value;
		}
	}
	/**
	 * 初始化模板引擎(@jayter按需初始化)
	 * @param array $config
	 * @return \think\Template
	 */
	public function template(){
		if(!$this->template){
			$this->template = new Template($this->config);
		}
		return $this->template;
	}
	
	/**
	 * 检测是否存在模板文件
	 * @access public
	 * @param string $template 模板文件或者模板规则
	 * @return bool
	 */
	public function exists($template)
	{
		if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
			$template = $this->parseTemplate($template);
		}
		return is_file($template);
	}
	
	/**
	 * 渲染模板文件(jayter缓存时不初始化Template)
	 * @access public
	 * @param string    $template 模板文件
	 * @param array     $data 模板变量
	 * @param array     $config 模板参数
	 * @return void
	 */
	public function fetch($template, $data = [], $config = [])
	{
		$this->config($config);
		if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
			$template = $this->parseTemplate($template);
		}
		if (!is_file($template)) {
			throw new TemplateNotFoundException('template not exists:' . $template, $template);
		} 
		//非调试模式先查看缓存避免加载模板引擎 @jayter
		if(!App::$debug){
			//静态渲染缓存
			if (!empty($this->config['cache_id']) && $this->config['display_cache']) {
				$cacheContent = Cache::get($this->config['cache_id']);
				if (false !== $cacheContent) {
					echo $cacheContent;
					return;
				}
			}
			//本地解析缓存
			$cacheFile = TEMP_PATH . $this->config['cache_prefix'].md5($this->config['layout_name'].$template).'.'.$this->config['cache_suffix'];
			if($this->checkCache($cacheFile)){
				if (!empty($data) && is_array($data)) {
					extract($data, EXTR_OVERWRITE);
				}
				include $cacheFile;
				return;
			}
		}
		App::$debug && Log::record('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]', 'info');
		$this->template()->fetch($template, $data, $config);
		
	}
	
	/**
	 * 渲染模板内容(jayter缓存时不初始化Template)
	 * @access public
	 * @param string    $template 模板内容
	 * @param array     $data 模板变量
	 * @param array     $config 模板参数
	 * @return void
	 */
	public function display($template, $data = [], $config = [])
	{
		$this->config($config);
		if(!App::$debug){ 
			//非调试模式先查看缓存避免加载模板引擎 @jayter
			$cacheFile = TEMP_PATH.$this->config['cache_prefix'].md5($template).'.'.$this->config['cache_suffix'];
			if($this->checkCache($cacheFile)){
				if (!empty($data) && is_array($data)) {
					extract($data, EXTR_OVERWRITE);
				}
				include $cacheFile;
				return;
			}
		}
		$this->template()->display($template, $data, $config);
	}
	
	/**
	 * 自动定位模板文件
	 * @access private
	 * @param string $template 模板文件规则
	 * @return string
	 */
	private function parseTemplate($template)
	{
		$request = Request::instance();
		if (strpos($template, '@')) {
			// 跨模块调用
			list($module, $template) = explode('@', $template);
		}
		if ($this->config['view_base']) {
			// 基础视图目录
			$module = isset($module) ? $module : $request->module();
			$path   = $this->config['view_base'] . ($module ? $module . DS : '');
		} else {
			$path = isset($module) ? APP_PATH . $module . DS . 'view' . DS : $this->config['view_path'];
		}
		
		$depr = $this->config['view_depr'];
		if (0 !== strpos($template, '/')) {
			$template   = str_replace(['/', ':'], $depr, $template);
			$controller = Loader::parseName($request->controller());
			if ($controller) {
				if ('' == $template) {
					// 如果模板文件名为空 按照默认规则定位
					$template = str_replace('.', DS, $controller) . $depr . (1 == $this->config['auto_rule'] ? Loader::parseName($request->action(true)) : $request->action());
				} elseif (false === strpos($template, $depr)) {
					$template = str_replace('.', DS, $controller) . $depr . $template;
				}
			}
		} else {
			$template = str_replace(['/', ':'], $depr, substr($template, 1));
		}
		return $path . ltrim($template, '/') . '.' . $this->config['view_suffix'];
	}
	
	/**
	 * 检查编译缓存是否有效(@jayter)
	 * @param string $cacheFile 缓存文件名
	 * @return boolean
	 */
	public function checkCache($cacheFile){
		// 未开启缓存功能
		if (!$this->config['tpl_cache']) {
			return false;
		}
		// 缓存文件不存在
		if (!is_file($cacheFile)) {
			return false;
		}
		//不再支持检查文件是否更新(需要请参考Template->checkCahce)
		
		$cacheTime = intval($this->config['cache_time']);
		if (0 != $cacheTime && $_SERVER['REQUEST_TIME'] > filemtime($cacheFile) + $cacheTime) {
			return false; // 缓存是否在有效期
		}
		return true;
	}
	
	public function __call($method, $params)
	{
		$this->template();
		return call_user_func_array([$this->template, $method], $params);
	}
}
