# mon-log

基于 `PSR-3` 实现的高扩展性日志驱动库，支持文件、数据库等多种记录方式，解析器与记录器均可自定义扩展。

## 环境要求

- PHP >= 7.1
- psr/log ^1.1
- 使用 `DbRecord` 时需要 `PDO` + `pdo_mysql` 扩展

## 安装

```bash
composer require mongdch/mon-log
```

## 核心概念

| 类 | 职责 |
|---|---|
| `mon\log\Log` | 单个日志驱动实例，组合一个解析器 + 一个记录器，实现 PSR-3 |
| `mon\log\Logger` | 通道工厂（单例），管理多个命名通道 |
| `mon\log\format\*` | 日志内容解析器，实现 `FormatInterface` |
| `mon\log\record\*` | 日志记录器，实现 `RecordInterface` |

## 使用

### Log 单实例

```php
use mon\log\Log;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;

// 解析器
$format = new LineFormat();
// 记录器
$record = new FileRecord([
    // 日志文件大小（字节）
    'maxSize'   => 20480000,
    // 日志目录
    'logPath'   => __DIR__ . '/log',
    // 日志滚动卷数
    'rollNum'   => 3,
    // 日志名称，空则使用当前日期作为名称
    'logName'   => '',
]);

// 第三个参数 $throw：日志失败是否抛出异常，默认 true，false 时降级不中断业务
$logger = new Log($format, $record);

// 记录日志，支持 PSR-3 全部级别
$logger->debug('Test debug log');
$logger->notice('Test notice log');
$logger->info('Test info log', ['trace' => true]);
```

### Logger 通道工厂

```php
use mon\log\Logger;
use mon\log\format\JsonFormat;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;

// 配置信息
$config = [
    // 通道
    'default' => [
        // 日志失败是否抛出异常，默认 true
        'throw'     => false,
        // 解析器
        'format'    => [
            // 类名
            'handler'   => LineFormat::class,
            // 配置信息
            'config'    => []
        ],
        // 记录器
        'record'    => [
            // 类名
            'handler'   => FileRecord::class,
            // 配置信息
            'config'    => [
                // 日志文件大小（字节）
                'maxSize'   => 20480000,
                // 日志目录，建议使用绝对路径
                'logPath'   => __DIR__ . '/log',
                // 日志滚动卷数
                'rollNum'   => 3,
                // 日志名称，空则使用当前日期作为名称
                'logName'   => '',
            ]
        ]
    ],
    'json' => [
        // 解析器
        'format'    => [
            'handler'   => JsonFormat::class,
            'config'    => []
        ],
        // 记录器
        'record'    => [
            'handler'   => FileRecord::class,
            'config'    => [
                'logPath'   => __DIR__ . '/log/json',
            ]
        ]
    ]
];

// 注册通道
$factory = Logger::instance()->registerChannel($config);

// 记录日志
$factory->channel('default')->info('test log');
$factory->channel('json')->info('test json log');
// 不传通道名，使用默认通道（default）
$factory->channel()->debug('test trace log', ['trace' => true]);

// 静态调用（仅限默认通道）
Logger::info('test static log');

// 其它通道管理方法
$factory->hasChannel('json');       // 是否存在通道
$factory->createChannel('db', []);  // 动态创建通道
$factory->setDefaultChannel('json');// 设置默认通道
$factory->removeChannel('json');    // 删除通道
$factory->getChannels();            // 获取所有通道
```

## 配置说明

### FileRecord

| 配置 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `save` | bool | `true` | 是否自动写入文件，`false` 时仅缓存，需手动调用 `save()` |
| `clear` | bool | `true` | 写入**成功**后清除缓存日志；写入失败时保留缓存以便重试 |
| `logName` | string | `''` | 日志文件名，空则使用当天日期 `Ymd` |
| `maxSize` | int | `20480000` | 单个日志文件最大字节数 |
| `logPath` | string | `'.'` | 日志目录，常驻内存进程建议使用绝对路径 |
| `rollNum` | int | `3` | 日志分卷数，超出 `maxSize` 时滚动为 `xxx_1.log`、`xxx_2.log` ... |

> 注意：单条日志内容超过 `maxSize` 时会抛出 `RuntimeException`。

### DbRecord

| 配置 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `save` | bool | `false` | 是否自动写库，`false` 时仅缓存，需手动调用 `save()` 批量写入（推荐） |
| `clear` | bool | `true` | 写库**成功**后清除缓存日志 |
| `host` | string | `127.0.0.1` | 服务器地址 |
| `database` | string | `''` | 数据库名 |
| `table` | string | `'log'` | 表名，仅允许 `字母、数字、下划线`，且以字母或下划线开头 |
| `username` | string | `''` | 用户名 |
| `password` | string | `''` | 密码 |
| `port` | string/int | `'3306'` | 端口 |
| `charset` | string | `'utf8mb4'` | 数据库编码 |

写入采用**参数绑定**的批量 `INSERT`；`ext` 字段传入数组时会自动 JSON 编码存储。
面向 Workerman 等常驻内存场景：SQL 执行遇到 MySQL 断线（错误码 `2006`/`2013`）时会自动重置连接并重试一次。

数据表参考结构：

```sql
CREATE TABLE `log` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `level` varchar(16) NOT NULL DEFAULT '' COMMENT '日志级别',
    `content` text NOT NULL COMMENT '日志内容',
    `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `ip` varchar(46) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `ext` text COMMENT '扩展信息，数组自动JSON编码',
    `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日志表';
```

### LineFormat / JsonFormat

| 配置 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `level` | bool | `true` | 日志是否包含级别 |
| `date` | bool | `true` | 日志是否包含时间 |
| `date_format` | string | `'Y-m-d H:i:s'` | 时间格式，启用 `date` 时有效 |
| `trace` | bool | `false` | 是否启用日志追踪（调用文件与行号） |
| `layer` | int | `3` | 追踪层级，启用 `trace` 时有效；实际堆栈层级不足时自动忽略追踪信息 |

两种格式的输出示例：

```text
# LineFormat
[2026-11-07 12:00:00] [info] Test info log

# JsonFormat
{"date":"2026-11-07 12:00:00","level":"info","message":"Test info log"}
```

## context 动态参数

除静态配置外，每次记录日志时可通过 `$context` 覆盖/补充行为：

| 参数 | 作用于 | 说明 |
|---|---|---|
| `date` / `date_format` / `level` / `trace` / `layer` | 解析器 | 覆盖解析器同名配置 |
| `replace` | 解析器 | 占位符替换，如 `$logger->info('hello {name}', ['replace' => ['name' => 'mon']])` |
| `save` / `clear` | 记录器 | 覆盖记录器同名配置，临时控制本次是否立即写入、是否清缓存 |
| `logName` / `logPath` | FileRecord | 覆盖日志名与目录 |
| `uid` / `ip` / `ext` | DbRecord | 写库时的业务扩展字段，`ext` 支持数组 |

## 缓存与批量写入

适合高并发或常驻内存场景，攒批后一次性写入：

```php
use mon\log\Logger;

$factory = Logger::instance()->registerChannel([
    'default' => [
        'record' => ['handler' => FileRecord::class, 'config' => ['save' => false, 'logPath' => __DIR__ . '/log']]
    ]
]);

// 仅缓存，不落盘
$factory->channel()->info('batch log 1');
$factory->channel()->warning('batch log 2');

// 获取缓存中的日志
$logs = $factory->channel()->getLog();

// 一次性写入（也可在定时器 / 进程退出回调中调用）
$factory->channel()->saveLog();
```

## 异常降级

日志属于旁路逻辑，建议在生产环境关闭异常抛出，避免日志失败（如磁盘写满、DB 断连）中断业务主流程：

```php
// 方式一：实例化时传入
$logger = new Log($format, $record, false);

// 方式二：setter
$logger->setThrow(false);

// 方式三：通道配置
$factory->createChannel('default', ['throw' => false, ...]);
```

## 扩展

解析器需实现 `\mon\log\interfaces\FormatInterface`，记录器需实现 `\mon\log\interfaces\RecordInterface`。

完整示例参考 [examples/extend.php](examples/extend.php)，工厂用法参考 [examples/factory.php](examples/factory.php)。

## 变更记录

### 1.0.2
- 修复 `DbRecord` 自动保存配置（`save => true`）不生效的问题
- 修复写库/写文件失败时缓存被清空导致日志丢失的问题，改为写入成功后再清缓存
- 修复日志分卷按字符数计算的问题，改为按字节数计算
- 修复 `JsonFormat` 输出字段名拼写错误：`messgae` -> `message`（**注意：该字段为破坏性变更，下游解析需同步调整**）
- 修复日志追踪堆栈层级不足时的 undefined index 错误
- `DbRecord` 改为参数绑定写入，表名校验防注入，`ext` 数组自动 JSON 编码
- `DbRecord` 支持断线重连（错误码 2006/2013）
- 新增 `throw` 配置支持日志失败降级，不中断业务

### 1.0.1
- 解析器、记录器支持配置化
