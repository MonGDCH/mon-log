<?php

declare(strict_types=1);

namespace mon\log;

use RuntimeException;

/**
 * 日志工具类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Util
{
    /**
     * 单例实体
     *
     * @var Util
     */
    protected static $instance = null;

    /**
     * 获取单例
     *
     * @param array $options 初始化参数
     * @return Util
     */
    public static function instance($options = []): Util
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($options);
        }

        return static::$instance;
    }

    /**
     * 私有构造方法
     */
    protected function __construct()
    {
    }

    /**
     * 创建文件
     *
     * @param  string  $content 写入内容
     * @param  string  $path    文件路径
     * @param  boolean $append  存在文件是否继续写入
     * @return boolean
     */
    public function createFile(string $content, string $path, $append = true): bool
    {
        $dirPath = dirname($path);
        is_dir($dirPath) || mkdir($dirPath, 0755, true);
        $save = file_put_contents($path, $content, $append ? FILE_APPEND : 0);
        return boolval($save);
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
    public function subsectionFile(string $content, string $path, int $maxSize = 20480000, int $rollNum = 3, string $postfix = '.log'): bool
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
     * 分卷重命名文件
     *
     * @param  string $path     文件路径
     * @param  integer $rollNum 分卷数
     * @param  string $postfix  后缀名
     * @throws RuntimeException
     * @return void
     */
    protected function shiftFile(string $path, int $rollNum, string $postfix = '.log')
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
