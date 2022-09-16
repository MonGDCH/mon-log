<?php

use mon\log\interfaces\FormatInterface;
use mon\log\interfaces\RecordInterface;
use mon\log\Logger;

require __DIR__ . '/../vendor/autoload.php';

/**
 * 自定义解析器，需实现 \mon\log\interfaces\FormatInterface 接口
 */
class MyFormat implements FormatInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [];

    /**
     * 解析日志内容
     *
     * @param mixed $level      日志级别
     * @param mixed $message    日志信息
     * @param array $context    信息参数
     * @return string
     */
    public function format($level, $message, array $context = []): string
    {
        $date = date('Y-m-d H:i:s', time());
        $log = "{$date} {$level} {$this->config['name']} {$message}" . PHP_EOL;
        return $log;
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return mixed
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
}

/**
 * 自定义记录器，需实现 \mon\log\interfaces\RecordInterface 接口
 */
class MyRecord implements RecordInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [];

    /**
     * 记录日志
     *
     * @param mixed $level      日志级别
     * @param string $message   日志信息
     * @return void
     */
    public function record($level, string $messgae): void
    {
        $path = $this->config['path'] . DIRECTORY_SEPARATOR . 'my.log';
        file_put_contents($path, $messgae, FILE_APPEND);
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return mixed
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
}

// 解析器
$format = new MyFormat;
$format->setConfig(['name' => 'myformat']);

// 记录器
$record = new MyRecord;
$record->setConfig(['path' => __DIR__]);

// 记录日志
$logger = new Logger($format, $record);
$logger->info('Test extend logger');
