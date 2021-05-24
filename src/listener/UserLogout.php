<?php
declare (strict_types = 1);

namespace muser\listener;

class UserLogout
{
    /**
     * 登录事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        clearUserAuth($event);
    }    
}
