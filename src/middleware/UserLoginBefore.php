<?php
namespace muser\middleware;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/3/1
 * Time: 17:20
 */
class UserLoginBefore
{
    public function handle($request, \Closure $next)
    {
        $request->hello = 'ThinkPHP';

        exit('x');
        return $next($request);
    }
}