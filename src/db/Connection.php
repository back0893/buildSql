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

namespace think\db;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use think\Db;

/**
 * Class Connection
 * @package think\db
 * @property Builder $builder
 */
abstract class Connection
{
    const PARAM_FLOAT          = 21;
    protected static $instance = null;
    /** @var string 当前SQL指令 */
    protected $queryStr = '';
    // 错误信息
    protected $error = '';

    // 使用Builder类
    protected $builderClassName;
    // Builder对象
    protected $builder;
    public static function instance($config = [])
    {
        if(is_null(self::$instance)){
            $class=get_called_class();
            self::$instance = new $class();
        }
        return self::$instance;
    }

    public function getConfig($name){
        return '';
    }
    /**
     * 架构函数 读取数据库配置信息
     * @access protected
     * @param array $config 数据库配置数组
     */
    protected function __construct()
    {
        // 创建Builder对象
        $class = $this->getBuilderClass();

        $this->builder = new $class($this);

        // 执行初始化操作
        $this->initialize();
    }

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function initialize()
    {}
    /**
     * 获取当前连接器类对应的Builder类
     * @access public
     * @return string
     */
    public function getBuilderClass()
    {
        if (!empty($this->builderClassName)) {
            return $this->builderClassName;
        }

        return $this->getConfig('builder') ?: '\\think\\db\\builder\\' . ucfirst($this->getConfig('type'));
    }

    /**
     * 设置当前的数据库Builder对象
     * @access protected
     * @param Builder    $builder
     * @return void
     */
    protected function setBuilder(Builder $builder)
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * 获取当前的builder实例对象
     * @access public
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }


    /**
     * 将SQL语句中的__TABLE_NAME__字符串替换成带前缀的表名（小写）
     * @access public
     * @param string $sql sql语句
     * @return string
     */
    public function parseSqlTable($sql)
    {
        if (false !== strpos($sql, '__')) {
            $prefix = '';
            $sql    = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $sql);
        }

        return $sql;
    }

    /**
     * 查找单条记录
     * @access public
     * @param Query  $query        查询对象
     * @return array|null|\PDOStatement|string
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function find(Query $query)
    {
        // 分析查询表达式
        $options = $query->getOptions();
        $pk      = $query->getPk($options);

        $data = $options['data'];

        $query->setOption('limit', 1);


        if (is_string($pk) && !is_array($data)) {
            if (isset($key) && strpos($key, '|')) {
                list($a, $val) = explode('|', $key);
                $item[$pk]     = $val;
            } else {
                $item[$pk] = $data;
            }
            $data = $item;
        }
        $query->setOption('data', $data);

        // 生成查询SQL
        $sql = $this->builder->select($query);

        $query->removeOption('limit');

        $bind = $query->getBind();


        // 获取实际执行的SQL语句
        return $this->getRealSql($sql, $bind);
    }

    /**
     * 查找记录
     * @access public
     * @param Query   $query        查询对象
     * @return array|\PDOStatement|string
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function select(Query $query)
    {
        // 分析查询表达式
        $options = $query->getOptions();


        // 生成查询SQL
        $sql = $this->builder->select($query);

        $bind = $query->getBind();


        // 获取实际执行的SQL语句
        return $this->getRealSql($sql, $bind);
    }

    /**
     * 插入记录
     * @access public
     * @param Query   $query        查询对象
     * @param boolean $replace      是否replace
     * @param boolean $getLastInsID 返回自增主键
     * @param string  $sequence     自增序列名
     * @return integer|string
     */
    public function insert(Query $query, $replace = false, $getLastInsID = false, $sequence = null)
    {
        // 分析查询表达式
        $options = $query->getOptions();

        // 生成SQL语句
        $sql = $this->builder->insert($query, $replace);

        $bind = $query->getBind();


        // 获取实际执行的SQL语句
        return $this->getRealSql($sql, $bind);
    }

    /**
     * 批量插入记录
     * @access public
     * @param Query     $query      查询对象
     * @param mixed     $dataSet    数据集
     * @param bool      $replace    是否replace
     * @param integer   $limit      每次写入数据限制
     * @return integer|string
     * @throws \Exception
     * @throws \Throwable
     */
    public function insertAll(Query $query, $dataSet = [], $replace = false, $limit = null)
    {
        if (!is_array(reset($dataSet))) {
            return false;
        }

        $options = $query->getOptions();

        $sql  = $this->builder->insertAll($query, $dataSet, $replace);
        $bind = $query->getBind();


        // 获取实际执行的SQL语句
        return $this->getRealSql($sql, $bind);
    }

    /**
     * 通过Select方式插入记录
     * @access public
     * @param Query     $query      查询对象
     * @param string    $fields     要插入的数据表字段名
     * @param string    $table      要插入的数据表名
     * @return integer|string
     * @throws PDOException
     */
    public function selectInsert(Query $query, $fields, $table)
    {
        // 分析查询表达式
        $options = $query->getOptions();

        // 生成SQL语句
        $table = $this->parseSqlTable($table);

        $sql = $this->builder->selectInsert($query, $fields, $table);

        $bind = $query->getBind();


        return $this->getRealSql($sql, $bind);
    }

    /**
     * 更新记录
     * @access public
     * @param Query     $query  查询对象
     * @return integer|string
     * @throws Exception
     * @throws PDOException
     */
    public function update(Query $query)
    {
        $options = $query->getOptions();

        $pk   = $query->getPk($options);
        $data = $options['data'];

        if (empty($options['where'])) {
            throw new Exception('miss update condition');
        }

        // 更新数据
        $query->setOption('data', $data);

        // 生成UPDATE SQL语句
        $sql  = $this->builder->update($query);
        $bind = $query->getBind();


        // 获取实际执行的SQL语句
        return $this->getRealSql($sql, $bind);
    }

    /**
     * 删除记录
     * @access public
     * @param Query $query 查询对象
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function delete(Query $query)
    {
        // 分析查询表达式
        $options = $query->getOptions();
        $pk      = $query->getPk($options);
        $data    = $options['data'];

        if (true !== $data && empty($options['where'])) {
            // 如果条件为空 不进行删除操作 除非设置 1=1
            throw new Exception('delete without condition');
        }

        // 生成删除SQL语句
        $sql = $this->builder->delete($query);

        $bind = $query->getBind();


        // 获取实际执行的SQL语句
        return $this->getRealSql($sql, $bind);
    }

    /**
     * 得到某个字段的值
     * @access public
     * @param Query     $query 查询对象
     * @param string    $field   字段名
     * @param bool      $default   默认值
     * @return mixed
     */
    public function value(Query $query, $field, $default = null)
    {
        $options = $query->getOptions();

        if (isset($options['field'])) {
            $query->removeOption('field');
        }

        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }

        $query->setOption('field', $field);
        $query->setOption('limit', 1);

        // 生成查询SQL
        $sql = $this->builder->select($query);

        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }

        $query->removeOption('limit');

        $bind = $query->getBind();


        return $this->getRealSql($sql, $bind);
    }

    /**
     * 得到某个列的数组
     * @access public
     * @param Query     $query 查询对象
     * @param string    $field 字段名 多个字段用逗号分隔
     * @param string    $key   索引
     * @return string
     */
    public function column(Query $query, $field, $key = '')
    {
        $options = $query->getOptions();


        if (isset($options['field'])) {
            $query->removeOption('field');
        }

        if (is_null($field)) {
            $field = ['*'];
        } elseif (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }

        if ($key && ['*'] != $field) {
            array_unshift($field, $key);
            $field = array_unique($field);
        }

        $query->setOption('field', $field);

        // 生成查询SQL
        $sql = $this->builder->select($query);

        // 还原field参数
        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }

        $bind = $query->getBind();


        return $this->getRealSql($sql, $bind);

    }

    /**
     * 得到某个字段的值
     * @access public
     * @param  Query     $query     查询对象
     * @param  string    $aggregate 聚合方法
     * @param  string    $field     字段名
     * @return mixed
     */
    public function aggregate(Query $query, $aggregate, $field)
    {
        if (is_string($field) && 0 === stripos($field, 'DISTINCT ')) {
            list($distinct, $field) = explode(' ', $field);
        }

        $field = $aggregate . '(' . (!empty($distinct) ? 'DISTINCT ' : '') . $this->builder->parseKey($query, $field, true) . ') AS tp_' . strtolower($aggregate);

        return $this->value($query, $field, 0);
    }

    /**
     * 根据参数绑定组装最终的SQL语句 便于调试
     * @access public
     * @param string    $sql 带参数绑定的sql语句
     * @param array     $bind 参数绑定列表
     * @return string
     */
    public function getRealSql($sql, array $bind = [])
    {
        if (is_array($sql)) {
            $sql = implode(';', $sql);
        }

        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            $type  = is_array($val) ? $val[1] : PDO::PARAM_STR;

            if (PDO::PARAM_INT == $type || self::PARAM_FLOAT == $type) {
                $value = (float) $value;
            } elseif (PDO::PARAM_STR == $type) {
                $value = '\'' . addslashes($value) . '\'';
            }

            // 判断占位符
            $sql = is_numeric($key) ?
            substr_replace($sql, $value, strpos($sql, '?'), 1) :
            str_replace(':' . $key, $value, $sql);
        }

        return rtrim($sql);
    }
}
