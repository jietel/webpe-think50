框架优化记录：
1. 统一使用入口文件start.php
2. /think/Loader.php文件288行增加LOAD_COMPOSER



## 框架优化记录：
+ 统一使用入口文件thinkphp\start.php优化配置
+ 系统加载修改think\Loader.php优化代替,Composer按需加载
+ 应用管理修改think\App.php
+ 助手函数修改think\helper.php
+ 系统视图修改think\view\driver\Think.php实现模板引擎4个类按需加载
+ 接口基类使用xwebcms\control\Restful代替，更完善的支持
+ 精简的模型类xwebcms\library\Model,按需加载
+ Env类删除,使用Loader::env
+ 开发结构调整webpe\functions.php和webpe\command.php默认加载
 
 
### helper.php

 
### think\Loader.php
+ autoload 优化
+ registerError 增加的按需加载异常类，在start.php中使用
+ register 去掉自动检测classmap配置?,映射部分类到xwebcms\library下
+ register 去掉自动检测Composer，改为手动调用registerComposerLoader
+ registerComposerLoader 改为手动调用
+ Env::get移到Loader::env

### think\App.php
+ 去掉application\lang\语言使用
+ init方法中去掉检测配置缓存init.php?
+ init方法中去掉模块扩展配置extra目录检测
+ init方法增加common防混淆，模块中命名为xxx/xxx.common.php
+ initCommon方法中加载helper.php,不从extra_file_list中加入
+ 修改module方法里实现模块单独可设置 app_debug

### think\View.php
+ 去掉__CSS__和__JS__替换

### think\Template.php
+ 增加{$xweb.xx.xx}兼容{$Think.xx.xx}
+ 修改parseThinkVar和parseVar类调用改为助手函数
+ 修改\think\Request::instance()为request()
+ 修改\think\Lang::get()为lang()

### think\template\taglib\Cx.php
+ 比较标签compare去掉equal|notequal
+ 判断变量定义标签present|notpresent去掉，可用{:isset($test)}
+ 判断常量定义标签defined|notdefined去掉，基本用不到
+ 定义常量标签define去掉，谁还在模板里用这个
+ 文件加载标签load|js|css去掉,用不到
+ URL标签去掉,用{:url(...)}
+ foreach|volist标签中empty="<div>"不能传HTML代码，但是可以用中括号代替[div class='test']xxx[/div]
+ foreach|volist标签中去掉:支持用函数传数组


### think\db\Connection.php
+ 增加SQL表前缀替换sqlParse

### think\Error.php
+ getExceptionHandler 增加自动切换ajax的异常模板

### think\debug\Html.php
+ output方法加入xweb模式输出调试信息


### xwebcms\library\Model.php
+ 模型继承使用，使用new实例化
+ 只使用模型中的方法不再加载Query.php等查询构造器
+ 支持$this->db()->全构造器查询
+ 适用于API等高性能原生SQL查询的场景，加载文件和占用内存减少很多

 
### xwebcms\control\Controller.php
+ 优化类并去掉trait引入以提升性能
+ 



ThinkPHP5在保持快速开发和大道至简的核心理念不变的同时，PHP版本要求提升到5.4，优化核心，减少依赖，基于全新的架构思想和命名空间实现，是ThinkPHP突破原有框架思路的颠覆之作，其主要特性包括：

 + 基于命名空间和众多PHP新特性
 + 核心功能组件化
 + 强化路由功能
 + 更灵活的控制器
 + 重构的模型和数据库类
 + 配置文件可分离
 + 重写的自动验证和完成
 + 简化扩展机制
 + API支持完善
 + 改进的Log类
 + 命令行访问支持
 + REST支持
 + 引导文件支持
 + 方便的自动生成定义
 + 真正惰性加载
 + 分布式环境支持
 + 支持Composer
 + 支持MongoDb


## 命名规范

ThinkPHP5的命名规范遵循`PSR-2`规范以及`PSR-4`自动加载规范。

## 参与开发
注册并登录 Github 帐号， fork 本项目并进行改动。

更多细节参阅 [CONTRIBUTING.md](CONTRIBUTING.md)

## 版权信息

ThinkPHP遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2006-2018 by ThinkPHP (http://thinkphp.cn)

All rights reserved。

ThinkPHP® 商标和著作权所有者为上海顶想信息科技有限公司。

更多细节参阅 [LICENSE.txt](LICENSE.txt)
