<?php

use mon\log\Logger;
use mon\log\format\JsonFormat;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use mon\log\record\FileBatchRecord;

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
            'config'    => []
        ],
        // 记录器
        'record'    => [
            // 类名
            'handler'   => FileRecord::class,
            // 配置信息
            'config'    => [
                // 日志文件大小
                'maxSize'   => 20480000,
                // 日志目录
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
            // 类名
            'handler'   => JsonFormat::class,
            // 配置信息
            'config'    => []
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
    'batch' => [
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
            'handler'   => FileBatchRecord::class,
            // 配置信息
            'config'    => [
                // 日志文件大小
                'maxSize'   => 20480000,
                // 日志目录
                'logPath'   => __DIR__ . '/log/batch',
                // 日志滚动卷数   
                'rollNum'   => 3,
                // 日志名称，空则使用当前日期作为名称       
                'logName'   => '',
            ]
        ]
    ],
];

// 注册日志工厂
$factory = Logger::instance()->registerChannel($config);

// 独立创建日志通道
$factory->createChannel('test');

// 记录日志
$factory->channel('test')->info('test channel log!');
$factory->channel('default')->info('test log');
$factory->channel('json')->info('test json log');
$factory->channel()->debug('test trace log', ['trace' => true]);


$factory->channel('batch')->info('test batch record...1');
$factory->channel('batch')->info('test batch record save1', ['save' => true]);
$factory->channel('batch')->info('test batch record...2');
$factory->channel('batch')->info('test batch record...3');
$factory->channel('batch')->info('test batch record save2', ['save' => true]);
