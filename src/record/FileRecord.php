<?php

declare(strict_types=1);

namespace mon\log\record;

use RuntimeException;
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
        // 日志文件大小
        'maxSize'   => 20480000,
        // 日志目录
        'logPath'   => __DIR__,
        // 日志滚动卷数   
        'rollNum'   => 3,
        // 日志名称，空则使用当前日期作为名称       
        'logName'   => '',
    ];

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
     * @return void
     */
    public function record($level, string $messgae): void
    {
        // 日志名
        $log_name = $this->config['logName'] ?: date('Ymd', time());
        // 日志路径
        $log_path = $this->config['logPath'] . DIRECTORY_SEPARATOR . $log_name;
        // 分卷记录日志
        $this->subsectionFile($messgae . PHP_EOL, $log_path, $this->config['maxSize'], $this->config['rollNum']);
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return FileRecord
     */
    public function setConfig(array $config)
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

    /**
     * 分卷记录文件
     *
     * @param  string  $content 记录的内容
     * @param  string  $path    保存的路径, 不含后缀
     * @param  integer $maxSize 文件最大尺寸
     * @param  string  $rollNum 分卷数
     * @param  string  $postfix 文件后缀
     * @throws RuntimeException
     * @return boolean
     */
    protected function subsectionFile(string $content, string $path, int $maxSize = 20480000, int $rollNum = 3, string $postfix = '.log'): bool
    {
        // 日志路径
        $destination = $path . $postfix;
        // 日志长度
        $contentLength = mb_strlen($content);
        // 判断写入内容的大小
        if ($contentLength > $maxSize) {
            throw new RuntimeException("Save log content size cannot exceed {$maxSize}, content size: {$contentLength}");
        }
        // 判断记录文件是否已存在，存在时文件大小不足写入
        if (file_exists($destination) && floor($maxSize) < (filesize($destination) + $contentLength)) {
            // 超出剩余写入大小，分卷写入
            $this->shiftFile($path, $rollNum, $postfix);
            return $this->createFile($content, $destination, false);
        }

        // 不存在文件或文件大小足够继续写入
        return $this->createFile($content, $destination);
    }

    /**
     * 创建文件
     *
     * @param  string  $content 写入内容
     * @param  string  $path    文件路径
     * @param  boolean $append  存在文件是否继续写入
     * @return boolean
     */
    protected function createFile(string $content, string $path, $append = true): bool
    {
        $dirPath = dirname($path);
        is_dir($dirPath) || mkdir($dirPath, 0755, true);
        $save = file_put_contents($path, $content, $append ? FILE_APPEND : 0);
        return boolval($save);
    }

    /**
     * 分卷重命名文件
     *
     * @param  string $path     文件路径
     * @param  integer $rollNum 分卷数
     * @param  string $postfix  后缀名
     * @throws RuntimeException
     * @return void
     */
    protected function shiftFile(string $path, int $rollNum, string $postfix = '.log'): void
    {
        // 判断是否存在最老的一份文件，存在则删除
        $oldest = $this->buildShiftName($path, ($rollNum - 1));
        $oldestFile = $oldest . $postfix;
        if (file_exists($oldestFile) && !unlink($oldestFile)) {
            throw new RuntimeException("Failed to delete old log file, oldFileName: {$oldestFile}");
        }

        // 循环重命名文件
        for ($i = ($rollNum - 2); $i >= 0; $i--) {
            // 最新的一卷不需要加上分卷号
            if ($i == 0) {
                $oldFile = $path;
            } else {
                // 获取分卷号文件名称
                $oldFile = $this->buildShiftName($path, $i);
            }

            // 重命名文件
            $oldFileName = $oldFile . $postfix;
            if (file_exists($oldFileName)) {
                $newFileNmae = $this->buildShiftName($path, ($i + 1)) . $postfix;
                // 重命名
                if (($oldFile != $newFileNmae) && is_writable($oldFileName) && !rename($oldFile, $newFileNmae)) {
                    throw new RuntimeException("Failed to rename volume file name, oldFileName: {$oldFileName}, newFileNmae: {$newFileNmae}");
                }
            }
        }
    }

    /**
     * 构造分卷文件名称
     *
     * @param  string  $fileName 文件名称，不含后缀
     * @param  integer $num      分卷数
     * @return string
     */
    protected function buildShiftName(string $fileName, int $num): string
    {
        return $fileName . '_' . $num;
    }
}
