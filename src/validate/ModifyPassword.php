<?php

namespace muser\validate;

class ModifyPassword extends Code
{

    protected $customizedMessage  =   [
        'password.require'   => '密码不能为空',
        'password.max'  => '年龄只能在1-120之间',
        'repassword.require'  => '重复密码不能为空',
        'repassword.checkEqual'  => '两个密码不一致',
    ];

    protected $customizedRule = [
        'password'   => 'require|max:15',
        'repassword'   => 'require|checkEqual:rule',
    ];

    function __construct()
    {
        parent::__construct();
        $this->rule($this->customizedRule);
        $this->message($this->customizedMessage);
    }

    protected function checkEqual($value, $rule, $data=[])
    {
        return $value == $data['password'];
    }


}
