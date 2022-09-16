<?php

use mon\log\LoggerFactory;
use mon\log\format\JsonFormat;
use mon\log\format\LineFormat;
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
    ]
];

// 注册日志工厂
$factory = LoggerFactory::instance()->registerChannel($config);


// 记录日志
$factory->channel('default')->info('test log');
$factory->channel('json')->info('test json log');
$factory->channel()->debug('test trace log', ['trace' => true]);
