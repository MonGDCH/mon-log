<?php

declare(strict_types=1);

namespace mon\log\format;

use InvalidArgumentException;
use mon\log\interfaces\FormatInterface;

/**
 * 按JSON格式解析日志内容
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class JsonFormat implements FormatInterface
{
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

        // 时间格式
        $dateformat = $context['date_format'] ?? 'Y-m-d H:i:s';
        // 日志内容
        $date = date($dateformat, time());
        $logs = [
            'level' => $level,
            'date'  => $date
        ];

        // 开启日志追踪
        if (isset($context['trace']) && $context['trace']) {
            // 获取追踪层级
            $layer = isset($context['layer']) ? intval($context['layer']) : 3;
            // 获取堆栈调用记录
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $layer);
            // 获取调用层
            $call_layer = $layer > 0 ? ($layer - 1) : $layer;
            // 获取调用文件
            $call_file = $traceInfo[$call_layer]['file'];
            // 获取调用行号
            $call_line = $traceInfo[$call_layer]['line'];
            // 记录日志
            $logs['file'] = $call_file;
            $logs['line'] = $call_line;
        }

        // 日志内容替换
        if (isset($context['replace']) && !empty($context['replace'])) {
            $replace = [];
            foreach ((array)$context['replace'] as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }

            $message = strtr($message, $replace);
        }

        $logs['messgae'] = $message;
        return json_encode($logs, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return JsonFormat
     */
    public function setConfig(array $config)
    {
        return $this;
    }
}
