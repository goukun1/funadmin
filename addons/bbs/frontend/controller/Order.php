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
 * Date: 2019/8/27
 */
namespace app\bbs\controller;

use app\common\controller\Frontend;

use lemo\helper\SignHelper;
use think\captcha\facade\Captcha;
use app\common\model\User as UserModel;
use think\facade\Db;
use think\facade\View;
class Order extends Frontend {

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function bill(){


    }

}