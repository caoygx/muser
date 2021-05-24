<?php
namespace muser;

use think\Service;
use muser\command\Publish;

/**
 * muser service
 * 
 * @author techlee@qq.com
 */
class UserService extends Service
{
    /**
     * Register service
     *
     * @return void
     */
    public function register()
    {
        // 注册数据迁移服务
        $this->app->register(\think\migration\Service::class);
        
        $this->app->bind('userModel', function () {
            return "userModel";
        });
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/user.php', 'user');



        $this->commands(['user:publish' => Publish::class]);
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom(string $path, string $key)
    {
        $config = $this->app->config->get($key, []);

        $this->app->config->set(array_merge(require $path, $config), $key);
    }
}