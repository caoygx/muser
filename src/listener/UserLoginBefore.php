<?php
declare (strict_types = 1);

namespace muser\listener;

class UserLoginBefore
{
    /**
     * 登录事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        //setUserAuth($event);
        var_dump("用户登录前");exit('x');
    }    
}
