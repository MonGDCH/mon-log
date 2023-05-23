<?php

declare(strict_types=1);

namespace mon\log\format;

use Psr\Log\InvalidArgumentException;
use mon\log\interfaces\FormatInterface;

/**
 * 按行解析日志内容
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1 2023-05-23 优化逻辑，支持配置化
 */
class LineFormat implements FormatInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
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
    ];

    /**
     * 解析日志内容
     *
     * @param string $level     日志级别
     * @param string $message   日志信息
     * @param array $context    信息参数
     * @throws InvalidArgumentException
     * @return string
     */
    public function format($level, $message, array $context = []): string
    {
        if (!is_string($message)) {
            throw new InvalidArgumentException('Log message type must be String');
        }

        // 日志内容
        $log = [];
        // 时间
        $showDate = $context['date'] ?? $this->config['date'];
        if ($showDate) {
            // 时间格式
            $dateformat = $context['date_format'] ?? $this->config['date_format'];
            $date = date($dateformat, time());
            $log[] = "[{$date}]";
        }
        // 日志级别
        $showLevel = $context['level'] ?? $this->config['level'];
        if ($showLevel) {
            $log[] = "[{$level}]";
        }
        // 日志追踪
        $showTrace = $context['trace'] ?? $this->config['trace'];
        if ($showTrace) {
            // 获取追踪层级
            $layer = $context['layer'] ?? $this->config['layer'];
            // 获取堆栈调用记录
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $layer);
            // 获取调用层
            $call_layer = $layer > 0 ? ($layer - 1) : $layer;
            // 获取调用文件
            $call_file = $traceInfo[$call_layer]['file'];
            // 获取调用行号
            $call_line = $traceInfo[$call_layer]['line'];
            // 记录日志
            $log[] = "{$call_file}:{$call_line}";
        }

        // 日志内容替换
        if (isset($context['replace']) && !empty($context['replace'])) {
            $replace = [];
            foreach ((array)$context['replace'] as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }

            $message = strtr($message, $replace);
        }

        $log[] = $message;
        return implode(' ', $log);
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return LineFormat
     */
    public function setConfig(array $config): LineFormat
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
