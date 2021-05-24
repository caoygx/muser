<?php
declare (strict_types = 1);

namespace muser\listener;

class UserLogin
{
    /**
     * 登录事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        setUserAuth($event);
    }    
}
