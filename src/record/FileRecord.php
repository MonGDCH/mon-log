<?php

declare(strict_types=1);

namespace mon\log\record;

use mon\log\Util;
use mon\log\interfaces\RecordInterface;

/**
 * 文件保存日志
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class FileRecord implements RecordInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 是否自动写入文件
        'save'      => true,
        // 写入文件后，清除缓存日志
        'clear'     => true,
        // 日志名称，空则使用当前日期作为名称       
        'logName'   => '',
        // 日志文件大小
        'maxSize'   => 20480000,
        // 日志目录
        'logPath'   => '.',
        // 日志滚动卷数   
        'rollNum'   => 3
    ];

    /**
     * 日志记录
     *
     * @var array
     */
    protected $logs = [];

    /**
     * 记录日志
     *
     * @param mixed $level      日志级别
     * @param string $message   日志信息
     * @param array $context    额外信息参数
     * @return boolean
     */
    public function record($level, string $message, array $context = []): bool
    {
        $this->logs[] = $message;

        // 是否写入日志
        $save = $context['save'] ?? $this->config['save'];
        if ($save === true) {
            return $this->save($context);
        }

        return true;
    }

    /**
     * 保存日志
     *
     * @param array $context    配置参数
     * @return boolean
     */
    public function save(array $context = []): bool
    {
        // 日志名
        $name = $context['logName'] ?? $this->config['logName'];
        $log_name = $name ?: date('Ymd', time());
        // 日志路径
        $path = $context['logPath'] ?? $this->config['logPath'];
        $log_path = $path . DIRECTORY_SEPARATOR . $log_name;
        // 日志信息
        $log = implode(PHP_EOL, $this->logs) . PHP_EOL;
        // 保存后，清除日志记录
        $clear = $context['clear'] ?? $this->config['clear'];
        if ($clear !== false) {
            $this->clearLog();
        }

        // 分卷记录日志
        return Util::subsectionFile($log, $log_path, $this->config['maxSize'], $this->config['rollNum']);
    }

    /**
     * 获取日志缓存记录
     *
     * @return array
     */
    public function getLog(): array
    {
        return $this->logs;
    }

    /**
     * 清除日志缓存记录
     *
     * @return FileRecord
     */
    public function clearLog(): FileRecord
    {
        $this->logs = [];
        return $this;
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return FileRecord
     */
    public function setConfig(array $config): FileRecord
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
