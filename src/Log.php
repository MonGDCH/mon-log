<?php

declare(strict_types=1);

namespace mon\log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use mon\log\interfaces\FormatInterface;
use mon\log\interfaces\RecordInterface;

/**
 * 日志驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Log extends AbstractLogger implements LoggerInterface
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
     * @param FormatInterface $format   日志内容解析器，默认 LineFormat
     * @param RecordInterface $record   日志内容记录器，默认 FileRecord
     */
    public function __construct(FormatInterface $format = null, RecordInterface $record = null)
    {
        $this->format = $format ?? new LineFormat();
        $this->record = $record ?? new FileRecord();
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
     * 获取日志保存驱动
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
     * @param string $level     日志级别
     * @param string $message   日志内容
     * @param array $context    额外信息
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        // 解析获取日志内容
        $content = $this->getFormat()->format($level, $message, $context);
        // 保存
        $this->getRecord()->record($level, $content, $context);
    }

    /**
     * 获取缓存中的日志记录
     *
     * @return array
     */
    public function getLog(): array
    {
        return $this->getRecord()->getLog();
    }

    /**
     * 保存日志
     *
     * @param array $context    配置信息
     * @return boolean
     */
    public function saveLog(array $context = []): bool
    {
        return $this->getRecord()->save($context);
    }
}
