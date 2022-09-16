<?php

declare(strict_types=1);

namespace mon\log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use mon\log\interfaces\FormatInterface;
use mon\log\interfaces\RecordInterface;

/**
 * 日志驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    /**
     * 日志内容解析驱动
     *
     * @var FormatInterface
     */
    protected $format;

    /**
     * 日志保存驱动
     *
     * @var RecordInterface
     */
    protected $record;

    /**
     * 构造方法
     *
     * @param FormatInterface $format   日志内容解析器
     * @param RecordInterface $record   日志内容记录器
     */
    public function __construct(FormatInterface $format, RecordInterface $record)
    {
        $this->format = $format;
        $this->record = $record;
    }

    /**
     * 获取日志内容解析驱动
     *
     * @return FormatInterface
     */
    public function getFormat(): FormatInterface
    {
        return $this->format;
    }

    /**
     * 获取保存驱动
     *
     * @return RecordInterface
     */
    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    /**
     * 记录日志
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        // 解析获取日志内容
        $content = $this->getFormat()->format($level, $message, $context);
        // 保存
        $this->getRecord()->record($level, $content);
    }
}
