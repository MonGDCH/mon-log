<?php

declare(strict_types=1);

namespace mon\log\interfaces;

/**
 * 日志保存接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.1   2023-05-23 增加getConfig接口，优化返回结果
 */
interface RecordInterface
{
    /**
     * 记录日志
     *
     * @param mixed $level      日志级别
     * @param string $message   日志信息
     * @param array $context    信息参数
     * @return boolean
     */
    public function record($level, string $messgae, array $context = []): bool;

    /**
     * 保存日志
     *
     * @param array $context
     * @return boolean
     */
    public function save(array $context = []): bool;

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return RecordInterface
     */
    public function setConfig(array $config): RecordInterface;

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * 获取日志缓存记录
     *
     * @return array
     */
    public function getLog(): array;
}
