<?php
/**
 * XwebCms必须引入该入口
 * Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
 * Author: liu21st <liu21st@gmail.com>
 * Modify: jayter <jayter2@qq.com>
 */

define('XWEB_VERSION', '2.0.0');
define('THINK_VERSION', '5.0.23');
define('THINK_START_TIME', microtime(true));
define('THINK_START_MEM', memory_get_usage());
define('EXT', '.php');
define('DS', DIRECTORY_SEPARATOR);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
define('XWEB_PATH', __DIR__ . DS);
define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
define('THINK_PATH',ROOT_PATH .'thinkphp'.DS);
define('LIB_PATH', THINK_PATH . 'library' . DS);
define('CORE_PATH', LIB_PATH . 'think' . DS);
define('TRAIT_PATH', LIB_PATH . 'traits' . DS);
define('EXTEND_PATH', XWEB_PATH . 'extend' . DS);
define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
define('LOG_PATH', RUNTIME_PATH . 'log' . DS);
define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);
defined('CONF_PATH') or define('CONF_PATH', APP_PATH); // 配置文件目录
defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀


// 环境常量
define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);

// 载入Loader类
require CORE_PATH . 'Loader.php';//CORE_PATH

// 加载环境变量配置文件
if (is_file(ROOT_PATH . '.env')) {
    $env = parse_ini_file(ROOT_PATH . '.env', true);

    foreach ($env as $key => $val) {
        $name = ENV_PREFIX . strtoupper($key);

        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $item = $name . '_' . strtoupper($k);
                putenv("$item=$v");
            }
        } else {
            putenv("$name=$val");
        }
    }
}

// 注册自动加载
\think\Loader::register();

// 注册错误和异常处理机制
\think\Loader::registerError();

// 加载惯例配置文件
\think\Config::set(include XWEB_PATH . 'convention' . EXT);




