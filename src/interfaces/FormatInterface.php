<?php

declare(strict_types=1);

namespace mon\log\interfaces;

/**
 * 解析日志内容接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1   2023-05-23 增加getConfig接口，优化返回结果
 */
interface FormatInterface
{
    /**
     * 解析日志内容
     *
     * @param mixed $level      日志级别
     * @param mixed $message    日志信息
     * @param array $context    信息参数
     * @return string
     */
    public function format($level, $message, array $context = []): string;

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return FormatInterface
     */
    public function setConfig(array $config): FormatInterface;

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array;
}
