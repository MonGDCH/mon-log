<?php

declare(strict_types=1);

namespace mon\log\interfaces;

/**
 * 日志保存接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface RecordInterface
{
    /**
     * 记录日志
     *
     * @param mixed $level      日志级别
     * @param string $message   日志信息
     * @param array $context    信息参数
     * @return mixed
     */
    public function record($level, string $messgae, array $context = []);

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return mixed
     */
    public function setConfig(array $config);
}
