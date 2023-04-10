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
class FileBatchRecord implements RecordInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 日志文件大小
        'maxSize' => 20480000,
        // 日志目录
        'logPath' => '.',
        // 日志滚动卷数   
        'rollNum' => 3,
        // 日志名称，空则使用当前日期作为名称       
        'logName' => '',
    ];

    /**
     * 日志记录
     *
     * @var array
     */
    protected $logs = [];

    /**
     * 构造方法
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 记录日志
     *
     * @param mixed $level      日志级别
     * @param string $message   日志信息
     * @param array $context    信息参数
     * @return boolean
     */
    public function record($level, string $message, array $context = []): bool
    {
        $this->logs[] = $message;

        // 是否写入日志
        if (isset($context['save']) && $context['save'] === true) {
            // 日志名
            if (isset($context['logName']) && !empty($context['logName'])) {
                $log_name = $context['logName'];
            } else {
                $log_name = $this->config['logName'] ?: date('Ymd', time());
            }
            // 日志路径
            $log_path = $this->config['logPath'] . DIRECTORY_SEPARATOR . $log_name;
            // 日志内容
            $logs = '';
            foreach ($this->logs as $line) {
                $logs .= $line . PHP_EOL;
            }
            // 默认保存后，清除日志记录
            if (!isset($context['clear']) || $context['clear'] !== false) {
                $this->clearLog();
            }
            // 分卷记录日志
            return Util::instance()->subsectionFile($logs, $log_path, $this->config['maxSize'], $this->config['rollNum']);
        }

        return true;
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
     * @return void
     */
    public function clearLog()
    {
        $this->logs = [];
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return FileBatchRecord
     */
    public function setConfig(array $config): FileBatchRecord
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