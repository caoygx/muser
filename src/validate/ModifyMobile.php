<?php

namespace muser\validate;

class ModifyMobile extends Code
{

    protected $customizedRule = [
        'mobile'  => 'require|mobile|checkUnique:rule',
    ];

    protected $customizedMessage  =   [
        'mobile.require' => '手机号是必填',
        'mobile.checkUnique'     => '手机已经被使用了',
        'mobile.mobile'     => '必须是正确的手机号',
    ];

    function __construct()
    {
        parent::__construct();
        $this->rule($this->customizedRule);
        $this->message($this->customizedMessage);
    }

    protected function checkEqual($value, $rule, $data=[])
    {
        return $value == $data['username'];
    }

    protected function checkUnique($value, $rule, $data=[],$column="",$description="")
    {
        $r = \think\facade\Db::name('User')->where([$column=>$value])->find();
        return empty($r);
    }


}
