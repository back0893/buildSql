<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\db\connector;

use PDO;
use think\db\Connection;
use think\db\Query;

/**
 * mysql数据库驱动
 */
class Mysql extends Connection
{

    protected $builder = '\\think\\db\\builder\\Mysql';
    protected $builderClassName='\\think\\db\\builder\\Mysql';

}
