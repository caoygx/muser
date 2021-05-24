composer user模块 
以composer形式加载应用模块,这样可以把常用的公共模块放入composer中，如用户模块，订单模块

 此模块包含收下功能：
1. 前台注册登录
2. 用户中心信息修改
3. 后台用户管理



使用`composer`安装：

```
composer require rrbrr/user
```

注册服务，在应用的全局公共文件`service.php`中加入：

```php
return [
    // ...

    muser\UserService::class,
];
```

发布配置文件和数据库迁移文件：

```
php think user:publish
```

这将自动在app目录下生成 `config/user.php` 文件。


执行迁移工具（**确保数据库配置信息正确**）：

```
php think migrate:run
```

这将创建名为 `user` 的表。

用户表名更改在config/user目录下

# 使用方法
创建app/controller/User.php文件
```php
namespace app\controller;
use muser\user\controller\User as CUser;

class User 
{

    //登录
    function login()
    {
        $cuser = new CUser($this->app);
        $post = input();
        $r =  $cuser->login($post);
        return $r;
    }

    //注册
    function register()
    {
        $cuser = new CUser($this->app);
        $post = input();
        $r =  $cuser->register($post);
        return $r;
    }

    //获取验证码
    function getCode()
    {
        $cuser = new CUser($this->app);
        $post = input();
        $r =  $cuser->getCode($post);
        return $r;
    }


}
```
url访问测试
http://localhost/user/login?username=test&password=xx&ret_format=json
这样就相当于把所有请求全部转发给了composer中的user包来处理。如果其它项目也需要用户登录，只要引入这个包即可。
如果把这些包能做成api,就相当于简单的微服务接口了。