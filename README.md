## M构造操作数据库的SQL语句[curd]
Author: Rumble

Url:  https://github.com/liu/buildsql

### 插件安装
* `composer require liu/buildsql`

### 开发目的
* 由于很多sql构建类需要建立mysql连接，但有的时候我们只想要sql语句，不想与数据库进行连接，这样有一个纯粹的sql构建工具就尤为重要了。

* 当前sql构建工具基于thinkphp5.1 进行开发,目前我只是一些修改

### 操作说明
* 使用该工具无需配置任何数据库信息，不会建立mysql连接
* 使用时直接使用Db类进行生成就可以了


### 不支持的操作方式
* with,relation ,exception,filed,等操作我全部删除,只留下了可以操作的

### 特别鸣谢

* 感谢thinkphp的开源
* https://github.com/lumiza/buildsql 给我启发
