# 瞎写的

#### 介紹
瞎写的，自己用的

#### 架構
瞎几吧乱抄的, 依赖以下扩展
```text
phpredis
swoole
inotify
```

#### 安裝教程
```text
新建 $dir/composer.json  
新建 $dir/app/controller  
新建 $dir/app/model  
新建 $dir/app/middleware  
新建 $dir/commands  
新建 $dir/components  
新建 $dir/config  
新建 $dir/routes 
``` 

添加composer 内容  
```json
{
	"autoload": {
		"psr-4": {
			"app\\": "app"
		},
		"files": [
		]
	},
	"require": {
		"php": ">= 7.1",
	},
	"require-dev": {
		"forset/forset": "dev-master",
		"swoole/ide-helper": "@dev"
	}
}

```


```bash
执行 composer update
```

#### 使用說明

新建 $dir/routes/web.php
```php
<?php

/** @var Router $router */
$router = app()->get('router');
$router->get('index', 'SiteController@index');
$router->post('index', 'SiteController@index');
$router->any('index', 'SiteController@index');
$router->delete('index', 'SiteController@index');
$router->put('index', 'SiteController@index');

$options = [
    'prefix' => '', //前缀
    'namespace' => '', //Controller使用的命名空间如  namespace='server'则访问 app\controller\server\TestController
    'filter' => [   // 过滤请求用的, 参数效验同 model写法
    	 'grant' => [] ,    //权限效验回调函数
    	 'header' => [      //效验请求头所需数据
    	 	[['token', 'user', 'time', 'source'], 'required'],
    	 	[['token', 'source'], 'string'],
    	 	[['user', 'time'], 'int', 'maxLength' => 32],
         ],
    	 'body' => [      //效验请求体所需数据
    	 	[['token', 'user', 'time', 'source'], 'required'],
    	 	[['token', 'source'], 'string'],
    	 	[['user', 'time'], 'int', 'maxLength' => 32],
         ]
     ],     
    'middleware' => '',  // 中间件	
    'options' => '',  // ajax跨域请求处理	
];

$router->group($options, function (\Beauty\route\Router $router){
    $router->get('index', 'SiteController@index');
    $router->post('index', 'SiteController@index');
    $router->any('index', 'SiteController@index');
    $router->delete('index', 'SiteController@index');
    $router->put('index', 'SiteController@index');
});

```

新建 $dir/execfile并添加内容
```php
<?php
//error_reporting(E_ALL & ~E_NOTICE);

define('APP_PATH', __DIR__);
define('DISPLAY_ERRORS', TRUE);
define('DEBUG', TRUE);
define('DB_EMPTY', 3001);
define('DB_ERROR', 3002);
define('PARAM_NOT_EXISTS', 4001);
define('PARAM_EMPTY', 4004);

use Beauty\web\Application;

$array = parse_ini_file(APP_PATH . '/.env', true);
foreach ($array as $key => $val) {
	putenv($key . '=' . $val);
}


require_once __DIR__ . '/vendor/autoload.php';
Beauty.php

$config = require_once __DIR__ . '/config/configure.php';

$init = new Application($config);
$init->initial();
```

添加配置项内容 $dir/config/configure.php
```php
<?php

return [
	'id' => 'restful',
	'runtimePath' => __DIR__ . '/../runtime',
	'components' => [
		'config' => [
			'class' => \Beauty\base\Config::class,
			'cache_time' => 60 * 60 * 24,
			'usePipeMessage' => TRUE,
			'wss' => \app\socket\GameSocket::class,
			'udp' => [
				'host' => '0.0.0.0',
				'port' => 33305,
			]
		],
		'error' => [
			'class' => \app\MyErrorHandler::class
		],
		'socket' => [
			'class' => 'Beauty\server\Socket',
			'host' => '127.0.0.1',
			'port' => 6500,
			'serverHost' => '127.0.0.1',
			'serverPort' => 6600,
			'config' => [
				'worker_num' => 32,
				'reactor_num' => 8,
				'task_worker_num' => 20
			],
            'callback' => [
                'handshake' => [WebSocket::class, 'onHandshake'],
                'message' => [WebSocket::class, 'onMessage'],
                'close' => [WebSocket::class, 'onClose'],
            ]
		],
		'redis' => [
			'class' => 'Beauty\cache\Redis',
			'host' => '127.0.0.1',
			'port' => '6379',
			'prefix' => '',
			'auth' => '',
			'databases' => '0',
		],
		'qn' => [
			'class' => '\Beauty\core\Qn',
			'access_key' => '',
			'secret_key' => '',
			'bucket' => '',
		],

		'default' => [
			'class' => 'Beauty\db\Connection',
			'id' => 'default',
			'cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST,
			'username' => CONNECT_USER,
			'password' => CONNECT_PASS,
			'tablePrefix' => 'aircraftwar_',
			'masterConfig' => [
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
			],
			'slaveConfig' => [
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
				['cds' => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST, 'username' => CONNECT_USER, 'password' => CONNECT_PASS,],
			],
		],
	],
	'aliases' => []
];

```

```bash
启动  php $dir/execfile 或 php $dir/execfile start  
重启  php $dir/execfile restart   
停止  php $dir/execfile stop 
```  

#Command
注册命令  
```php
<?php

class exranpk extends Command{
	
	public $command = 'exmple:test';
	
	public $description = '任务描述';
	
	public $dataFile = '/usr/local/config.json';
	
	public $dataType = 'json';
	
	public function handler()
	{
		
	}
	
}
```


使用  
```bash
php artu master:qtes --key=o --key=b --key=v
```

#### 參與貢獻

1. Fork 本倉庫
2. 新建 Feat_xxx 分支
3. 提交代碼
4. 新建 Pull Request


#### 碼雲特技

1. 使用 Readme\_XXX.md 來支持不同的語言，例如 Readme\_en.md, Readme\_zh.md
2. 碼雲官方博客 [blog.gitee.com](https://blog.gitee.com)
3. 妳可以 [https://gitee.com/explore](https://gitee.com/explore) 這個地址來了解碼雲上的優秀開源項目
4. [GVP](https://gitee.com/gvp) 全稱是碼雲最有價值開源項目，是碼雲綜合評定出的優秀開源項目
5. 碼雲官方提供的使用手冊 [https://gitee.com/help](https://gitee.com/help)
6. 碼雲封面人物是壹檔用來展示碼雲會員風采的欄目 [https://gitee.com/gitee-stars/](https://gitee.com/gitee-stars/)
