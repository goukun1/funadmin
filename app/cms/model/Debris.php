<?php
/**
 * funadmin
 * ============================================================================
 * 版权所有 2018-2027 funadmin，并保留所有权利。
 * 网站地址: https://www.funadmin.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/9/2
 */

namespace app\cms\model;

use app\common\model\BaseModel;
use app\cms\model\DebrisPos;
use think\model\concern\SoftDelete;

class Debris extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $name = 'addons_cms_debris';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function debrisPos()
    {
        return $this->belongsTo(DebrisPos::class, 'pid', 'id');

    }


}
