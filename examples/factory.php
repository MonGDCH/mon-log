<?php

use mon\log\Logger;
use mon\log\format\JsonFormat;
use mon\log\format\LineFormat;
use mon\log\record\DbRecord;
use mon\log\record\FileRecord;

require __DIR__ . '/../vendor/autoload.php';

// 配置信息
$config = [
    // 通道
    'default' => [
        // 解析器
        'format'    => [
            // 类名
            'handler'   => LineFormat::class,
            // 配置信息
            'config'    => [
                // 日志是否包含级别
                'level'         => true,
                // 日志是否包含时间
                'date'          => true,
                // 时间格式，启用日志时间时有效
                'date_format'   => 'Y-m-d H:i:s',
                // 是否启用日志追踪
                'trace'         => false,
                // 追踪层级，启用日志追踪时有效
                'layer'         => 3
            ]
        ],
        // 记录器
        'record'    => [
            // 类名
            'handler'   => FileRecord::class,
            // 配置信息
            'config'    => [
                // 是否自动写入文件
                'save'      => false,
                // 写入文件后，清除缓存日志
                'clear'     => true,
                // 日志名称，空则使用当前日期作为名称       
                'logName'   => '',
                // 日志文件大小
                'maxSize'   => 20480000,
                // 日志目录
                'logPath'   => __DIR__ . '/log',
                // 日志滚动卷数   
                'rollNum'   => 3
            ]
        ]
    ],
    'json' => [
        // 解析器
        'format'    => [
            // 类名
            'handler'   => JsonFormat::class,
            // 配置信息
            'config'    => [
                // 日志是否包含级别
                'level'         => true,
                // 日志是否包含时间
                'date'          => true,
                // 时间格式，启用日志时间时有效
                'date_format'   => 'Y-m-d H:i:s',
                // 是否启用日志追踪
                'trace'         => false,
                // 追踪层级，启用日志追踪时有效
                'layer'         => 3
            ]
        ],
        // 记录器
        'record'    => [
            // 类名
            'handler'   => FileRecord::class,
            // 配置信息
            'config'    => [
                'logPath'   => __DIR__ . '/log/json',
            ]
        ]
    ],
];

// 注册日志工厂
$factory = Logger::instance()->registerChannel($config);

// 独立创建日志通道
$factory->createChannel('db', [
    // 解析器
    'format'    => [
        // 类名
        'handler'   => LineFormat::class,
        // 配置信息
        'config'    => [
            // 是否启用日志追踪
            'trace'         => true,
        ]
    ],
    // 记录器
    'record'    => [
        // 类名
        'handler'   => DbRecord::class,
        // 配置信息
        'config'    => [
            // 数据库名
            'database'      => 'test',
            // 表名
            'table'         => 'log',
            // 用户名
            'username'      => 'root',
            // 密码
            'password'      => '123456',
        ]
    ]
]);

// 设置默认通道名称
$factory->setDefaultChannel('default');

// 记录日志
$factory->channel()->info('record log');
$factory->channel()->info('record log123');
$factory->channel()->info('record log465', ['save' => true]);

$factory->channel('json')->info('test json log');
$factory->channel('json')->debug('test trace log', ['trace' => false]);


// $factory->channel('db')->info('test db record...1');
// $factory->channel('db')->debug('test db record ');
// $factory->channel('db')->error('test db record', ['uid' => 2, 'ext' => '123456', 'ip' => '127.0.0.1']);
// $factory->channel('db')->info('test db record...2');
// $factory->channel('db')->info('test db record...3', ['save' => true]);
