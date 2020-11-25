<?php
/**
 * lemocms
 * ============================================================================
 * 版权所有 2018-2027 lemocms，并保留所有权利。
 * 网站地址: https://www.lemocms.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/8/2
 */
namespace app\bbs\controller;



use app\common\controller\Frontend;

class Error extends Frontend {

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function err()
    {
        return view('404');
    }

    public function notice()
    {
        return view();
    }


}