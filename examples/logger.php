<?php

use mon\log\format\LineFormat;
use mon\log\Logger;
use mon\log\record\FileRecord;

require __DIR__ . '/../vendor/autoload.php';

// 解析器
$format = new LineFormat();
// 记录器
$record = new FileRecord([
    // 日志文件大小
    'maxSize'   => 20480000,
    // 日志目录
    'logPath'   => __DIR__ . '/log',
    // 日志滚动卷数   
    'rollNum'   => 3,
    // 日志名称，空则使用当前日期作为名称       
    'logName'   => '',
]);

$logger = new Logger($format, $record);

// 记录日志
$logger->notice('Test notice log');
$logger->info('Test info log', ['trace' => true]);