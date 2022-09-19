<?php

declare(strict_types=1);

namespace mon\log;

use ErrorException;
use Psr\Log\LoggerInterface;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use Psr\Log\InvalidArgumentException;
use mon\log\interfaces\FormatInterface;
use mon\log\interfaces\RecordInterface;

/**
 * 日志工厂
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Logger
{
    /**
     * 单例实现
     *
     * @var Logger
     */
    protected static $instance;

    /**
     * 日志记录通道
     *
     * @var LoggerInterface[]
     */
    protected $channels = [];

    /**
     * 私有化构造方法
     */
    protected function __construct()
    {
    }

    /**
     * 获取实例
     *
     * @return Logger
     */
    public static function instance(): Logger
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * 注册通道
     *
     * @param array $configs 批量通道配置
     * @throws InvalidArgumentException
     * @return Logger
     */
    public function registerChannel(array $configs): Logger
    {
        foreach ($configs as $name => $config) {
            $this->createChannel($name, $config);
        }

        return $this;
    }


    /**
     * 创建通道
     *
     * @param string $name  通道名称
     * @param array $config 通道配置
     * @throws InvalidArgumentException
     * @return Logger
     */
    public function createChannel(string $name, array $config = []): Logger
    {
        // 格式化配置
        $config = $this->formatConfig($config);

        // 创建解析器
        $format_handler = $config['format']['handler'];
        if (!is_subclass_of($format_handler, FormatInterface::class)) {
            throw new InvalidArgumentException("Format handler class {$format_handler} not implements " . FormatInterface::class);
        }
        $format_config = $config['format']['config'];
        /** @var FormatInterface $format */
        $format = new $format_handler;
        $format->setConfig($format_config);

        // 创建记录器
        $record_handler = $config['record']['handler'];
        if (!is_subclass_of($record_handler, RecordInterface::class)) {
            throw new InvalidArgumentException("Record handler class {$record_handler} not implements " . RecordInterface::class);
        }
        $record_config = $config['record']['config'];
        /** @var RecordInterface $record */
        $record = new $record_handler;
        $record->setConfig($record_config);

        // 创建保存日志驱动
        $this->channels[$name] = new Log($format, $record);

        return $this;
    }

    /**
     * 是否存在指定通道
     *
     * @param string $name  通道名称
     * @return boolean
     */
    public function hasChannel(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * 删除指定通道
     *
     * @param string $name  通道名称
     * @return void
     */
    public function removeChannel(string $name)
    {
        unset($this->channels[$name]);
    }

    /**
     * 获取所有通道
     *
     * @return array
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * 获取通道
     *
     * @param string $name  通道名称
     * @throws ErrorException
     * @return LoggerInterface
     */
    public function channel(string $name = 'default'): LoggerInterface
    {
        if (!$this->hasChannel($name)) {
            throw new ErrorException('Logger channel [' . $name . '] not found!');
        }

        return $this->channels[$name];
    }

    /**
     * 格式化配置信息
     *
     * @param array $config 配置信息
     * @return array
     */
    protected function formatConfig(array $config): array
    {
        // 默认配置
        $default = [
            // 解析器
            'format' => ['handler' => LineFormat::class, 'config' => []],
            // 记录器
            'record' => ['handler' => FileRecord::class, 'config' => []]
        ];

        // 覆盖配置
        if (isset($config['format']) && isset($config['format']['handler']) && !empty($config['format']['handler'])) {
            $default['format']['handler'] = $config['format']['handler'];
        }
        if (isset($config['format']) && isset($config['format']['config']) && !empty($config['format']['config'])) {
            $default['format']['config'] = $config['format']['config'];
        }
        if (isset($config['record']) && isset($config['record']['handler']) && !empty($config['record']['handler'])) {
            $default['record']['handler'] = $config['record']['handler'];
        }
        if (isset($config['record']) && isset($config['record']['config']) && !empty($config['record']['config'])) {
            $default['record']['config'] = $config['record']['config'];
        }

        return $default;
    }
}
