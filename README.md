## OAuth PHP SDK（Laravel 5 Package）


## 目录
- [安装](#安装)
- [配置](#配置)
    - [申请资源](#申请资源)
    - [配置文件](#配置文件)
- [使用](#使用)
    - [登录流程](#登录流程)
    - [注销流程](#注销流程)
    - [OAuth服务器注销回调](#OAuth服务器注销回调)
    - [代码示例](#代码示例)
- [参数签名](#参数签名)
- [数据同步](#数据同步)
    - [数据同步说明](#数据同步说明)
    - [同步代码示例](#同步代码示例)

## 安装
- 添加composer依赖

```json
{
    "require": {
        "user-center/oauth-sdk-php": "~1.0"
    },
    "repositories": [
        {
            "type": "git",
            "url": "git@g.haodai.com:user-center/oauth-sdk-php.git"
        }
    ]
}
```

- 打开`config/app.php`在`providers`数组中添加服务提供者:

```php
Haodai\UCenter\OAuth\OAuthServiceProvider::class,
```

- 打开`config/app.php`在`aliases`数组中添加门面:

```php
'OAuthClient' => \Haodai\UCenter\OAuth\Facades\OAuthClient::class,
```

- 运行如下命令生成配置文件`config/oauth.php`:

```php
php artisan vendor:publish
```

- 打开`app/Http/Kernel.php`添加中间件:

```php
'oauth.sign' => \Haodai\UCenter\OAuth\Middleware\VerifySignature::class,
```

## 配置

#### 申请资源
向用户中心申请资源，如`host`、`client_id`、`client_secret`、`scope`,申请前提供以下内容：
- 应用名称
- 回调地址
- 数据同步地址
- 同步登出地址
- scope权限列表，默认为user_info

#### 配置文件
配置`config/oauth.php`中各配置项. 推荐使用env文件配置用来区分不同环境：
- `host`: 当前环境对应的OAuth服务器环境域名(包含http://)
- `client_id`: OAuth服务器发放
- `client_secret`: OAuth服务器发放
- `scope`: 需要获取的OAuth服务器资源，多个资源使用`,`号分隔，OAuth服务器发放
- `redirect_uri`: OAuth登录成功回调地址
- `session_name`: session名称


## 使用

#### 登录流程 
1. 用户登录请求重定向至OAuth服务器登录页面
2. OAuth服务器登录成功后以`GET`请求子应用回调地址
3. 子应用回调中处理用户登录，可包含以下操作：`获取用户信息`、`更新用户信息`、`登录处理`、`跳转至首页`

#### 注销流程
1. 用户退出登录状态，重定向至OAuth服务器登录页面
2. 用户重新发起新的登录流程

#### OAuth服务器注销回调
1. OAuth服务器退出登录状态
2. 回调当前用户关联的所有子应用同步注销地址

#### 代码示例

app/Http/routes.php
```php
Route::get('auth/login', 'AuthController@login')->name('auth.login');
Route::get('auth/logout', 'AuthController@logout')->name('auth.logout');

Route::get('oauth/logout', 'OAuthController@logout')->name('oauth.logout')->middleware('oauth.sign');
Route::get('oauth/callback', 'OAuthController@callback')->name('oauth.callback');
```

app/Http/Controllers/AuthController.php
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Haodai\UCenter\OAuth\Facades\OAuthClient;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * 登录
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }
        return redirect()->to(OAuthClient::getLoginUrl());
    }

    /**
     * 注销
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        if (Auth::check()) {
            Auth::logout();
        }
        return redirect()->to(OAuthClient::getLogoutUrl());
    }
}

```

app/Http/Controllers/OAuthController.php
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Haodai\UCenter\OAuth\Exception\OAuthException;
use Haodai\UCenter\OAuth\Facades\OAuthClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    /**
     * OAuth同步注销回调
     */
    public function logout()
    {
        if (Auth::check()) {
            Auth::logout();
        }
        return response()->json([
            'status' => 'success'
        ])->setCallback(Input::get('callback'));
    }

    /**
     * OAuth登录成功回调
     */
    public function callback()
    {
        try {
            $loginUserInfo = OAuthClient::getLoginUserInfo();
        } catch (OAuthException $e) {
            Log::error('OAuth登录异常', ['message' => $e->getMessage()]);
            // 异常处理
        }
        // 同步/更新用户信息
        // 后续流程
    }
}

```


## 参数签名
`\Haodai\UCenter\OAuth\Utils\ParameterSign`提供参数签名，签名有效期为3秒，需保证子应用服务器和OAuth服务器时间保持同步

`\Haodai\UCenter\OAuth\Middleware\VerifySignature`中间件提供参数签名验证，若签名验证失败，http返回403状态码，并返回如下数据：

```json
{
   "message": "签名验证失败",
   "status": 10001
}
```

## 数据同步
#### 数据同步说明
子应用需要提供数据同步接口，用来同步用户中心的公共资源：
- 用户被创建/更新时会同步至当前用户关联的所有子应用
- 城市数据被创建/更新时会同步所有关联的子应用
- 以`POST`方式请求数据同步接口，并且有`参数签名`
        
`GET`参数示例：
```json
{
    "type": "data", // 同步类型，目前只有data(数据)
    "data_type": "user"  // 数据类型，目前只有user(用户数据)和zone(城市数据)
}
```
 
`POST`参数示例：
```json
{
    "data": [
        {
            "id": "65", 
            "username": "zhangsan", 
            "nickname": "zhangsan", 
            "email": "zhangsan@163.com", 
            "mobile": "13923232323", 
            "status": "1"
        }, 
        {
            "id": "65", 
            "username": "lisi", 
            "nickname": "lisi", 
            "email": "lisi@163.com", 
            "mobile": "13923232324", 
            "status": "1"
        }
    ]
}
```

#### 同步代码示例

app/Http/routes.php
```php
Route::post('api/sync', 'Api\\SyncController@sync')->name('api.sync')->middleware('oauth.sign');
```
app/Http/Controllers/Api/SyncController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Repositories\UserRepository;
use App\Repositories\ZoneRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * 同步类型
     * @var array
     */
    private $validSyncTypes = ['data'];

    /**
     * 同步数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request)
    {

        $syncType = $request->input('type', '');
        $dataType = $request->input('data_type', '');

        // 判断是否是有效的同步类型。
        if (empty($syncType) || !in_array($syncType, $this->validSyncTypes)) {
            return response()->json([
                'status_code' => 4005,
                'msg' => '无效的同步类型'
            ]);
        }
        
        $syncMethod = 'sync' . ucfirst($dataType) . 'Info';
        $handler = $this->getSyncHandler($dataType);

        if (!$handler) {
            return response()->json([
                'status_code' => 4004,
                'msg' => '无效的同步数据类型',
            ]);
        }
        $data = $request->input('data', []);
        // 执行同步数据操作。
        $result = DB::transaction(function () use ($handler, $syncMethod, $data) {
            foreach ($data as $item) {
                if(!$handler->{$syncMethod}((array) $item)) {
                    return false;
                }
            }
        });

        if (false === $result) {
            $statusCode = 200001;
            $msg = '同步失败';
        } else {
            $statusCode = 2000;
            $msg = '同步成功';
        }

        return response()->json([
            'status_code' => $statusCode,
            'msg' => $msg,
        ]);
    }

    /**
     * 同步数据策略
     * @param $type
     * @return UserRepository|ZoneRepository|bool
     */
    public function getSyncHandler($type)
    {
        $handler = false;
        switch($type) {
            case 'user':
                $handler = new UserRepository();
                break;
            case 'zone':
                $handler = new ZoneRepository();
                break;
            default:
                break;
        }
        return $handler;
    }
}

```

